<?php

namespace App\Services\Dte;

use App\IssuableDteType;
use App\Models\Company;
use App\Models\Departament;
use App\Models\District;
use App\Models\Dte;
use App\Models\DteCorrelative;
use App\Models\Municipalities;
use App\Models\Receivers;
use App\Services\Dte\Documents\DteDocumentBuilder;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DteIssuanceService
{
    public function __construct(
        private readonly FirmadorService $firmador,
        private readonly MhTransmissionService $transmission,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{record: Dte, mhResult: array<string, mixed>}
     */
    public function issue(string $companyId, array $payload): array
    {
        $company = Company::query()->findOrFail($companyId);
        $dteType = IssuableDteType::from((string) ($payload['tipoDte'] ?? IssuableDteType::CONSUMER_INVOICE->value));
        $builder = DteDocumentBuilder::for($dteType);
        $builder->ensureIssuerCanEmit($company);

        $environment = (string) $company->environment;
        $correlative = $this->nextCorrelative($company, $dteType->value);
        $generationCode = strtoupper((string) Str::uuid());
        $controlNumber = $this->controlNumber($company, $dteType->value, $correlative);
        $now = now('America/El_Salvador');

        $document = $builder->build($company, $payload, $controlNumber, $generationCode, $now);
        $receiverIdentity = DteDocumentBuilder::receiverIdentity($document['receptor']);
        $receiver = $this->persistReceiver($companyId, $document['receptor'], $receiverIdentity);

        $dte = Dte::query()->create([
            'company_id' => $companyId,
            'generation_code' => $generationCode,
            'control_number' => $controlNumber,
            'dte_type' => $dteType->value,
            'version' => (string) $document['identificacion']['version'],
            'environment' => $environment,
            'status' => 'BORRADOR',
            'issuer_nit' => $company->nit,
            'receiver_document' => $receiverIdentity['number'],
            'receiver_id' => $receiver?->id,
            'total_amount' => (int) round($document['resumen']['totalPagar'] * 100),
            'original_json' => $document,
        ]);

        $signedDocument = $this->firmador->signDocument($companyId, $document, $environment, $company->nit);
        $dte->update([
            'status' => 'FIRMADO',
            'signed_json' => $signedDocument,
        ]);

        $mhResult = $this->transmission->transmitDte($dte->refresh());
        $dte->update([
            'status' => ($mhResult['estado'] ?? null) === 'PROCESADO' ? 'PROCESADO' : 'RECHAZADO',
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'observations' => json_encode($mhResult['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
            'mh_response_json' => $mhResult,
        ]);

        return [
            'record' => $dte->refresh(),
            'mhResult' => $mhResult,
        ];
    }

    private function nextCorrelative(Company $company, string $dteType): int
    {
        $key = sprintf('%s-%s%s', $dteType, $company->own_establishment_code, $company->own_pos_code);

        return DB::transaction(function () use ($company, $key): int {
            $row = DteCorrelative::query()->firstOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                ['last_value' => '0'],
            );
            $row = DteCorrelative::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $next = ((int) $row->last_value) + 1;
            $row->update(['last_value' => (string) $next]);

            return $next;
        });
    }

    private function controlNumber(Company $company, string $dteType, int $correlative): string
    {
        return sprintf(
            'DTE-%s-%s%s-%s',
            $dteType,
            $company->own_establishment_code,
            $company->own_pos_code,
            str_pad((string) $correlative, 15, '0', STR_PAD_LEFT),
        );
    }

    /**
     * @param  array<string, mixed>  $receiver
     * @param  array{type: ?string, number: ?string}  $identity
     */
    private function persistReceiver(string $companyId, array $receiver, array $identity): ?Receivers
    {
        if ($identity['type'] === null || $identity['number'] === null) {
            return null;
        }

        $departmentId = Departament::query()->where('code', data_get($receiver, 'direccion.departamento'))->value('id');
        $municipalityId = Municipalities::query()
            ->where('departament_id', $departmentId)
            ->where('code', data_get($receiver, 'direccion.municipio'))
            ->value('id');
        $districtId = District::query()
            ->where('municipality_id', $municipalityId)
            ->where('code', data_get($receiver, 'direccion.distrito'))
            ->value('id');

        return Receivers::query()->updateOrCreate(
            ['company_id' => $companyId, 'document_number' => $identity['number']],
            [
                'document_type' => $identity['type'],
                'nrc' => $receiver['nrc'] ?? null,
                'name' => $receiver['nombre'] ?? $identity['number'],
                'economic_activity_code' => $receiver['codActividad'] ?? null,
                'economic_activity_description' => $receiver['descActividad'] ?? null,
                'departament_id' => $departmentId,
                'municipality_id' => $municipalityId,
                'district_id' => $districtId,
                'address_complement' => data_get($receiver, 'direccion.complemento'),
                'phone' => $receiver['telefono'] ?? null,
                'email' => $receiver['correo'] ?? null,
            ],
        );
    }
}
