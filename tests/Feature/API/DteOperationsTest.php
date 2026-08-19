<?php

use App\Environment;
use App\Models\ContingencyEvent;
use App\Models\DteInvalidation;
use App\Role;
use App\Services\Dte\ContingencyService;
use App\Services\Dte\DteInvalidationService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

function validInvalidationPayload(): array
{
    return [
        'tipoAnulacion' => 1,
        'motivoAnulacion' => 'Error en la información del documento',
        'nombreResponsable' => 'Responsable DTE',
        'tipDocResponsable' => '13',
        'numDocResponsable' => '012345678',
        'nombreSolicita' => 'Solicitante DTE',
        'tipDocSolicita' => '13',
        'numDocSolicita' => '012345678',
    ];
}

function validContingencyPayload(): array
{
    return [
        'dtes' => [[
            'codigoGeneracion' => (string) Str::uuid(),
            'tipoDte' => '01',
        ]],
        'fInicio' => '2026-08-18',
        'hInicio' => '10:00:00',
        'fFin' => '2026-08-18',
        'hFin' => '10:30:00',
        'tipoContingencia' => 1,
        'motivoContingencia' => 'No disponibilidad del servicio de Hacienda',
    ];
}

test('company member can invalidate a DTE through nested store endpoint', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $dte = DteApiTestData::dte($company);
    $payload = validInvalidationPayload();
    $mhResult = ['estado' => 'PROCESADO', 'selloRecibido' => 'INVALIDATION-SEAL', 'observaciones' => []];
    $invalidation = DteInvalidation::query()->create([
        'company_id' => $company->id,
        'dte_id' => $dte->id,
        'generation_code' => strtoupper((string) Str::uuid()),
        'invalidation_type' => '1',
        'reason' => $payload['motivoAnulacion'],
        'name_person_in_charge' => $payload['nombreResponsable'],
        'doc_type_person_in_charge' => $payload['tipDocResponsable'],
        'doc_number_person_in_charge' => $payload['numDocResponsable'],
        'name_request' => $payload['nombreSolicita'],
        'doc_type_request' => $payload['tipDocSolicita'],
        'doc_number_request' => $payload['numDocSolicita'],
        'original_json' => ['identificacion' => ['version' => 3]],
        'signed_json' => 'signed-invalidation',
        'received_seal' => 'INVALIDATION-SEAL',
        'status' => 'PROCESADO',
        'environment' => Environment::SANDBOX->value,
    ]);

    $this->mock(DteInvalidationService::class)
        ->shouldReceive('invalidate')
        ->once()
        ->withArgs(fn ($receivedDte, array $receivedPayload): bool => $receivedDte->is($dte) && $receivedPayload === $payload)
        ->andReturnUsing(function () use ($dte, $mhResult, $invalidation): array {
            $dte->update(['status' => 'INVALIDADO']);

            return [
                'updated' => $dte->fresh(),
                'mhResult' => $mhResult,
                'invalidation' => $invalidation,
            ];
        });
    Sanctum::actingAs($user);

    $this->postJson(route('api.v1.dtes.invalidations.store', $dte), $payload)
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'dte' => $dte->fresh()->toArray(),
                'invalidation' => $invalidation->toArray(),
                'mh_result' => $mhResult,
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('invalidation rejects another company and already invalidated DTEs', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $otherDte = DteApiTestData::dte($otherCompany);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->postJson(route('api.v1.dtes.invalidations.store', $otherDte), validInvalidationPayload())
        ->assertForbidden();

    $ownInvalidated = DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
        'status' => 'INVALIDADO',
    ]);
    $this->postJson(route('api.v1.dtes.invalidations.store', $ownInvalidated), validInvalidationPayload())
        ->assertForbidden();
});

test('DTE invalidation requires authentication', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);

    $this->postJson(route('api.v1.dtes.invalidations.store', $dte), validInvalidationPayload())
        ->assertUnauthorized();
});

