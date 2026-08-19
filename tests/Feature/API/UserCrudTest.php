<?php

use App\Models\Company;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function createUserCrudCompany(): Company
{
    $departamentId = (string) Str::uuid();
    $municipalityId = (string) Str::uuid();

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

    return Company::query()->create([
        'name' => 'Rutely, S.A. de C.V.',
        'address' => 'San Salvador',
        'phone' => '22223333',
        'nit' => '06142812901015',
        'nrc' => '1234567',
        'commercial_name' => 'Rutely',
        'economic_activity_code' => '62010',
        'establishment_type' => '01',
        'departament_id' => $departamentId,
        'municipality_id' => $municipalityId,
        'email' => 'billing@rutely.biz',
        'mh_establishment_code' => '0001',
        'mh_pos_code' => '0001',
        'own_establishment_code' => '0001',
        'own_pos_code' => '0001',
    ]);
}

function userCrudAdmin(Company $company, Role $role = Role::ADMIN): User
{
    return User::factory()->create([
        'company_id' => $company->id,
        'role' => $role->value,
    ]);
}

function validUserPayload(): array
{
    return [
        'name' => 'Operador DTE',
        'email' => 'operador@rutely.biz',
        'phone' => '77778888',
        'password' => 'secret123',
        'role' => Role::USER->value,
        'status' => true,
    ];
}

