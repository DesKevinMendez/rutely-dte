<?php

use App\Models\Dte;
use App\Models\Receivers;
use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;
use Tests\Support\MhSchema;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
    $this->seed(PaymentMethodSeeder::class);
});

function excludedSubjectReceiver(array $overrides): array
{
    $payload = DteApiTestData::validExcludedSubjectPayload();
    $payload['receptor'] = array_merge($payload['receptor'], $overrides);

    return $payload;
}

function excludedSubjectWithoutReceiverField(string $field): array
{
    $payload = DteApiTestData::validExcludedSubjectPayload();
    unset($payload['receptor'][$field]);

    return $payload;
}

test('issues an excluded subject invoice without IVA and with the income tax withheld', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));
    $payload = DteApiTestData::validExcludedSubjectPayload();
    $payload['items'] = [['descripcion' => 'Servicio de jardinería', 'cantidad' => 2, 'precioUni' => 50, 'montoDescu' => 0]];
    $payload['resumen'] = ['reteRenta' => 10];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    $response->assertExactJson(['data' => [
        'record' => $dte->toArray(),
        'mh_result' => DteApiTestData::mhResult(),
    ]]);

    $document = signedDocument($dte->signed_json);

    expect(MhSchema::errors($document, MhSchema::EXCLUDED_SUBJECT))->toBe([])
        ->and($dte->version)->toBe('2')
        ->and($dte->dte_type)->toBe('14')
        ->and($dte->total_amount)->toBe(9000)
        ->and($dte->receiver_document)->toBe('012345678')
        ->and($document)->not->toHaveKey('documentoRelacionado')
        ->and($document['emisor'])->not->toHaveKey('nombreComercial')
        ->and($document['receptor'])->toBe($payload['receptor'])
        ->and($document['cuerpoDocumento'][0]['compra'])->toEqual(100)
        ->and($document['cuerpoDocumento'][0])->not->toHaveKey('ventaGravada')
        ->and($document['resumen']['totalCompra'])->toEqual(100)
        ->and($document['resumen']['subTotal'])->toEqual(100)
        ->and($document['resumen']['reteRenta'])->toEqual(10)
        ->and($document['resumen']['totalPagar'])->toEqual(90)
        ->and($document['resumen']['pagos'][0]['montoPago'])->toEqual(90)
        ->and($document['resumen'])->not->toHaveKey('totalIva');

    $this->assertDatabaseHas(Receivers::class, [
        'id' => $dte->receiver_id,
        'company_id' => $company->id,
        'document_type' => '13',
        'document_number' => '012345678',
    ]);
});

test('excluded subject invoice does not withhold income tax unless the client sends it', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $payload = DteApiTestData::validExcludedSubjectPayload();
    $payload['items'] = [['descripcion' => 'Compra', 'cantidad' => 1, 'precioUni' => 25]];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $document = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json);

    expect(MhSchema::errors($document, MhSchema::EXCLUDED_SUBJECT))->toBe([])
        ->and($document['resumen']['reteRenta'])->toEqual(0)
        ->and($document['resumen']['totalPagar'])->toEqual(25);
});

test('excluded subject invoice applies the global discount before withholding income tax', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $payload = DteApiTestData::validExcludedSubjectPayload();
    $payload['items'] = [['descripcion' => 'Compra', 'cantidad' => 2, 'precioUni' => 50, 'montoDescu' => 4.50]];
    $payload['resumen'] = ['descu' => 5.25, 'reteRenta' => 9.03];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));
    $document = signedDocument($dte->signed_json);

    expect(MhSchema::errors($document, MhSchema::EXCLUDED_SUBJECT))->toBe([])
        ->and($dte->total_amount)->toBe(8122)
        ->and($document['cuerpoDocumento'][0]['compra'])->toEqual(95.50)
        ->and($document['resumen']['totalCompra'])->toEqual(95.50)
        ->and($document['resumen']['descu'])->toEqual(5.25)
        ->and($document['resumen']['totalDescu'])->toEqual(9.75)
        ->and($document['resumen']['subTotal'])->toEqual(90.25)
        ->and($document['resumen']['totalPagar'])->toEqual(81.22)
        ->and($document['resumen']['pagos'][0]['montoPago'])->toEqual(81.22);
});

