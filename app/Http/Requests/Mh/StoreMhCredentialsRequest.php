<?php

namespace App\Http\Requests\Mh;

use App\Environment;
use App\Models\MhCredentials;
use App\Services\Dte\MhCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMhCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MhCredentials::class) ?? false;
    }

    /**
     * The NIT is written with hyphens in El Salvador, but Hacienda receives only its digits.
     */
    protected function prepareForValidation(): void
    {
        $nit = $this->input('nit');

        if (is_string($nit)) {
            $this->merge(['nit' => str_replace('-', '', $nit)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'environment' => ['required', 'string', Rule::enum(Environment::class)],
            'nit' => ['required', 'string', 'regex:'.MhCatalog::NIT_PATTERN],
            'pwd' => ['required', 'string'],
        ];
    }
}
