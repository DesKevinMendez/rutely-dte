<?php

use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\EstablishmentTypesSeeder;
use Database\Seeders\ItemTypeSeeder;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(EstablishmentTypesSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});
test('company member can list DTEs with Spatie filters and common collection', function (Role $role) {
    $company = DteApiTestData::company();
    $user = DteApiTestData::user($company, $role);
    $dte = DteApiTestData::dte($company, ['dte_type' => '01', 'status' => 'PROCESADO']);
    DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-03-00010001-000000000000002',
        'dte_type' => '03',
        'status' => 'RECHAZADO',
    ]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.dtes.index', [
        'filter' => ['tipoDte' => '01', 'estado' => 'PROCESADO'],
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [$dte->fresh()->toArray()],
            'pagination' => [
                'total' => 1,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
            ],
        ]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('company member can filter DTEs by emission date', function () {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company, [
        'original_json' => [
            'identificacion' => ['fecEmi' => '2026-08-18'],
        ],
    ]);
    DteApiTestData::dte($company, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-03-00010001-000000000000002',
        'dte_type' => '03',
        'original_json' => [
            'identificacion' => ['fecEmi' => '2026-08-19'],
        ],
    ]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.dtes.index', [
        'filter' => ['date' => '2026-08-18'],
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [$dte->fresh()->toArray()],
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

test('DTE list is tenant isolated', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $own = DteApiTestData::dte($company);
    DteApiTestData::dte($otherCompany, [
        'generation_code' => strtoupper((string) Str::uuid()),
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->getJson(route('api.v1.dtes.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => [$own->fresh()->toArray()],
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

test('company API token with list dte ability can list only its own DTEs', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    $own = DteApiTestData::dte($company);
    DteApiTestData::dte($otherCompany, [
        'control_number' => 'DTE-01-00010001-000000000000002',
    ]);
    $token = $company->createToken('External ERP', ['list:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.dtes.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => [$own->fresh()->toArray()],
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

test('company API token without list dte ability cannot list DTEs', function () {
    $company = DteApiTestData::company();
    $token = $company->createToken('External ERP', ['create:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.dtes.index'))
        ->assertForbidden();
});
