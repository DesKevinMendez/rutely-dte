<?php

use App\Models\Dte;
use App\Models\DteCorrelative;
use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;
use Tests\Support\MhSchema;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});

test('a company with an incomplete issuer profile cannot issue and consumes no correlative', function (array $overrides, array $payload, string $message) {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue($overrides), Role::USER));

    $this->postJson(route('api.v1.dtes.store'), $payload)
        ->assertBadRequest()
        ->assertExactJson(['message' => $message]);

    expect(Dte::query()->count())->toBe(0)
        ->and(DteCorrelative::query()->count())->toBe(0);
})->with([
    'no district' => [['district_id' => null], DteApiTestData::validDtePayload(), 'La empresa no tiene un distrito configurado, es obligatorio para emitir DTE.'],
    'establishment code without letter' => [['own_establishment_code' => '0001'], DteApiTestData::validDtePayload(), 'El código de establecimiento de la empresa debe iniciar con M, B, S o P seguido de 3 números.'],
    'point of sale code without letter' => [['own_pos_code' => '0001'], DteApiTestData::validDtePayload(), 'El código de punto de venta de la empresa debe iniciar con P seguido de 3 números.'],
    'consumer invoice without NRC' => [['nrc' => null], DteApiTestData::validDtePayload(), 'La empresa no tiene un NRC configurado, es obligatorio para emitir este tipo de DTE.'],
    'tax credit without NRC' => [['nrc' => null], DteApiTestData::validTaxCreditPayload(), 'La empresa no tiene un NRC configurado, es obligatorio para emitir este tipo de DTE.'],
]);

test('an excluded subject invoice can be issued by a company without NRC', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(['nrc' => null]), Role::USER));

    $response = $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validExcludedSubjectPayload())
        ->assertCreated();

    $document = signedDocument(Dte::query()->findOrFail($response->json('data.record.id'))->signed_json);

    expect(MhSchema::errors($document, MhSchema::EXCLUDED_SUBJECT))->toBe([])
        ->and($document['emisor']['nrc'])->toBeNull();
});
