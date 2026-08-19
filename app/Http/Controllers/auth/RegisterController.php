<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\CommonResource;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request): CommonResource
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'company_id' => null,
            'role' => Role::ADMIN->value,
        ]);

        Mail::to($user)->send(new WelcomeMail($user));

        return CommonResource::make($user);
    }
}
