<?php

use App\Environment;
use App\Models\ContingencyEvent;
use App\Models\DteInvalidation;
use App\Models\MhTransmission;
use App\Role;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

function emptyQueuePagination(): array
{
    return [
        'total' => 0,
        'per_page' => 50,
        'current_page' => 1,
        'last_page' => 1,
        'from' => null,
        'to' => null,
    ];
}

test('company member can list pending queue with common collection and Spatie filters', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $dte = DteApiTestData::dte($company);
    $failed = transmissionFor($dte);
    $pendingDte = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    transmissionFor($pendingDte, ['status' => 'pending', 'error' => null]);
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
    transmissionFor($retriedDte);
    $latestAttempt = transmissionFor($retriedDte, [
        'attempt' => 2,
        'request_json' => ['idEnvio' => 2],
        'http_status' => 400,
        'error' => 'Error en transmisión MH [HTTP 400]',
    ]);
    $recoveredDte = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    transmissionFor($recoveredDte);
    transmissionFor($recoveredDte, [
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
    createSandboxCredentials($company);
    $dte = DteApiTestData::dte($company, [
        'status' => 'RECHAZADO',
        'received_seal' => null,
        'mh_response_json' => ['estado' => 'RECHAZADO', 'codigoMsg' => '400'],
    ]);
    transmissionFor($dte, ['http_status' => 400, 'error' => 'Error en transmisión MH [HTTP 400]']);
    fakeMhResponse('recepciondte', [
        'estado' => 'RECHAZADO',
        'codigoGeneracion' => $dte->generation_code,
        'selloRecibido' => null,
        'codigoMsg' => '004',
        'descripcionMsg' => '[identificacion.numeroControl] YA EXISTE UN REGISTRO CON ESE VALOR',
        'observaciones' => [],
    ], 400);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))->assertOk();

    $latestAttempt = MhTransmission::query()->where('attempt', 2)->sole();

    expect($latestAttempt->status)->toBe('failed')
        ->and($dte->fresh()->only(['status', 'received_seal', 'mh_response_json']))->toEqual([
            'status' => 'RECHAZADO',
            'received_seal' => null,
            'mh_response_json' => ['estado' => 'RECHAZADO', 'codigoMsg' => '400'],
        ]);

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

test('a successful retry updates the DTE with the Hacienda response and leaves the queue', function () {
    $company = DteApiTestData::company();
    createSandboxCredentials($company);
    $dte = DteApiTestData::dte($company, [
        'status' => 'RECHAZADO',
        'received_seal' => null,
        'observations' => json_encode(['Error en transmisión MH [HTTP 503]']),
        'mh_response_json' => ['estado' => 'RECHAZADO', 'codigoMsg' => '503'],
    ]);
    $failed = transmissionFor($dte, ['http_status' => 503, 'error' => 'Error en transmisión MH [HTTP 503]']);
    $mhResponse = [
        'version' => 2,
        'ambiente' => '00',
        'versionApp' => 2,
        'estado' => 'PROCESADO',
        'codigoGeneracion' => $dte->generation_code,
        'selloRecibido' => '202617FEDDCF2155456A9406A11D6F313944MUVI',
        'fhProcesamiento' => '11/11/2026 09:15:00',
        'clasificaMsg' => '10',
        'codigoMsg' => '001',
        'descripcionMsg' => 'RECIBIDO',
        'observaciones' => [],
    ];
    fakeMhResponse('recepciondte', $mhResponse);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'count' => 1,
                'results' => [[
                    'transmission_id' => $failed->id,
                    'result' => $mhResponse,
                ]],
            ],
        ]);

    expect($dte->fresh()->only(['status', 'received_seal', 'observations', 'mh_response_json']))->toEqual([
        'status' => 'PROCESADO',
        'received_seal' => '202617FEDDCF2155456A9406A11D6F313944MUVI',
        'observations' => '[]',
        'mh_response_json' => $mhResponse,
    ]);

    $this->getJson(route('api.v1.queue.index'))
        ->assertOk()
        ->assertExactJson(['data' => [], 'pagination' => emptyQueuePagination()]);
});

