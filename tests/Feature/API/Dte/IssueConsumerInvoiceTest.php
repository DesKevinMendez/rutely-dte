<?php

use App\Models\Dte;
use App\Models\Receivers;
use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;
use Tests\Support\MhSchema;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});

function consumerInvoicePayload(array $overrides = []): array
{
    return array_replace_recursive(DteApiTestData::validDtePayload(), $overrides);
}

function consumerInvoiceReceiver(array $overrides = []): array
{
    return consumerInvoicePayload(['receptor' => $overrides]);
}

test('issues an anonymous consumer invoice whose prices already include IVA', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), [
        'tipoDte' => '01',
        'items' => [['descripcion' => 'Servicio de monitoreo', 'cantidad' => 1, 'precioUni' => 100]],
    ])->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    $response->assertExactJson(['data' => [
        'record' => $dte->toArray(),
        'mh_result' => DteApiTestData::mhResult(),
    ]]);

    $document = signedDocument($dte->signed_json);

    expect(MhSchema::errors($document, MhSchema::CONSUMER_INVOICE))->toBe([])
        ->and($dte->company_id)->toBe($company->id)
        ->and($dte->version)->toBe('2')
        ->and($dte->status)->toBe('PROCESADO')
        ->and($dte->total_amount)->toBe(10000)
        ->and($dte->receiver_document)->toBeNull()
        ->and($dte->receiver_id)->toBeNull()
        ->and(Receivers::query()->count())->toBe(0)
        ->and($document['receptor'])->toBe([
            'tipoDocumento' => null,
            'numDocumento' => null,
            'nrc' => null,
            'nombre' => null,
            'codActividad' => null,
            'descActividad' => null,
            'direccion' => null,
            'telefono' => null,
            'correo' => null,
        ])
        ->and($document['cuerpoDocumento'][0]['ventaGravada'])->toEqual(100)
        ->and($document['cuerpoDocumento'][0]['ivaItem'])->toEqual(11.50442478)
        ->and($document['cuerpoDocumento'][0]['tributos'])->toBeNull()
        ->and($document['resumen']['tributos'])->toBeNull()
        ->and($document['resumen']['totalGravada'])->toEqual(100)
        ->and($document['resumen']['totalIva'])->toEqual(11.5)
        ->and($document['resumen']['montoTotalOperacion'])->toEqual(100)
        ->and($document['resumen']['totalPagar'])->toEqual(100)
        ->and($document['resumen']['totalLetras'])->toBe('CIEN 00/100 USD')
        ->and($document['resumen']['condicionOperacion'])->toBe(1)
        ->and($document['resumen']['pagos'])->toEqual([[
            'codigo' => '01',
            'montoPago' => 100,
            'referencia' => null,
            'plazo' => null,
            'periodo' => null,
        ]]);
});

test('issues a consumer invoice to an identified receiver and stores the receiver', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), consumerInvoicePayload([
        'receptor' => [
            'tipoDocumento' => '13',
            'numDocumento' => '012345678',
            'nrc' => '123456',
            'nombre' => 'María Pérez',
            'codActividad' => '62010',
            'descActividad' => 'Programación informática',
            'direccion' => DteApiTestData::receiverAddress(),
            'telefono' => '77778888',
            'correo' => 'maria@example.com',
        ],
    ]))->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));
    $receiver = Receivers::query()->firstOrFail();

    expect(MhSchema::errors(signedDocument($dte->signed_json), MhSchema::CONSUMER_INVOICE))->toBe([])
        ->and($dte->receiver_document)->toBe('012345678')
        ->and($dte->receiver_id)->toBe($receiver->id)
        ->and($dte->original_json['receptor']['direccion'])->toBe(DteApiTestData::receiverAddress());

    $this->assertDatabaseHas(Receivers::class, [
        'id' => $receiver->id,
        'company_id' => $company->id,
        'document_type' => '13',
        'document_number' => '012345678',
        'district_id' => $company->district_id,
    ]);
});