test('DTE invalidation request validation returns 422', function (string $field, mixed $value, string $message) {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));
    $payload = validInvalidationPayload();
    $payload[$field] = $value;

    $this->postJson(route('api.v1.dtes.invalidations.store', $dte), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'type integer' => ['tipoAnulacion', 'one', 'El campo tipo de anulación debe ser un número entero.'],
    'type supported' => ['tipoAnulacion', 4, 'El valor seleccionado para tipo de anulación no es válido.'],
    'reason string' => ['motivoAnulacion', ['error'], 'El campo motivo de anulación debe ser una cadena de caracteres.'],
    'reason minimum' => ['motivoAnulacion', '1234', 'El campo motivo de anulación debe contener al menos 5 caracteres.'],
    'reason maximum' => ['motivoAnulacion', str_repeat('a', 501), 'El campo motivo de anulación no debe contener más de 500 caracteres.'],
    'responsible name string' => ['nombreResponsable', ['Name'], 'El campo nombre del responsable debe ser una cadena de caracteres.'],
    'responsible name maximum' => ['nombreResponsable', str_repeat('a', 256), 'El campo nombre del responsable no debe contener más de 255 caracteres.'],
    'responsible doc type string' => ['tipDocResponsable', ['13'], 'El campo tipo de documento del responsable debe ser una cadena de caracteres.'],
    'responsible doc type maximum' => ['tipDocResponsable', str_repeat('a', 11), 'El campo tipo de documento del responsable no debe contener más de 10 caracteres.'],
    'responsible doc number string' => ['numDocResponsable', ['123'], 'El campo número de documento del responsable debe ser una cadena de caracteres.'],
    'responsible doc number maximum' => ['numDocResponsable', str_repeat('1', 51), 'El campo número de documento del responsable no debe contener más de 50 caracteres.'],
    'requester name string' => ['nombreSolicita', ['Name'], 'El campo nombre del solicitante debe ser una cadena de caracteres.'],
    'requester name maximum' => ['nombreSolicita', str_repeat('a', 256), 'El campo nombre del solicitante no debe contener más de 255 caracteres.'],
    'requester doc type string' => ['tipDocSolicita', ['13'], 'El campo tipo de documento del solicitante debe ser una cadena de caracteres.'],
    'requester doc type maximum' => ['tipDocSolicita', str_repeat('a', 11), 'El campo tipo de documento del solicitante no debe contener más de 10 caracteres.'],
    'requester doc number string' => ['numDocSolicita', ['123'], 'El campo número de documento del solicitante debe ser una cadena de caracteres.'],
    'requester doc number maximum' => ['numDocSolicita', str_repeat('1', 51), 'El campo número de documento del solicitante no debe contener más de 50 caracteres.'],
]);

