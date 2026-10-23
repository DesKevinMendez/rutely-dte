<?php

use App\Models\User;
use App\Role;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

test('superadmin can show a company that is not assigned to their account', function (): void {
    $company = DteApiTestData::company();

    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.show', $company))
        ->assertOk()
        ->assertExactJson(['data' => $company->fresh()->toArray()]);
});

test('only superadmins can show a company', function (Role $role): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->getJson(route('api.v1.superadmin.companies.show', $company))
        ->assertForbidden();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('showing a company requires authentication', function (): void {
    $company = DteApiTestData::company();

    $this->getJson(route('api.v1.superadmin.companies.show', $company))
        ->assertUnauthorized();
});

test('superadmin company show returns not found for an unknown company', function (): void {
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.show', Str::uuid()))
        ->assertNotFound();
});
