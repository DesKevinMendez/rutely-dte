<?php

namespace App\Services\Mh;

use App\Environment;
use App\Exceptions\MhCredentialsRejectedException;
use App\MhLoginStatus;
use App\Models\MhCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MhAuthService
{
    public function token(string $companyId, Environment|string $environment): string
    {
        $environmentValue = $environment instanceof Environment ? $environment->value : $environment;
        $credentials = MhCredentials::query()
            ->where('company_id', $companyId)
            ->where('environment', $environmentValue)
            ->where('active', true)
            ->latest('updated_at')
            ->first();

        if ($credentials === null) {
            throw new RuntimeException('No hay credenciales de Hacienda configuradas para la empresa y el ambiente seleccionados.');
        }

        $cachedToken = $this->cachedToken($credentials);

        if ($cachedToken !== null) {
            return $cachedToken;
        }

        if ($this->wasRejected($credentials)) {
            throw new RuntimeException('Hacienda ya rechazó estas credenciales. Actualícelas antes de reintentar para evitar el bloqueo del usuario.');
        }

        try {
            $token = $this->login($credentials->nit, $credentials->password, $environmentValue);
        } catch (MhCredentialsRejectedException $exception) {
            Cache::forever($this->rejectedCacheKey($credentials), $this->credentialFingerprint($credentials));

            throw $exception;
        }

        $this->rememberToken($credentials, $token);

        return $token;
    }

    /**
     * Asks Hacienda for a session and returns its token, without storing anything.
     *
     * @throws MhCredentialsRejectedException when Hacienda does not accept the credentials
     * @throws ConnectionException when Hacienda cannot be reached
     * @throws RuntimeException when Hacienda answers with a server error
     */
    public function login(string $nit, string $password, string $environment): string
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post($this->authUrl($environment), [
                'user' => $nit,
                'pwd' => $password,
            ]);

        if ($response->serverError()) {
            throw new RuntimeException("Error en autenticación MH [HTTP {$response->status()}]: {$response->body()}");
        }

        $token = $response->json('body.token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new MhCredentialsRejectedException("Hacienda rechazó las credenciales [HTTP {$response->status()}]: {$response->body()}");
        }

        return str_starts_with($token, 'Bearer ') ? substr($token, 7) : $token;
    }

    /**
     * Keeps the session Hacienda granted to these credentials, which also lifts any earlier rejection of them.
     */
    public function rememberToken(MhCredentials $credentials, string $token): void
    {
        Cache::forget($this->rejectedCacheKey($credentials));
        Cache::put($this->tokenCacheKey($credentials), [
            'credential_fingerprint' => $this->credentialFingerprint($credentials),
            'token' => $token,
        ], now()->addHours(23));
    }

    public function clear(string $companyId, Environment|string $environment): void
    {
        $environmentValue = $environment instanceof Environment ? $environment->value : $environment;
        Cache::forget("mh-auth:{$companyId}:{$environmentValue}");
    }

    /**
     * Reads the result of the last login without contacting Hacienda.
     */
    public function loginStatus(MhCredentials $credentials): MhLoginStatus
    {
        if ($this->cachedToken($credentials) !== null) {
            return MhLoginStatus::AUTHENTICATED;
        }

        return $this->wasRejected($credentials) ? MhLoginStatus::REJECTED : MhLoginStatus::UNVERIFIED;
    }

    private function cachedToken(MhCredentials $credentials): ?string
    {
        $cached = Cache::get($this->tokenCacheKey($credentials));

        if (
            is_array($cached)
            && ($cached['credential_fingerprint'] ?? null) === $this->credentialFingerprint($credentials)
            && is_string($cached['token'] ?? null)
            && $cached['token'] !== ''
        ) {
            return $cached['token'];
        }

        return null;
    }

    private function wasRejected(MhCredentials $credentials): bool
    {
        return Cache::get($this->rejectedCacheKey($credentials)) === $this->credentialFingerprint($credentials);
    }

    private function tokenCacheKey(MhCredentials $credentials): string
    {
        return "mh-auth:{$credentials->company_id}:{$credentials->environment->value}";
    }

    private function rejectedCacheKey(MhCredentials $credentials): string
    {
        return "mh-auth-rejected:{$credentials->company_id}:{$credentials->environment->value}";
    }

    private function credentialFingerprint(MhCredentials $credentials): string
    {
        return hash('sha256', implode('|', [
            (string) $credentials->getKey(),
            $credentials->nit,
            (string) $credentials->getRawOriginal('password'),
        ]));
    }

    private function authUrl(string $environment): string
    {
        return $environment === Environment::PRODUCTION->value
            ? 'https://api.dtes.mh.gob.sv/seguridad/auth'
            : 'https://apitest.dtes.mh.gob.sv/seguridad/auth';
    }
}
