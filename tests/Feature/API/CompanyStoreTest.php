<?php

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

    $this->assertDatabaseHas('companies', [
        'id' => $companyId,
        'name' => $payload['name'],
        'nit' => $payload['nit'],
    ]);

    $this->assertDatabaseHas('users', [
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

    $this->assertDatabaseHas('companies', [
        'id' => $companyId,
        'name' => $payload['name'],
    ]);

    $this->assertDatabaseHas('users', [
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

test('company request validation returns 422', function (string $field, mixed $value) {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = validCompanyPayload();
    $payload[$field] = $value;

    $this->postJson(route('api.v1.companies.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'name is required' => ['name', null],
    'name must be a string' => ['name', ['Rutely']],
    'name has a maximum length' => ['name', str_repeat('a', 256)],
    'address is required' => ['address', null],
    'address must be a string' => ['address', ['San Salvador']],
    'address has a maximum length' => ['address', str_repeat('a', 256)],
    'phone is required' => ['phone', null],
    'phone must be a string' => ['phone', ['22223333']],
    'phone has a minimum length' => ['phone', '1234567'],
    'phone has a maximum length' => ['phone', str_repeat('1', 31)],
    'nit is required' => ['nit', null],
    'nit must be a string' => ['nit', ['06142812901015']],
    'nit has a valid format' => ['nit', '12345678'],
    'nrc must be a string' => ['nrc', ['1234567']],
    'nrc has a valid format' => ['nrc', 'ABC123'],
    'commercial name is required' => ['commercial_name', null],
    'commercial name must be a string' => ['commercial_name', ['Rutely']],
    'commercial name has a maximum length' => ['commercial_name', str_repeat('a', 151)],
    'economic activity code is required' => ['economic_activity_code', null],
    'economic activity code must be a string' => ['economic_activity_code', ['62010']],
    'economic activity code must exist' => ['economic_activity_code', '999999'],
    'establishment type is required' => ['establishment_type', null],
    'establishment type must be a string' => ['establishment_type', ['01']],
    'establishment type must exist' => ['establishment_type', '99'],
    'departament is required' => ['departament_id', null],
    'departament must be a uuid' => ['departament_id', 'invalid-uuid'],
    'departament must exist' => ['departament_id', '00000000-0000-4000-8000-000000000001'],
    'municipality is required' => ['municipality_id', null],
    'municipality must be a uuid' => ['municipality_id', 'invalid-uuid'],
    'municipality must exist' => ['municipality_id', '00000000-0000-4000-8000-000000000002'],
    'district must be a uuid' => ['district_id', 'invalid-uuid'],
    'district must exist' => ['district_id', '00000000-0000-4000-8000-000000000003'],
    'email is required' => ['email', null],
    'email must be valid' => ['email', 'invalid-email'],
    'email has a maximum length' => ['email', str_repeat('a', 256).'@example.com'],
    'mh establishment code is required' => ['mh_establishment_code', null],
    'mh establishment code must be a string' => ['mh_establishment_code', ['0001']],
    'mh establishment code must have four characters' => ['mh_establishment_code', '001'],
    'mh pos code is required' => ['mh_pos_code', null],
    'mh pos code must be a string' => ['mh_pos_code', ['0001']],
    'mh pos code must have four characters' => ['mh_pos_code', '001'],
    'own establishment code is required' => ['own_establishment_code', null],
    'own establishment code must be a string' => ['own_establishment_code', ['0001']],
    'own establishment code must have four characters' => ['own_establishment_code', '001'],
    'own pos code is required' => ['own_pos_code', null],
    'own pos code must be a string' => ['own_pos_code', ['0001']],
    'own pos code must have four characters' => ['own_pos_code', '001'],
]);
