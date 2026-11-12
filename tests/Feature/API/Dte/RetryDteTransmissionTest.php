<?php

use App\Environment;
use App\Models\Company;
use App\Models\Dte;
use App\Models\DteCorrelative;
use App\Models\MhTransmission;
use App\Role;
use Illuminate\Http\Client\Request;
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

/**
 * A DTE that Hacienda rejected because its control number was already registered.
 *
 * @param  array<string, mixed>  $rejectionOverrides
 */
function duplicateControlNumberDte(Company $company, array $rejectionOverrides = [], string $controlNumber = 'DTE-01-M001P001-000000000000006'): Dte
{
    $dte = DteApiTestData::dte($company, [
        'control_number' => $controlNumber,
        'status' => 'RECHAZADO',
        'received_seal' => null,
        'mh_response_json' => ['estado' => 'RECHAZADO', 'codigoMsg' => '400'],
    ]);
    transmissionFor($dte, [
        'request_json' => ['idEnvio' => 1, 'codigoGeneracion' => $dte->generation_code],
        'http_status' => 400,
        'response_json' => [
            'estado' => 'RECHAZADO',
            'codigoGeneracion' => $dte->generation_code,
            'clasificaMsg' => '11',
            'codigoMsg' => '004',
            'descripcionMsg' => '[identificacion.numeroControl] YA EXISTE UN REGISTRO CON ESE VALOR',
            'observaciones' => [],
        ],
        ...$rejectionOverrides,
    ]);

    return $dte;
}

/**
 * A DTE that Hacienda accepted (sealed) in the company's establishment, unless overridden.
 *
 * @param  array<string, mixed>  $overrides
 */
function acceptedDte(Company $company, string $controlNumber, array $overrides = [], string $issuedOn = '2026-08-18'): Dte
{
    $dte = DteApiTestData::dte($company, ['control_number' => $controlNumber, ...$overrides]);
    $document = $dte->original_json;
    data_set($document, 'identificacion.numeroControl', $controlNumber);
    data_set($document, 'identificacion.fecEmi', $issuedOn);
    $dte->update(['original_json' => $document]);

    return $dte;
}

function lastCorrelativeIs(Company $company, int $lastValue): void
{
    DteCorrelative::query()->create([
        'company_id' => $company->id,
        'key' => DteCorrelative::keyFor($company, 2026),
        'last_value' => (string) $lastValue,
    ]);
}

test('a DTE rejected for a repeated control number is signed again after the last number Hacienda accepted, with the same generation code', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    lastCorrelativeIs($company, 5);
    acceptedDte($company, 'DTE-01-M001P001-000000000000022');
    $dte = duplicateControlNumberDte($company);
    $generationCode = $dte->generation_code;
    fakeMhResponse('recepciondte', processedMhResponse($dte));
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    $dte->refresh();
    $signedDocument = signedDocument($dte->signed_json);
    expect($dte->only(['status', 'control_number', 'generation_code']))->toBe([
        'status' => 'PROCESADO',
        'control_number' => 'DTE-01-M001P001-000000000000023',
        'generation_code' => $generationCode,
    ])
        ->and($dte->original_json['identificacion'])->toMatchArray([
            'numeroControl' => 'DTE-01-M001P001-000000000000023',
            'codigoGeneracion' => $generationCode,
        ])
        ->and($signedDocument['identificacion'])->toMatchArray([
            'numeroControl' => 'DTE-01-M001P001-000000000000023',
            'codigoGeneracion' => $generationCode,
        ]);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/fesv/recepciondte')
        && $request['codigoGeneracion'] === $generationCode
        && $request['documento'] === $dte->signed_json);
    $this->assertDatabaseHas(DteCorrelative::class, [
        'company_id' => $company->id,
        'key' => DteCorrelative::keyFor($company, 2026),
        'last_value' => '23',
    ]);
});

test('a repeated control number corrected after 24 hours keeps the same generation code', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    acceptedDte($company, 'DTE-01-M001P001-000000000000022');
    $dte = duplicateControlNumberDte($company, ['responded_at' => now()->subHours(25)]);
    $generationCode = $dte->generation_code;
    fakeMhResponse('recepciondte', processedMhResponse($dte));
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    $dte->refresh();
    expect($dte->only(['control_number', 'generation_code']))->toBe([
        'control_number' => 'DTE-01-M001P001-000000000000023',
        'generation_code' => $generationCode,
    ])
        ->and(signedDocument($dte->signed_json)['identificacion'])->toMatchArray([
            'numeroControl' => 'DTE-01-M001P001-000000000000023',
            'codigoGeneracion' => $generationCode,
        ]);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/fesv/recepciondte')
        && $request['codigoGeneracion'] === $generationCode);
});

