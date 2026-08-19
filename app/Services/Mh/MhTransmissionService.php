<?php

namespace App\Services\Mh;

use App\Environment;
use App\Models\ContingencyEvent;
use App\Models\Dte;
use App\Models\DteInvalidation;
use App\Models\MhTransmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Throwable;

class MhTransmissionService
{
    public function __construct(private readonly MhAuthService $authService) {}

    /** @return array<string, mixed> */
    public function transmitDte(Dte $dte): array
    {
        return $this->transmit(
            model: $dte,
            operation: 'dte',
            endpoint: '/recepciondte',
            body: [
                'ambiente' => $dte->environment,
                'version' => (int) $dte->version,
                'tipoDte' => $dte->dte_type,
                'documento' => $dte->signed_json,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function transmitInvalidation(DteInvalidation $invalidation): array
    {
        return $this->transmit(
            model: $invalidation,
            operation: 'invalidation',
            endpoint: '/anulardte',
            body: [
                'ambiente' => $invalidation->environment,
                'version' => 3,
                'documento' => $invalidation->signed_json,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function transmitContingency(ContingencyEvent $event): array
    {
        return $this->transmit(
            model: $event,
            operation: 'contingency',
            endpoint: '/contingencia',
            body: [
                'ambiente' => $event->environment,
                'version' => 4,
                'documento' => $event->signed_json,
            ],
        );
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function transmit(Model $model, string $operation, string $endpoint, array $body): array
    {
        $companyId = (string) $model->getAttribute('company_id');
        $environment = (string) $model->getAttribute('environment');
        $attempt = MhTransmission::query()
            ->where('company_id', $companyId)
            ->where('transmittable_type', $model->getMorphClass())
            ->where('transmittable_id', $model->getKey())
            ->where('operation', $operation)
            ->max('attempt');
        $attempt = ((int) $attempt) + 1;
        $requestBody = ['idEnvio' => $attempt, ...$body];

        $transmission = MhTransmission::query()->create([
            'company_id' => $companyId,
            'transmittable_type' => $model->getMorphClass(),
            'transmittable_id' => $model->getKey(),
            'operation' => $operation,
            'attempt' => $attempt,
            'request_json' => $requestBody,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        try {
            $token = $this->authService->token($companyId, $environment);
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->post($this->baseUrl($environment).$endpoint, $requestBody);

            $responseBody = $response->json();
            if (! is_array($responseBody)) {
                $responseBody = ['raw' => $response->body()];
            }

            if (! $response->successful()) {
                $error = "Error en transmisión MH [HTTP {$response->status()}]: {$response->body()}";
                $transmission->update([
                    'response_json' => $responseBody,
                    'http_status' => $response->status(),
                    'status' => 'failed',
                    'error' => $error,
                    'responded_at' => now(),
                ]);

                return [
                    'estado' => 'RECHAZADO',
                    'codigoMsg' => (string) $response->status(),
                    'descripcionMsg' => $error,
                    'observaciones' => [$error],
                ];
            }

            $status = ($responseBody['estado'] ?? null) === 'PROCESADO' ? 'success' : 'rejected';
            $transmission->update([
                'response_json' => $responseBody,
                'http_status' => $response->status(),
                'status' => $status,
                'responded_at' => now(),
            ]);

            return $responseBody;
        } catch (Throwable $exception) {
            $transmission->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'responded_at' => now(),
            ]);

            return [
                'estado' => 'RECHAZADO',
                'codigoMsg' => '500',
                'descripcionMsg' => $exception->getMessage(),
                'observaciones' => [$exception->getMessage()],
            ];
        }
    }

    private function baseUrl(string $environment): string
    {
        return $environment === Environment::PRODUCTION->value
            ? 'https://api.dtes.mh.gob.sv/fesv'
            : 'https://apitest.dtes.mh.gob.sv/fesv';
    }
}
