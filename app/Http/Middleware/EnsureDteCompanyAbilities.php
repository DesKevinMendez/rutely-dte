<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDteCompanyAbilities
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $principal = $request->user();

        if (! $principal || ! $principal->currentAccessToken()) {
            throw new AuthenticationException;
        }

        if ($principal instanceof User) {
            return $next($request);
        }

        if (! $principal instanceof Company || $abilities === []) {
            throw new AuthorizationException;
        }

        foreach ($abilities as $ability) {
            if (! $principal->tokenCan($ability)) {
                throw new AuthorizationException;
            }
        }

        return $next($request);
    }
}