test('only numbers Hacienda accepted in the same establishment, environment and fiscal year are continued', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    lastCorrelativeIs($company, 5);
    acceptedDte($company, 'DTE-01-M001P001-000000000000035');
    acceptedDte($company, 'DTE-03-M001P002-000000000000045', ['dte_type' => '03', 'status' => 'INVALIDADO']);
    acceptedDte($company, 'DTE-01-M001P001-000000000000090', ['status' => 'RECHAZADO', 'received_seal' => null]);
    acceptedDte($company, 'DTE-01-M002P001-000000000000080');
    acceptedDte($company, 'DTE-01-M001P001-000000000000070', ['environment' => Environment::PRODUCTION->value]);
    acceptedDte($company, 'DTE-01-M001P001-000000000000060', issuedOn: '2025-12-31');
    acceptedDte(otherCompany(), 'DTE-01-M001P001-000000000000050');
    $dte = duplicateControlNumberDte($company);
    fakeMhResponse('recepciondte', processedMhResponse($dte));
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    expect($dte->refresh()->control_number)->toBe('DTE-01-M001P001-000000000000046')
        ->and(signedDocument($dte->signed_json)['identificacion']['numeroControl'])
        ->toBe('DTE-01-M001P001-000000000000046');
    $this->assertDatabaseHas(DteCorrelative::class, [
        'company_id' => $company->id,
        'key' => DteCorrelative::keyFor($company, 2026),
        'last_value' => '46',
    ]);
});

test('without numbers accepted by Hacienda the correction continues after the rejected number', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    lastCorrelativeIs($company, 3);
    $dte = duplicateControlNumberDte($company, controlNumber: 'DTE-01-M001P001-000000000000500');
    fakeMhResponse('recepciondte', processedMhResponse($dte));
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    expect($dte->refresh()->control_number)->toBe('DTE-01-M001P001-000000000000501');
    $this->assertDatabaseHas(DteCorrelative::class, [
        'company_id' => $company->id,
        'key' => DteCorrelative::keyFor($company, 2026),
        'last_value' => '501',
    ]);
});

test('correcting a repeated control number never moves the establishment correlative back', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    lastCorrelativeIs($company, 100);
    acceptedDte($company, 'DTE-01-M001P001-000000000000035');
    $dte = duplicateControlNumberDte($company);
    fakeMhResponse('recepciondte', processedMhResponse($dte));
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    expect($dte->refresh()->control_number)->toBe('DTE-01-M001P001-000000000000036');
    $this->assertDatabaseHas(DteCorrelative::class, [
        'company_id' => $company->id,
        'key' => DteCorrelative::keyFor($company, 2026),
        'last_value' => '100',
    ]);
});

test('a DTE rejected for any other reason is sent again unchanged', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    lastCorrelativeIs($company, 22);
    $dte = duplicateControlNumberDte($company, [
        'response_json' => [
            'estado' => 'RECHAZADO',
            'codigoMsg' => '004',
            'descripcionMsg' => '[identificacion.codigoGeneracion] YA EXISTE UN REGISTRO CON ESE VALOR',
            'observaciones' => [],
        ],
    ]);
    fakeMhResponse('recepciondte', processedMhResponse($dte));
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))->assertOk();

    expect($dte->fresh()->only(['control_number', 'generation_code', 'signed_json']))->toBe(
        $dte->only(['control_number', 'generation_code', 'signed_json']),
    );
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/fesv/recepciondte')
        && $request['documento'] === 'signed-jws');
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $company->id, 'last_value' => '22']);
});

test('a repeated control number that cannot be signed again is not sent', function () {
    $company = DteApiTestData::company();
    $dte = duplicateControlNumberDte($company);
    Http::fake();
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->postJson(route('api.v1.dtes.retries.store', $dte))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'record' => $dte->fresh()->toArray(),
                'mh_result' => [
                    'estado' => 'RECHAZADO',
                    'codigoMsg' => '500',
                    'descripcionMsg' => 'No hay material de firma configurado para la empresa y el ambiente seleccionados.',
                    'observaciones' => ['No hay material de firma configurado para la empresa y el ambiente seleccionados.'],
                ],
            ],
        ]);

    Http::assertNothingSent();
    expect($dte->fresh()->only(['control_number', 'signed_json']))->toBe($dte->only(['control_number', 'signed_json']))
        ->and(MhTransmission::query()->count())->toBe(1);
});
