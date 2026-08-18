<?php

use Database\Seeders\EstablishmentTypesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(EstablishmentTypesSeeder::class);
});

test('establishment types return the exact collection response', function () {
    $expected = DB::table('establishment_types')
        ->select(['id', 'code', 'description'])
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.establishment-types.index'));

    $response->assertStatus(200)
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

test('establishment types can be filtered by code', function () {
    $expected = DB::table('establishment_types')
        ->select(['id', 'code', 'description'])
        ->where('code', '02')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.establishment-types.index', [
        'filter' => ['code' => '02'],
    ]));

    $response->assertStatus(200)
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
