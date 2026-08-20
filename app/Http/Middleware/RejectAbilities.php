<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenAbilityException;
use App\Models\Company;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class RejectAbilities
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$abilities
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\AuthenticationException|\Illuminate\Auth\Access\AuthorizationException|\App\Exceptions\ForbiddenAbilityException
     */
    public function handle($request, $next, ...$abilities)
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new AuthenticationException;
        }

        if ($request->user() instanceof Company) {
            throw new AuthorizationException('Company tokens are not allowed to access this resource.');
        }

        foreach ($abilities as $ability) {
            if (in_array(
                $ability,
                $request->user()->currentAccessToken()->abilities ?? [],
                true,
            )) {
                throw new ForbiddenAbilityException($ability);
            }
        }

        return $next($request);
    }
}
