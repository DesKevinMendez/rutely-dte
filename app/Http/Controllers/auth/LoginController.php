<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\CommonResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request)
    {
        $validate = $request->validated();

        $user = User::where('email', $validate['email'])->first();

        if (! $user || ! Hash::check($validate['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Tus credenciales no coinciden con nuestros registros'],
            ]);
        }

        $token = $user->createToken($validate['device_name'])->plainTextToken;

        return CommonResource::make([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
