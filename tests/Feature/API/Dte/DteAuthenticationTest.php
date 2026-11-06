<?php

use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\EstablishmentTypesSeeder;
use Database\Seeders\ItemTypeSeeder;
use Tests\Support\DteApiTestData;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(EstablishmentTypesSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});
test('DTE endpoints require authentication', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);

    $this->getJson(route('api.v1.dtes.index'))->assertUnauthorized();
    $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())->assertUnauthorized();
    $this->getJson(route('api.v1.dtes.show', $dte))->assertUnauthorized();
});
