<?php

namespace App\Http\Requests\ApiToken;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['sometimes', 'bail', 'array', 'min:1'],
            'abilities.*' => [
                'bail',
                'required',
                'string',
                'distinct',
                Rule::in(['create:dte', 'list:dte']),
            ],
        ];
    }
}