test('an admin can list users from its company with common collection', function () {
    $company = createUserCrudCompany();
    $admin = userCrudAdmin($company);
    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => Role::USER->value,
        'name' => 'Ana Operadora',
        'email' => 'ana@example.com',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson(route('api.v1.users.index', ['filter' => ['role' => Role::USER->value]]));

    $response->assertOk()->assertExactJson([
        'data' => [$member->fresh()->toArray()],
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

test('user list never leaks users from another company', function () {
    $company = createUserCrudCompany();
    $admin = userCrudAdmin($company);
    User::factory()->create(['company_id' => $company->id, 'role' => Role::USER->value]);

    $otherCompany = Company::query()->create($company->only([
        'name', 'address', 'phone', 'nit', 'nrc', 'commercial_name', 'economic_activity_code',
        'establishment_type', 'departament_id', 'municipality_id', 'district_id', 'email',
        'mh_establishment_code', 'mh_pos_code', 'own_establishment_code', 'own_pos_code',
    ]) + ['name' => 'Other Company', 'nit' => '06142812901016', 'email' => 'other@rutely.biz']);
    User::factory()->create(['company_id' => $otherCompany->id, 'role' => Role::USER->value]);

    Sanctum::actingAs($admin);

    $this->getJson(route('api.v1.users.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('an admin can create a user and company is assigned from authentication context', function () {
    $company = createUserCrudCompany();
    Sanctum::actingAs(userCrudAdmin($company));

    $payload = validUserPayload();
    $response = $this->postJson(route('api.v1.users.store'), $payload);

    $response->assertCreated()->assertExactJson([
        'data' => [
            'id' => $response->json('data.id'),
            'name' => $payload['name'],
            'email' => $payload['email'],
            'email_verified_at' => null,
            'phone' => $payload['phone'],
            'company_id' => $company->id,
            'role' => $payload['role'],
            'status' => true,
            'created_at' => $response->json('data.created_at'),
            'updated_at' => $response->json('data.updated_at'),
        ],
    ]);

    $this->assertDatabaseHas(User::class, [
        'email' => $payload['email'],
        'company_id' => $company->id,
        'role' => Role::USER->value,
        'status' => true,
    ]);
});

test('user role and status use secure defaults', function () {
    $company = createUserCrudCompany();
    Sanctum::actingAs(userCrudAdmin($company));

    $payload = validUserPayload();
    unset($payload['role'], $payload['status']);

    $response = $this->postJson(route('api.v1.users.store'), $payload);

    $response->assertCreated();
    expect($response->json('data.role'))->toBe(Role::USER->value)
        ->and($response->json('data.status'))->toBeTrue();
});

test('an admin can show update and delete a company user', function () {
    $company = createUserCrudCompany();
    $admin = userCrudAdmin($company);
    $member = User::factory()->create(['company_id' => $company->id, 'role' => Role::USER->value]);
    Sanctum::actingAs($admin);

    $this->getJson(route('api.v1.users.show', $member))
        ->assertOk()
        ->assertExactJson(['data' => $member->fresh()->toArray()]);

    $update = ['name' => 'Usuario actualizado', 'status' => false];
    $response = $this->patchJson(route('api.v1.users.update', $member), $update);
    $member->refresh();

    $response->assertOk()->assertExactJson(['data' => $member->toArray()]);
    expect($member->name)->toBe('Usuario actualizado')->and($member->status)->toBeFalse();

    $this->deleteJson(route('api.v1.users.destroy', $member))
        ->assertOk()
        ->assertExactJson(['message' => 'Usuario eliminado correctamente.']);

    $this->assertDatabaseMissing(User::class, ['id' => $member->id]);
});

test('user management requires authentication', function (string $method, string $routeName) {
    $company = createUserCrudCompany();
    $member = User::factory()->create(['company_id' => $company->id]);
    $route = str_contains($routeName, '.show') || str_contains($routeName, '.update') || str_contains($routeName, '.destroy')
        ? route($routeName, $member)
        : route($routeName);

    $this->json($method, $route)->assertUnauthorized();
})->with([
    ['GET', 'api.v1.users.index'],
    ['POST', 'api.v1.users.store'],
    ['GET', 'api.v1.users.show'],
    ['PATCH', 'api.v1.users.update'],
    ['DELETE', 'api.v1.users.destroy'],
]);

test('an operational user cannot manage users', function () {
    $company = createUserCrudCompany();
    Sanctum::actingAs(User::factory()->create(['company_id' => $company->id, 'role' => Role::USER->value]));

    $this->getJson(route('api.v1.users.index'))->assertForbidden();
    $this->postJson(route('api.v1.users.store'), validUserPayload())->assertForbidden();
});

test('an admin cannot access a user from another company', function () {
    $company = createUserCrudCompany();
    $admin = userCrudAdmin($company);
    $otherCompany = Company::query()->create($company->only([
        'name', 'address', 'phone', 'nit', 'nrc', 'commercial_name', 'economic_activity_code',
        'establishment_type', 'departament_id', 'municipality_id', 'district_id', 'email',
        'mh_establishment_code', 'mh_pos_code', 'own_establishment_code', 'own_pos_code',
    ]) + ['name' => 'Other Company', 'nit' => '06142812901016', 'email' => 'other@rutely.biz']);
    $otherUser = User::factory()->create(['company_id' => $otherCompany->id, 'role' => Role::USER->value]);
    Sanctum::actingAs($admin);

    $this->getJson(route('api.v1.users.show', $otherUser))->assertForbidden();
    $this->patchJson(route('api.v1.users.update', $otherUser), ['name' => 'Hacked'])->assertForbidden();
    $this->deleteJson(route('api.v1.users.destroy', $otherUser))->assertForbidden();
});

test('an admin cannot manage a superadmin and cannot delete itself', function () {
    $company = createUserCrudCompany();
    $admin = userCrudAdmin($company);
    $superadmin = userCrudAdmin($company, Role::SUPERADMIN);
    Sanctum::actingAs($admin);

    $this->getJson(route('api.v1.users.show', $superadmin))->assertForbidden();
    $this->patchJson(route('api.v1.users.update', $superadmin), ['name' => 'Nope'])->assertForbidden();
    $this->deleteJson(route('api.v1.users.destroy', $superadmin))->assertForbidden();
    $this->deleteJson(route('api.v1.users.destroy', $admin))->assertForbidden();
});

test('store user request validation returns 422', function (string $field, mixed $value, string $message) {
    $company = createUserCrudCompany();
    Sanctum::actingAs(userCrudAdmin($company));

    if ($field === 'email' && $value === 'existing@rutely.biz') {
        User::factory()->create(['email' => $value, 'company_id' => $company->id]);
    }

    $payload = validUserPayload();
    $payload[$field] = $value;

    $this->postJson(route('api.v1.users.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'name is required' => ['name', null, 'El campo nombre es obligatorio.'],
    'name must be a string' => ['name', ['User'], 'El campo nombre debe ser una cadena de caracteres.'],
    'name has a minimum length' => ['name', 'A', 'El campo nombre debe contener al menos 2 caracteres.'],
    'name has a maximum length' => ['name', str_repeat('a', 256), 'El campo nombre no debe contener más de 255 caracteres.'],
    'email is required' => ['email', null, 'El campo correo electrónico es obligatorio.'],
    'email must be valid' => ['email', 'invalid', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'email has a maximum length' => ['email', str_repeat('a', 245).'@example.com', 'El campo correo electrónico no debe contener más de 255 caracteres.'],
    'email must be unique' => ['email', 'existing@rutely.biz', 'El campo correo electrónico ya ha sido registrado.'],
    'phone must be a string' => ['phone', ['77778888'], 'El campo teléfono debe ser una cadena de caracteres.'],
    'phone has a minimum length' => ['phone', '1234567', 'El campo teléfono debe contener al menos 8 caracteres.'],
    'phone has a maximum length' => ['phone', str_repeat('1', 31), 'El campo teléfono no debe contener más de 30 caracteres.'],
    'password is required' => ['password', null, 'El campo contraseña es obligatorio.'],
    'password must be a string' => ['password', ['secret'], 'El campo contraseña debe ser una cadena de caracteres.'],
    'password has a minimum length' => ['password', '12345', 'El campo contraseña debe contener al menos 6 caracteres.'],
    'password has a maximum length' => ['password', str_repeat('a', 256), 'El campo contraseña no debe contener más de 255 caracteres.'],
    'role must be a string' => ['role', ['user'], 'El campo rol debe ser una cadena de caracteres.'],
    'role must be supported' => ['role', Role::SUPERADMIN->value, 'El valor seleccionado para rol no es válido.'],
    'status must be boolean' => ['status', 'yes', 'El campo estado debe ser verdadero o falso.'],
    'company id is prohibited' => ['company_id', (string) Str::uuid(), 'El campo empresa está prohibido.'],
]);

test('update user request validation returns 422', function (string $field, mixed $value, string $message) {
    $company = createUserCrudCompany();
    $admin = userCrudAdmin($company);
    $member = User::factory()->create(['company_id' => $company->id, 'role' => Role::USER->value]);
    Sanctum::actingAs($admin);

    if ($field === 'email' && $value === 'existing@rutely.biz') {
        User::factory()->create(['email' => $value, 'company_id' => $company->id]);
    }

    $this->patchJson(route('api.v1.users.update', $member), [$field => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'name cannot be null' => ['name', null, 'El campo nombre es obligatorio.'],
    'name must be a string' => ['name', ['User'], 'El campo nombre debe ser una cadena de caracteres.'],
    'name has a minimum length' => ['name', 'A', 'El campo nombre debe contener al menos 2 caracteres.'],
    'name has a maximum length' => ['name', str_repeat('a', 256), 'El campo nombre no debe contener más de 255 caracteres.'],
    'email cannot be null' => ['email', null, 'El campo correo electrónico es obligatorio.'],
    'email must be valid' => ['email', 'invalid', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'email has a maximum length' => ['email', str_repeat('a', 245).'@example.com', 'El campo correo electrónico no debe contener más de 255 caracteres.'],
    'email must be unique' => ['email', 'existing@rutely.biz', 'El campo correo electrónico ya ha sido registrado.'],
    'phone must be a string' => ['phone', ['77778888'], 'El campo teléfono debe ser una cadena de caracteres.'],
    'phone has a minimum length' => ['phone', '1234567', 'El campo teléfono debe contener al menos 8 caracteres.'],
    'phone has a maximum length' => ['phone', str_repeat('1', 31), 'El campo teléfono no debe contener más de 30 caracteres.'],
    'password cannot be null' => ['password', null, 'El campo contraseña es obligatorio.'],
    'password must be a string' => ['password', ['secret'], 'El campo contraseña debe ser una cadena de caracteres.'],
    'password has a minimum length' => ['password', '12345', 'El campo contraseña debe contener al menos 6 caracteres.'],
    'password has a maximum length' => ['password', str_repeat('a', 256), 'El campo contraseña no debe contener más de 255 caracteres.'],
    'role cannot be null' => ['role', null, 'El campo rol es obligatorio.'],
    'role must be a string' => ['role', ['user'], 'El campo rol debe ser una cadena de caracteres.'],
    'role must be supported' => ['role', Role::SUPERADMIN->value, 'El valor seleccionado para rol no es válido.'],
    'status cannot be null' => ['status', null, 'El campo estado es obligatorio.'],
    'status must be boolean' => ['status', 'yes', 'El campo estado debe ser verdadero o falso.'],
    'company id is prohibited' => ['company_id', (string) Str::uuid(), 'El campo empresa está prohibido.'],
]);
