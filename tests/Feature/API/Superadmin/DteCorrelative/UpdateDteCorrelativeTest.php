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

test('superadmin sets the next correlative for the current establishment and local year', function (): void {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company();
    DteCorrelative::factory()->create(['company_id' => $otherCompany->id, 'key' => '2026-'.$company->mh_establishment_code, 'last_value' => '7']);
    DteCorrelative::factory()->create(['company_id' => $company->id, 'key' => '2025-'.$company->mh_establishment_code, 'last_value' => '8']);
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));

    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), ['next_value' => 125])
        ->assertOk()->assertExactJson(['data' => ['year' => 2026, 'next_value' => 125]]);
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $company->id, 'key' => '2026-'.$company->mh_establishment_code, 'last_value' => '124']);
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $otherCompany->id, 'key' => '2026-'.$company->mh_establishment_code, 'last_value' => '7']);
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $company->id, 'key' => '2025-'.$company->mh_establishment_code, 'last_value' => '8']);
});

test('superadmin can decrease the next correlative', function (): void {
    $company = DteApiTestData::company();
    DteCorrelative::factory()->create(['company_id' => $company->id, 'key' => DteCorrelative::keyFor($company, 2026), 'last_value' => '25']);
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));

    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), ['next_value' => 10])
        ->assertOk()->assertExactJson(['data' => ['year' => 2026, 'next_value' => 10]]);
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $company->id, 'last_value' => '9']);
});

test('updates use the local fiscal year on both sides of new year', function (): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));

    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), ['next_value' => 15])
        ->assertOk()->assertExactJson(['data' => ['year' => 2026, 'next_value' => 15]]);

    $this->travelTo(Carbon::parse('2027-01-01 00:00:00', 'America/El_Salvador'));
    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), ['next_value' => 30])
        ->assertOk()->assertExactJson(['data' => ['year' => 2027, 'next_value' => 30]]);

    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $company->id, 'key' => DteCorrelative::keyFor($company, 2026), 'last_value' => '14']);
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $company->id, 'key' => DteCorrelative::keyFor($company, 2027), 'last_value' => '29']);
});

test('updating a correlative requires authentication', function (): void {
    $company = DteApiTestData::company();
    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), ['next_value' => 10])->assertUnauthorized();
});

test('only superadmins can update a correlative', function (Role $role): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, $role));
    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), ['next_value' => 10])->assertForbidden();
    expect(DteCorrelative::query()->count())->toBe(0);
})->with(['admin' => Role::ADMIN, 'user' => Role::USER]);

test('a user cannot update another company correlative', function (Role $role): void {
    $userCompany = DteApiTestData::company();
    $otherCompany = DteApiTestData::company();
    DteCorrelative::factory()->create(['company_id' => $otherCompany->id, 'key' => DteCorrelative::keyFor($otherCompany, 2026), 'last_value' => '42']);
    Sanctum::actingAs(DteApiTestData::user($userCompany, $role));

    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $otherCompany), ['next_value' => 10])->assertForbidden();
    $this->assertDatabaseHas(DteCorrelative::class, ['company_id' => $otherCompany->id, 'last_value' => '42']);
})->with(['admin' => Role::ADMIN, 'user' => Role::USER]);

test('updating a correlative for an unknown company returns not found', function (): void {
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));
    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', fake()->uuid()), ['next_value' => 10])->assertNotFound();
});

test('the next correlative is validated', function (array $payload, string $message): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create(['company_id' => null]));
    $this->patchJson(route('api.v1.superadmin.companies.dte-correlative.update', $company), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors(['next_value' => $message]);
})->with([
    'required' => [[], 'El campo próximo correlativo es obligatorio.'],
    'integer' => [['next_value' => 'abc'], 'El campo próximo correlativo debe ser un número entero.'],
    'minimum' => [['next_value' => 0], 'El campo próximo correlativo debe ser al menos 1.'],
    'maximum' => [['next_value' => 1000000000000000], 'El campo próximo correlativo no debe ser mayor que 999999999999999.'],
]);
