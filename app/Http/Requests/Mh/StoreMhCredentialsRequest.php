<?php

namespace App\Http\Requests\Mh;

use App\Environment;
use App\Models\MhCredentials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMhCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MhCredentials::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'environment' => ['required', 'string', Rule::enum(Environment::class)],
            'nit' => ['required', 'string', 'min:1', 'max:20'],
            'pwd' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