test('a consumer invoice sends and stores NIT and DUI without hyphens', function (string $type, string $written, string $sent) {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), consumerInvoiceReceiver([
        'tipoDocumento' => $type,
        'numDocumento' => $written,
    ]))->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    expect($dte->receiver_document)->toBe($sent)
        ->and(signedDocument($dte->signed_json)['receptor']['numDocumento'])->toBe($sent)
        ->and(Receivers::query()->firstOrFail()->document_number)->toBe($sent);
})->with([
    'DUI with its hyphen' => ['13', '01234567-8', '012345678'],
    'DUI in digits' => ['13', '012345678', '012345678'],
    'NIT with its hyphens' => ['36', '0614-150592-101-5', '06141505921015'],
    'NIT in digits' => ['36', '06141505921015', '06141505921015'],
    'NIT of 9 digits' => ['36', '01234567-8', '012345678'],
    'passport keeps its own format' => ['03', 'A-1234567', 'A-1234567'],
]);

test('a consumer invoice below $25,000 does not need to identify the receiver', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), [
        'tipoDte' => '01',
        'items' => [['descripcion' => 'Maquinaria', 'cantidad' => 1, 'precioUni' => 24999.99]],
    ])->assertCreated();

    expect(Dte::query()->findOrFail($response->json('data.record.id'))->receiver_document)->toBeNull();
});

test('a consumer invoice from $25,000 is issued when the receiver is identified', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), [
        'tipoDte' => '01',
        'receptor' => ['tipoDocumento' => '13', 'numDocumento' => '012345678', 'nombre' => 'María Pérez'],
        'items' => [['descripcion' => 'Maquinaria', 'cantidad' => 1, 'precioUni' => 25000]],
    ])->assertCreated();

    $document = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json);

    expect($document['receptor']['tipoDocumento'])->toBe('13')
        ->and($document['receptor']['numDocumento'])->toBe('012345678')
        ->and($document['receptor']['nombre'])->toBe('María Pérez');
});

test('a consumer invoice from $25,000 requires the receiver identification', function (array $receiver, string $field, string $message) {
    Sanctum::actingAs(DteApiTestData::user(DteApiTestData::company(), Role::USER));

    $this->postJson(route('api.v1.dtes.store'), [
        'tipoDte' => '01',
        'receptor' => $receiver,
        'items' => [['descripcion' => 'Maquinaria', 'cantidad' => 2, 'precioUni' => 12500]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'document type' => [[], 'receptor.tipoDocumento', 'El tipo de documento del receptor es obligatorio en operaciones de $25,000.00 o más.'],
    'document number' => [[], 'receptor.numDocumento', 'El número de documento del receptor es obligatorio en operaciones de $25,000.00 o más.'],
    'name' => [['tipoDocumento' => '13', 'numDocumento' => '012345678'], 'receptor.nombre', 'El nombre del receptor es obligatorio en operaciones de $25,000.00 o más.'],
]);

test('the $25,000 identification rule only applies to the consumer invoice', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $payload = DteApiTestData::validExcludedSubjectPayload();
    $payload['items'] = [['descripcion' => 'Maquinaria', 'cantidad' => 1, 'precioUni' => 25000]];

    $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();
});

test('a line without type or unit is a service with the unit of measure 99', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), [
        'tipoDte' => '01',
        'items' => [['descripcion' => 'Consultoría', 'cantidad' => 1, 'precioUni' => 10]],
    ])->assertCreated();

    $item = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json)['cuerpoDocumento'][0];

    expect($item['tipoItem'])->toBe(2)
        ->and($item['uniMedida'])->toBe(99);
});

test('goods and mixed lines keep the unit of measure sent by the client', function (int $itemType) {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), consumerInvoicePayload([
        'items' => [['tipoItem' => $itemType, 'uniMedida' => 59]],
    ]))->assertCreated();

    $item = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json)['cuerpoDocumento'][0];

    expect($item['tipoItem'])->toBe($itemType)
        ->and($item['uniMedida'])->toBe(59);
})->with([
    'goods' => [1],
    'goods and services' => [3],
]);

test('a consumer invoice sends the receiver NRC without its hyphen', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), consumerInvoiceReceiver(['nrc' => '987654-3']))->assertCreated();

    expect(signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json)['receptor']['nrc'])
        ->toBe('9876543');
});

