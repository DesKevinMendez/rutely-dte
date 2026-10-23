<?php

use App\Http\Middleware\EnsureSuperadmin;
use App\Models\User;
use App\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

function superadminMiddlewareRequest(?Authenticatable $user): Request
{
    $request = Request::create('/api/v1/superadmin/companies');
    $request->setUserResolver(fn (): ?Authenticatable => $user);

    return $request;
}

test('allows superadmins and returns the next middleware response', function () {
    $user = new User(['role' => Role::SUPERADMIN->value]);
    $request = superadminMiddlewareRequest($user);
    $response = new Response('authorized');
    $nextWasCalled = false;

    $result = (new EnsureSuperadmin)->handle($request, function (Request $receivedRequest) use ($request, $response, &$nextWasCalled): Response {
        $nextWasCalled = true;
        expect($receivedRequest)->toBe($request);

        return $response;
    });

    expect($nextWasCalled)->toBeTrue()
        ->and($result)->toBe($response);
});

test('rejects users without the superadmin role', function (Role $role) {
    $request = superadminMiddlewareRequest(new User(['role' => $role->value]));
    $nextWasCalled = false;

    expect(fn () => (new EnsureSuperadmin)->handle($request, function (Request $request) use (&$nextWasCalled): Response {
        $nextWasCalled = true;

        return new Response;
    }))->toThrow(AuthorizationException::class);

    expect($nextWasCalled)->toBeFalse();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('rejects unauthenticated requests', function () {
    $request = superadminMiddlewareRequest(null);

    expect(fn () => (new EnsureSuperadmin)->handle($request, fn (Request $request): Response => new Response))
        ->toThrow(AuthorizationException::class);
});

test('rejects authenticated identities that are not application users', function () {
    $user = new GenericUser(['role' => Role::SUPERADMIN->value]);
    $request = superadminMiddlewareRequest($user);

    expect(fn () => (new EnsureSuperadmin)->handle($request, fn (Request $request): Response => new Response))
        ->toThrow(AuthorizationException::class);
});
