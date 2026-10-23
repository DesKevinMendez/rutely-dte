<?php

use App\Models\Company;
use App\Models\User;
use App\Role;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

/** @return array<string, mixed> */
function validSuperadminCompanyPayload(Company $company): array
{
    return Arr::only($company->getAttributes(), [
        'name',
        'address',
        'phone',
        'nit',
        'nrc',
        'commercial_name',
        'economic_activity_code',
        'establishment_type',
        'departament_id',
        'municipality_id',
        'district_id',
        'email',
        'mh_establishment_code',
        'mh_pos_code',
        'own_establishment_code',
        'own_pos_code',
    ]);
}

test('superadmin can create an onboarded company without being assigned to it', function () {
    $assignedCompany = DteApiTestData::company();
    $superadmin = DteApiTestData::user($assignedCompany, Role::SUPERADMIN);
    Sanctum::actingAs($superadmin);

    $payload = array_replace(validSuperadminCompanyPayload($assignedCompany), [
        'name' => 'Nueva empresa, S.A. de C.V.',
        'nit' => '06142812901016',
        'commercial_name' => 'Nueva empresa',
        'email' => 'nueva@example.com',
        'is_onboarded' => false,
    ]);

    $response = $this->postJson(route('api.v1.superadmin.companies.store'), $payload);
    $response->assertCreated();

    $company = Company::query()->where('nit', $payload['nit'])->firstOrFail();

    $response->assertExactJson(['data' => $company->fresh()->toArray()]);

    $this->assertDatabaseHas(Company::class, [
        'id' => $company->id,
        'is_onboarded' => '1',
    ]);
    $this->assertSame($assignedCompany->id, $superadmin->fresh()->company_id);
});

test('only superadmins can create companies', function (Role $role): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->postJson(route('api.v1.superadmin.companies.store'), [])->assertForbidden();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('creating a company requires authentication', function (): void {
    $this->postJson(route('api.v1.superadmin.companies.store'), [])->assertUnauthorized();
});

test('superadmin company creation validates every input', function (string $field, mixed $value, string $message): void {
    $existingCompany = DteApiTestData::company();
    $payload = validSuperadminCompanyPayload($existingCompany);
    $payload[$field] = $value;

    Sanctum::actingAs(User::factory()->create([
        'company_id' => null,
        'role' => Role::SUPERADMIN->value,
    ]));

    $this->postJson(route('api.v1.superadmin.companies.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);

    $this->assertDatabaseCount(Company::class, 1);
})->with([
    'name' => ['name', null, 'El campo nombre es obligatorio.'],
    'address' => ['address', null, 'El campo dirección es obligatorio.'],
    'phone' => ['phone', '1234567', 'El campo teléfono debe contener al menos 8 caracteres.'],
    'nit' => ['nit', '12345678', 'El formato del campo NIT no es válido.'],
    'nrc' => ['nrc', 'ABC123', 'El formato del campo NRC no es válido.'],
    'commercial name' => ['commercial_name', null, 'El campo nombre comercial es obligatorio.'],
    'economic activity' => ['economic_activity_code', '999999', 'El valor seleccionado para código de actividad económica no es válido.'],
    'establishment type' => ['establishment_type', '99', 'El valor seleccionado para tipo de establecimiento no es válido.'],
    'department' => ['departament_id', 'invalid-uuid', 'El campo departamento debe ser un UUID válido.'],
    'municipality' => ['municipality_id', 'invalid-uuid', 'El campo municipio debe ser un UUID válido.'],
    'district' => ['district_id', 'invalid-uuid', 'El campo distrito debe ser un UUID válido.'],
    'email' => ['email', 'invalid-email', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'MH establishment code' => ['mh_establishment_code', '001', 'El campo código de establecimiento MH debe contener 4 caracteres.'],
    'MH point of sale code' => ['mh_pos_code', '001', 'El campo código de punto de venta MH debe contener 4 caracteres.'],
    'own establishment code' => ['own_establishment_code', '001', 'El campo código propio de establecimiento debe contener 4 caracteres.'],
    'own point of sale code' => ['own_pos_code', '001', 'El campo código propio de punto de venta debe contener 4 caracteres.'],
]);
