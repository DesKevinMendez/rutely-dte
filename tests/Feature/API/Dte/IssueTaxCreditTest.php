<?php

use App\Models\Dte;
use App\Models\Receivers;
use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;
use Tests\Support\MhSchema;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});

function taxCreditReceiver(array $overrides): array
{
    $payload = DteApiTestData::validTaxCreditPayload();
    $payload['receptor'] = array_merge($payload['receptor'], $overrides);

    return $payload;
}

function taxCreditWithoutReceiverField(string $field): array
{
    $payload = DteApiTestData::validTaxCreditPayload();
    unset($payload['receptor'][$field]);

    return $payload;
}

test('issues a tax credit adding IVA on top of the prices', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));
    $payload = DteApiTestData::validTaxCreditPayload();
    $payload['items'] = [
        ['descripcion' => 'Dispositivo GPS', 'cantidad' => 2, 'precioUni' => 90, 'tipoItem' => 1, 'codigo' => 'GPS-PRO', 'uniMedida' => 59],
        ['descripcion' => 'Monitoreo premium', 'cantidad' => 1, 'precioUni' => 20],
    ];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    $response->assertExactJson(['data' => [
        'record' => $dte->toArray(),
        'mh_result' => DteApiTestData::mhResult(),
    ]]);

    $document = signedDocument($dte->signed_json);

    expect(MhSchema::errors($document, MhSchema::TAX_CREDIT))->toBe([])
        ->and($dte->version)->toBe('4')
        ->and($dte->dte_type)->toBe('03')
        ->and($dte->total_amount)->toBe(22600)
        ->and($dte->receiver_document)->toBe('06141505921015')
        ->and($document['receptor'])->toBe($payload['receptor'])
        ->and($document['cuerpoDocumento'][0]['ventaGravada'])->toEqual(180)
        ->and($document['cuerpoDocumento'][0]['tributos'])->toBe(['20'])
        ->and($document['cuerpoDocumento'][0])->not->toHaveKey('ivaItem')
        ->and($document['cuerpoDocumento'][1]['codigo'])->toBeNull()
        ->and($document['resumen']['totalGravada'])->toEqual(200)
        ->and($document['resumen']['tributos'])->toEqual([[
            'codigo' => '20',
            'descripcion' => 'Impuesto al Valor Agregado 13%',
            'valor' => 26,
        ]])
        ->and($document['resumen']['montoTotalOperacion'])->toEqual(226)
        ->and($document['resumen']['totalPagar'])->toEqual(226)
        ->and($document['resumen'])->not->toHaveKey('totalIva');

    $this->assertDatabaseHas(Receivers::class, [
        'id' => $dte->receiver_id,
        'company_id' => $company->id,
        'document_type' => '36',
        'document_number' => '06141505921015',
        'district_id' => $company->district_id,
    ]);
});

test('tax credit adds perceived IVA and subtracts withheld IVA from the total', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $payload = DteApiTestData::validTaxCreditPayload();
    $payload['items'] = [['descripcion' => 'Servicio', 'cantidad' => 1, 'precioUni' => 100]];
    $payload['resumen'] = ['ivaPerci' => 1, 'ivaRete' => 1.5];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));
    $summary = $dte->original_json['resumen'];

    expect(MhSchema::errors(signedDocument($dte->signed_json), MhSchema::TAX_CREDIT))->toBe([])
        ->and($summary['montoTotalOperacion'])->toEqual(113)
        ->and($summary['ivaPerci'])->toEqual(1)
        ->and($summary['ivaRete'])->toEqual(1.5)
        ->and($summary['totalPagar'])->toEqual(112.5)
        ->and($dte->total_amount)->toBe(11250);
});

test('a tax credit line without value does not declare IVA', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $payload = DteApiTestData::validTaxCreditPayload();
    $payload['items'] = [['descripcion' => 'Bonificación', 'cantidad' => 1, 'precioUni' => 0]];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $document = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json);

    expect(MhSchema::errors($document, MhSchema::TAX_CREDIT))->toBe([])
        ->and($document['cuerpoDocumento'][0]['tributos'])->toBeNull()
        ->and($document['resumen']['tributos'])->toBeNull();
});

test('a tax credit sends the receiver NIT and NRC without hyphens', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), taxCreditReceiver([
        'nit' => '0614-150592-101-5',
        'nrc' => '987654-3',
    ]))->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));
    $receiver = signedDocument($dte->signed_json)['receptor'];

    expect($dte->receiver_document)->toBe('06141505921015')
        ->and($receiver['nit'])->toBe('06141505921015')
        ->and($receiver['nrc'])->toBe('9876543')
        ->and(Receivers::query()->firstOrFail()->nrc)->toBe('9876543');
});

