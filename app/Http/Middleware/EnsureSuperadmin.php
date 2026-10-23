<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Role;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! ($user instanceof User) || $user->role !== Role::SUPERADMIN->value) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
