<?php

use Database\Seeders\DepartamentSeeder;
use Database\Seeders\DistrictSeeder;
use Database\Seeders\MunicipalitiesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([
        DepartamentSeeder::class,
        MunicipalitiesSeeder::class,
        DistrictSeeder::class,
    ]);
});

test('districts return the exact collection response', function () {
    $expected = DB::table('districts')
        ->select([
            'id',
            DB::raw('departament_id as department_id'),
            'municipality_id',
            'code',
            'name',
        ])
        ->orderBy('name')
        ->orderBy('code')
        ->limit(10)
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.districts.index'));

    $response->assertStatus(200)
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 262,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 27,
                'from' => 1,
                'to' => 10,
            ],
        ]);
});

test('districts can be filtered by municipality id', function () {
    $municipalityId = DB::table('municipalities')
        ->where('departament_code', '06')
        ->where('code', '23')
        ->value('id');

    $expected = DB::table('districts')
        ->select([
            'id',
            DB::raw('departament_id as department_id'),
            'municipality_id',
            'code',
            'name',
        ])
        ->where('municipality_id', $municipalityId)
        ->orderBy('name')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.districts.index', [
        'filter' => ['municipality_id' => $municipalityId],
    ]));

    $response->assertStatus(200)
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
