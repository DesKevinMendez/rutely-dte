<?php

use Database\Seeders\EconomicActivitySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(EconomicActivitySeeder::class);
});

test('economic activities return the exact collection response', function () {
    $expected = DB::table('economic_activities')
        ->select(['id', 'code', 'description'])
        ->orderBy('code')
        ->limit(10)
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.economic-activities.index'));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 774,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 78,
                'from' => 1,
                'to' => 10,
            ],
        ]);
});

test('economic activities can be filtered by description', function () {
    $expected = DB::table('economic_activities')
        ->select(['id', 'code', 'description'])
        ->where('description', 'like', '%Programación Informática%')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.economic-activities.index', [
        'filter' => ['description' => 'Programación Informática'],
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => count($expected),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => count($expected),
            ],
        ]);
});
