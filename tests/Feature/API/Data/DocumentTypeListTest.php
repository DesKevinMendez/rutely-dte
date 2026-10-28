<?php

use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
});

test('document types return the exact collection response', function () {
    $expected = DB::table('document_types')
        ->select(['id', 'code', 'description'])
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    expect(array_column($expected, 'code'))->toBe(['03', '13', '36', '37'])
        ->and(array_column($expected, 'description'))->toBe(['Pasaporte', 'DUI', 'NIT', 'Otro']);

    $response = $this->getJson(route('api.v1.data.document-types.index'));

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

test('document types can be filtered by code', function () {
    $expected = DB::table('document_types')
        ->select(['id', 'code', 'description'])
        ->where('code', '36')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.document-types.index', [
        'filter' => ['code' => '36'],
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

test('document types can be filtered by part of the description', function () {
    $expected = DB::table('document_types')
        ->select(['id', 'code', 'description'])
        ->where('description', 'like', '%sapo%')
        ->orderBy('code')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $response = $this->getJson(route('api.v1.data.document-types.index', [
        'filter' => ['description' => 'sapo'],
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

test('document types accept up to one hundred items per page', function () {
    $response = $this->getJson(route('api.v1.data.document-types.index', [
        'per_page' => 100,
    ]));

    $response->assertOk()
        ->assertJsonPath('pagination.per_page', 100)
        ->assertJsonPath('pagination.total', 4);
});

test('document types reject unknown filters', function () {
    $response = $this->getJson(route('api.v1.data.document-types.index', [
        'filter' => ['unknown' => 'value'],
    ]));

    $response->assertBadRequest();
});
