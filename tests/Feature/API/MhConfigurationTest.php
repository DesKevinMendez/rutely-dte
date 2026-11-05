<?php

use App\Environment;
use App\Models\Company;
use App\Models\MhCertificates;
use App\Models\MhCredentials;
use App\Models\User;
use App\Role;
use App\Services\Mh\MhAuthService;
use App\Services\Mh\MhCertificateService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Rutely\DteSigned\Certificate\MhCertificate;

beforeEach(function () {
    Http::preventStrayRequests();
});

/**
 * @param  array<string, mixed>  $body
 */
function fakeMhLogin(array $body = ['status' => 'OK', 'body' => ['token' => 'Bearer mh-token']], int $status = 200): void
{
    Http::fake(['https://apitest.dtes.mh.gob.sv/seguridad/auth' => Http::response($body, $status)]);
}

/**
 * @return array{data: array<string, mixed>}
 */
function mhCredentialsJson(Company $company, string $loginStatus): array
{
    $credentials = MhCredentials::query()->where('company_id', $company->id)->firstOrFail();

    return ['data' => [
        'id' => $credentials->id,
        'environment' => $credentials->environment->value,
        'nit' => $credentials->nit,
        'active' => $credentials->active,
        'login_status' => $loginStatus,
        'updated_at' => $credentials->updated_at?->toJSON(),
    ]];
}

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

function mhConfigurationOtherCompany(Company $company): Company
{
    return Company::query()->create(array_merge($company->only([
        'address', 'phone', 'nrc', 'commercial_name', 'economic_activity_code', 'establishment_type',
        'departament_id', 'municipality_id', 'district_id', 'mh_establishment_code', 'mh_pos_code',
        'own_establishment_code', 'own_pos_code',
    ]), [
        'name' => 'Other Company',
        'nit' => '06142812901016',
        'email' => 'other@rutely.biz',
    ]));
}

function mhConfigurationAdmin(Company $company, Role $role = Role::ADMIN): User
{
    return User::factory()->withRole($role)->create(['company_id' => $company->id]);
}

test('admin can store and show MH credentials without exposing the password', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    fakeMhLogin();

    $store = $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Prueba-01',
    ]);
    $id = $store->json('data.id');
    $updatedAt = $store->json('data.updated_at');
    $expected = ['data' => [
        'id' => $id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'active' => true,
        'login_status' => 'authenticated',
        'updated_at' => $updatedAt,
    ]];

    $store->assertOk()->assertExactJson($expected);
    expect(DB::table('mh_credentials')->where('id', $id)->value('password'))->not->toBe('Hacienda-Prueba-01');

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
    fakeMhLogin();

    $first = $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Primera-01',
    ])->assertOk();
    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Segunda-02',
    ])->assertOk();

    $own = MhCredentials::query()->where('company_id', $company->id)->firstOrFail();
    expect(MhCredentials::query()->where('company_id', $company->id)->count())->toBe(1)
        ->and($own->password)->toBe('Hacienda-Segunda-02');

    $this->getJson(route('api.v1.mh-credentials.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $first->json('data.id'),
            'environment' => Environment::SANDBOX->value,
            'nit' => $company->nit,
            'active' => true,
            'login_status' => 'authenticated',
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
    'environment is required' => [['nit' => '06142812901015', 'pwd' => 'Hacienda-Prueba-01'], 'environment', 'El campo ambiente es obligatorio.'],
    'environment must be a string' => [['environment' => ['00'], 'nit' => '06142812901015', 'pwd' => 'Hacienda-Prueba-01'], 'environment', 'El campo ambiente debe ser una cadena de caracteres.'],
    'environment must be valid' => [['environment' => '99', 'nit' => '06142812901015', 'pwd' => 'Hacienda-Prueba-01'], 'environment', 'El valor seleccionado para ambiente no es válido.'],
    'nit is required' => [['environment' => '00', 'pwd' => 'Hacienda-Prueba-01'], 'nit', 'El campo NIT es obligatorio.'],
    'nit must be a string' => [['environment' => '00', 'nit' => ['0614'], 'pwd' => 'Hacienda-Prueba-01'], 'nit', 'El campo NIT debe ser una cadena de caracteres.'],
    'nit with letters' => [['environment' => '00', 'nit' => '0614281290101A', 'pwd' => 'Hacienda-Prueba-01'], 'nit', 'El formato del campo NIT no es válido.'],
    'nit with an invalid length' => [['environment' => '00', 'nit' => str_repeat('1', 21), 'pwd' => 'Hacienda-Prueba-01'], 'nit', 'El formato del campo NIT no es válido.'],
    'password is required' => [['environment' => '00', 'nit' => '06142812901015'], 'pwd', 'El campo contraseña del Usuario API es obligatorio.'],
    'password must be a string' => [['environment' => '00', 'nit' => '06142812901015', 'pwd' => ['secret']], 'pwd', 'El campo contraseña del Usuario API debe ser una cadena de caracteres.'],
]);

