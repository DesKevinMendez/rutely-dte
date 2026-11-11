<?php

use App\Environment;
use App\Models\MhCredentials;
use App\Models\MhTransmission;
use App\Role;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

function transmissionForDte($dte, array $overrides = []): MhTransmission
{
    return MhTransmission::query()->create(array_merge([
        'company_id' => $dte->company_id,
        'transmittable_type' => $dte->getMorphClass(),
        'transmittable_id' => $dte->id,
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

test('company member can list pending queue with common collection and Spatie filters', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $dte = DteApiTestData::dte($company);
    $failed = transmissionForDte($dte);
    $pendingDte = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    transmissionForDte($pendingDte, ['status' => 'pending', 'error' => null]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.queue.index', ['filter' => ['operation' => 'dte', 'status' => 'failed']]))
        ->assertOk()
        ->assertExactJson([
            'data' => [$failed->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('queue lists each transmission once with its latest attempt', function () {
    $company = DteApiTestData::company();
    $retriedDte = DteApiTestData::dte($company);
    transmissionForDte($retriedDte);
    $latestAttempt = transmissionForDte($retriedDte, [
        'attempt' => 2,
        'request_json' => ['idEnvio' => 2],
        'http_status' => 400,
        'error' => 'Error en transmisión MH [HTTP 400]',
    ]);
    $recoveredDte = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    transmissionForDte($recoveredDte);
    transmissionForDte($recoveredDte, [
        'attempt' => 2,
        'request_json' => ['idEnvio' => 2],
        'status' => 'success',
        'error' => null,
        'http_status' => 200,
        'response_json' => ['estado' => 'PROCESADO'],
    ]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->getJson(route('api.v1.queue.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => [$latestAttempt->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
            ],
        ]);
});

test('retrying a rejected transmission keeps one queue row and raises its attempt', function () {
    $company = DteApiTestData::company();
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    $dte = DteApiTestData::dte($company);
    transmissionForDte($dte, ['http_status' => 400, 'error' => 'Error en transmisión MH [HTTP 400]']);
    Http::fake([
        'apitest.dtes.mh.gob.sv/seguridad/auth' => Http::response(['status' => 'OK', 'body' => ['token' => 'Bearer mh-token']]),
        'apitest.dtes.mh.gob.sv/fesv/recepciondte' => Http::response(['estado' => 'RECHAZADO'], 400),
    ]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))->assertOk();

    $latestAttempt = MhTransmission::query()->where('attempt', 2)->sole();

    expect($latestAttempt->status)->toBe('failed');

    $this->getJson(route('api.v1.queue.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => [$latestAttempt->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
            ],
        ]);
});

test('queue list is tenant isolated', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $ownDte = DteApiTestData::dte($company);
    $otherDte = DteApiTestData::dte($otherCompany, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    $own = transmissionForDte($ownDte);
    transmissionForDte($otherDte);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.queue.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => [$own->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
            ],
        ]);
});

test('admin can retry failed queue entries with common response', function (Role $role) {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    $failed = transmissionForDte($dte);
    $result = ['estado' => 'PROCESADO', 'selloRecibido' => 'RETRY-SEAL'];

    $this->mock(MhTransmissionService::class)
        ->shouldReceive('retry')
        ->once()
        ->withArgs(fn (MhTransmission $transmission): bool => $transmission->is($failed))
        ->andReturn($result);
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->postJson(route('api.v1.queue.retries.store'))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'count' => 1,
                'results' => [[
                    'transmission_id' => $failed->id,
                    'result' => $result,
                ]],
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'superadmin' => Role::SUPERADMIN,
]);

test('queue retry resends only the latest failed attempt of each transmission', function () {
    $company = DteApiTestData::company();
    $retriedDte = DteApiTestData::dte($company);
    transmissionForDte($retriedDte);
    $latestAttempt = transmissionForDte($retriedDte, ['attempt' => 2, 'request_json' => ['idEnvio' => 2]]);
    $recoveredDte = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    transmissionForDte($recoveredDte);
    transmissionForDte($recoveredDte, [
        'attempt' => 2,
        'request_json' => ['idEnvio' => 2],
        'status' => 'success',
        'error' => null,
        'http_status' => 200,
        'response_json' => ['estado' => 'PROCESADO'],
    ]);
    $result = ['estado' => 'RECHAZADO', 'codigoMsg' => '400'];

    $this->mock(MhTransmissionService::class)
        ->shouldReceive('retry')
        ->once()
        ->withArgs(fn (MhTransmission $transmission): bool => $transmission->is($latestAttempt))
        ->andReturn($result);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'count' => 1,
                'results' => [[
                    'transmission_id' => $latestAttempt->id,
                    'result' => $result,
                ]],
            ],
        ]);
});

test('queue retry returns exact empty result when nothing failed', function () {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))
        ->assertOk()
        ->assertExactJson(['data' => ['count' => 0, 'results' => []]]);
});

test('operational user can read queue but cannot retry it', function () {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.queue.index'))->assertOk();
    $this->postJson(route('api.v1.queue.retries.store'))->assertForbidden();
});

test('queue endpoints require authentication', function () {
    $this->getJson(route('api.v1.queue.index'))->assertUnauthorized();
    $this->postJson(route('api.v1.queue.retries.store'))->assertUnauthorized();
});

test('company member can read tenant dashboard with exact metrics', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $dte = DteApiTestData::dte($company, ['status' => 'PROCESADO', 'total_amount' => 1130]);
    transmissionForDte($dte, ['status' => 'failed']);

    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $otherDte = DteApiTestData::dte($otherCompany, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
        'total_amount' => 999999,
    ]);
    transmissionForDte($otherDte, ['status' => 'failed']);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.dashboard.show'))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'metrics' => [
                    'total' => 1,
                    'processed' => 1,
                    'rejected' => 0,
                    'invalidated' => 0,
                    'total_amount' => 1130,
                    'pending_transmissions' => 1,
                ],
                'recent_dtes' => [$dte->fresh()->toArray()],
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('dashboard counts a retried transmission once', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company, ['status' => 'RECHAZADO']);
    transmissionForDte($dte);
    transmissionForDte($dte, ['attempt' => 2, 'request_json' => ['idEnvio' => 2]]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->getJson(route('api.v1.dashboard.show'))
        ->assertOk()
        ->assertJson(['data' => ['metrics' => ['pending_transmissions' => 1]]]);
});

test('dashboard requires authentication', function () {
    $this->getJson(route('api.v1.dashboard.show'))->assertUnauthorized();
});
