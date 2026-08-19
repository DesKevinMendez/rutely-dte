<?php

use App\Environment;
use App\Models\Company;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function companyCrudPayload(): array
{
    $departamentId = (string) Str::uuid();
    $municipalityId = (string) Str::uuid();
    $districtId = (string) Str::uuid();

    DB::table('departaments')->insert([
        'id' => $departamentId,
        'code' => '06',
        'name' => 'San Salvador',
    ]);
    DB::table('municipalities')->insert([
        'id' => $municipalityId,
        'departament_id' => $departamentId,
        'departament_code' => '06',
        'code' => '01',
        'name' => 'San Salvador Centro',
    ]);
    DB::table('districts')->insert([
        'id' => $districtId,
        'departament_id' => $departamentId,
        'municipality_id' => $municipalityId,
        'code' => '01',
        'name' => 'San Salvador',
    ]);
    DB::table('economic_activities')->insert([
        'id' => (string) Str::uuid(),
        'code' => '62010',
        'description' => 'Programación informática',
    ]);
    DB::table('establishment_types')->insert([
        'id' => (string) Str::uuid(),
        'code' => '01',
        'description' => 'Sucursal / Agencia',
    ]);

    return [
        'name' => 'Rutely, S.A. de C.V.',
        'address' => 'San Salvador, El Salvador',
        'phone' => '22223333',
        'nit' => '06142812901015',
        'nrc' => '1234567',
        'commercial_name' => 'Rutely',
        'economic_activity_code' => '62010',
        'establishment_type' => '01',
        'departament_id' => $departamentId,
        'municipality_id' => $municipalityId,
        'district_id' => $districtId,
        'email' => 'billing@rutely.biz',
        'mh_establishment_code' => '0001',
        'mh_pos_code' => '0001',
        'own_establishment_code' => '0001',
        'own_pos_code' => '0001',
    ];
}

function createCompanyCrudCompany(): Company
{
    return Company::query()->create(companyCrudPayload());
}

