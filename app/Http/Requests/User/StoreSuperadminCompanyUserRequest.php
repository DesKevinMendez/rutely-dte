<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuperadminCompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasRole(Role::SUPERADMIN->value);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['bail', 'required', 'string', 'max:255', 'email', Rule::unique(User::class, 'email')],
            'phone' => ['bail', 'nullable', 'string', 'min:8', 'max:30'],
            'password' => ['prohibited'],
            'company_id' => ['prohibited'],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
