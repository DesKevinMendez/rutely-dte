<?php

use App\Models\Company;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function validCompanyPayload(): array
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

test('an admin can create a company and is assigned to it', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = validCompanyPayload();

    $response = $this->postJson(route('api.v1.companies.store'), $payload);

    $response->assertCreated()
        ->assertExactJson([
            'data' => [
                'id' => $response->json('data.id'),
                ...$payload,
                'is_onboarded' => $response->json('data.is_onboarded'),
                'environment' => $response->json('data.environment'),
                'created_at' => $response->json('data.created_at'),
                'updated_at' => $response->json('data.updated_at'),
            ],
        ]);

    $companyId = $response->json('data.id');

    $this->assertDatabaseHas(Company::class, [
        'id' => $companyId,
        'name' => $payload['name'],
        'nit' => $payload['nit'],
    ]);

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'company_id' => $companyId,
    ]);
});

test('a superadmin can create a company and is assigned to it', function () {
    $user = User::factory()->create([
        'role' => Role::SUPERADMIN->value,
    ]);

    Sanctum::actingAs($user);

    $payload = validCompanyPayload();

    $response = $this->postJson(route('api.v1.companies.store'), $payload);

    $response->assertCreated();

    $companyId = $response->json('data.id');

    $this->assertDatabaseHas(Company::class, [
        'id' => $companyId,
        'name' => $payload['name'],
    ]);

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'company_id' => $companyId,
    ]);
});

test('an unauthenticated user cannot create a company', function () {
    $this->postJson(route('api.v1.companies.store'))
        ->assertUnauthorized();
});

test('a user with a company cannot create another company', function (Role $role) {
    $payload = validCompanyPayload();
    $companyId = (string) Str::uuid();

    DB::table('companies')->insert([
        'id' => $companyId,
        ...$payload,
    ]);

    $user = User::factory()->create([
        'role' => $role->value,
        'company_id' => $companyId,
    ]);

    Sanctum::actingAs($user);

    $this->postJson(route('api.v1.companies.store'))
        ->assertForbidden();
})->with([
    'admin' => Role::ADMIN,
    'superadmin' => Role::SUPERADMIN,
]);

test('nullable company fields may be null', function (string $field) {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = validCompanyPayload();
    $payload[$field] = null;

    $this->postJson(route('api.v1.companies.store'), $payload)
        ->assertCreated();
})->with([
    'nrc' => 'nrc',
    'district' => 'district_id',
]);