test('tax credit validation returns 422 with the official limits', function (array $payload, string $field, string $message) {
    Sanctum::actingAs(DteApiTestData::user(DteApiTestData::company(), Role::USER));

    $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'receiver required' => [['tipoDte' => '03', 'items' => DteApiTestData::validDtePayload()['items']], 'receptor', 'El campo receptor es obligatorio.'],
    'receiver must be array' => [['tipoDte' => '03', 'receptor' => 'x', 'items' => DteApiTestData::validDtePayload()['items']], 'receptor', 'El campo receptor debe ser un arreglo.'],
    'document type prohibited' => [taxCreditReceiver(['tipoDocumento' => '36']), 'receptor.tipoDocumento', 'El campo tipo de documento del receptor está prohibido.'],
    'document number prohibited' => [taxCreditReceiver(['numDocumento' => '06141505921015']), 'receptor.numDocumento', 'El campo receptor.num documento está prohibido.'],
    'NIT required' => [taxCreditWithoutReceiverField('nit'), 'receptor.nit', 'El campo NIT del receptor es obligatorio.'],
    'NIT must be string' => [taxCreditReceiver(['nit' => ['0614']]), 'receptor.nit', 'El campo NIT del receptor debe ser una cadena de caracteres.'],
    'NIT format' => [taxCreditReceiver(['nit' => '0614-150592']), 'receptor.nit', 'El formato del campo NIT del receptor no es válido.'],
    'NRC required' => [taxCreditWithoutReceiverField('nrc'), 'receptor.nrc', 'El campo receptor.nrc es obligatorio.'],
    'NRC digits only' => [taxCreditReceiver(['nrc' => '98765A']), 'receptor.nrc', 'El formato del campo receptor.nrc no es válido.'],
    'NRC minimum' => [taxCreditReceiver(['nrc' => '1']), 'receptor.nrc', 'El campo receptor.nrc debe contener al menos 2 caracteres.'],
    'NRC maximum' => [taxCreditReceiver(['nrc' => '123456789']), 'receptor.nrc', 'El campo receptor.nrc no debe contener más de 8 caracteres.'],
    'name required' => [taxCreditWithoutReceiverField('nombre'), 'receptor.nombre', 'El campo receptor.nombre es obligatorio.'],
    'name maximum' => [taxCreditReceiver(['nombre' => str_repeat('a', 251)]), 'receptor.nombre', 'El campo receptor.nombre no debe contener más de 250 caracteres.'],
    'activity code required' => [taxCreditWithoutReceiverField('codActividad'), 'receptor.codActividad', 'El campo receptor.cod actividad es obligatorio.'],
    'activity code in catalog' => [taxCreditReceiver(['codActividad' => '99999']), 'receptor.codActividad', 'El valor seleccionado para receptor.cod actividad no es válido.'],
    'activity description required' => [taxCreditWithoutReceiverField('descActividad'), 'receptor.descActividad', 'El campo receptor.desc actividad es obligatorio.'],
    'activity description minimum' => [taxCreditReceiver(['descActividad' => 'Abcd']), 'receptor.descActividad', 'El campo receptor.desc actividad debe contener al menos 5 caracteres.'],
    'activity description maximum' => [taxCreditReceiver(['descActividad' => str_repeat('a', 151)]), 'receptor.descActividad', 'El campo receptor.desc actividad no debe contener más de 150 caracteres.'],
    'commercial name must be string' => [taxCreditReceiver(['nombreComercial' => ['x']]), 'receptor.nombreComercial', 'El campo nombre comercial del receptor debe ser una cadena de caracteres.'],
    'commercial name maximum' => [taxCreditReceiver(['nombreComercial' => str_repeat('a', 151)]), 'receptor.nombreComercial', 'El campo nombre comercial del receptor no debe contener más de 150 caracteres.'],
    'address required' => [taxCreditWithoutReceiverField('direccion'), 'receptor.direccion', 'El campo receptor.direccion es obligatorio.'],
    'phone minimum' => [taxCreditReceiver(['telefono' => '1234567']), 'receptor.telefono', 'El campo receptor.telefono debe contener al menos 8 caracteres.'],
    'email maximum' => [taxCreditReceiver(['correo' => str_repeat('a', 92).'@mail.com']), 'receptor.correo', 'El campo correo del receptor no debe contener más de 100 caracteres.'],
    'other taxes per item not available' => [[...DteApiTestData::validTaxCreditPayload(), 'items' => [['descripcion' => 'Línea', 'cantidad' => 1, 'precioUni' => 1, 'tipoItem' => 4]]], 'items.0.tipoItem', 'El tipo de ítem 4 (otros tributos por ítem) todavía no se puede emitir.'],
    'nothing to pay on credit' => [[...DteApiTestData::validTaxCreditPayload(), 'items' => [['descripcion' => 'Línea', 'cantidad' => 1, 'precioUni' => 0]], 'resumen' => ['condicionOperacion' => 2, 'pagos' => [['codigo' => '01', 'montoPago' => 0, 'plazo' => '01', 'periodo' => 1]]]], 'resumen.condicionOperacion', 'La condición de la operación debe ser 1 (Contado) cuando el total a pagar es $0.00.'],
    'income tax prohibited' => [[...DteApiTestData::validTaxCreditPayload(), 'resumen' => ['reteRenta' => 1]], 'resumen.reteRenta', 'El campo retención de renta está prohibido.'],
    'perceived IVA numeric' => [[...DteApiTestData::validTaxCreditPayload(), 'resumen' => ['ivaPerci' => 'x']], 'resumen.ivaPerci', 'El campo IVA percibido debe ser un número.'],
    'perceived IVA nonnegative' => [[...DteApiTestData::validTaxCreditPayload(), 'resumen' => ['ivaPerci' => -1]], 'resumen.ivaPerci', 'El campo IVA percibido debe ser mayor o igual que 0.'],
    'perceived IVA limit' => [[...DteApiTestData::validTaxCreditPayload(), 'resumen' => ['ivaPerci' => 100000000000]], 'resumen.ivaPerci', 'El campo IVA percibido debe ser menor que 100000000000.'],
    'perceived IVA decimals' => [[...DteApiTestData::validTaxCreditPayload(), 'resumen' => ['ivaPerci' => 1.234]], 'resumen.ivaPerci', 'El campo IVA percibido debe tener 0-2 decimales.'],
]);