test('store MH credentials stores and sends to Hacienda only the digits of a NIT written with hyphens', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    fakeMhLogin();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => '0614-281290-101-5',
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk()->assertJsonPath('data.nit', '06142812901015');

    expect(MhCredentials::query()->where('company_id', $company->id)->value('nit'))->toBe('06142812901015');
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->isForm()
        && $request['user'] === '06142812901015'
        && $request['pwd'] === 'Hacienda-Prueba-01');
});

test('store MH credentials logs in to the Hacienda environment of the credentials', function (Environment $environment, string $url) {
    Sanctum::actingAs(mhConfigurationAdmin(mhConfigurationCompany()));
    Http::fake([$url => Http::response(['status' => 'OK', 'body' => ['token' => 'mh-token']])]);

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => $environment->value,
        'nit' => '06142812901015',
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk()->assertJsonPath('data.login_status', 'authenticated');

    Http::assertSent(fn (Request $request): bool => $request->url() === $url);
})->with([
    'sandbox' => [Environment::SANDBOX, 'https://apitest.dtes.mh.gob.sv/seguridad/auth'],
    'production' => [Environment::PRODUCTION, 'https://api.dtes.mh.gob.sv/seguridad/auth'],
]);

test('store MH credentials does not save what Hacienda rejects', function (array $body, int $status) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Exceptions::fake();
    fakeMhLogin($body, $status);

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Incorrecta-01',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pwd' => 'Hacienda rechazó el NIT o la contraseña del Usuario API. Las credenciales no se guardaron.']);

    expect(MhCredentials::query()->where('company_id', $company->id)->exists())->toBeFalse();
    $this->getJson(route('api.v1.mh-credentials.show'))->assertNotFound();
    Exceptions::assertReportedCount(1);
})->with([
    'controlled error' => [['status' => 'ERROR', 'error' => 'Unauthorized', 'message' => 'Usuario no valido'], 200],
    'unauthorized' => [['status' => 'ERROR', 'message' => 'Usuario no valido'], 401],
]);

test('store MH credentials does not save when Hacienda is unavailable', function (Closure $failure) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Exceptions::fake();
    Http::fake(['https://apitest.dtes.mh.gob.sv/seguridad/auth' => $failure()]);

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Prueba-01',
    ])
        ->assertServiceUnavailable()
        ->assertExactJson(['message' => 'No se pudo contactar a Hacienda para comprobar el login. Las credenciales no se guardaron; inténtelo de nuevo.']);

    expect(MhCredentials::query()->where('company_id', $company->id)->exists())->toBeFalse();
    Exceptions::assertReportedCount(1);
})->with([
    'server error' => fn () => fn () => Http::response('Service Unavailable', 503),
    'connection failure' => fn () => fn () => Http::failedConnection(),
]);

test('store MH credentials keeps the working ones when Hacienda rejects the new ones', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Exceptions::fake();
    Http::fakeSequence('https://apitest.dtes.mh.gob.sv/seguridad/auth')
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token']])
        ->push(['status' => 'ERROR', 'message' => 'Usuario no valido'], 401);

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Correcta-01',
    ])->assertOk();
    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Incorrecta-02',
    ])->assertUnprocessable();

    expect(MhCredentials::query()->where('company_id', $company->id)->firstOrFail()->password)->toBe('Hacienda-Correcta-01')
        ->and(app(MhAuthService::class)->token($company->id, Environment::SANDBOX))->toBe('mh-token');
    $this->getJson(route('api.v1.mh-credentials.show'))
        ->assertOk()
        ->assertExactJson(mhCredentialsJson($company, 'authenticated'));
    Http::assertSentCount(2);
});