test('company member can create contingency event with common response', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $payload = validContingencyPayload();
    $document = ['identificacion' => ['version' => 4, 'ambiente' => '00']];
    $mhResult = ['estado' => 'RECIBIDO', 'selloRecibido' => 'CONTINGENCY-SEAL', 'observaciones' => []];
    $event = ContingencyEvent::query()->create([
        'company_id' => $company->id,
        'generation_code' => strtoupper((string) Str::uuid()),
        'environment' => Environment::SANDBOX->value,
        'contingency_type' => '1',
        'reason' => $payload['motivoContingencia'],
        'start_date_at' => '2026-08-18 10:00:00',
        'end_date_at' => '2026-08-18 10:30:00',
        'original_json' => $document,
        'signed_json' => 'signed-contingency',
        'received_seal' => 'CONTINGENCY-SEAL',
        'status' => 'RECIBIDO',
    ]);

    $this->mock(ContingencyService::class)
        ->shouldReceive('create')
        ->once()
        ->with($company->id, $payload)
        ->andReturn(['event' => $event, 'document' => $document, 'mhResult' => $mhResult]);
    Sanctum::actingAs($user);

    $this->postJson(route('api.v1.contingency.events.store'), $payload)
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'event' => $event->toArray(),
                'contingency_document' => $document,
                'mh_result' => $mhResult,
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('contingency event endpoint requires authentication', function () {
    $this->postJson(route('api.v1.contingency.events.store'), validContingencyPayload())
        ->assertUnauthorized();
});

test('contingency event request validation returns 422', function (array $payload, string $field, string $message) {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->postJson(route('api.v1.contingency.events.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'DTE list required' => [array_diff_key(validContingencyPayload(), ['dtes' => true]), 'dtes', 'El campo DTE es obligatorio.'],
    'DTE list array' => [array_replace(validContingencyPayload(), ['dtes' => 'dte']), 'dtes', 'El campo DTE debe ser un arreglo.'],
    'DTE list minimum' => [array_replace(validContingencyPayload(), ['dtes' => []]), 'dtes', 'El campo DTE es obligatorio.'],
    'DTE list maximum' => [array_replace(validContingencyPayload(), ['dtes' => array_fill(0, 101, ['codigoGeneracion' => '11111111-1111-4111-8111-111111111111', 'tipoDte' => '01'])]), 'dtes', 'El campo DTE no debe contener más de 100 elementos.'],
    'generation code required' => [array_replace(validContingencyPayload(), ['dtes' => [['tipoDte' => '01']]]), 'dtes.0.codigoGeneracion', 'El campo código de generación es obligatorio.'],
    'generation code UUID' => [array_replace(validContingencyPayload(), ['dtes' => [['codigoGeneracion' => 'invalid', 'tipoDte' => '01']]]), 'dtes.0.codigoGeneracion', 'El campo código de generación debe ser un UUID válido.'],
    'DTE type required' => [array_replace(validContingencyPayload(), ['dtes' => [['codigoGeneracion' => '11111111-1111-4111-8111-111111111111']]]), 'dtes.0.tipoDte', 'El campo tipo de DTE es obligatorio.'],
    'DTE type string' => [array_replace(validContingencyPayload(), ['dtes' => [['codigoGeneracion' => '11111111-1111-4111-8111-111111111111', 'tipoDte' => ['01']]]]), 'dtes.0.tipoDte', 'El campo tipo de DTE debe ser una cadena de caracteres.'],
    'DTE type supported' => [array_replace(validContingencyPayload(), ['dtes' => [['codigoGeneracion' => '11111111-1111-4111-8111-111111111111', 'tipoDte' => '99']]]), 'dtes.0.tipoDte', 'El valor seleccionado para tipo de DTE no es válido.'],
    'start date required' => [array_diff_key(validContingencyPayload(), ['fInicio' => true]), 'fInicio', 'El campo fecha de inicio es obligatorio.'],
    'start date format' => [array_replace(validContingencyPayload(), ['fInicio' => '18-08-2026']), 'fInicio', 'El campo fecha de inicio debe tener el formato Y-m-d.'],
    'start time required' => [array_diff_key(validContingencyPayload(), ['hInicio' => true]), 'hInicio', 'El campo hora de inicio es obligatorio.'],
    'start time format' => [array_replace(validContingencyPayload(), ['hInicio' => '10:00']), 'hInicio', 'El campo hora de inicio debe tener el formato H:i:s.'],
    'end date required' => [array_diff_key(validContingencyPayload(), ['fFin' => true]), 'fFin', 'El campo fecha de fin es obligatorio.'],
    'end date format' => [array_replace(validContingencyPayload(), ['fFin' => '18-08-2026']), 'fFin', 'El campo fecha de fin debe tener el formato Y-m-d.'],
    'end time required' => [array_diff_key(validContingencyPayload(), ['hFin' => true]), 'hFin', 'El campo hora de fin es obligatorio.'],
    'end time format' => [array_replace(validContingencyPayload(), ['hFin' => '10:30']), 'hFin', 'El campo hora de fin debe tener el formato H:i:s.'],
    'contingency type required' => [array_diff_key(validContingencyPayload(), ['tipoContingencia' => true]), 'tipoContingencia', 'El campo tipo de contingencia es obligatorio.'],
    'contingency type integer' => [array_replace(validContingencyPayload(), ['tipoContingencia' => 'one']), 'tipoContingencia', 'El campo tipo de contingencia debe ser un número entero.'],
    'contingency type supported' => [array_replace(validContingencyPayload(), ['tipoContingencia' => 5]), 'tipoContingencia', 'El valor seleccionado para tipo de contingencia no es válido.'],
    'contingency reason string' => [array_replace(validContingencyPayload(), ['motivoContingencia' => ['reason']]), 'motivoContingencia', 'El campo motivo de contingencia debe ser una cadena de caracteres.'],
    'contingency reason minimum' => [array_replace(validContingencyPayload(), ['motivoContingencia' => '1234']), 'motivoContingencia', 'El campo motivo de contingencia debe contener al menos 5 caracteres.'],
    'contingency reason maximum' => [array_replace(validContingencyPayload(), ['motivoContingencia' => str_repeat('a', 501)]), 'motivoContingencia', 'El campo motivo de contingencia no debe contener más de 500 caracteres.'],
]);

test('company member can read contingency status and admin can update it', function () {
    $company = DteApiTestData::company();
    $admin = DteApiTestData::user($company, Role::ADMIN);
    Sanctum::actingAs($admin);

    $this->getJson(route('api.v1.contingency.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'contingency_active' => false,
            'circuit_state' => 'CLOSED',
        ]]);

    $this->patchJson(route('api.v1.contingency.update'), ['active' => true])
        ->assertOk()
        ->assertExactJson(['data' => [
            'contingency_active' => true,
            'circuit_state' => 'MANUAL_OPEN',
        ]]);
});

test('operational user can read but cannot update contingency status', function () {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.contingency.show'))->assertOk();
    $this->patchJson(route('api.v1.contingency.update'), ['active' => true])->assertForbidden();
});

test('contingency status requires authentication', function () {
    $this->getJson(route('api.v1.contingency.show'))->assertUnauthorized();
    $this->patchJson(route('api.v1.contingency.update'), ['active' => true])->assertUnauthorized();
});

test('contingency status request validation returns 422', function (array $payload, string $message) {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::ADMIN));

    $this->patchJson(route('api.v1.contingency.update'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['active' => $message]);
})->with([
    'active required' => [[], 'El campo activo es obligatorio.'],
    'active boolean' => [['active' => 'yes'], 'El campo activo debe ser verdadero o falso.'],
]);