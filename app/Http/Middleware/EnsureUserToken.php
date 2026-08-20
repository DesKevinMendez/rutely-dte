<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class EnsureUserToken
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\AuthenticationException|\Illuminate\Auth\Access\AuthorizationException
     */
    public function handle($request, $next)
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new AuthenticationException;
        }

        if (! ($request->user() instanceof User)) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
