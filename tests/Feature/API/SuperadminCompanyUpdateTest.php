<?php

use App\Models\User;
use App\Role;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

test('superadmin can update a company that is not assigned to their account', function (): void {
    $company = DteApiTestData::company();
    $superadmin = User::factory()->create([
        'company_id' => null,
        'role' => Role::SUPERADMIN->value,
    ]);
    Sanctum::actingAs($superadmin);

    $response = $this->patchJson(
        route('api.v1.superadmin.companies.update', $company),
        [
            'name' => 'Rutely DTE actualizada, S.A. de C.V.',
            'phone' => '22224444',
        ],
    );

    $company->refresh();

    $response->assertOk()
        ->assertExactJson(['data' => $company->toArray()]);

    expect($company->name)->toBe('Rutely DTE actualizada, S.A. de C.V.')
        ->and($company->phone)->toBe('22224444');
});

test('only superadmins can update a company', function (Role $role): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->patchJson(
        route('api.v1.superadmin.companies.update', $company),
        ['name' => 'No autorizado'],
    )->assertForbidden();

    expect($company->fresh()->name)->toBe('Rutely, S.A. de C.V.');
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('updating a company requires authentication', function (): void {
    $company = DteApiTestData::company();

    $this->patchJson(
        route('api.v1.superadmin.companies.update', $company),
        ['name' => 'No autorizado'],
    )->assertUnauthorized();
});

test('superadmin company update accepts an empty partial payload', function (): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->create([
        'company_id' => null,
        'role' => Role::SUPERADMIN->value,
    ]));

    $this->patchJson(route('api.v1.superadmin.companies.update', $company), [])
        ->assertOk()
        ->assertExactJson(['data' => $company->fresh()->toArray()]);
});

test('superadmin company update allows null optional fields', function (): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->create([
        'company_id' => null,
        'role' => Role::SUPERADMIN->value,
    ]));

    $this->patchJson(
        route('api.v1.superadmin.companies.update', $company),
        ['nrc' => null, 'district_id' => null],
    )
        ->assertOk()
        ->assertJsonPath('data.nrc', null)
        ->assertJsonPath('data.district_id', null);
});

test('superadmin company update validates each request rule', function (string $field, mixed $value, string $message): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->create([
        'company_id' => null,
        'role' => Role::SUPERADMIN->value,
    ]));

    $this->patchJson(route('api.v1.superadmin.companies.update', $company), [$field => $value])
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
    'MH establishment code cannot be null' => ['mh_establishment_code', null, 'El campo código de establecimiento MH es obligatorio.'],
    'MH establishment code must be a string' => ['mh_establishment_code', ['0001'], 'El campo código de establecimiento MH debe ser una cadena de caracteres.'],
    'MH establishment code has size four' => ['mh_establishment_code', '001', 'El campo código de establecimiento MH debe contener 4 caracteres.'],
    'MH point of sale code cannot be null' => ['mh_pos_code', null, 'El campo código de punto de venta MH es obligatorio.'],
    'MH point of sale code must be a string' => ['mh_pos_code', ['0001'], 'El campo código de punto de venta MH debe ser una cadena de caracteres.'],
    'MH point of sale code has size four' => ['mh_pos_code', '001', 'El campo código de punto de venta MH debe contener 4 caracteres.'],
    'own establishment code cannot be null' => ['own_establishment_code', null, 'El campo código propio de establecimiento es obligatorio.'],
    'own establishment code must be a string' => ['own_establishment_code', ['0001'], 'El campo código propio de establecimiento debe ser una cadena de caracteres.'],
    'own establishment code has size four' => ['own_establishment_code', '001', 'El campo código propio de establecimiento debe contener 4 caracteres.'],
    'own point of sale code cannot be null' => ['own_pos_code', null, 'El campo código propio de punto de venta es obligatorio.'],
    'own point of sale code must be a string' => ['own_pos_code', ['0001'], 'El campo código propio de punto de venta debe ser una cadena de caracteres.'],
    'own point of sale code has size four' => ['own_pos_code', '001', 'El campo código propio de punto de venta debe contener 4 caracteres.'],
]);
