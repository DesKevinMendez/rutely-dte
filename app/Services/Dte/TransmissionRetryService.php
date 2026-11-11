<?php

namespace App\Services\Dte;

use App\Models\ContingencyEvent;
use App\Models\Dte;
use App\Models\DteInvalidation;
use App\Models\MhTransmission;
use App\Services\Mh\MhTransmissionService;

class TransmissionRetryService
{
    public function __construct(private readonly MhTransmissionService $transmission) {}

    /**
     * Resend a failed transmission and, when Hacienda accepts it, update the
     * transmitted record the same way its original issuance would have.
     *
     * @return array<string, mixed>
     */
    public function retry(MhTransmission $transmission): array
    {
        $mhResult = $this->transmission->retry($transmission);

        if (! in_array($mhResult['estado'] ?? null, ['PROCESADO', 'RECIBIDO'], true)) {
            return $mhResult;
        }

        $transmitted = $transmission->transmittable;

        match (true) {
            $transmitted instanceof Dte => $this->acceptDte($transmitted, $mhResult),
            $transmitted instanceof DteInvalidation => $this->acceptInvalidation($transmitted, $mhResult),
            $transmitted instanceof ContingencyEvent => $this->acceptContingencyEvent($transmitted, $mhResult),
            default => null,
        };

        return $mhResult;
    }

    /** @param  array<string, mixed>  $mhResult */
    private function acceptDte(Dte $dte, array $mhResult): void
    {
        $dte->update([
            'status' => $mhResult['estado'],
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'observations' => json_encode($mhResult['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
            'mh_response_json' => $mhResult,
        ]);
    }

    /** @param  array<string, mixed>  $mhResult */
    private function acceptInvalidation(DteInvalidation $invalidation, array $mhResult): void
    {
        $observations = $this->joinedObservations($mhResult);

        $invalidation->update([
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'status' => 'PROCESADO',
            'observations' => $observations,
        ]);

        $invalidation->dte->update([
            'status' => 'INVALIDADO',
            'observations' => $observations,
            'mh_response_json' => $mhResult,
        ]);
    }

    /** @param  array<string, mixed>  $mhResult */
    private function acceptContingencyEvent(ContingencyEvent $event, array $mhResult): void
    {
        $event->update([
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'status' => 'RECIBIDO',
            'observations' => $this->joinedObservations($mhResult),
        ]);
    }

    /** @param  array<string, mixed>  $mhResult */
    private function joinedObservations(array $mhResult): ?string
    {
        $observations = implode('; ', $mhResult['observaciones'] ?? []);

        return $observations !== '' ? $observations : ($mhResult['descripcionMsg'] ?? null);
    }
}