test('transmits a credit purchase with zero payment and the agreed term', function () {
    $company = companyWithSigningCertificate();
    createSandboxCredentials($company);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));
    Http::preventStrayRequests();
    fakeMhResponse('recepciondte', DteApiTestData::mhResult());

    $payload = DteApiTestData::validExcludedSubjectPayload();
    $payload['resumen'] = [
        'condicionOperacion' => 2,
        'pagos' => [['montoPago' => 0, 'plazo' => '02', 'periodo' => '6']],
    ];

    $response = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));
    $document = signedDocument($dte->signed_json);

    expect(MhSchema::errors($document, MhSchema::EXCLUDED_SUBJECT))->toBe([])
        ->and($dte->status)->toBe('PROCESADO')
        ->and($dte->total_amount)->toBe(1000)
        ->and($document['resumen']['condicionOperacion'])->toBe(2)
        ->and($document['resumen']['pagos'])->toBe([[
            'codigo' => null,
            'montoPago' => 0.0,
            'referencia' => null,
            'plazo' => '02',
            'periodo' => 6.0,
        ]]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte'
        && $request->data() === [
            'idEnvio' => 1,
            'ambiente' => '00',
            'version' => 2,
            'tipoDte' => '14',
            'documento' => $dte->signed_json,
            'codigoGeneracion' => $dte->generation_code,
        ]);
});

test('an excluded subject invoice sends the DUI without its hyphen', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), excludedSubjectReceiver(['numDocumento' => '01234567-8']))->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    expect($dte->receiver_document)->toBe('012345678')
        ->and(signedDocument($dte->signed_json)['receptor']['numDocumento'])->toBe('012345678');
});