test('company request validation returns 422', function (string $field, mixed $value, string $message) {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = validCompanyPayload();
    $payload[$field] = $value;

    $this->postJson(route('api.v1.companies.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'name is required' => ['name', null, 'El campo nombre es obligatorio.'],
    'name must be a string' => ['name', ['Rutely'], 'El campo nombre debe ser una cadena de caracteres.'],
    'name has a maximum length' => ['name', str_repeat('a', 256), 'El campo nombre no debe contener más de 255 caracteres.'],
    'address is required' => ['address', null, 'El campo dirección es obligatorio.'],
    'address must be a string' => ['address', ['San Salvador'], 'El campo dirección debe ser una cadena de caracteres.'],
    'address has a maximum length' => ['address', str_repeat('a', 256), 'El campo dirección no debe contener más de 255 caracteres.'],
    'phone is required' => ['phone', null, 'El campo teléfono es obligatorio.'],
    'phone must be a string' => ['phone', ['22223333'], 'El campo teléfono debe ser una cadena de caracteres.'],
    'phone has a minimum length' => ['phone', '1234567', 'El campo teléfono debe contener al menos 8 caracteres.'],
    'phone has a maximum length' => ['phone', str_repeat('1', 31), 'El campo teléfono no debe contener más de 30 caracteres.'],
    'nit is required' => ['nit', null, 'El campo NIT es obligatorio.'],
    'nit must be a string' => ['nit', ['06142812901015'], 'El campo NIT debe ser una cadena de caracteres.'],
    'nit has a valid format' => ['nit', '12345678', 'El formato del campo NIT no es válido.'],
    'nrc must be a string' => ['nrc', ['1234567'], 'El campo NRC debe ser una cadena de caracteres.'],
    'nrc has a valid format' => ['nrc', 'ABC123', 'El formato del campo NRC no es válido.'],
    'commercial name is required' => ['commercial_name', null, 'El campo nombre comercial es obligatorio.'],
    'commercial name must be a string' => ['commercial_name', ['Rutely'], 'El campo nombre comercial debe ser una cadena de caracteres.'],
    'commercial name has a maximum length' => ['commercial_name', str_repeat('a', 151), 'El campo nombre comercial no debe contener más de 150 caracteres.'],
    'economic activity code is required' => ['economic_activity_code', null, 'El campo código de actividad económica es obligatorio.'],
    'economic activity code must be a string' => ['economic_activity_code', ['62010'], 'El campo código de actividad económica debe ser una cadena de caracteres.'],
    'economic activity code must exist' => ['economic_activity_code', '999999', 'El valor seleccionado para código de actividad económica no es válido.'],
    'establishment type is required' => ['establishment_type', null, 'El campo tipo de establecimiento es obligatorio.'],
    'establishment type must be a string' => ['establishment_type', ['01'], 'El campo tipo de establecimiento debe ser una cadena de caracteres.'],
    'establishment type must exist' => ['establishment_type', '99', 'El valor seleccionado para tipo de establecimiento no es válido.'],
    'departament is required' => ['departament_id', null, 'El campo departamento es obligatorio.'],
    'departament must be a uuid' => ['departament_id', 'invalid-uuid', 'El campo departamento debe ser un UUID válido.'],
    'departament must exist' => ['departament_id', '00000000-0000-4000-8000-000000000001', 'El valor seleccionado para departamento no es válido.'],
    'municipality is required' => ['municipality_id', null, 'El campo municipio es obligatorio.'],
    'municipality must be a uuid' => ['municipality_id', 'invalid-uuid', 'El campo municipio debe ser un UUID válido.'],
    'municipality must exist' => ['municipality_id', '00000000-0000-4000-8000-000000000002', 'El valor seleccionado para municipio no es válido.'],
    'district must be a uuid' => ['district_id', 'invalid-uuid', 'El campo distrito debe ser un UUID válido.'],
    'district must exist' => ['district_id', '00000000-0000-4000-8000-000000000003', 'El valor seleccionado para distrito no es válido.'],
    'email is required' => ['email', null, 'El campo correo electrónico es obligatorio.'],
    'email must be valid' => ['email', 'invalid-email', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'email has a maximum length' => ['email', str_repeat('a', 256).'@example.com', 'El campo correo electrónico no debe contener más de 255 caracteres.'],
    'mh establishment code is required' => ['mh_establishment_code', null, 'El campo código de establecimiento MH es obligatorio.'],
    'mh establishment code must be a string' => ['mh_establishment_code', ['0001'], 'El campo código de establecimiento MH debe ser una cadena de caracteres.'],
    'mh establishment code must have four characters' => ['mh_establishment_code', '001', 'El campo código de establecimiento MH debe contener 4 caracteres.'],
    'mh pos code is required' => ['mh_pos_code', null, 'El campo código de punto de venta MH es obligatorio.'],
    'mh pos code must be a string' => ['mh_pos_code', ['0001'], 'El campo código de punto de venta MH debe ser una cadena de caracteres.'],
    'mh pos code must have four characters' => ['mh_pos_code', '001', 'El campo código de punto de venta MH debe contener 4 caracteres.'],
    'own establishment code is required' => ['own_establishment_code', null, 'El campo código propio de establecimiento es obligatorio.'],
    'own establishment code must be a string' => ['own_establishment_code', ['0001'], 'El campo código propio de establecimiento debe ser una cadena de caracteres.'],
    'own establishment code must have four characters' => ['own_establishment_code', '001', 'El campo código propio de establecimiento debe contener 4 caracteres.'],
    'own pos code is required' => ['own_pos_code', null, 'El campo código propio de punto de venta es obligatorio.'],
    'own pos code must be a string' => ['own_pos_code', ['0001'], 'El campo código propio de punto de venta debe ser una cadena de caracteres.'],
    'own pos code must have four characters' => ['own_pos_code', '001', 'El campo código propio de punto de venta debe contener 4 caracteres.'],
]);
