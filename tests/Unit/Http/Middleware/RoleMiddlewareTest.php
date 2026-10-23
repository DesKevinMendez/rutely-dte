<?php

use App\Models\User;
use App\Role;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

function roleMiddlewareRequest(?Authenticatable $user): Request
{
    Auth::forgetGuards();

    if ($user !== null) {
        Auth::guard('web')->setUser($user);
    }

    return Request::create('/api/v1/superadmin/companies');
}

test('allows users with the required Spatie role', function () {
    $user = User::factory()->create(['role' => Role::SUPERADMIN->value]);
    $request = roleMiddlewareRequest($user);
    $response = new Response('authorized');
    $nextWasCalled = false;

    $result = (new RoleMiddleware)->handle($request, function (Request $receivedRequest) use ($request, $response, &$nextWasCalled): Response {
        $nextWasCalled = true;
        expect($receivedRequest)->toBe($request);

        return $response;
    }, Role::SUPERADMIN->value);

    expect($nextWasCalled)->toBeTrue()
        ->and($result)->toBe($response);
});

test('rejects users without the required Spatie role', function (Role $role) {
    $user = User::factory()->create(['role' => $role->value]);
    $request = roleMiddlewareRequest($user);
    $nextWasCalled = false;

    expect(fn () => (new RoleMiddleware)->handle($request, function (Request $request) use (&$nextWasCalled): Response {
        $nextWasCalled = true;

        return new Response;
    }, Role::SUPERADMIN->value))->toThrow(UnauthorizedException::class);

    expect($nextWasCalled)->toBeFalse();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('rejects unauthenticated requests', function () {
    $request = roleMiddlewareRequest(null);

    expect(fn () => (new RoleMiddleware)->handle($request, fn (Request $request): Response => new Response, Role::SUPERADMIN->value))
        ->toThrow(UnauthorizedException::class);
});

test('rejects authenticated identities that do not have the HasRoles trait', function () {
    $user = new class extends AuthenticatableUser {};
    $request = roleMiddlewareRequest($user);

    expect(fn () => (new RoleMiddleware)->handle($request, fn (Request $request): Response => new Response, Role::SUPERADMIN->value))
        ->toThrow(UnauthorizedException::class);
});
