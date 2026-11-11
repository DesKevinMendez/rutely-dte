<?php

use App\Models\Company;
use App\Models\Dte;
use App\Models\MhTransmission;
use App\Role;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

function rejectedDte(Company $company): Dte
{
    $dte = DteApiTestData::dte($company, [
        'status' => 'RECHAZADO',
        'received_seal' => null,
        'observations' => json_encode(['Error en transmisión MH [HTTP 503]']),
        'mh_response_json' => ['estado' => 'RECHAZADO', 'codigoMsg' => '503'],
    ]);
    transmissionFor($dte, ['http_status' => 503, 'error' => 'Error en transmisión MH [HTTP 503]']);

    return $dte;
}

function processedMhResponse(Dte $dte): array
{
    return [
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
}

function otherCompany(): Company
{
    return DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
}

test('admin can retry a failed DTE and gets it back updated', function () {
    $company = DteApiTestData::company();
    createSandboxCredentials($company);
    $dte = rejectedDte($company);
    $mhResponse = processedMhResponse($dte);
    fakeMhResponse('recepciondte', $mhResponse);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $response = $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    $dte->refresh();
    expect($dte->only(['status', 'received_seal', 'observations', 'mh_response_json']))->toEqual([
        'status' => 'PROCESADO',
        'received_seal' => '202617FEDDCF2155456A9406A11D6F313944MUVI',
        'observations' => '[]',
        'mh_response_json' => $mhResponse,
    ]);
    $response->assertExactJson([
        'data' => [
            'record' => $dte->toArray(),
            'mh_result' => $mhResponse,
        ],
    ]);
    $this->assertDatabaseHas(MhTransmission::class, [
        'transmittable_id' => $dte->id,
        'operation' => 'dte',
        'attempt' => 2,
        'status' => 'success',
    ]);
});

test('company API token with list dte ability can retry its failed DTE', function () {
    $company = DteApiTestData::company();
    createSandboxCredentials($company);
    $dte = rejectedDte($company);
    $mhResponse = processedMhResponse($dte);
    fakeMhResponse('recepciondte', $mhResponse);
    $token = $company->createToken('POS', ['list:dte'])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson(route('api.v1.dtes.retries.store', $dte))
        ->assertOk();

    $dte->refresh();
    expect($dte->only(['status', 'received_seal']))->toBe([
        'status' => 'PROCESADO',
        'received_seal' => '202617FEDDCF2155456A9406A11D6F313944MUVI',
    ]);
    $response->assertExactJson([
        'data' => [
            'record' => $dte->toArray(),
            'mh_result' => $mhResponse,
        ],
    ]);
});

test('a DTE rejected again keeps its data and returns the Hacienda answer', function () {
    $company = DteApiTestData::company();
    createSandboxCredentials($company);
    $dte = rejectedDte($company);
    $mhBody = [
        'estado' => 'RECHAZADO',
        'codigoGeneracion' => $dte->generation_code,
        'selloRecibido' => null,
        'codigoMsg' => '004',
        'descripcionMsg' => '[identificacion.numeroControl] YA EXISTE UN REGISTRO CON ESE VALOR',
        'observaciones' => [],
    ];
    fakeMhResponse('recepciondte', $mhBody, 400);
    $error = 'Error en transmisión MH [HTTP 400]: '.json_encode($mhBody);
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'record' => $dte->fresh()->toArray(),
                'mh_result' => [
                    'estado' => 'RECHAZADO',
                    'codigoMsg' => '400',
                    'descripcionMsg' => $error,
                    'observaciones' => [$error],
                ],
            ],
        ]);

    expect($dte->fresh()->only(['status', 'received_seal', 'mh_response_json']))->toEqual([
        'status' => 'RECHAZADO',
        'received_seal' => null,
        'mh_response_json' => ['estado' => 'RECHAZADO', 'codigoMsg' => '503'],
    ]);
    $this->assertDatabaseHas(MhTransmission::class, [
        'transmittable_id' => $dte->id,
        'attempt' => 2,
        'status' => 'failed',
        'http_status' => 400,
    ]);
});

test('a DTE without a failed transmission cannot be retried', function (Closure $prepare) {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    $prepare($dte);
    Http::fake();
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))
        ->assertConflict()
        ->assertExactJson(['message' => 'Este DTE no tiene una transmisión fallida para reintentar.']);

    Http::assertNothingSent();
})->with([
    'never transmitted' => fn () => fn (Dte $dte) => null,
    'accepted on a later attempt' => fn () => function (Dte $dte): void {
        transmissionFor($dte);
        transmissionFor($dte, ['attempt' => 2, 'status' => 'success', 'error' => null, 'http_status' => 200]);
    },
    'rejected by Hacienda' => fn () => fn (Dte $dte) => transmissionFor($dte, ['status' => 'rejected', 'http_status' => 200]),
]);

test('users who are not admins cannot retry a DTE', function (Role $role) {
    $company = DteApiTestData::company();
    $dte = rejectedDte($company);
    Http::fake();
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertForbidden();

    Http::assertNothingSent();
    expect(MhTransmission::query()->count())->toBe(1);
})->with([
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('a forbidden user gets 403 before the conflict', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertForbidden();
});

test('company API token without list dte ability cannot retry a DTE', function () {
    $company = DteApiTestData::company();
    $dte = rejectedDte($company);
    Http::fake();
    $token = $company->createToken('POS', ['create:dte'])->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.dtes.retries.store', $dte))
        ->assertForbidden();

    Http::assertNothingSent();
});

test('admin cannot retry a DTE from another company', function () {
    $company = DteApiTestData::company();
    $dte = rejectedDte(otherCompany());
    Http::fake();
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertForbidden();

    Http::assertNothingSent();
    expect(MhTransmission::query()->count())->toBe(1);
});

test('company API token cannot retry a DTE from another company', function () {
    $company = DteApiTestData::company();
    $dte = rejectedDte(otherCompany());
    Http::fake();
    $token = $company->createToken('POS', ['list:dte'])->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.dtes.retries.store', $dte))
        ->assertForbidden();

    Http::assertNothingSent();
    expect(MhTransmission::query()->count())->toBe(1);
});

test('retrying a DTE requires authentication', function () {
    $dte = rejectedDte(DteApiTestData::company());

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertUnauthorized();
});
