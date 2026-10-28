<?php

use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

test('DTE creation returns bad request when signing material is not configured for the company environment', function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->postJson(
        route('api.v1.dtes.store'),
        DteApiTestData::validDtePayload(),
    )
        ->assertStatus(400)
        ->assertExactJson([
            'message' => 'No hay material de firma configurado para la empresa y el ambiente seleccionados.',
        ]);
});