test('consumer invoice applies the item discount and the withheld IVA', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), [
        'tipoDte' => '01',
        'items' => [['descripcion' => 'Cuadernos', 'cantidad' => 2, 'precioUni' => 25, 'montoDescu' => 5]],
        'resumen' => ['ivaRete' => 0.40, 'observaciones' => 'Pedido 15'],
    ])->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));
    $summary = $dte->original_json['resumen'];

    expect(MhSchema::errors(signedDocument($dte->signed_json), MhSchema::CONSUMER_INVOICE))->toBe([])
        ->and($dte->original_json['cuerpoDocumento'][0]['ventaGravada'])->toEqual(45)
        ->and($summary['totalDescu'])->toEqual(5)
        ->and($summary['totalIva'])->toEqual(5.18)
        ->and($summary['ivaRete'])->toEqual(0.4)
        ->and($summary['montoTotalOperacion'])->toEqual(45)
        ->and($summary['totalPagar'])->toEqual(44.6)
        ->and($summary['observaciones'])->toBe('Pedido 15')
        ->and($dte->total_amount)->toBe(4460);
});

test('consumer invoice on credit keeps the payment terms sent by the client', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), consumerInvoicePayload([
        'resumen' => [
            'condicionOperacion' => 2,
            'pagos' => [['codigo' => '01', 'montoPago' => 10, 'plazo' => '02', 'periodo' => 6]],
        ],
    ]))->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    expect(MhSchema::errors(signedDocument($dte->signed_json), MhSchema::CONSUMER_INVOICE))->toBe([])
        ->and($dte->original_json['resumen']['condicionOperacion'])->toBe(2)
        ->and($dte->original_json['resumen']['pagos'])->toEqual([[
            'codigo' => '01',
            'montoPago' => 10,
            'referencia' => null,
            'plazo' => '02',
            'periodo' => 6,
        ]]);
});

test('document types and points of sale share the consecutive of their establishment', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $controlNumber = fn (array $payload): string => $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertCreated()
        ->json('data.record.control_number');

    expect($controlNumber(DteApiTestData::validDtePayload()))->toBe('DTE-01-M001P001-000000000000001')
        ->and($controlNumber(DteApiTestData::validTaxCreditPayload()))->toBe('DTE-03-M001P001-000000000000002');

    $company->update(['mh_pos_code' => 'P002']);

    expect($controlNumber(DteApiTestData::validExcludedSubjectPayload()))->toBe('DTE-14-M001P002-000000000000003');
});

test('each establishment keeps its own consecutive', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $controlNumber = fn (): string => $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())
        ->assertCreated()
        ->json('data.record.control_number');

    expect($controlNumber())->toBe('DTE-01-M001P001-000000000000001');

    $company->update(['mh_establishment_code' => 'S002']);

    expect($controlNumber())->toBe('DTE-01-S002P001-000000000000001');
});

test('the consecutive restarts with each fiscal year in El Salvador', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));

    $controlNumber = fn (): string => $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())
        ->assertCreated()
        ->json('data.record.control_number');

    $this->travelTo(Carbon::parse('2026-12-31 23:59:00', 'America/El_Salvador'));

    expect($controlNumber())->toBe('DTE-01-M001P001-000000000000001')
        ->and($controlNumber())->toBe('DTE-01-M001P001-000000000000002');

    $this->travelTo(Carbon::parse('2027-01-01 00:00:00', 'America/El_Salvador'));

    expect($controlNumber())->toBe('DTE-01-M001P001-000000000000001');
});

test('control numbers follow the official structure', function () {
    $company = companyReadyToIssue();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $controlNumber = fn (array $payload): string => $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertCreated()
        ->json('data.record.control_number');

    expect($controlNumber(DteApiTestData::validDtePayload()))->toBe('DTE-01-M001P001-000000000000001')
        ->and($controlNumber(DteApiTestData::validDtePayload()))->toBe('DTE-01-M001P001-000000000000002')
        ->and($controlNumber(DteApiTestData::validTaxCreditPayload()))->toBe('DTE-03-M001P001-000000000000003')
        ->and($controlNumber(DteApiTestData::validExcludedSubjectPayload()))->toBe('DTE-14-M001P001-000000000000004');
});

