<?php

use App\Models\User;
use App\Role;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

test('superadmin can list all companies with the common paginated response', function () {
    $firstCompany = DteApiTestData::company([
        'name' => 'Abarca, S.A. de C.V.',
        'nit' => '06142812901015',
        'email' => 'abarca@example.com',
    ]);
    DteApiTestData::company([
        'name' => 'Zamora, S.A. de C.V.',
        'nit' => '06142812901016',
        'email' => 'zamora@example.com',
    ]);

    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.index', ['per_page' => 1]))
        ->assertOk()
        ->assertExactJson([
            'data' => [$firstCompany->fresh()->toArray()],
            'pagination' => [
                'total' => 2,
                'per_page' => 1,
                'current_page' => 1,
                'last_page' => 2,
                'from' => 1,
                'to' => 1,
            ],
        ]);
});

test('superadmin can filter companies by a partial name', function () {
    $matchingCompany = DteApiTestData::company([
        'name' => 'Abarca, S.A. de C.V.',
        'nit' => '06142812901015',
        'email' => 'abarca@example.com',
    ]);
    DteApiTestData::company([
        'name' => 'Zamora, S.A. de C.V.',
        'nit' => '06142812901016',
        'email' => 'zamora@example.com',
    ]);

    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.index', [
        'filter' => ['name' => 'barca'],
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [$matchingCompany->fresh()->toArray()],
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

test('company listing is restricted to superadmins', function (Role $role) {
    Sanctum::actingAs(User::factory()->withRole($role)->create());

    $this->getJson(route('api.v1.superadmin.companies.index'))->assertForbidden();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('superadmin company listing requires authentication', function () {
    $this->getJson(route('api.v1.superadmin.companies.index'))->assertUnauthorized();
});
