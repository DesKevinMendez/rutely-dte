<?php

use Database\Seeders\DepartamentSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DepartamentSeeder::class);
});

test('departments can be listed without authentication', function () {
    $expected = DB::table('departaments')
        ->select(['id', 'code', 'name'])
        ->orderBy('code')
        ->limit(10)
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.departments.index'));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 14,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 2,
                'from' => 1,
                'to' => 10,
            ],
        ]);
});

test('departments can be filtered by name', function () {
    $expected = DB::table('departaments')
        ->select(['id', 'code', 'name'])
        ->where('name', 'like', '%San Salvador%')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.departments.index', [
        'filter' => ['name' => 'San Salvador'],
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