test('excluded subject validation returns 422 with the official limits', function (array $payload, string $field, string $message) {
    Sanctum::actingAs(DteApiTestData::user(DteApiTestData::company(), Role::USER));

    $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'receiver required' => [['tipoDte' => '14', 'items' => DteApiTestData::validDtePayload()['items']], 'receptor', 'El campo receptor es obligatorio.'],
    'receiver must be array' => [['tipoDte' => '14', 'receptor' => 'x', 'items' => DteApiTestData::validDtePayload()['items']], 'receptor', 'El campo receptor debe ser un arreglo.'],
    'NIT prohibited' => [excludedSubjectReceiver(['nit' => '06141505921015']), 'receptor.nit', 'El campo NIT del receptor está prohibido.'],
    'NRC prohibited' => [excludedSubjectReceiver(['nrc' => '123456']), 'receptor.nrc', 'El campo receptor.nrc está prohibido.'],
    'commercial name prohibited' => [excludedSubjectReceiver(['nombreComercial' => 'Comercial']), 'receptor.nombreComercial', 'El campo nombre comercial del receptor está prohibido.'],
    'document type required' => [excludedSubjectWithoutReceiverField('tipoDocumento'), 'receptor.tipoDocumento', 'El campo tipo de documento del receptor es obligatorio.'],
    'document type must be string' => [excludedSubjectReceiver(['tipoDocumento' => ['13']]), 'receptor.tipoDocumento', 'El campo tipo de documento del receptor debe ser una cadena de caracteres.'],
    'document type in catalog' => [excludedSubjectReceiver(['tipoDocumento' => '99']), 'receptor.tipoDocumento', 'El valor seleccionado para tipo de documento del receptor no es válido.'],
    'document number required' => [excludedSubjectWithoutReceiverField('numDocumento'), 'receptor.numDocumento', 'El campo receptor.num documento es obligatorio.'],
    'document number maximum' => [excludedSubjectReceiver(['tipoDocumento' => '03', 'numDocumento' => str_repeat('1', 21)]), 'receptor.numDocumento', 'El campo receptor.num documento no debe contener más de 20 caracteres.'],
    'DUI with 9 digits' => [excludedSubjectReceiver(['numDocumento' => '0123456-7']), 'receptor.numDocumento', 'El formato del campo receptor.num documento no es válido.'],
    'NIT with 9 or 14 digits' => [excludedSubjectReceiver(['tipoDocumento' => '36', 'numDocumento' => '0614-150592']), 'receptor.numDocumento', 'El formato del campo receptor.num documento no es válido.'],
    'name required' => [excludedSubjectWithoutReceiverField('nombre'), 'receptor.nombre', 'El campo receptor.nombre es obligatorio.'],
    'name maximum' => [excludedSubjectReceiver(['nombre' => str_repeat('a', 251)]), 'receptor.nombre', 'El campo receptor.nombre no debe contener más de 250 caracteres.'],
    'activity code in catalog' => [excludedSubjectReceiver(['codActividad' => '99999']), 'receptor.codActividad', 'El valor seleccionado para receptor.cod actividad no es válido.'],
    'activity description minimum' => [excludedSubjectReceiver(['descActividad' => 'Abcd']), 'receptor.descActividad', 'El campo receptor.desc actividad debe contener al menos 5 caracteres.'],
    'activity description maximum' => [excludedSubjectReceiver(['descActividad' => str_repeat('a', 151)]), 'receptor.descActividad', 'El campo receptor.desc actividad no debe contener más de 150 caracteres.'],
    'address required' => [excludedSubjectWithoutReceiverField('direccion'), 'receptor.direccion', 'El campo receptor.direccion es obligatorio.'],
    'phone minimum' => [excludedSubjectReceiver(['telefono' => '1234567']), 'receptor.telefono', 'El campo receptor.telefono debe contener al menos 8 caracteres.'],
    'email needs a top level domain' => [excludedSubjectReceiver(['correo' => 'usuario@dominio']), 'receptor.correo', 'El campo correo del receptor debe ser una dirección de correo electrónico válida.'],
    'email minimum' => [excludedSubjectReceiver(['correo' => 'a@b.c']), 'receptor.correo', 'El campo correo del receptor debe contener al menos 6 caracteres.'],
    'other taxes per item not available' => [[...DteApiTestData::validExcludedSubjectPayload(), 'items' => [['descripcion' => 'Línea', 'cantidad' => 1, 'precioUni' => 1, 'tipoItem' => 4]]], 'items.0.tipoItem', 'El tipo de ítem 4 (otros tributos por ítem) todavía no se puede emitir.'],
    'nothing to pay on credit' => [[...DteApiTestData::validExcludedSubjectPayload(), 'items' => [['descripcion' => 'Línea', 'cantidad' => 1, 'precioUni' => 0]], 'resumen' => ['condicionOperacion' => 2, 'pagos' => [['montoPago' => 0, 'plazo' => '01', 'periodo' => 1]]]], 'resumen.condicionOperacion', 'La condición de la operación debe ser 1 (Contado) cuando el total a pagar es $0.00.'],
    'payment code prohibited on credit' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['condicionOperacion' => 2, 'pagos' => [['codigo' => '01', 'montoPago' => 0, 'plazo' => '02', 'periodo' => 6]]]], 'resumen.pagos.0.codigo', 'El campo código de forma de pago está prohibido.'],
    'payment amount zero on credit' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['condicionOperacion' => 2, 'pagos' => [['montoPago' => 10, 'plazo' => '02', 'periodo' => 6]]]], 'resumen.pagos.0.montoPago', 'El campo monto de la forma de pago no debe ser mayor que 0.'],
    'payment reference prohibited on credit' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['condicionOperacion' => 2, 'pagos' => [['montoPago' => 0, 'referencia' => '123', 'plazo' => '02', 'periodo' => 6]]]], 'resumen.pagos.0.referencia', 'El campo referencia de la forma de pago está prohibido.'],
    'cash payment reference prohibited' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 10, 'referencia' => '123']]]], 'resumen.pagos.0.referencia', 'El campo referencia de la forma de pago está prohibido cuando código de forma de pago es 01.'],
    'global discount nonnegative' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['descu' => -1]], 'resumen.descu', 'El campo descuento global debe ser mayor o igual que 0.'],
    'global discount decimals' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['descu' => 1.234]], 'resumen.descu', 'El campo descuento global debe tener 0-2 decimales.'],
    'global discount above purchases' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['descu' => 10.01]], 'resumen.descu', 'El descuento global no puede ser mayor que el total de compras.'],
    'income tax above subtotal after global discount' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['descu' => 2, 'reteRenta' => 8.01]], 'resumen', 'Las retenciones no pueden ser mayores que el total de la operación.'],
    'withheld IVA prohibited' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['ivaRete' => 1]], 'resumen.ivaRete', 'El campo IVA retenido está prohibido.'],
    'perceived IVA prohibited' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['ivaPerci' => 1]], 'resumen.ivaPerci', 'El campo IVA percibido está prohibido.'],
    'income tax numeric' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['reteRenta' => 'x']], 'resumen.reteRenta', 'El campo retención de renta debe ser un número.'],
    'income tax nonnegative' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['reteRenta' => -1]], 'resumen.reteRenta', 'El campo retención de renta debe ser mayor o igual que 0.'],
    'income tax limit' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['reteRenta' => 100000000000]], 'resumen.reteRenta', 'El campo retención de renta debe ser menor que 100000000000.'],
    'income tax decimals' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['reteRenta' => 1.234]], 'resumen.reteRenta', 'El campo retención de renta debe tener 0-2 decimales.'],
    'income tax above total' => [[...DteApiTestData::validExcludedSubjectPayload(), 'resumen' => ['reteRenta' => 10.01]], 'resumen', 'Las retenciones no pueden ser mayores que el total de la operación.'],
]);