test('store MH credentials sends again a password that was rejected, since nothing was saved', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Exceptions::fake();
    Http::fakeSequence('https://apitest.dtes.mh.gob.sv/seguridad/auth')
        ->push(['status' => 'ERROR', 'message' => 'Usuario no valido'], 401)
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token']]);
    $payload = ['environment' => '00', 'nit' => $company->nit, 'pwd' => 'Hacienda-Prueba-01'];

    $this->postJson(route('api.v1.mh-credentials.store'), $payload)->assertUnprocessable();
    $this->postJson(route('api.v1.mh-credentials.store'), $payload)
        ->assertOk()
        ->assertExactJson(mhCredentialsJson($company, 'authenticated'));

    Http::assertSentCount(2);
});

test('store MH credentials logs in again when a record exists and updates it along with the session', function (string $password) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fakeSequence('https://apitest.dtes.mh.gob.sv/seguridad/auth')
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token-1']])
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token-2']]);

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk();
    $firstUpdatedAt = MhCredentials::query()->where('company_id', $company->id)->firstOrFail()->updated_at;

    $this->travel(1)->hours();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => $password,
    ])->assertOk()->assertExactJson(mhCredentialsJson($company, 'authenticated'));

    $credentials = MhCredentials::query()->where('company_id', $company->id)->sole();

    expect($credentials->password)->toBe($password)
        ->and($credentials->updated_at->greaterThan($firstUpdatedAt))->toBeTrue()
        ->and(app(MhAuthService::class)->token($company->id, Environment::SANDBOX))->toBe('mh-token-2');
    Http::assertSentCount(2);
})->with([
    'same password' => ['Hacienda-Prueba-01'],
    'new password' => ['Hacienda-Nueva-02'],
]);

test('store MH credentials is forbidden with a NIT registered to another company', function (string $nit) {
    $company = mhConfigurationCompany();
    $otherCompany = mhConfigurationOtherCompany($company);
    MhCredentials::query()->create([
        'company_id' => $otherCompany->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => '012345678',
        'password' => 'other-secret',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fake();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $nit,
        'pwd' => 'Hacienda-Prueba-01',
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'No está autorizado para registrar credenciales con este NIT.']);

    Http::assertNothingSent();
    expect(MhCredentials::query()->where('company_id', $company->id)->exists())->toBeFalse()
        ->and(MhCredentials::query()->where('company_id', $otherCompany->id)->sole()->password)->toBe('other-secret');
})->with([
    'NIT of the company' => ['06142812901016'],
    'NIT of the company written with hyphens' => ['0614-281290-101-6'],
    'NIT stored in the credentials of the company' => ['012345678'],
]);

test('store MH credentials validates the payload before checking who the NIT belongs to', function () {
    $company = mhConfigurationCompany();
    mhConfigurationOtherCompany($company);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fake();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => '06142812901016',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pwd' => 'El campo contraseña del Usuario API es obligatorio.']);

    Http::assertNothingSent();
});

test('store MH credentials accepts a NIT that only the own company has registered', function () {
    $company = mhConfigurationCompany();
    mhConfigurationOtherCompany($company);
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::PRODUCTION->value,
        'nit' => '012345678',
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    fakeMhLogin();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => '012345678',
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk()->assertJsonPath('data.nit', '012345678');
});

test('store MH credentials accepts the same NIT of a company in sandbox and production', function (string $firstEnvironment, string $secondEnvironment) {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fake(['*/seguridad/auth' => Http::response(['status' => 'OK', 'body' => ['token' => 'mh-token']])]);

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => $firstEnvironment,
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk();
    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => $secondEnvironment,
        'nit' => '0614-281290-101-5',
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk();

    expect(MhCredentials::query()->where('company_id', $company->id)->where('nit', $company->nit)->count())->toBe(2);

    foreach ([$firstEnvironment, $secondEnvironment] as $environment) {
        $this->getJson(route('api.v1.mh-credentials.show', ['environment' => $environment]))
            ->assertOk()
            ->assertJson(['data' => [
                'environment' => $environment,
                'nit' => $company->nit,
                'login_status' => 'authenticated',
            ]]);
    }

    Http::assertSentCount(2);
})->with([
    'sandbox first' => ['00', '01'],
    'production first' => ['01', '00'],
]);

