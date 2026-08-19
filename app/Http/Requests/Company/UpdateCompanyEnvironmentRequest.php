<?php

namespace App\Http\Requests\Company;

use App\Environment;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && ($this->user()?->can('update', $company) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'environment' => ['required', 'string', Rule::enum(Environment::class)],
        ];
    }
}
