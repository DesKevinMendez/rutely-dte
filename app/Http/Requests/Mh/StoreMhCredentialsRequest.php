<?php

namespace App\Http\Requests\Mh;

use App\Environment;
use App\Models\MhCredentials;
use App\Services\Dte\MhCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The password format comes from the Manual Técnico V2.0, section 1.1.
 */
class StoreMhCredentialsRequest extends FormRequest
{
    private const PASSWORD_MIN_LENGTH = 13;

    private const PASSWORD_MAX_LENGTH = 25;

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
            'pwd' => [
                'required',
                'string',
                Password::min(self::PASSWORD_MIN_LENGTH)
                    ->max(self::PASSWORD_MAX_LENGTH)
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }
}
