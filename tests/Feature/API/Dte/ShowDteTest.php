<?php

use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\EstablishmentTypesSeeder;
use Database\Seeders\ItemTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(EstablishmentTypesSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});
test('company member can show its DTE with common response', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.dtes.show', $dte))
        ->assertOk()
        ->assertExactJson(['data' => $dte->fresh()->toArray()]);
});

test('DTE show rejects cross-company access', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $dte = DteApiTestData::dte($otherCompany);
    Sanctum::actingAs(DteApiTestData::user($company));

    $this->getJson(route('api.v1.dtes.show', $dte))->assertForbidden();
});

test('company API token with list dte ability can obtain its DTE', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    $token = $company->createToken('External ERP', ['list:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.dtes.show', $dte))
        ->assertOk()
        ->assertExactJson(['data' => $dte->fresh()->toArray()]);
});

test('company API token cannot obtain a DTE from another company', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $dte = DteApiTestData::dte($otherCompany);
    $token = $company->createToken('External ERP', ['list:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.dtes.show', $dte))
        ->assertForbidden();
});

test('company API token without list dte ability cannot obtain a DTE', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    $token = $company->createToken('External ERP', ['create:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.dtes.show', $dte))
        ->assertForbidden();
});
