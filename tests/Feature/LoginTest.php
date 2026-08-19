<?php

use App\Models\User;

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
        "data" => [
            "token" => $response->json('data.token'),
            "user" => [
                "id" => $response->json('data.user.id'),
                "company_id" => null,
                "role" => $response->json('data.user.role'),
                "phone" => $response->json('data.user.phone'),
                "name" => $response->json('data.user.name'),
                "email" => $response->json('data.user.email'),
                "email_verified_at" => $response->json('data.user.email_verified_at'),
                "created_at" => $response->json('data.user.created_at'),
                "updated_at" => $response->json('data.user.updated_at')
            ]
        ]
    ]);
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
            "message" => "Tus credenciales no coinciden con nuestros registros",
            "errors" => [
                "email" => [
                    "Tus credenciales no coinciden con nuestros registros"
                ]
            ]
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
