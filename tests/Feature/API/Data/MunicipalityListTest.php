<?php

use Database\Seeders\DepartamentSeeder;
use Database\Seeders\MunicipalitiesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([
        DepartamentSeeder::class,
        MunicipalitiesSeeder::class,
    ]);
});

test('municipalities return the exact collection response', function () {
    $expected = DB::table('municipalities')
        ->select([
            'id',
            DB::raw('departament_id as department_id'),
            DB::raw('departament_code as department_code'),
            'code',
            'name',
        ])
        ->orderBy('departament_code')
        ->orderBy('code')
        ->limit(10)
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.municipalities.index'));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 44,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 5,
                'from' => 1,
                'to' => 10,
            ],
        ]);
});

test('municipalities can be filtered by department code', function () {
    $expected = DB::table('municipalities')
        ->select([
            'id',
            DB::raw('departament_id as department_id'),
            DB::raw('departament_code as department_code'),
            'code',
            'name',
        ])
        ->where('departament_code', '06')
        ->orderBy('departament_code')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.municipalities.index', [
        'filter' => ['department_code' => '06'],
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