test('the control number uses the codes assigned by Hacienda and the issuer keeps its own', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue([
        'mh_establishment_code' => 'S005',
        'mh_pos_code' => 'P007',
        'own_establishment_code' => 'INT1',
        'own_pos_code' => 'CAJ1',
    ]), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())->assertCreated();

    $document = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json);

    expect(MhSchema::errors($document, MhSchema::CONSUMER_INVOICE))->toBe([])
        ->and($document['identificacion']['numeroControl'])->toBe('DTE-01-S005P007-000000000000001')
        ->and($document['emisor']['codEstable'])->toBe('INT1')
        ->and($document['emisor']['codPuntoVenta'])->toBe('CAJ1');
});

test('a company API token issues the consumer invoice for its own company', function () {
    $company = companyReadyToIssue();
    $token = $company->createToken('External ERP', ['create:dte'])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())
        ->assertCreated();

    expect(Dte::query()->findOrFail($response->json('data.record.id'))->company_id)->toBe($company->id);
});

test('consumer invoice validation returns 422 with the official limits', function (array $payload, string $field, string $message) {
    Sanctum::actingAs(DteApiTestData::user(DteApiTestData::company(), Role::USER));

    $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'receiver NIT prohibited' => [consumerInvoiceReceiver(['nit' => '06141505921015']), 'receptor.nit', 'El campo NIT del receptor está prohibido.'],
    'receiver commercial name prohibited' => [consumerInvoiceReceiver(['nombreComercial' => 'Comercial']), 'receptor.nombreComercial', 'El campo nombre comercial del receptor está prohibido.'],
    'document type required with number' => [consumerInvoiceReceiver(['numDocumento' => '012345678']), 'receptor.tipoDocumento', 'El campo tipo de documento del receptor es obligatorio cuando receptor.num documento está presente.'],
    'document number required with type' => [consumerInvoiceReceiver(['tipoDocumento' => '13']), 'receptor.numDocumento', 'El campo receptor.num documento es obligatorio cuando tipo de documento del receptor está presente.'],
    'document number maximum' => [consumerInvoiceReceiver(['tipoDocumento' => '03', 'numDocumento' => str_repeat('1', 21)]), 'receptor.numDocumento', 'El campo receptor.num documento no debe contener más de 20 caracteres.'],
    'DUI with 9 digits' => [consumerInvoiceReceiver(['tipoDocumento' => '13', 'numDocumento' => '0123456-7']), 'receptor.numDocumento', 'El formato del campo receptor.num documento no es válido.'],
    'DUI with digits only' => [consumerInvoiceReceiver(['tipoDocumento' => '13', 'numDocumento' => '0123456A8']), 'receptor.numDocumento', 'El formato del campo receptor.num documento no es válido.'],
    'NIT with 9 or 14 digits' => [consumerInvoiceReceiver(['tipoDocumento' => '36', 'numDocumento' => '0614-150592']), 'receptor.numDocumento', 'El formato del campo receptor.num documento no es válido.'],
    'NRC digits only' => [consumerInvoiceReceiver(['nrc' => '98765A']), 'receptor.nrc', 'El formato del campo receptor.nrc no es válido.'],
    'NRC minimum' => [consumerInvoiceReceiver(['nrc' => '1']), 'receptor.nrc', 'El campo receptor.nrc debe contener al menos 2 caracteres.'],
    'NRC maximum' => [consumerInvoiceReceiver(['nrc' => '123456789']), 'receptor.nrc', 'El campo receptor.nrc no debe contener más de 8 caracteres.'],
    'name maximum' => [consumerInvoiceReceiver(['nombre' => str_repeat('a', 251)]), 'receptor.nombre', 'El campo receptor.nombre no debe contener más de 250 caracteres.'],
    'activity code in catalog' => [consumerInvoiceReceiver(['codActividad' => '99999']), 'receptor.codActividad', 'El valor seleccionado para receptor.cod actividad no es válido.'],
    'activity description minimum' => [consumerInvoiceReceiver(['descActividad' => 'Abcd']), 'receptor.descActividad', 'El campo receptor.desc actividad debe contener al menos 5 caracteres.'],
    'activity description maximum' => [consumerInvoiceReceiver(['descActividad' => str_repeat('a', 151)]), 'receptor.descActividad', 'El campo receptor.desc actividad no debe contener más de 150 caracteres.'],
    'phone minimum' => [consumerInvoiceReceiver(['telefono' => '1234567']), 'receptor.telefono', 'El campo receptor.telefono debe contener al menos 8 caracteres.'],
    'phone maximum' => [consumerInvoiceReceiver(['telefono' => str_repeat('1', 31)]), 'receptor.telefono', 'El campo receptor.telefono no debe contener más de 30 caracteres.'],
    'email minimum' => [consumerInvoiceReceiver(['correo' => 'a@b.c']), 'receptor.correo', 'El campo correo del receptor debe contener al menos 6 caracteres.'],
    'email maximum' => [consumerInvoiceReceiver(['correo' => str_repeat('a', 92).'@mail.com']), 'receptor.correo', 'El campo correo del receptor no debe contener más de 100 caracteres.'],
    'department in catalog' => [consumerInvoiceReceiver(['direccion' => [...DteApiTestData::receiverAddress(), 'departamento' => '99']]), 'receptor.direccion.departamento', 'El valor seleccionado para departamento del receptor no es válido.'],
    'municipality in catalog' => [consumerInvoiceReceiver(['direccion' => [...DteApiTestData::receiverAddress(), 'municipio' => '99']]), 'receptor.direccion.municipio', 'El valor seleccionado para municipio del receptor no es válido.'],
    'district required' => [consumerInvoiceReceiver(['direccion' => ['departamento' => '06', 'municipio' => '01', 'complemento' => 'Calle 1']]), 'receptor.direccion.distrito', 'El campo distrito del receptor es obligatorio cuando receptor.direccion está presente.'],
    'district must be string' => [consumerInvoiceReceiver(['direccion' => [...DteApiTestData::receiverAddress(), 'distrito' => ['01']]]), 'receptor.direccion.distrito', 'El campo distrito del receptor debe ser una cadena de caracteres.'],
    'district size' => [consumerInvoiceReceiver(['direccion' => [...DteApiTestData::receiverAddress(), 'distrito' => '1']]), 'receptor.direccion.distrito', 'El campo distrito del receptor debe contener 2 caracteres.'],
    'district in catalog' => [consumerInvoiceReceiver(['direccion' => [...DteApiTestData::receiverAddress(), 'distrito' => '99']]), 'receptor.direccion.distrito', 'El valor seleccionado para distrito del receptor no es válido.'],
    'item description maximum' => [consumerInvoicePayload(['items' => [['descripcion' => str_repeat('a', 1501)]]]), 'items.0.descripcion', 'El campo descripción del ítem no debe contener más de 1500 caracteres.'],
    'item quantity limit' => [consumerInvoicePayload(['items' => [['cantidad' => 100000000000]]]), 'items.0.cantidad', 'El campo cantidad del ítem debe ser menor que 100000000000.'],
    'item quantity decimals' => [consumerInvoicePayload(['items' => [['cantidad' => 1.123456789]]]), 'items.0.cantidad', 'El campo cantidad del ítem debe tener 0-8 decimales.'],
    'item price limit' => [consumerInvoicePayload(['items' => [['precioUni' => 100000000000]]]), 'items.0.precioUni', 'El campo precio unitario del ítem debe ser menor que 100000000000.'],
    'item price decimals' => [consumerInvoicePayload(['items' => [['precioUni' => 1.123456789]]]), 'items.0.precioUni', 'El campo precio unitario del ítem debe tener 0-8 decimales.'],
    'item discount limit' => [consumerInvoicePayload(['items' => [['montoDescu' => 100000000000]]]), 'items.0.montoDescu', 'El campo monto de descuento del ítem debe ser menor que 100000000000.'],
    'item discount decimals' => [consumerInvoicePayload(['items' => [['montoDescu' => 1.123456789]]]), 'items.0.montoDescu', 'El campo monto de descuento del ítem debe tener 0-8 decimales.'],
    'item discount above line value' => [consumerInvoicePayload(['items' => [['montoDescu' => 10.01]]]), 'items.0.montoDescu', 'El monto de descuento del ítem no puede ser mayor que el valor de la línea.'],
    'item code maximum' => [consumerInvoicePayload(['items' => [['codigo' => str_repeat('a', 26)]]]), 'items.0.codigo', 'El campo código del ítem no debe contener más de 25 caracteres.'],
    'item unit in catalog' => [consumerInvoicePayload(['items' => [['uniMedida' => 3]]]), 'items.0.uniMedida', 'El valor seleccionado para unidad de medida del ítem no es válido.'],
    'service must use unit 99' => [consumerInvoicePayload(['items' => [['tipoItem' => 2, 'uniMedida' => 59]]]), 'items.0.uniMedida', 'Los servicios deben usar la unidad de medida 99 (Otra).'],
    'line without type is a service' => [['tipoDte' => '01', 'items' => [['descripcion' => 'Línea', 'cantidad' => 1, 'precioUni' => 10, 'uniMedida' => 59]]], 'items.0.uniMedida', 'Los servicios deben usar la unidad de medida 99 (Otra).'],
    'other taxes per item not available' => [consumerInvoicePayload(['items' => [['tipoItem' => 4]]]), 'items.0.tipoItem', 'El tipo de ítem 4 (otros tributos por ítem) todavía no se puede emitir.'],
    'summary must be array' => [consumerInvoicePayload(['resumen' => 'resumen']), 'resumen', 'El campo resumen debe ser un arreglo.'],
    'withheld IVA numeric' => [consumerInvoicePayload(['resumen' => ['ivaRete' => 'x']]), 'resumen.ivaRete', 'El campo IVA retenido debe ser un número.'],
    'withheld IVA nonnegative' => [consumerInvoicePayload(['resumen' => ['ivaRete' => -1]]), 'resumen.ivaRete', 'El campo IVA retenido debe ser mayor o igual que 0.'],
    'withheld IVA limit' => [consumerInvoicePayload(['resumen' => ['ivaRete' => 100000000000]]), 'resumen.ivaRete', 'El campo IVA retenido debe ser menor que 100000000000.'],
    'withheld IVA decimals' => [consumerInvoicePayload(['resumen' => ['ivaRete' => 1.234]]), 'resumen.ivaRete', 'El campo IVA retenido debe tener 0-2 decimales.'],
    'withheld IVA above total' => [consumerInvoicePayload(['resumen' => ['ivaRete' => 10.01]]), 'resumen', 'Las retenciones no pueden ser mayores que el total de la operación.'],
    'perceived IVA prohibited' => [consumerInvoicePayload(['resumen' => ['ivaPerci' => 1]]), 'resumen.ivaPerci', 'El campo IVA percibido está prohibido.'],
    'income tax prohibited' => [consumerInvoicePayload(['resumen' => ['reteRenta' => 1]]), 'resumen.reteRenta', 'El campo retención de renta está prohibido.'],
    'condition integer' => [consumerInvoicePayload(['resumen' => ['condicionOperacion' => 'x']]), 'resumen.condicionOperacion', 'El campo condición de la operación debe ser un número entero.'],
    'condition in catalog' => [consumerInvoicePayload(['resumen' => ['condicionOperacion' => 4]]), 'resumen.condicionOperacion', 'El valor seleccionado para condición de la operación no es válido.'],
    'payments required on credit' => [consumerInvoicePayload(['resumen' => ['condicionOperacion' => 2]]), 'resumen.pagos', 'El campo formas de pago es obligatorio cuando condición de la operación es 2.'],
    'payments required when mixed' => [consumerInvoicePayload(['resumen' => ['condicionOperacion' => 3]]), 'resumen.pagos', 'El campo formas de pago es obligatorio cuando condición de la operación es 3.'],
    'payments must be array' => [consumerInvoicePayload(['resumen' => ['pagos' => 'x']]), 'resumen.pagos', 'El campo formas de pago debe ser un arreglo.'],
    'payments minimum' => [consumerInvoicePayload(['resumen' => ['pagos' => []]]), 'resumen.pagos', 'El campo formas de pago debe contener al menos 1 elementos.'],
    'payments must add up to total' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 9.98]]]]), 'resumen.pagos', 'La suma de las formas de pago debe coincidir con el total a pagar.'],
    'payment code required' => [consumerInvoicePayload(['resumen' => ['pagos' => [['montoPago' => 10]]]]), 'resumen.pagos.0.codigo', 'El campo código de forma de pago es obligatorio.'],
    'payment code must be string' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => 1, 'montoPago' => 10]]]]), 'resumen.pagos.0.codigo', 'El campo código de forma de pago debe ser una cadena de caracteres.'],
    'payment code in catalog' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '06', 'montoPago' => 10]]]]), 'resumen.pagos.0.codigo', 'El valor seleccionado para código de forma de pago no es válido.'],
    'payment amount required' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01']]]]), 'resumen.pagos.0.montoPago', 'El campo monto de la forma de pago es obligatorio.'],
    'payment amount numeric' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 'x']]]]), 'resumen.pagos.0.montoPago', 'El campo monto de la forma de pago debe ser un número.'],
    'payment amount nonnegative' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => -1]]]]), 'resumen.pagos.0.montoPago', 'El campo monto de la forma de pago debe ser mayor o igual que 0.'],
    'payment amount limit' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 100000000000]]]]), 'resumen.pagos.0.montoPago', 'El campo monto de la forma de pago debe ser menor que 100000000000.'],
    'payment amount decimals' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 1.234]]]]), 'resumen.pagos.0.montoPago', 'El campo monto de la forma de pago debe tener 0-2 decimales.'],
    'payment reference must be string' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '02', 'montoPago' => 10, 'referencia' => ['x']]]]]), 'resumen.pagos.0.referencia', 'El campo referencia de la forma de pago debe ser una cadena de caracteres.'],
    'payment reference maximum' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '02', 'montoPago' => 10, 'referencia' => str_repeat('1', 51)]]]]), 'resumen.pagos.0.referencia', 'El campo referencia de la forma de pago no debe contener más de 50 caracteres.'],
    'payment term required on credit' => [consumerInvoicePayload(['resumen' => ['condicionOperacion' => 2, 'pagos' => [['codigo' => '01', 'montoPago' => 10, 'periodo' => 6]]]]), 'resumen.pagos.0.plazo', 'El campo plazo de la forma de pago es obligatorio cuando condición de la operación es 2.'],
    'payment term must be string' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 10, 'plazo' => 2]]]]), 'resumen.pagos.0.plazo', 'El campo plazo de la forma de pago debe ser una cadena de caracteres.'],
    'payment term in catalog' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 10, 'plazo' => '04']]]]), 'resumen.pagos.0.plazo', 'El valor seleccionado para plazo de la forma de pago no es válido.'],
    'payment period required on credit' => [consumerInvoicePayload(['resumen' => ['condicionOperacion' => 2, 'pagos' => [['codigo' => '01', 'montoPago' => 10, 'plazo' => '02']]]]), 'resumen.pagos.0.periodo', 'El campo período de la forma de pago es obligatorio cuando condición de la operación es 2.'],
    'payment period numeric' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 10, 'periodo' => 'x']]]]), 'resumen.pagos.0.periodo', 'El campo período de la forma de pago debe ser un número.'],
    'payment period greater than zero' => [consumerInvoicePayload(['resumen' => ['pagos' => [['codigo' => '01', 'montoPago' => 10, 'periodo' => 0]]]]), 'resumen.pagos.0.periodo', 'El campo período de la forma de pago debe ser mayor que 0.'],
    'observations must be string' => [consumerInvoicePayload(['resumen' => ['observaciones' => ['x']]]), 'resumen.observaciones', 'El campo observaciones debe ser una cadena de caracteres.'],
    'observations maximum' => [consumerInvoicePayload(['resumen' => ['observaciones' => str_repeat('a', 3001)]]), 'resumen.observaciones', 'El campo observaciones no debe contener más de 3000 caracteres.'],
]);
