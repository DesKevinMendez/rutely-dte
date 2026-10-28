<?php

use App\Models\DocumentType;
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

    expect(array_column($expected, 'code'))->toBe(['02', '03', '13', '36', '37'])
        ->and(array_column($expected, 'description'))->toBe(['Carnet de Residente', 'Pasaporte', 'DUI', 'NIT', 'Otro']);

    $response = $this->getJson(route('api.v1.data.document-types.index'));

    $response->assertOk()
        ->assertExactJson([
            'data' => $expected,
            'pagination' => [
                'total' => 5,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 5,
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

test('document types can be filtered by id', function () {
    $documentType = DB::table('document_types')
        ->select(['id', 'code', 'description'])
        ->where('code', '36')
        ->first();

    $response = $this->getJson(route('api.v1.data.document-types.index', [
        'filter' => ['id' => $documentType->id],
    ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => [(array) $documentType],
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
        ->assertJsonPath('pagination.total', 5);
});

test('document types reject unknown filters', function () {
    $response = $this->getJson(route('api.v1.data.document-types.index', [
        'filter' => ['unknown' => 'value'],
    ]));

    $response->assertBadRequest();
});

test('document type factories create valid persisted models', function () {
    DB::table('document_types')->delete();

    $documentType = DocumentType::factory()->create();

    $this->assertMatchesRegularExpression('/^\d{2}$/', $documentType->code);
    $this->assertNotEmpty($documentType->description);
    $this->assertDatabaseHas('document_types', [
        'id' => $documentType->id,
        'code' => $documentType->code,
        'description' => $documentType->description,
    ]);
});

test('document type seeder is idempotent and updates catalog rows by code', function () {
    $originalRows = DB::table('document_types')->get()->keyBy('code');

    $this->seed(DocumentTypeSeeder::class);

    expect(DB::table('document_types')->count())->toBe($originalRows->count());

    $reseededRows = DB::table('document_types')->get()->keyBy('code');

    foreach ($originalRows as $code => $originalRow) {
        expect($reseededRows->get($code)->id)->toBe($originalRow->id);
    }

    DB::table('document_types')->where('code', '13')->update([
        'description' => 'Descripción anterior',
        'updated_at' => '2000-01-01 00:00:00',
    ]);

    $this->seed(DocumentTypeSeeder::class);

    $updatedRow = DB::table('document_types')->where('code', '13')->first();

    expect($updatedRow->id)->toBe($originalRows->get('13')->id)
        ->and($updatedRow->description)->toBe('DUI')
        ->and($updatedRow->created_at)->toBe($originalRows->get('13')->created_at)
        ->and($updatedRow->updated_at)->not->toBe('2000-01-01 00:00:00');
});
