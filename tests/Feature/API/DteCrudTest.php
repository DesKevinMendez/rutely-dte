<?php

use App\Environment;
use App\Models\Dte;
use App\Role;
use App\Services\Dte\DteIssuanceService;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

function dteValidationPayload(array $overrides = []): array
{
    return array_replace_recursive(DteApiTestData::validDtePayload(), $overrides);
}

test('company member can list DTEs with Spatie filters and common collection', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $dte = DteApiTestData::dte($company, ['dte_type' => '01', 'status' => 'PROCESADO']);
    DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Illuminate\Support\Str::uuid()),
        'control_number' => 'DTE-03-00010001-000000000000002',
        'dte_type' => '03',
        'status' => 'RECHAZADO',
    ]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.dtes.index', [
        'filter' => ['tipoDte' => '01', 'estado' => 'PROCESADO'],
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [$dte->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 10,
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

test('DTE list is tenant isolated', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $own = DteApiTestData::dte($company);
    DteApiTestData::dte($otherCompany, [
        'generation_code' => strtoupper((string) Illuminate\Support\Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.dtes.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => [$own->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
            ],
        ]);
});

test('company member can create a DTE through issuance service with common response', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $record = DteApiTestData::dte($company);
    $payload = DteApiTestData::validDtePayload();
    $mhResult = [
        'estado' => 'PROCESADO',
        'selloRecibido' => 'SELLO-MH',
        'observaciones' => [],
    ];

    $this->mock(DteIssuanceService::class)
        ->shouldReceive('issue')
        ->once()
        ->with($company->id, $payload)
        ->andReturn(['record' => $record, 'mhResult' => $mhResult]);
    Sanctum::actingAs($user);

    $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertCreated()
        ->assertExactJson([
            'data' => [
                'record' => $record->toArray(),
                'mh_result' => $mhResult,
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('company member can show its DTE with common response', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.dtes.show', $dte))
        ->assertOk()
        ->assertExactJson(['data' => $dte->fresh()->toArray()]);
});

test('DTE show rejects cross-company access', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $dte = DteApiTestData::dte($otherCompany);
    Sanctum::actingAs(DteApiTestData::user($company));

    $this->getJson(route('api.v1.dtes.show', $dte))->assertForbidden();
});

test('DTE endpoints require authentication', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);

    $this->getJson(route('api.v1.dtes.index'))->assertUnauthorized();
    $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())->assertUnauthorized();
    $this->getJson(route('api.v1.dtes.show', $dte))->assertUnauthorized();
});

