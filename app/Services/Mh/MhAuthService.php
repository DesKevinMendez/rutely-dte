<?php

namespace App\Services\Mh;

use App\Environment;
use App\Models\MhCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MhAuthService
{
    public function token(string $companyId, Environment|string $environment): string
    {
        $environmentValue = $environment instanceof Environment ? $environment->value : $environment;
        $cacheKey = "mh-auth:{$companyId}:{$environmentValue}";

        return Cache::remember($cacheKey, now()->addHours(23), function () use ($companyId, $environmentValue): string {
            $credentials = MhCredentials::query()
                ->where('company_id', $companyId)
                ->where('environment', $environmentValue)
                ->where('active', true)
                ->latest('updated_at')
                ->first();

            if ($credentials === null) {
                throw new RuntimeException('No hay credenciales de Hacienda configuradas para la empresa y el ambiente seleccionados.');
            }

            $response = Http::asForm()
                ->timeout(10)
                ->post($this->authUrl($environmentValue), [
                    'user' => $credentials->nit,
                    'pwd' => $credentials->password,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("Error en autenticación MH [HTTP {$response->status()}]: {$response->body()}");
            }

            $token = $response->json('body.token');
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('La respuesta de autenticación MH no contiene un token válido.');
            }

            return str_starts_with($token, 'Bearer ') ? substr($token, 7) : $token;
        });
    }

    public function clear(string $companyId, Environment|string $environment): void
    {
        $environmentValue = $environment instanceof Environment ? $environment->value : $environment;
        Cache::forget("mh-auth:{$companyId}:{$environmentValue}");
    }

    private function authUrl(string $environment): string
    {
        return $environment === Environment::PRODUCTION->value
            ? 'https://api.dtes.mh.gob.sv/seguridad/auth'
            : 'https://apitest.dtes.mh.gob.sv/seguridad/auth';
    }
}
