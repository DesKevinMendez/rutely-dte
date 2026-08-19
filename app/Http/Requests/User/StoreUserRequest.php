<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'min:8', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['sometimes', 'string', Rule::in([Role::ADMIN->value, Role::USER->value])],
            'status' => ['sometimes', 'boolean'],
            'company_id' => ['prohibited'],
        ];
    }
}