test('store DTE request validation returns 422', function (array $payload, string $field, string $message) {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'tipo DTE must be string' => [dteValidationPayload(['tipoDte' => ['01']]), 'tipoDte', 'El campo tipo de DTE debe ser una cadena de caracteres.'],
    'tipo DTE must be supported' => [dteValidationPayload(['tipoDte' => '99']), 'tipoDte', 'El valor seleccionado para tipo de DTE no es válido.'],
    'environment must be string' => [dteValidationPayload(['ambiente' => ['00']]), 'ambiente', 'El campo ambiente debe ser una cadena de caracteres.'],
    'environment must be supported' => [dteValidationPayload(['ambiente' => '99']), 'ambiente', 'El valor seleccionado para ambiente no es válido.'],
    'issuer must be array' => [dteValidationPayload(['emisor' => 'issuer']), 'emisor', 'El campo emisor debe ser un arreglo.'],
    'issuer NIT must be string' => [dteValidationPayload(['emisor' => ['nit' => ['0614']]]), 'emisor.nit', 'El campo NIT del emisor debe ser una cadena de caracteres.'],
    'issuer NIT format' => [dteValidationPayload(['emisor' => ['nit' => 'ABC']]), 'emisor.nit', 'El formato del campo NIT del emisor no es válido.'],
    'issuer NRC must be string' => [dteValidationPayload(['emisor' => ['nrc' => ['123']]]), 'emisor.nrc', 'El campo emisor.nrc debe ser una cadena de caracteres.'],
    'issuer name must be string' => [dteValidationPayload(['emisor' => ['nombre' => ['Rutely']]]), 'emisor.nombre', 'El campo nombre del emisor debe ser una cadena de caracteres.'],
    'issuer name minimum' => [dteValidationPayload(['emisor' => ['nombre' => 'AB']]), 'emisor.nombre', 'El campo nombre del emisor debe contener al menos 3 caracteres.'],
    'issuer activity code must be string' => [dteValidationPayload(['emisor' => ['codActividad' => ['62010']]]), 'emisor.codActividad', 'El campo código de actividad del emisor debe ser una cadena de caracteres.'],
    'issuer activity code size' => [dteValidationPayload(['emisor' => ['codActividad' => '6201']]), 'emisor.codActividad', 'El campo código de actividad del emisor debe contener 5 caracteres.'],
    'issuer activity description must be string' => [dteValidationPayload(['emisor' => ['descActividad' => ['Software']]]), 'emisor.descActividad', 'El campo descripción de actividad del emisor debe ser una cadena de caracteres.'],
    'issuer activity description minimum' => [dteValidationPayload(['emisor' => ['descActividad' => 'AB']]), 'emisor.descActividad', 'El campo descripción de actividad del emisor debe contener al menos 3 caracteres.'],
    'issuer commercial name must be string' => [dteValidationPayload(['emisor' => ['nombreComercial' => ['Rutely']]]), 'emisor.nombreComercial', 'El campo emisor.nombre comercial debe ser una cadena de caracteres.'],
    'issuer establishment type must be string' => [dteValidationPayload(['emisor' => ['tipoEstablecimiento' => ['01']]]), 'emisor.tipoEstablecimiento', 'El campo tipo de establecimiento del emisor debe ser una cadena de caracteres.'],
    'issuer establishment type must be supported' => [dteValidationPayload(['emisor' => ['tipoEstablecimiento' => '99']]), 'emisor.tipoEstablecimiento', 'El valor seleccionado para tipo de establecimiento del emisor no es válido.'],
    'issuer address must be array' => [dteValidationPayload(['emisor' => ['direccion' => 'address']]), 'emisor.direccion', 'El campo emisor.direccion debe ser un arreglo.'],
    'issuer department required with address' => [dteValidationPayload(['emisor' => ['direccion' => ['municipio' => '01', 'complemento' => 'Calle 1']]]), 'emisor.direccion.departamento', 'El campo departamento del emisor es obligatorio cuando emisor.direccion está presente.'],
    'issuer department must be string' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => ['06'], 'municipio' => '01', 'complemento' => 'Calle 1']]]), 'emisor.direccion.departamento', 'El campo departamento del emisor debe ser una cadena de caracteres.'],
    'issuer department size' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '6', 'municipio' => '01', 'complemento' => 'Calle 1']]]), 'emisor.direccion.departamento', 'El campo departamento del emisor debe contener 2 caracteres.'],
    'issuer municipality required with address' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '06', 'complemento' => 'Calle 1']]]), 'emisor.direccion.municipio', 'El campo municipio del emisor es obligatorio cuando emisor.direccion está presente.'],
    'issuer municipality must be string' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '06', 'municipio' => ['01'], 'complemento' => 'Calle 1']]]), 'emisor.direccion.municipio', 'El campo municipio del emisor debe ser una cadena de caracteres.'],
    'issuer municipality size' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '06', 'municipio' => '1', 'complemento' => 'Calle 1']]]), 'emisor.direccion.municipio', 'El campo municipio del emisor debe contener 2 caracteres.'],
    'issuer address complement required' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '06', 'municipio' => '01']]]), 'emisor.direccion.complemento', 'El campo complemento de dirección del emisor es obligatorio cuando emisor.direccion está presente.'],
    'issuer address complement must be string' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '06', 'municipio' => '01', 'complemento' => ['Calle']]]]), 'emisor.direccion.complemento', 'El campo complemento de dirección del emisor debe ser una cadena de caracteres.'],
    'issuer address complement minimum' => [dteValidationPayload(['emisor' => ['direccion' => ['departamento' => '06', 'municipio' => '01', 'complemento' => 'AB']]]), 'emisor.direccion.complemento', 'El campo complemento de dirección del emisor debe contener al menos 3 caracteres.'],
    'issuer phone must be string' => [dteValidationPayload(['emisor' => ['telefono' => ['22223333']]]), 'emisor.telefono', 'El campo emisor.telefono debe ser una cadena de caracteres.'],
    'issuer email must be valid' => [dteValidationPayload(['emisor' => ['correo' => 'invalid']]), 'emisor.correo', 'El campo correo del emisor debe ser una dirección de correo electrónico válida.'],
    'receiver must be array' => [dteValidationPayload(['receptor' => 'receiver']), 'receptor', 'El campo receptor debe ser un arreglo.'],
    'receiver document type must be string' => [dteValidationPayload(['receptor' => ['tipoDocumento' => ['36']]]), 'receptor.tipoDocumento', 'El campo tipo de documento del receptor debe ser una cadena de caracteres.'],
    'receiver document type supported' => [dteValidationPayload(['receptor' => ['tipoDocumento' => '99']]), 'receptor.tipoDocumento', 'El valor seleccionado para tipo de documento del receptor no es válido.'],
    'receiver document number must be string' => [dteValidationPayload(['receptor' => ['numDocumento' => ['0614']]]), 'receptor.numDocumento', 'El campo receptor.num documento debe ser una cadena de caracteres.'],
    'receiver NRC must be string' => [dteValidationPayload(['receptor' => ['nrc' => ['123']]]), 'receptor.nrc', 'El campo receptor.nrc debe ser una cadena de caracteres.'],
    'receiver name must be string' => [dteValidationPayload(['receptor' => ['nombre' => ['Cliente']]]), 'receptor.nombre', 'El campo receptor.nombre debe ser una cadena de caracteres.'],
    'receiver activity code must be string' => [dteValidationPayload(['receptor' => ['codActividad' => ['46900']]]), 'receptor.codActividad', 'El campo receptor.cod actividad debe ser una cadena de caracteres.'],
    'receiver activity description must be string' => [dteValidationPayload(['receptor' => ['descActividad' => ['Servicios']]]), 'receptor.descActividad', 'El campo receptor.desc actividad debe ser una cadena de caracteres.'],
    'receiver address must be array' => [dteValidationPayload(['receptor' => ['direccion' => 'address']]), 'receptor.direccion', 'El campo receptor.direccion debe ser un arreglo.'],
    'receiver department required' => [dteValidationPayload(['receptor' => ['direccion' => ['municipio' => '01', 'complemento' => 'Calle 1']]]), 'receptor.direccion.departamento', 'El campo departamento del receptor es obligatorio cuando receptor.direccion está presente.'],
    'receiver department must be string' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => ['06'], 'municipio' => '01', 'complemento' => 'Calle 1']]]), 'receptor.direccion.departamento', 'El campo departamento del receptor debe ser una cadena de caracteres.'],
    'receiver department size' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '6', 'municipio' => '01', 'complemento' => 'Calle 1']]]), 'receptor.direccion.departamento', 'El campo departamento del receptor debe contener 2 caracteres.'],
    'receiver municipality required' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '06', 'complemento' => 'Calle 1']]]), 'receptor.direccion.municipio', 'El campo municipio del receptor es obligatorio cuando receptor.direccion está presente.'],
    'receiver municipality must be string' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '06', 'municipio' => ['01'], 'complemento' => 'Calle 1']]]), 'receptor.direccion.municipio', 'El campo municipio del receptor debe ser una cadena de caracteres.'],
    'receiver municipality size' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '06', 'municipio' => '1', 'complemento' => 'Calle 1']]]), 'receptor.direccion.municipio', 'El campo municipio del receptor debe contener 2 caracteres.'],
    'receiver complement required' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '06', 'municipio' => '01']]]), 'receptor.direccion.complemento', 'El campo complemento de dirección del receptor es obligatorio cuando receptor.direccion está presente.'],
    'receiver complement must be string' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '06', 'municipio' => '01', 'complemento' => ['Calle']]]]), 'receptor.direccion.complemento', 'El campo complemento de dirección del receptor debe ser una cadena de caracteres.'],
    'receiver complement minimum' => [dteValidationPayload(['receptor' => ['direccion' => ['departamento' => '06', 'municipio' => '01', 'complemento' => 'AB']]]), 'receptor.direccion.complemento', 'El campo complemento de dirección del receptor debe contener al menos 3 caracteres.'],
    'receiver phone must be string' => [dteValidationPayload(['receptor' => ['telefono' => ['77778888']]]), 'receptor.telefono', 'El campo receptor.telefono debe ser una cadena de caracteres.'],
    'receiver email must be valid' => [dteValidationPayload(['receptor' => ['correo' => 'invalid']]), 'receptor.correo', 'El campo correo del receptor debe ser una dirección de correo electrónico válida.'],
    'items required' => [[], 'items', 'El campo ítems es obligatorio.'],
    'items must be array' => [['items' => 'items'], 'items', 'El campo ítems debe ser un arreglo.'],
    'items minimum' => [['items' => []], 'items', 'El campo ítems debe contener al menos 1 elementos.'],
    'items maximum' => [['items' => array_fill(0, 201, ['descripcion' => 'Item', 'cantidad' => 1, 'precioUni' => 1])], 'items', 'El campo ítems no debe contener más de 200 elementos.'],
    'item description required' => [['items' => [['cantidad' => 1, 'precioUni' => 1]]], 'items.0.descripcion', 'El campo descripción del ítem es obligatorio.'],
    'item description must be string' => [['items' => [['descripcion' => ['Item'], 'cantidad' => 1, 'precioUni' => 1]]], 'items.0.descripcion', 'El campo descripción del ítem debe ser una cadena de caracteres.'],
    'item quantity required' => [['items' => [['descripcion' => 'Item', 'precioUni' => 1]]], 'items.0.cantidad', 'El campo cantidad del ítem es obligatorio.'],
    'item quantity numeric' => [['items' => [['descripcion' => 'Item', 'cantidad' => 'x', 'precioUni' => 1]]], 'items.0.cantidad', 'El campo cantidad del ítem debe ser un número.'],
    'item quantity greater than zero' => [['items' => [['descripcion' => 'Item', 'cantidad' => 0, 'precioUni' => 1]]], 'items.0.cantidad', 'El campo cantidad del ítem debe ser mayor que 0.'],
    'item price required' => [['items' => [['descripcion' => 'Item', 'cantidad' => 1]]], 'items.0.precioUni', 'El campo precio unitario del ítem es obligatorio.'],
    'item price numeric' => [['items' => [['descripcion' => 'Item', 'cantidad' => 1, 'precioUni' => 'x']]], 'items.0.precioUni', 'El campo precio unitario del ítem debe ser un número.'],
    'item price nonnegative' => [['items' => [['descripcion' => 'Item', 'cantidad' => 1, 'precioUni' => -1]]], 'items.0.precioUni', 'El campo precio unitario del ítem debe ser mayor o igual que 0.'],
    'item discount numeric' => [dteValidationPayload(['items' => [['montoDescu' => 'x']]]), 'items.0.montoDescu', 'El campo monto de descuento del ítem debe ser un número.'],
    'item discount nonnegative' => [dteValidationPayload(['items' => [['montoDescu' => -1]]]), 'items.0.montoDescu', 'El campo monto de descuento del ítem debe ser mayor o igual que 0.'],
    'item type integer' => [dteValidationPayload(['items' => [['tipoItem' => ['2']]]]), 'items.0.tipoItem', 'El campo tipo de ítem debe ser un número entero.'],
    'item type supported' => [dteValidationPayload(['items' => [['tipoItem' => 5]]]), 'items.0.tipoItem', 'El valor seleccionado para tipo de ítem no es válido.'],
    'item code must be string' => [dteValidationPayload(['items' => [['codigo' => ['GPS']]]]), 'items.0.codigo', 'El campo código del ítem debe ser una cadena de caracteres.'],
    'item unit must be integer' => [dteValidationPayload(['items' => [['uniMedida' => 'x']]]), 'items.0.uniMedida', 'El campo unidad de medida del ítem debe ser un número entero.'],
]);
