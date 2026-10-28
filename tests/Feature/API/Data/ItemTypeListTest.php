<?php

use App\Models\ItemType;
use Database\Seeders\ItemTypeSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(ItemTypeSeeder::class);
});

test('item types return the exact collection response', function () {
    $expected = DB::table('item_types')
        ->select(['id', 'code', 'description'])
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    expect(array_column($expected, 'code'))->toBe([1, 2, 3, 4])
        ->and(array_column($expected, 'description'))->toBe([
            'Bienes',
            'Servicios',
            'Ambos (Bienes y Servicios, incluye los dos inherente a los Productos o servicios)',
            'Otros tributos por ítem',
        ]);

    $response = $this->getJson(route('api.v1.data.item-types.index'));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 4,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 4,
            ],
        ]);
});

test('item types can be filtered by code', function () {
    $expected = DB::table('item_types')
        ->select(['id', 'code', 'description'])
        ->where('code', 4)
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.item-types.index', [
        'filter' => ['code' => 4],
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

test('item types can be filtered by id', function () {
    $itemType = DB::table('item_types')
        ->select(['id', 'code', 'description'])
        ->where('code', 4)
        ->first();

    $response = $this->getJson(route('api.v1.data.item-types.index', [
        'filter' => ['id' => $itemType->id],
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => [(array) $itemType],
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

test('item types can be filtered by part of the description', function () {
    $expected = DB::table('item_types')
        ->select(['id', 'code', 'description'])
        ->where('description', 'like', '%Bienes y Servicios%')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.item-types.index', [
        'filter' => ['description' => 'Bienes y Servicios'],
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

test('item types accept up to one hundred items per page', function () {
    $response = $this->getJson(route('api.v1.data.item-types.index', [
        'per_page' => 100,
    ]));

    $response->assertOk()
        ->assertJsonPath('pagination.per_page', 100)
        ->assertJsonPath('pagination.total', 4);
});

test('item types reject unknown filters', function () {
    $response = $this->getJson(route('api.v1.data.item-types.index', [
        'filter' => ['unknown' => 'value'],
    ]));

    $response->assertBadRequest();
});

test('item type factories create valid persisted models', function () {
    DB::table('item_types')->delete();

    $itemType = ItemType::factory()->create();

    $this->assertSame(1, $itemType->code);
    $this->assertNotEmpty($itemType->description);
    $this->assertDatabaseHas('item_types', [
        'id' => $itemType->id,
        'code' => $itemType->code,
        'description' => $itemType->description,
    ]);
});

test('item type seeder is idempotent and updates catalog rows by code', function () {
    $originalRows = DB::table('item_types')->get()->keyBy('code');

    $this->seed(ItemTypeSeeder::class);

    expect(DB::table('item_types')->count())->toBe($originalRows->count());

    $reseededRows = DB::table('item_types')->get()->keyBy('code');

    foreach ($originalRows as $code => $originalRow) {
        expect($reseededRows->get($code)->id)->toBe($originalRow->id);
    }

    DB::table('item_types')->where('code', 1)->update([
        'description' => 'Descripción anterior',
        'updated_at' => '2000-01-01 00:00:00',
    ]);

    $this->seed(ItemTypeSeeder::class);

    $updatedRow = DB::table('item_types')->where('code', 1)->first();

    expect($updatedRow->id)->toBe($originalRows->get(1)->id)
        ->and($updatedRow->description)->toBe('Bienes')
        ->and($updatedRow->created_at)->toBe($originalRows->get(1)->created_at)
        ->and($updatedRow->updated_at)->not->toBe('2000-01-01 00:00:00');
});