test('company members can show their company with common response', function (Role $role) {
    $company = createCompanyCrudCompany();
    $user = User::factory()->create(['company_id' => $company->id, 'role' => $role->value]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.companies.show', $company))
        ->assertOk()
        ->assertExactJson(['data' => $company->fresh()->toArray()]);
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('admin and superadmin can update their company', function (Role $role) {
    $company = createCompanyCrudCompany();
    Sanctum::actingAs(User::factory()->create(['company_id' => $company->id, 'role' => $role->value]));

    $response = $this->patchJson(route('api.v1.companies.update', $company), [
        'name' => 'Rutely DTE',
        'phone' => '22224444',
    ]);
    $company->refresh();

    $response->assertOk()->assertExactJson(['data' => $company->toArray()]);
    expect($company->name)->toBe('Rutely DTE')->and($company->phone)->toBe('22224444');
})->with([
    'admin' => Role::ADMIN,
    'superadmin' => Role::SUPERADMIN,
]);

test('operational user can read but cannot update company', function () {
    $company = createCompanyCrudCompany();
    Sanctum::actingAs(User::factory()->create(['company_id' => $company->id, 'role' => Role::USER->value]));

    $this->getJson(route('api.v1.companies.show', $company))->assertOk();
    $this->patchJson(route('api.v1.companies.update', $company), ['name' => 'Nope'])->assertForbidden();
});

test('company access is tenant isolated', function () {
    $company = createCompanyCrudCompany();
    $otherCompany = Company::query()->create([
        ...$company->only([
            'address', 'phone', 'nrc', 'commercial_name', 'economic_activity_code', 'establishment_type',
            'departament_id', 'municipality_id', 'district_id', 'mh_establishment_code', 'mh_pos_code',
            'own_establishment_code', 'own_pos_code',
        ]),
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]);
    Sanctum::actingAs(User::factory()->create(['company_id' => $company->id, 'role' => Role::ADMIN->value]));

    $this->getJson(route('api.v1.companies.show', $otherCompany))->assertForbidden();
    $this->patchJson(route('api.v1.companies.update', $otherCompany), ['name' => 'Nope'])->assertForbidden();
});

test('company show and update require authentication', function () {
    $company = createCompanyCrudCompany();

    $this->getJson(route('api.v1.companies.show', $company))->assertUnauthorized();
    $this->patchJson(route('api.v1.companies.update', $company), ['name' => 'Nope'])->assertUnauthorized();
});

test('company members can read environment and only privileged roles can update it', function (Role $role, bool $canUpdate) {
    $company = createCompanyCrudCompany();
    $user = User::factory()->create(['company_id' => $company->id, 'role' => $role->value]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.companies.environment.show', $company))
        ->assertOk()
        ->assertExactJson(['data' => ['environment' => Environment::SANDBOX->value]]);

    $response = $this->patchJson(route('api.v1.companies.environment.update', $company), [
        'environment' => Environment::PRODUCTION->value,
    ]);

    if (! $canUpdate) {
        $response->assertForbidden();

        return;
    }

    $response->assertOk()->assertExactJson(['data' => ['environment' => Environment::PRODUCTION->value]]);
    expect($company->fresh()->environment)->toBe(Environment::PRODUCTION->value);
})->with([
    'admin' => [Role::ADMIN, true],
    'user' => [Role::USER, false],
    'superadmin' => [Role::SUPERADMIN, true],
]);

test('company environment request validation returns 422', function (string $field, mixed $value, string $message) {
    $company = createCompanyCrudCompany();
    Sanctum::actingAs(User::factory()->create(['company_id' => $company->id, 'role' => Role::ADMIN->value]));

    $this->patchJson(route('api.v1.companies.environment.update', $company), [$field => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'environment is required' => ['environment', null, 'El campo ambiente es obligatorio.'],
    'environment must be a string' => ['environment', ['00'], 'El campo ambiente debe ser una cadena de caracteres.'],
    'environment must be supported' => ['environment', '99', 'El valor seleccionado para ambiente no es válido.'],
]);

test('update company request validation returns 422', function (string $field, mixed $value, string $message) {
    $company = createCompanyCrudCompany();
    Sanctum::actingAs(User::factory()->create(['company_id' => $company->id, 'role' => Role::ADMIN->value]));

    $this->patchJson(route('api.v1.companies.update', $company), [$field => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'name cannot be null' => ['name', null, 'El campo nombre es obligatorio.'],
    'name must be a string' => ['name', ['Rutely'], 'El campo nombre debe ser una cadena de caracteres.'],
    'name has a maximum length' => ['name', str_repeat('a', 256), 'El campo nombre no debe contener más de 255 caracteres.'],
    'address cannot be null' => ['address', null, 'El campo dirección es obligatorio.'],
    'address must be a string' => ['address', ['San Salvador'], 'El campo dirección debe ser una cadena de caracteres.'],
    'address has a maximum length' => ['address', str_repeat('a', 256), 'El campo dirección no debe contener más de 255 caracteres.'],
    'phone cannot be null' => ['phone', null, 'El campo teléfono es obligatorio.'],
    'phone must be a string' => ['phone', ['22223333'], 'El campo teléfono debe ser una cadena de caracteres.'],
    'phone has a minimum length' => ['phone', '1234567', 'El campo teléfono debe contener al menos 8 caracteres.'],
    'phone has a maximum length' => ['phone', str_repeat('1', 31), 'El campo teléfono no debe contener más de 30 caracteres.'],
    'nit cannot be null' => ['nit', null, 'El campo NIT es obligatorio.'],
    'nit must be a string' => ['nit', ['06142812901015'], 'El campo NIT debe ser una cadena de caracteres.'],
    'nit has a valid format' => ['nit', '12345678', 'El formato del campo NIT no es válido.'],
    'nrc must be a string' => ['nrc', ['1234567'], 'El campo NRC debe ser una cadena de caracteres.'],
    'nrc has a valid format' => ['nrc', 'ABC123', 'El formato del campo NRC no es válido.'],
    'commercial name cannot be null' => ['commercial_name', null, 'El campo nombre comercial es obligatorio.'],
    'commercial name must be a string' => ['commercial_name', ['Rutely'], 'El campo nombre comercial debe ser una cadena de caracteres.'],
    'commercial name has a maximum length' => ['commercial_name', str_repeat('a', 151), 'El campo nombre comercial no debe contener más de 150 caracteres.'],
    'economic activity code cannot be null' => ['economic_activity_code', null, 'El campo código de actividad económica es obligatorio.'],
    'economic activity code must be a string' => ['economic_activity_code', ['62010'], 'El campo código de actividad económica debe ser una cadena de caracteres.'],
    'economic activity code must exist' => ['economic_activity_code', '999999', 'El valor seleccionado para código de actividad económica no es válido.'],
    'establishment type cannot be null' => ['establishment_type', null, 'El campo tipo de establecimiento es obligatorio.'],
    'establishment type must be a string' => ['establishment_type', ['01'], 'El campo tipo de establecimiento debe ser una cadena de caracteres.'],
    'establishment type must exist' => ['establishment_type', '99', 'El valor seleccionado para tipo de establecimiento no es válido.'],
    'departament cannot be null' => ['departament_id', null, 'El campo departamento es obligatorio.'],
    'departament must be a uuid' => ['departament_id', 'invalid-uuid', 'El campo departamento debe ser un UUID válido.'],
    'departament must exist' => ['departament_id', '00000000-0000-4000-8000-000000000001', 'El valor seleccionado para departamento no es válido.'],
    'municipality cannot be null' => ['municipality_id', null, 'El campo municipio es obligatorio.'],
    'municipality must be a uuid' => ['municipality_id', 'invalid-uuid', 'El campo municipio debe ser un UUID válido.'],
    'municipality must exist' => ['municipality_id', '00000000-0000-4000-8000-000000000002', 'El valor seleccionado para municipio no es válido.'],
    'district must be a uuid' => ['district_id', 'invalid-uuid', 'El campo distrito debe ser un UUID válido.'],
    'district must exist' => ['district_id', '00000000-0000-4000-8000-000000000003', 'El valor seleccionado para distrito no es válido.'],
    'email cannot be null' => ['email', null, 'El campo correo electrónico es obligatorio.'],
    'email must be valid' => ['email', 'invalid-email', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'email has a maximum length' => ['email', str_repeat('a', 256).'@example.com', 'El campo correo electrónico no debe contener más de 255 caracteres.'],
    'mh establishment code cannot be null' => ['mh_establishment_code', null, 'El campo código de establecimiento MH es obligatorio.'],
    'mh establishment code must be a string' => ['mh_establishment_code', ['0001'], 'El campo código de establecimiento MH debe ser una cadena de caracteres.'],
    'mh establishment code has size four' => ['mh_establishment_code', '001', 'El campo código de establecimiento MH debe contener 4 caracteres.'],
    'mh pos code cannot be null' => ['mh_pos_code', null, 'El campo código de punto de venta MH es obligatorio.'],
    'mh pos code must be a string' => ['mh_pos_code', ['0001'], 'El campo código de punto de venta MH debe ser una cadena de caracteres.'],
    'mh pos code has size four' => ['mh_pos_code', '001', 'El campo código de punto de venta MH debe contener 4 caracteres.'],
    'own establishment code cannot be null' => ['own_establishment_code', null, 'El campo código propio de establecimiento es obligatorio.'],
    'own establishment code must be a string' => ['own_establishment_code', ['0001'], 'El campo código propio de establecimiento debe ser una cadena de caracteres.'],
    'own establishment code has size four' => ['own_establishment_code', '001', 'El campo código propio de establecimiento debe contener 4 caracteres.'],
    'own pos code cannot be null' => ['own_pos_code', null, 'El campo código propio de punto de venta es obligatorio.'],
    'own pos code must be a string' => ['own_pos_code', ['0001'], 'El campo código propio de punto de venta debe ser una cadena de caracteres.'],
    'own pos code has size four' => ['own_pos_code', '001', 'El campo código propio de punto de venta debe contener 4 caracteres.'],
]);
