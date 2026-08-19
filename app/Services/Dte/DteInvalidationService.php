<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\Dte;
use App\Models\DteInvalidation;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Support\Str;

class DteInvalidationService
{
    public function __construct(
        private readonly FirmadorService $firmador,
        private readonly MhTransmissionService $transmission,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array{updated: Dte, mhResult: array<string, mixed>, invalidation: DteInvalidation}
     */
    public function invalidate(Dte $dte, array $payload): array
    {
        $company = Company::query()->findOrFail($dte->company_id);
        $original = $dte->original_json;
        $now = now('America/El_Salvador');
        $nameResponsible = (string) ($payload['nombreResponsable'] ?? 'OPERADOR');
        $docTypeResponsible = (string) ($payload['tipDocResponsable'] ?? '13');
        $docNumberResponsible = (string) ($payload['numDocResponsable'] ?? '000000000');
        $nameRequester = (string) ($payload['nombreSolicita'] ?? $nameResponsible);
        $docTypeRequester = (string) ($payload['tipDocSolicita'] ?? $docTypeResponsible);
        $docNumberRequester = (string) ($payload['numDocSolicita'] ?? $docNumberResponsible);
        $type = (int) ($payload['tipoAnulacion'] ?? 1);
        $reason = (string) ($payload['motivoAnulacion'] ?? 'Anulación por error en información');
        $generationCode = strtoupper((string) Str::uuid());

        $document = [
            'identificacion' => [
                'version' => 3,
                'ambiente' => $dte->environment,
                'codigoGeneracion' => $generationCode,
                'fecEmi' => $now->format('Y-m-d'),
                'horEmi' => $now->format('H:i:s'),
                'fusion' => null,
            ],
            'emisor' => [
                'nit' => data_get($original, 'emisor.nit'),
                'nombre' => data_get($original, 'emisor.nombre'),
                'codEstableMH' => $company->mh_establishment_code,
                'codEstable' => $company->own_establishment_code,
                'codPuntoVentaMH' => $company->mh_pos_code,
                'codPuntoVenta' => $company->own_pos_code,
                'telefono' => data_get($original, 'emisor.telefono'),
                'correo' => data_get($original, 'emisor.correo'),
            ],
            'documento' => [
                'tipoDte' => $dte->dte_type,
                'codigoGeneracion' => $dte->generation_code,
                'selloRecibido' => $dte->received_seal,
                'numeroControl' => $dte->control_number,
                'fecEmi' => data_get($original, 'identificacion.fecEmi'),
                'codigoGeneracionR' => null,
                'tipoDocumento' => data_get($original, 'receptor.tipoDocumento'),
                'numDocumento' => data_get($original, 'receptor.numDocumento'),
                'nombre' => data_get($original, 'receptor.nombre'),
                'telefono' => data_get($original, 'receptor.telefono'),
                'correo' => data_get($original, 'receptor.correo'),
            ],
            'motivo' => [
                'tipoAnulacion' => $type,
                'motivoAnulacion' => $reason,
                'nombreResponsable' => $nameResponsible,
                'tipDocResponsable' => $docTypeResponsible,
                'numDocResponsable' => $docNumberResponsible,
                'nombreSolicita' => $nameRequester,
                'tipDocSolicita' => $docTypeRequester,
                'numDocSolicita' => $docNumberRequester,
            ],
        ];

        $invalidation = DteInvalidation::query()->create([
            'company_id' => $company->id,
            'dte_id' => $dte->id,
            'generation_code' => $generationCode,
            'invalidation_type' => (string) $type,
            'reason' => $reason,
            'name_person_in_charge' => $nameResponsible,
            'doc_type_person_in_charge' => $docTypeResponsible,
            'doc_number_person_in_charge' => $docNumberResponsible,
            'name_request' => $nameRequester,
            'doc_type_request' => $docTypeRequester,
            'doc_number_request' => $docNumberRequester,
            'original_json' => $document,
            'status' => 'BORRADOR',
            'environment' => $dte->environment,
        ]);

        $signed = $this->firmador->signDocument($company->id, $document, $dte->environment, $company->nit);
        $invalidation->update(['signed_json' => $signed, 'status' => 'FIRMADO']);

        $mhResult = $this->transmission->transmitInvalidation($invalidation->refresh());
        $approved = in_array($mhResult['estado'] ?? null, ['PROCESADO', 'RECIBIDO'], true);
        $observations = implode('; ', $mhResult['observaciones'] ?? []);
        if ($observations === '') {
            $observations = $mhResult['descripcionMsg'] ?? null;
        }

        $invalidation->update([
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'status' => $approved ? 'PROCESADO' : 'RECHAZADO',
            'observations' => $observations,
        ]);

        $dte->update([
            'status' => $approved ? 'INVALIDADO' : 'RECHAZADO',
            'observations' => $observations,
            'mh_response_json' => $mhResult,
        ]);

        return [
            'updated' => $dte->refresh(),
            'mhResult' => $mhResult,
            'invalidation' => $invalidation->refresh(),
        ];
    }
}
