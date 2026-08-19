<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\ContingencyEvent;
use App\Models\Departament;
use App\Models\Dte;
use App\Models\Municipalities;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ContingencyService
{
    public function __construct(
        private readonly FirmadorService $firmador,
        private readonly MhTransmissionService $transmission,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array{event: ContingencyEvent, document: array<string, mixed>, mhResult: array<string, mixed>}
     */
    public function create(string $companyId, array $payload): array
    {
        $company = Company::query()->findOrFail($companyId);
        $generationCode = strtoupper((string) Str::uuid());
        $now = now('America/El_Salvador');
        $departmentCode = Departament::query()->find($company->departament_id)?->code;
        $municipalityCode = Municipalities::query()->find($company->municipality_id)?->code;
        $reason = (string) ($payload['motivoContingencia'] ?? 'No disponibilidad de sistema MH');

        $document = [
            'identificacion' => [
                'version' => 4,
                'ambiente' => $company->environment,
                'codigoGeneracion' => $generationCode,
                'fTransmision' => $now->format('Y-m-d'),
                'hTransmision' => $now->format('H:i:s'),
            ],
            'emisor' => [
                'nit' => $company->nit,
                'nombre' => $company->name,
                'nombreComercial' => $company->commercial_name,
                'tipoEstablecimiento' => $company->establishment_type,
                'direccion' => [
                    'departamento' => $departmentCode,
                    'municipio' => $municipalityCode,
                    'complemento' => $company->address,
                ],
                'telefono' => $company->phone,
                'correo' => $company->email,
                'codEstableMH' => $company->mh_establishment_code,
                'codPuntoVentaMH' => $company->mh_pos_code,
            ],
            'detalleDTE' => collect($payload['dtes'])->values()->map(fn (array $dte, int $index): array => [
                'noItem' => $index + 1,
                'codigoGeneracion' => strtoupper($dte['codigoGeneracion']),
                'tipoDoc' => $dte['tipoDte'],
            ])->all(),
            'motivo' => [
                'fInicio' => $payload['fInicio'],
                'hInicio' => $payload['hInicio'],
                'fFin' => $payload['fFin'],
                'hFin' => $payload['hFin'],
                'tipoContingencia' => (int) $payload['tipoContingencia'],
                'motivoContingencia' => $reason,
            ],
        ];

        $event = ContingencyEvent::query()->create([
            'company_id' => $companyId,
            'generation_code' => $generationCode,
            'environment' => $company->environment,
            'contingency_type' => (string) $payload['tipoContingencia'],
            'reason' => $reason,
            'start_date_at' => Carbon::createFromFormat('Y-m-d H:i:s', $payload['fInicio'].' '.$payload['hInicio']),
            'end_date_at' => Carbon::createFromFormat('Y-m-d H:i:s', $payload['fFin'].' '.$payload['hFin']),
            'original_json' => $document,
            'status' => 'BORRADOR',
        ]);

        $signed = $this->firmador->signDocument($companyId, $document, $company->environment, $company->nit);
        $event->update(['signed_json' => $signed, 'status' => 'FIRMADO']);
        $mhResult = $this->transmission->transmitContingency($event->refresh());
        $approved = in_array($mhResult['estado'] ?? null, ['RECIBIDO', 'PROCESADO'], true);
        $observations = implode('; ', $mhResult['observaciones'] ?? []);
        if ($observations === '') {
            $observations = $mhResult['descripcionMsg'] ?? null;
        }

        $event->update([
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'status' => $approved ? 'RECIBIDO' : 'RECHAZADO',
            'observations' => $observations,
        ]);

        Dte::query()
            ->where('company_id', $companyId)
            ->whereIn(
                'generation_code',
                collect($payload['dtes'])->pluck('codigoGeneracion')->map(fn ($code) => strtoupper((string) $code)),
            )
            ->update(['contingency_event_id' => $event->id]);

        return [
            'event' => $event->refresh(),
            'document' => $document,
            'mhResult' => $mhResult,
        ];
    }
}
