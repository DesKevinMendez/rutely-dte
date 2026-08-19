<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['sometimes', 'nullable', 'string', 'min:8', 'max:30'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'max:255'],
            'role' => ['sometimes', 'required', 'string', Rule::in([Role::ADMIN->value, Role::USER->value])],
            'status' => ['sometimes', 'required', 'boolean'],
            'company_id' => ['prohibited'],
        ];
    }
}
