<?php

use App\Models\DteCorrelative;
use App\Models\User;
use App\Role;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

beforeEach(function (): void {
    $this->travelTo(Carbon::parse('2026-12-31 23:30:00', 'America/El_Salvador'));
});

test('superadmin sees the next correlative of the selected company and establishment', function (): void {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company();
    DteCorrelative::factory()->create(['company_id' => $company->id, 'key' => DteCorrelative::keyFor($company, 2026), 'last_value' => '124']);
    DteCorrelative::factory()->create(['company_id' => $otherCompany->id, 'key' => DteCorrelative::keyFor($otherCompany, 2026), 'last_value' => '7']);
    DteCorrelative::factory()->create(['company_id' => $company->id, 'key' => '2026-OTRO', 'last_value' => '40']);
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));

    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', $company))
        ->assertOk()->assertExactJson(['data' => ['year' => 2026, 'next_value' => 125]]);
    expect(DteCorrelative::query()->count())->toBe(3);
});

test('the next correlative defaults to one for a new fiscal year in local time', function (): void {
    $company = DteApiTestData::company();
    DteCorrelative::factory()->create(['company_id' => $company->id, 'key' => DteCorrelative::keyFor($company, 2026), 'last_value' => '124']);
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));

    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', $company))
        ->assertOk()->assertExactJson(['data' => ['year' => 2026, 'next_value' => 125]]);

    $this->travelTo(Carbon::parse('2027-01-01 00:00:00', 'America/El_Salvador'));
    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', $company))
        ->assertOk()->assertExactJson(['data' => ['year' => 2027, 'next_value' => 1]]);
    expect(DteCorrelative::query()->count())->toBe(1);
});

test('showing a correlative requires authentication', function (): void {
    $company = DteApiTestData::company();

    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', $company))->assertUnauthorized();
});

test('only superadmins can see a correlative', function (Role $role): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', $company))->assertForbidden();
})->with(['admin' => Role::ADMIN, 'user' => Role::USER]);

test('a user cannot see another company correlative', function (Role $role): void {
    $userCompany = DteApiTestData::company();
    $otherCompany = DteApiTestData::company();
    DteCorrelative::factory()->create(['company_id' => $otherCompany->id, 'key' => DteCorrelative::keyFor($otherCompany, 2026), 'last_value' => '42']);
    Sanctum::actingAs(DteApiTestData::user($userCompany, $role));

    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', $otherCompany))->assertForbidden();
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $otherCompany->id, 'last_value' => '42']);
})->with(['admin' => Role::ADMIN, 'user' => Role::USER]);

test('showing a correlative for an unknown company returns not found', function (): void {
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));

    $this->getJson(route('api.v1.superadmin.companies.dte-correlative.show', fake()->uuid()))->assertNotFound();
});
