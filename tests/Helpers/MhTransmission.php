<?php

use App\Environment;
use App\Models\Company;
use App\Models\MhCredentials;
use App\Models\MhTransmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

/**
 * A transmission attempt of the given DTE, invalidation or contingency event,
 * failed by default.
 *
 * @param  array<string, mixed>  $overrides
 */
function transmissionFor(Model $transmittable, array $overrides = []): MhTransmission
{
    return MhTransmission::query()->create(array_merge([
        'company_id' => $transmittable->company_id,
        'transmittable_type' => $transmittable->getMorphClass(),
        'transmittable_id' => $transmittable->id,
        'operation' => 'dte',
        'attempt' => 1,
        'request_json' => ['idEnvio' => 1],
        'response_json' => null,
        'http_status' => null,
        'status' => 'failed',
        'error' => 'MH unavailable',
        'sent_at' => now(),
        'responded_at' => now(),
    ], $overrides));
}

function createSandboxCredentials(Company $company): MhCredentials
{
    return MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
}

/**
 * Fake the MH sandbox: authentication succeeds and the given endpoint answers with the body.
 *
 * @param  array<string, mixed>  $body
 */
function fakeMhResponse(string $endpoint, array $body, int $status = 200): void
{
    Http::fake([
        'apitest.dtes.mh.gob.sv/seguridad/auth' => Http::response(['status' => 'OK', 'body' => ['token' => 'Bearer mh-token']]),
        "apitest.dtes.mh.gob.sv/fesv/{$endpoint}" => Http::response($body, $status),
    ]);
}
