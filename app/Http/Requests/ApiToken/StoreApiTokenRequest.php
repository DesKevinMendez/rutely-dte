<?php

namespace App\Http\Requests\ApiToken;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->company_id !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
