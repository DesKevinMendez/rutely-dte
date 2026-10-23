<?php

use App\Jobs\SendCompanyAdminCredentials;
use App\Mail\CompanyAdminCredentialsMail;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validSuperadminCompanyUserPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ana Pérez',
        'email' => 'ana.'.Str::uuid().'@example.com',
        'phone' => '+503 1234 5678',
    ], $overrides);
}

test('superadmin can list only users assigned to the selected company', function () {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Otra empresa, S.A. de C.V.',
        'nit' => '06142812901016',
        'email' => 'otra@example.com',
    ]);
    $matchingUser = User::factory()->withRole(Role::ADMIN)->create([
        'company_id' => $company->id,
        'name' => 'Ana Pérez',
    ]);
    User::factory()->withRole(Role::USER)->create([
        'company_id' => $company->id,
        'name' => 'Luis Gómez',
    ]);
    User::factory()->withRole(Role::ADMIN)->create(['company_id' => $otherCompany->id]);
    $secondCompanyUser = User::query()
        ->where('company_id', $company->id)
        ->where('id', '!=', $matchingUser->id)
        ->firstOrFail();

    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.users.index', $company))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                $matchingUser->fresh()->load('roles')->toArray(),
                $secondCompanyUser->load('roles')->toArray(),
            ],
            'pagination' => [
                'total' => 2,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 2,
            ],
        ]);
});

test('superadmin can create an active company admin with a generated password', function (string $phone) {
    Queue::fake();
    $company = DteApiTestData::company();
    $superadmin = User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]);
    $payload = validSuperadminCompanyUserPayload(['phone' => $phone]);

    Sanctum::actingAs($superadmin);
    $response = $this->postJson(route('api.v1.superadmin.companies.users.store', $company), $payload);
    $user = User::query()->where('email', $payload['email'])->firstOrFail();

    $response->assertCreated()->assertExactJson([
        'data' => $user->fresh()->load('roles')->toArray(),
    ]);

    expect($user->company_id)->toBe($company->id)
        ->and($user->phone)->toBe('+50312345678')
        ->and($user->status)->toBeTrue()
        ->and($user->hasRole(Role::ADMIN->value))->toBeTrue()
        ->and($superadmin->fresh()->company_id)->toBeNull();

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'phone' => '+50312345678',
    ]);

    Queue::assertPushed(
        SendCompanyAdminCredentials::class,
        fn (SendCompanyAdminCredentials $job): bool => $job->user->is($user)
            && $job->company->is($company)
            && mb_strlen($job->password) === 16
            && Hash::check($job->password, $user->password),
    );
})->with([
    'country code without spaces' => '+50312345678',
    'country code with grouped spaces' => '+503 1234 5678',
]);

test('company admin credentials mail shows the generated login information', function () {
    $company = DteApiTestData::company(['name' => 'Empresa de prueba']);
    $user = User::factory()->withRole(Role::ADMIN)->make([
        'name' => 'Ana Pérez',
        'email' => 'ana@example.com',
    ]);
    $password = 'temporary-password-123';

    (new CompanyAdminCredentialsMail($user, $company, $password))
        ->assertHasSubject('Bienvenido a Rutely - Credenciales de tu cuenta')
        ->assertSeeInHtml('Empresa de prueba')
        ->assertSeeInHtml('Ana Pérez')
        ->assertSeeInHtml('ana@example.com')
        ->assertSeeInHtml($password)
        ->assertSeeInHtml('#/login');
});

test('company admin credentials job sends the mailable to the new user', function () {
    Mail::fake();
    $company = DteApiTestData::company();
    $user = User::factory()->withRole(Role::ADMIN)->create([
        'company_id' => $company->id,
    ]);
    $password = 'temporary-password-123';

    (new SendCompanyAdminCredentials($user, $company, $password))->handle();

    Mail::assertSent(
        CompanyAdminCredentialsMail::class,
        fn (CompanyAdminCredentialsMail $mail): bool => $mail->hasTo($user->email)
            && $mail->password === $password,
    );
});

test('phone may be omitted when creating a superadmin company user', function () {
    Queue::fake();
    $company = DteApiTestData::company();
    $payload = validSuperadminCompanyUserPayload();
    unset($payload['phone']);
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->postJson(route('api.v1.superadmin.companies.users.store', $company), $payload)
        ->assertCreated();

    $this->assertDatabaseHas(User::class, [
        'email' => $payload['email'],
        'company_id' => $company->id,
        'phone' => null,
    ]);
});

test('only superadmins can access company users', function (Role $role) {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->withRole($role)->create([
        'company_id' => $company->id,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.users.index', $company))->assertForbidden();
    $this->postJson(route('api.v1.superadmin.companies.users.store', $company), [])->assertForbidden();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('company users endpoints require authentication', function () {
    $company = DteApiTestData::company();

    $this->getJson(route('api.v1.superadmin.companies.users.index', $company))->assertUnauthorized();
    $this->postJson(route('api.v1.superadmin.companies.users.store', $company), [])->assertUnauthorized();
});

test('superadmin company user creation validates one rule at a time', function (string $field, mixed $value, string $message): void {
    $company = DteApiTestData::company();

    if ($field === 'email' && $value === 'existing@example.com') {
        User::factory()->withRole(Role::ADMIN)->create([
            'company_id' => $company->id,
            'email' => $value,
        ]);
    }

    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->postJson(
        route('api.v1.superadmin.companies.users.store', $company),
        validSuperadminCompanyUserPayload([$field => $value]),
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'name is required' => ['name', null, 'El campo nombre es obligatorio.'],
    'name must be a string' => ['name', ['Ana'], 'El campo nombre debe ser una cadena de caracteres.'],
    'name has a minimum length' => ['name', 'A', 'El campo nombre debe contener al menos 2 caracteres.'],
    'name has a maximum length' => ['name', str_repeat('a', 256), 'El campo nombre no debe contener más de 255 caracteres.'],
    'email is required' => ['email', null, 'El campo correo electrónico es obligatorio.'],
    'email must be a string' => ['email', ['ana@example.com'], 'El campo correo electrónico debe ser una cadena de caracteres.'],
    'email must be valid' => ['email', 'invalid-email', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'email has a maximum length' => ['email', str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 63).'.'.str_repeat('e', 20), 'El campo correo electrónico no debe contener más de 255 caracteres.'],
    'email must be unique' => ['email', 'existing@example.com', 'El campo correo electrónico ya ha sido registrado.'],
    'phone must be a string' => ['phone', ['12345678'], 'El campo teléfono debe ser una cadena de caracteres.'],
    'phone must match the Salvadoran format' => ['phone', '1234-5678', 'El formato del campo teléfono no es válido.'],
    'phone has a maximum length' => ['phone', str_repeat('1', 256), 'El campo teléfono no debe contener más de 255 caracteres.'],
    'password is prohibited' => ['password', 'client-password', 'El campo contraseña está prohibido.'],
    'company is prohibited' => ['company_id', (string) Str::uuid(), 'El campo empresa está prohibido.'],
    'role is prohibited' => ['role', Role::SUPERADMIN->value, 'El campo rol está prohibido.'],
    'status is prohibited' => ['status', false, 'El campo estado está prohibido.'],
]);
