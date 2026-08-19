<?php

namespace App\Http\Requests\Mh;

use App\Environment;
use App\Models\MhCredentials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowMhCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MhCredentials::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'environment' => ['sometimes', 'string', Rule::enum(Environment::class)],
        ];
    }
}
