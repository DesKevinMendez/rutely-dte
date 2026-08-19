<?php

use App\Environment;
use App\Models\Company;
use App\Models\MhCredentials;
use App\Models\User;
use App\Role;
use App\Services\Mh\MhCertificateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function mhConfigurationCompany(): Company
{
    $departamentId = (string) Str::uuid();
    $municipalityId = (string) Str::uuid();

    DB::table('departaments')->insert(['id' => $departamentId, 'code' => '06', 'name' => 'San Salvador']);
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

function mhConfigurationAdmin(Company $company, Role $role = Role::ADMIN): User
{
    return User::factory()->create(['company_id' => $company->id, 'role' => $role->value]);
}

test('admin can store and show MH credentials without exposing the password', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $store = $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'pwd' => 'mh-secret',
    ]);
    $id = $store->json('data.id');
    $updatedAt = $store->json('data.updated_at');
    $expected = ['data' => [
        'id' => $id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'active' => true,
        'updated_at' => $updatedAt,
    ]];

    $store->assertOk()->assertExactJson($expected);
    expect(DB::table('mh_credentials')->where('id', $id)->value('password'))->not->toBe('mh-secret');

    $this->getJson(route('api.v1.mh-credentials.show', ['environment' => Environment::SANDBOX->value]))
        ->assertOk()
        ->assertExactJson($expected);
});

test('MH credentials are tenant isolated and update the environment singleton', function () {
    $company = mhConfigurationCompany();
    $otherCompany = Company::query()->create(array_merge($company->only([
        'address', 'phone', 'nrc', 'commercial_name', 'economic_activity_code', 'establishment_type',
        'departament_id', 'municipality_id', 'district_id', 'mh_establishment_code', 'mh_pos_code',
        'own_establishment_code', 'own_pos_code',
    ]), [
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]));

    MhCredentials::query()->create([
        'company_id' => $otherCompany->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $otherCompany->nit,
        'password' => 'other-secret',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $first = $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'pwd' => 'first-secret',
    ])->assertOk();
    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'pwd' => 'second-secret',
    ])->assertOk();

    $own = MhCredentials::query()->where('company_id', $company->id)->firstOrFail();
    expect(MhCredentials::query()->where('company_id', $company->id)->count())->toBe(1)
        ->and($own->password)->toBe('second-secret');

    $this->getJson(route('api.v1.mh-credentials.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $first->json('data.id'),
            'environment' => Environment::SANDBOX->value,
            'nit' => $company->nit,
            'active' => true,
            'updated_at' => $own->updated_at?->toJSON(),
        ]]);
});

test('operational user cannot manage MH credentials', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company, Role::USER));

    $this->getJson(route('api.v1.mh-credentials.show'))->assertForbidden();
    $this->postJson(route('api.v1.mh-credentials.store'), [])->assertForbidden();
});

test('MH credentials endpoints require authentication', function () {
    $this->getJson(route('api.v1.mh-credentials.show'))->assertUnauthorized();
    $this->postJson(route('api.v1.mh-credentials.store'), [])->assertUnauthorized();
});

