<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    Mail::fake();
});

function validRegisterPayload(): array
{
    return [
        'name' => 'Kevin Mendez',
        'email' => 'kevin@example.com',
        'phone' => '77778888',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];
}

test('a user can register and is assigned the admin role', function () {
    $payload = [
        ...validRegisterPayload(),
        'role' => Role::SUPERADMIN->value,
    ];

    $response = $this->postJson(route('register'), $payload);

    $response->assertCreated()
        ->assertExactJson([
            'data' => [
                'token' => $response->json('data.token'),
                'user' => [
                    'id' => $response->json('data.user.id'),
                    'company_id' => null,
                    'role' => Role::ADMIN->value,
                    'phone' => $payload['phone'],
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'created_at' => $response->json('data.user.created_at'),
                    'updated_at' => $response->json('data.user.updated_at'),
                ],
            ],
        ]);

    $userId = $response->json('data.user.id');

    $this->assertDatabaseHas(User::class, [
        'id' => $userId,
        'name' => $payload['name'],
        'email' => $payload['email'],
        'phone' => $payload['phone'],
        'company_id' => null,
        'role' => Role::ADMIN->value,
    ]);
    $this->assertDatabaseCount(User::class, 1);
    $this->assertDatabaseHas(PersonalAccessToken::class, [
        'tokenable_id' => $userId,
        'name' => 'register',
    ]);
    $this->assertDatabaseCount(PersonalAccessToken::class, 1);

    $user = User::query()->findOrFail($userId);

    expect(Hash::check($payload['password'], $user->password))->toBeTrue();
    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    Mail::assertQueued(WelcomeMail::class, function (WelcomeMail $mail) use ($payload, $userId): bool {
        return $mail->hasTo($payload['email'])
            && $mail->user->id === $userId
            && $mail->user->email === $payload['email'];
    });
});

test('welcome email contains the registered user information', function () {
    $user = User::factory()->make([
        'name' => 'Kevin Mendez',
        'email' => 'kevin@example.com',
    ]);

    $mailable = new WelcomeMail($user);

    $mailable->assertHasSubject('Bienvenido a Rutely')
        ->assertSeeInHtml('Kevin Mendez')
        ->assertSeeInHtml('kevin@example.com')
        ->assertSeeInText('Kevin Mendez')
        ->assertSeeInText('kevin@example.com');
});

test('phone may be null when registering', function () {
    $payload = validRegisterPayload();
    $payload['phone'] = null;

    $response = $this->postJson(route('register'), $payload);

    $response->assertCreated();

    $this->assertDatabaseHas(User::class, [
        'email' => $payload['email'],
        'phone' => null,
        'role' => Role::ADMIN->value,
    ]);
});

test('email must be unique when registering', function () {
    $existingUser = User::factory()->create([
        'email' => 'kevin@example.com',
    ]);

    $payload = validRegisterPayload();
    $payload['email'] = $existingUser->email;

    $this->postJson(route('register'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'El campo correo electrónico ya ha sido registrado.',
        ]);

    $this->assertDatabaseCount(User::class, 1);
});

test('password confirmation must match when registering', function () {
    $payload = validRegisterPayload();
    $payload['password_confirmation'] = 'different-password';

    $this->postJson(route('register'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'password' => 'La confirmación del campo contraseña no coincide.',
        ]);
});

test('register request validation returns 422', function (string $field, mixed $value, string $message) {
    $payload = validRegisterPayload();
    $payload[$field] = $value;

    $this->postJson(route('register'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'name is required' => ['name', null, 'El campo nombre es obligatorio.'],
    'name must be a string' => ['name', ['Kevin'], 'El campo nombre debe ser una cadena de caracteres.'],
    'name has a maximum length' => ['name', str_repeat('a', 256), 'El campo nombre no debe contener más de 255 caracteres.'],
    'email is required' => ['email', null, 'El campo correo electrónico es obligatorio.'],
    'email must be valid' => ['email', 'invalid-email', 'El campo correo electrónico debe ser una dirección de correo electrónico válida.'],
    'email has a maximum length' => ['email', str_repeat('a', 244).'@example.com', 'El campo correo electrónico no debe contener más de 255 caracteres.'],
    'phone must be a string' => ['phone', ['77778888'], 'El campo teléfono debe ser una cadena de caracteres.'],
    'phone has a minimum length' => ['phone', '1234567', 'El campo teléfono debe contener al menos 8 caracteres.'],
    'phone has a maximum length' => ['phone', str_repeat('1', 31), 'El campo teléfono no debe contener más de 30 caracteres.'],
    'password is required' => ['password', null, 'El campo contraseña es obligatorio.'],
    'password must be a string' => ['password', ['password123'], 'El campo contraseña debe ser una cadena de caracteres.'],
    'password has a minimum length' => ['password', '1234567', 'El campo contraseña debe contener al menos 8 caracteres.'],
]);