test('store MH credentials is forbidden with a NIT different from the other environment of the company', function (Environment $otherEnvironment, string $environment) {
    $company = mhConfigurationCompany();
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => $otherEnvironment->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fake();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => $environment,
        'nit' => '012345678',
        'pwd' => 'Hacienda-Prueba-01',
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'El NIT debe ser el mismo en los ambientes de pruebas y producción.']);

    Http::assertNothingSent();
    expect(MhCredentials::query()->where('company_id', $company->id)->sole()->nit)->toBe($company->nit);
})->with([
    'production differs from sandbox' => [Environment::SANDBOX, '01'],
    'sandbox differs from production' => [Environment::PRODUCTION, '00'],
]);

test('store MH credentials lets a company change the NIT while the other environment is empty', function () {
    $company = mhConfigurationCompany();
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    fakeMhLogin();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => '012345678',
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk()->assertExactJson(mhCredentialsJson($company, 'authenticated'));

    expect(MhCredentials::query()->where('company_id', $company->id)->sole()->nit)->toBe('012345678');
});

test('store MH credentials reports a NIT of another company before comparing it with the other environment', function () {
    $company = mhConfigurationCompany();
    mhConfigurationOtherCompany($company);
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fake();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '01',
        'nit' => '06142812901016',
        'pwd' => 'Hacienda-Prueba-01',
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'No está autorizado para registrar credenciales con este NIT.']);

    Http::assertNothingSent();
});

test('store MH credentials lifts the rejection of stored credentials once Hacienda accepts them', function () {
    $company = mhConfigurationCompany();
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fakeSequence('https://apitest.dtes.mh.gob.sv/seguridad/auth')
        ->push(['status' => 'ERROR', 'message' => 'Usuario bloqueado'], 401)
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token']])
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token-2']]);
    $service = app(MhAuthService::class);

    expect(fn () => $service->token($company->id, Environment::SANDBOX))
        ->toThrow(RuntimeException::class, 'Hacienda rechazó las credenciales');
    $this->getJson(route('api.v1.mh-credentials.show'))
        ->assertOk()
        ->assertExactJson(mhCredentialsJson($company, 'rejected'));

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk()->assertExactJson(mhCredentialsJson($company, 'authenticated'));

    $this->travel(24)->hours();

    expect($service->token($company->id, Environment::SANDBOX))->toBe('mh-token-2');
    Http::assertSentCount(3);
});

test('store MH credentials is limited to five attempts per minute', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    fakeMhLogin();
    $payload = ['environment' => '00', 'nit' => $company->nit, 'pwd' => 'Hacienda-Prueba-01'];

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.mh-credentials.store'), $payload)->assertOk();
    }

    $this->postJson(route('api.v1.mh-credentials.store'), $payload)->assertTooManyRequests();

    Http::assertSentCount(5);
});

test('show MH credentials reports them as unverified without contacting Hacienda', function () {
    $company = mhConfigurationCompany();
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));
    Http::fake();

    $this->getJson(route('api.v1.mh-credentials.show'))
        ->assertOk()
        ->assertExactJson(mhCredentialsJson($company, 'unverified'));

    Http::assertNothingSent();
});

test('show MH credentials stops reporting the login as authenticated when the token expires', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    fakeMhLogin();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $company->nit,
        'pwd' => 'Hacienda-Prueba-01',
    ])->assertOk()->assertJsonPath('data.login_status', 'authenticated');

    $this->travel(24)->hours();

    $this->getJson(route('api.v1.mh-credentials.show'))
        ->assertOk()
        ->assertExactJson(mhCredentialsJson($company, 'unverified'));
});