test('a successful invalidation retry marks the DTE as invalidated', function () {
    $company = DteApiTestData::company();
    createSandboxCredentials($company);
    $dte = DteApiTestData::dte($company, ['status' => 'RECHAZADO']);
    $invalidation = DteInvalidation::query()->create([
        'company_id' => $company->id,
        'dte_id' => $dte->id,
        'generation_code' => strtoupper((string) Str::uuid()),
        'invalidation_type' => '2',
        'reason' => 'Error en la información del documento',
        'name_person_in_charge' => 'Responsable',
        'doc_type_person_in_charge' => '13',
        'doc_number_person_in_charge' => '012345678',
        'name_request' => 'Solicitante',
        'doc_type_request' => '13',
        'doc_number_request' => '012345678',
        'original_json' => ['identificacion' => ['version' => 3]],
        'signed_json' => 'signed-invalidation',
        'status' => 'RECHAZADO',
        'observations' => 'Error en transmisión MH [HTTP 503]',
        'environment' => Environment::SANDBOX->value,
    ]);
    transmissionFor($invalidation, ['operation' => 'invalidation', 'http_status' => 503]);
    $mhResponse = [
        'version' => 2,
        'ambiente' => '00',
        'estado' => 'PROCESADO',
        'codigoGeneracion' => $invalidation->generation_code,
        'selloRecibido' => 'SELLO-ANULACION',
        'codigoMsg' => '001',
        'descripcionMsg' => 'RECIBIDO',
        'observaciones' => [],
    ];
    fakeMhResponse('anulardte', $mhResponse);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))->assertOk();

    expect($invalidation->fresh()->only(['status', 'received_seal', 'observations']))->toBe([
        'status' => 'PROCESADO',
        'received_seal' => 'SELLO-ANULACION',
        'observations' => 'RECIBIDO',
    ])
        ->and($dte->fresh()->only(['status', 'observations', 'mh_response_json']))->toEqual([
            'status' => 'INVALIDADO',
            'observations' => 'RECIBIDO',
            'mh_response_json' => $mhResponse,
        ]);
});

test('a successful contingency retry marks the event as received', function () {
    $company = DteApiTestData::company();
    createSandboxCredentials($company);
    $event = ContingencyEvent::query()->create([
        'company_id' => $company->id,
        'generation_code' => strtoupper((string) Str::uuid()),
        'environment' => Environment::SANDBOX->value,
        'contingency_type' => '1',
        'reason' => 'No disponibilidad de sistema MH',
        'start_date_at' => '2026-11-11 14:00:00',
        'end_date_at' => '2026-11-11 14:30:00',
        'original_json' => ['identificacion' => ['version' => 4]],
        'signed_json' => 'signed-contingency',
        'status' => 'RECHAZADO',
    ]);
    transmissionFor($event, ['operation' => 'contingency', 'http_status' => 503]);
    fakeMhResponse('contingencia', [
        'estado' => 'RECIBIDO',
        'codigoGeneracion' => $event->generation_code,
        'selloRecibido' => 'SELLO-CONTINGENCIA',
        'observaciones' => ['Evento registrado'],
    ]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.queue.retries.store'))->assertOk();

    expect($event->fresh()->only(['status', 'received_seal', 'observations']))->toBe([
        'status' => 'RECIBIDO',
        'received_seal' => 'SELLO-CONTINGENCIA',
        'observations' => 'Evento registrado',
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
    $own = transmissionFor($ownDte);
    transmissionFor($otherDte);
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
    $failed = transmissionFor($dte);
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
    transmissionFor($retriedDte);
    $latestAttempt = transmissionFor($retriedDte, ['attempt' => 2, 'request_json' => ['idEnvio' => 2]]);
    $recoveredDte = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    transmissionFor($recoveredDte);
    transmissionFor($recoveredDte, [
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
    transmissionFor($dte, ['status' => 'failed']);

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
    transmissionFor($otherDte, ['status' => 'failed']);
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
    transmissionFor($dte);
    transmissionFor($dte, ['attempt' => 2, 'request_json' => ['idEnvio' => 2]]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->getJson(route('api.v1.dashboard.show'))
        ->assertOk()
        ->assertJson(['data' => ['metrics' => ['pending_transmissions' => 1]]]);
});

test('dashboard requires authentication', function () {
    $this->getJson(route('api.v1.dashboard.show'))->assertUnauthorized();
});
