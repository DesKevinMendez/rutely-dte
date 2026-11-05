<?php

use App\Models\User;
use App\Role;

/** @return array{email: string, password: string, device_name: string} */
function loginPayload(string $email, string $password = 'wrong-password'): array
{
    return [
        'email' => $email,
        'password' => $password,
        'device_name' => 'test-device',
    ];
}

test('an user can do login', function () {
    $user = User::factory()->create();

    $route = route('login');
    $response = $this->post($route, [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertOk();

    $response->assertExactJson([
        'data' => [
            'token' => $response->json('data.token'),
            'user' => [
                'id' => $response->json('data.user.id'),
                'company_id' => $user->company_id,
                'company' => null,
                'roles' => $response->json('data.user.roles'),
                'phone' => $response->json('data.user.phone'),
                'name' => $response->json('data.user.name'),
                'email' => $response->json('data.user.email'),
                'email_verified_at' => $response->json('data.user.email_verified_at'),
                'created_at' => $response->json('data.user.created_at'),
                'updated_at' => $response->json('data.user.updated_at'),
            ],
        ],
    ]);
    $response->assertJsonPath('data.user.roles.0.name', Role::ADMIN->value);
});

test('a superadmin can log in without a company', function () {
    $superadmin = User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]);

    $response = $this->post(route('login'), [
        'email' => $superadmin->email,
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertOk()->assertJson([
        'data' => [
            'user' => [
                'company_id' => null,
            ],
        ],
    ])->assertJsonPath('data.user.roles.0.name', Role::SUPERADMIN->value);
});

test('an user cannot do login with wrong credentials', function () {
    $user = User::factory()->create();

    $route = route('login');
    $response = $this->post($route, [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable()
        ->assertExactJson([
            'message' => 'Tus credenciales no coinciden con nuestros registros',
            'errors' => [
                'email' => [
                    'Tus credenciales no coinciden con nuestros registros',
                ],
            ],
        ]);
});

test('email should be required', function () {
    $route = route('login');
    $response = $this->post($route, [
        'email' => '',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email' => 'El campo correo electrónico es obligatorio.']);
});

test('email should be an email valid', function () {
    $route = route('login');
    $response = $this->post($route, [
        'email' => 'invalid-email',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email' => 'El campo correo electrónico debe ser una dirección de correo electrónico válida.']);
});

test('password should be required', function () {
    $route = route('login');
    $response = $this->post($route, [
        'email' => 'user@example.com',
        'password' => '',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['password' => 'El campo contraseña es obligatorio.']);
});

test('login is blocked after five attempts, even with the right password', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), loginPayload($user->email))->assertUnprocessable();
    }

    $this->postJson(route('login'), loginPayload($user->email, 'password'))
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJson(['message' => 'Too Many Attempts.']);
});

test('successful logins count toward the login rate limit', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), loginPayload($user->email, 'password'))->assertOk();
    }

    $this->postJson(route('login'), loginPayload($user->email, 'password'))->assertTooManyRequests();
});

test('login rate limit also blocks attempts that rotate the email from the same IP address', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), loginPayload(fake()->unique()->safeEmail()))->assertUnprocessable();
    }

    $this->postJson(route('login'), loginPayload($user->email, 'password'))->assertTooManyRequests();
});

test('login rate limit does not block another IP address', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), loginPayload($user->email))->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->postJson(route('login'), loginPayload($user->email, 'password'))
        ->assertOk();
});

test('login rate limit expires after a minute', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), loginPayload($user->email))->assertUnprocessable();
    }

    $this->travel(61)->seconds();

    $this->postJson(route('login'), loginPayload($user->email, 'password'))->assertOk();
});

test('login with a non string email returns a validation error', function () {
    $this->postJson(route('login'), [
        'email' => ['user@example.com'],
        'password' => 'password',
        'device_name' => 'test-device',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'El campo correo electrónico debe ser una dirección de correo electrónico válida.']);
});