test('store MH credentials accepts a 9 digit NIT and any non-empty password', function (string $nit, string $password) {
    Sanctum::actingAs(mhConfigurationAdmin(mhConfigurationCompany()));
    fakeMhLogin();

    $this->postJson(route('api.v1.mh-credentials.store'), [
        'environment' => '00',
        'nit' => $nit,
        'pwd' => $password,
    ])->assertOk()->assertJsonPath('data.nit', $nit);
})->with([
    '9 digit NIT' => ['012345678', 'Hacienda-Prueba-01'],
    'single character password' => ['06142812901015', 'x'],
    'password longer than 25 characters' => ['06142812901015', str_repeat('x', 300)],
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

test('MH certificates are tenant isolated', function () {
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
    $otherCertificate = MhCertificates::query()->create([
        'company_id' => $otherCompany->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $otherCompany->nit,
        'encrypted_certificate' => '<certificado>other</certificado>',
        'encrypted_private_key_password' => 'other-secret',
        'active' => true,
    ]);
    Sanctum::actingAs(mhConfigurationAdmin($company));

    $this->getJson(route('api.v1.mh-certificates.show'))->assertNotFound();

    $this->mock(MhCertificateService::class)
        ->shouldReceive('validateForCompany')
        ->twice()
        ->with($company->id, '<certificado>own</certificado>', 'own-secret')
        ->andReturn($company->nit);

    $store = $this->postJson(route('api.v1.mh-certificates.store'), [
        'company_id' => $otherCompany->id,
        'environment' => Environment::SANDBOX->value,
        'certificadoXml' => '<certificado>own</certificado>',
        'passwordPri' => 'own-secret',
    ])->assertOk();

    $ownCertificate = MhCertificates::query()->findOrFail($store->json('data.id'));
    $otherCertificate->refresh();

    expect($ownCertificate->company_id)->toBe($company->id)
        ->and($ownCertificate->id)->not->toBe($otherCertificate->id)
        ->and($otherCertificate->company_id)->toBe($otherCompany->id)
        ->and($otherCertificate->encrypted_certificate)->toBe('<certificado>other</certificado>')
        ->and($otherCertificate->encrypted_private_key_password)->toBe('other-secret');

    $this->getJson(route('api.v1.mh-certificates.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $ownCertificate->id,
            'environment' => Environment::SANDBOX->value,
            'nit' => $company->nit,
            'active' => true,
            'updated_at' => $ownCertificate->updated_at?->toJSON(),
        ]]);
});

test('MH certificate secrets are never serialized', function () {
    $certificate = MhCertificates::query()->create([
        'company_id' => mhConfigurationCompany()->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => '06142812901015',
        'encrypted_certificate' => '<certificado>fixture</certificado>',
        'encrypted_private_key_password' => 'private-secret',
        'active' => true,
    ]);

    expect($certificate->toArray())->not->toHaveKeys(['encrypted_certificate', 'encrypted_private_key_password'])
        ->and($certificate->toJson())->not->toContain('fixture')->not->toContain('private-secret')
        ->and($certificate->encrypted_certificate)->toBe('<certificado>fixture</certificado>')
        ->and($certificate->encrypted_private_key_password)->toBe('private-secret');
});

test('stack traces expose only the first six characters of the certificate password', function () {
    $previousIgnoreArgs = ini_set('zend.exception_ignore_args', '0');
    $trace = '';

    try {
        MhCertificate::fromXml('<invalid/>', 'SECRET-PASSWORD-123');
    } catch (Throwable $exception) {
        $trace = $exception->getTraceAsString();
    }

    ini_set('zend.exception_ignore_args', (string) $previousIgnoreArgs);

    expect($trace)->toContain("'SECRET...'")->not->toContain('SECRET-');
});

test('MH certificate of exactly 2 MB passes the size rule', function () {
    $company = mhConfigurationCompany();
    Sanctum::actingAs(mhConfigurationAdmin($company));
    $certificateXml = str_repeat('a', 2097152);

    $this->mock(MhCertificateService::class)
        ->shouldReceive('validateForCompany')
        ->twice()
        ->with($company->id, $certificateXml, 'private-secret')
        ->andReturn($company->nit);

    $this->postJson(route('api.v1.mh-certificates.store'), [
        'environment' => Environment::SANDBOX->value,
        'certificadoXml' => $certificateXml,
        'passwordPri' => 'private-secret',
    ])->assertOk();
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
    'certificate cannot exceed 2 MB' => [['environment' => '00', 'certificadoXml' => str_repeat('a', 2097153)], 'certificadoXml', 'El certificado de Hacienda no debe pesar más de 2 MB.'],
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
