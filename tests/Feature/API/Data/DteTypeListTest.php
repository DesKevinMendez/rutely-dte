<?php

use App\Models\DteType;
use Database\Seeders\DteTypeSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DteTypeSeeder::class);
});

test('DTE types return the exact collection response', function () {
    $expected = DB::table('dte_types')
        ->select(['id', 'code', 'description'])
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    expect(array_column($expected, 'code'))->toBe(['01', '03', '04', '05', '06', '07', '08', '09', '11', '14', '15', '17', '18'])
        ->and(array_column($expected, 'description'))->toBe([
            'Factura',
            'Comprobante de crédito fiscal',
            'Nota de remisión',
            'Nota de crédito',
            'Nota de débito',
            'Comprobante de retención',
            'Comprobante de liquidación',
            'Documento contable de liquidación',
            'Facturas de exportación',
            'Factura de sujeto excluido',
            'Comprobante de donación',
            'Evento de Operaciones Especiales',
            'Evento de Retorno',
        ]);

    $response = $this->getJson(route('api.v1.data.dte-types.index', [
        'per_page' => 100,
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 13,
                'per_page' => 100,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 13,
            ],
        ]);
});

test('DTE types can be filtered by code', function () {
    $expected = DB::table('dte_types')
        ->select(['id', 'code', 'description'])
        ->where('code', '17')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.dte-types.index', [
        'filter' => ['code' => '17'],
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
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

test('DTE types can be filtered by id', function () {
    $dteType = DB::table('dte_types')
        ->select(['id', 'code', 'description'])
        ->where('code', '17')
        ->first();

    $response = $this->getJson(route('api.v1.data.dte-types.index', [
        'filter' => ['id' => $dteType->id],
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => [(array) $dteType],
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

test('DTE types can be filtered by part of the description', function () {
    $expected = DB::table('dte_types')
        ->select(['id', 'code', 'description'])
        ->where('description', 'like', '%Evento%')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.dte-types.index', [
        'filter' => ['description' => 'Evento'],
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 2,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 2,
            ],
        ]);
});

test('DTE types accept up to one hundred items per page', function () {
    $response = $this->getJson(route('api.v1.data.dte-types.index', [
        'per_page' => 100,
    ]));

    $response->assertOk()
        ->assertJsonPath('pagination.per_page', 100)
        ->assertJsonPath('pagination.total', 13);
});

test('DTE types reject unknown filters', function () {
    $response = $this->getJson(route('api.v1.data.dte-types.index', [
        'filter' => ['unknown' => 'value'],
    ]));

    $response->assertBadRequest();
});

test('DTE type factories create valid persisted models', function () {
    $dteType = DteType::factory()->create();

    $this->assertMatchesRegularExpression('/^\d{2}$/', $dteType->code);
    $this->assertNotEmpty($dteType->description);
    $this->assertDatabaseHas('dte_types', [
        'id' => $dteType->id,
        'code' => $dteType->code,
        'description' => $dteType->description,
    ]);
});