test('store MH credentials request validation returns 422', function (array $payload, string $field, string $message) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->postJson(route('api.v1.mh-credentials.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'environment is required' => [['nit' => '06142812901015', 'pwd' => 'secret'], 'environment', 'El campo ambiente es obligatorio.'],
    'environment must be a string' => [['environment' => ['00'], 'nit' => '06142812901015', 'pwd' => 'secret'], 'environment', 'El campo ambiente debe ser una cadena de caracteres.'],
    'environment must be valid' => [['environment' => '99', 'nit' => '06142812901015', 'pwd' => 'secret'], 'environment', 'El valor seleccionado para ambiente no es válido.'],
    'nit is required' => [['environment' => '00', 'pwd' => 'secret'], 'nit', 'El campo NIT es obligatorio.'],
    'nit must be a string' => [['environment' => '00', 'nit' => ['0614'], 'pwd' => 'secret'], 'nit', 'El campo NIT debe ser una cadena de caracteres.'],
    'nit has a maximum length' => [['environment' => '00', 'nit' => str_repeat('1', 21), 'pwd' => 'secret'], 'nit', 'El campo NIT no debe contener más de 20 caracteres.'],
    'password is required' => [['environment' => '00', 'nit' => '06142812901015'], 'pwd', 'El campo contraseña de Hacienda es obligatorio.'],
    'password must be a string' => [['environment' => '00', 'nit' => '06142812901015', 'pwd' => ['secret']], 'pwd', 'El campo contraseña de Hacienda debe ser una cadena de caracteres.'],
    'password has a maximum length' => [['environment' => '00', 'nit' => '06142812901015', 'pwd' => str_repeat('a', 256)], 'pwd', 'El campo contraseña de Hacienda no debe contener más de 255 caracteres.'],
]);

test('show MH credentials environment validation returns 422', function (mixed $environment, string $message) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->getJson(route('api.v1.mh-credentials.show', ['environment' => $environment]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['environment' => $message]);
})->with([
    'must be a string' => [['00'], 'El campo ambiente debe ser una cadena de caracteres.'],
    'must be valid' => ['99', 'El valor seleccionado para ambiente no es válido.'],
]);

test('admin can store and show MH certificate metadata while material remains encrypted', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->mock(MhCertificateService::class)
        ->shouldReceive('validateForCompany')
        ->twice()
        ->with($company->id, '<certificado>fixture</certificado>', 'private-secret')
        ->andReturn($company->nit);

    $store = $this->postJson(route('api.v1.mh-certificates.store'), [
        'environment' => Environment::SANDBOX->value,
        'certificadoXml' => '<certificado>fixture</certificado>',
        'passwordPri' => 'private-secret',
    ]);
    $id = $store->json('data.id');
    $updatedAt = $store->json('data.updated_at');
    $expected = ['data' => [
        'id' => $id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'active' => true,
        'updated_at' => $updatedAt,
    ]];

    $store->assertOk()->assertExactJson($expected);
    $raw = DB::table('mh_certificates')->where('id', $id)->first();
    expect($raw->encrypted_certificate)->not->toBe('<certificado>fixture</certificado>')
        ->and($raw->encrypted_private_key_password)->not->toBe('private-secret');

    $this->getJson(route('api.v1.mh-certificates.show', ['environment' => Environment::SANDBOX->value]))
        ->assertOk()
        ->assertExactJson($expected);
});

test('operational user cannot manage MH certificates', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company, Role::USER));

    $this->getJson(route('api.v1.mh-certificates.show'))->assertForbidden();
    $this->postJson(route('api.v1.mh-certificates.store'), [])->assertForbidden();
});

test('MH certificate endpoints require authentication', function () {
    $this->getJson(route('api.v1.mh-certificates.show'))->assertUnauthorized();
    $this->postJson(route('api.v1.mh-certificates.store'), [])->assertUnauthorized();
});

test('store MH certificate request validation returns 422', function (array $payload, string $field, string $message) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->postJson(route('api.v1.mh-certificates.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'environment is required' => [['certificadoXml' => '<xml/>'], 'environment', 'El campo ambiente es obligatorio.'],
    'environment must be a string' => [['environment' => ['00'], 'certificadoXml' => '<xml/>'], 'environment', 'El campo ambiente debe ser una cadena de caracteres.'],
    'environment must be valid' => [['environment' => '99', 'certificadoXml' => '<xml/>'], 'environment', 'El valor seleccionado para ambiente no es válido.'],
    'certificate is required' => [['environment' => '00'], 'certificadoXml', 'El campo certificado de Hacienda es obligatorio.'],
    'certificate must be a string' => [['environment' => '00', 'certificadoXml' => ['xml']], 'certificadoXml', 'El campo certificado de Hacienda debe ser una cadena de caracteres.'],
    'private password must be a string' => [['environment' => '00', 'certificadoXml' => '<xml/>', 'passwordPri' => ['secret']], 'passwordPri', 'El campo contraseña privada debe ser una cadena de caracteres.'],
    'private password has a maximum length' => [['environment' => '00', 'certificadoXml' => '<xml/>', 'passwordPri' => str_repeat('a', 256)], 'passwordPri', 'El campo contraseña privada no debe contener más de 255 caracteres.'],
]);

test('invalid MH certificate material returns the signer adapter validation error', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->postJson(route('api.v1.mh-certificates.store'), [
        'environment' => '00',
        'certificadoXml' => '<invalid/>',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'certificadoXml' => 'El certificado de Hacienda no es válido o la contraseña privada es incorrecta.',
        ]);
});

test('show MH certificate environment validation returns 422', function (mixed $environment, string $message) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->getJson(route('api.v1.mh-certificates.show', ['environment' => $environment]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['environment' => $message]);
})->with([
    'must be a string' => [['00'], 'El campo ambiente debe ser una cadena de caracteres.'],
    'must be valid' => ['99', 'El valor seleccionado para ambiente no es válido.'],
]);
