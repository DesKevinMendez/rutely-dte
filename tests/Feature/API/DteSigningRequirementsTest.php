<?php

use App\Models\Dte;
use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

test('DTE creation returns the created draft when signing material is not configured for the company environment', function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $response = $this->postJson(
        route('api.v1.dtes.store'),
        DteApiTestData::validDtePayload(),
    )
        ->assertCreated();

    $dte = Dte::query()->findOrFail($response->json('data.record.id'));

    $response->assertExactJson([
        'data' => [
            'record' => $dte->toArray(),
            'mh_result' => null,
        ],
    ]);

    expect($dte->status)->toBe('BORRADOR')
        ->and($dte->signed_json)->toBeNull();
});
