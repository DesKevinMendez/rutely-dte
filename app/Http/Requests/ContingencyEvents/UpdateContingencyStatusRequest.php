<?php

namespace App\Http\Requests\ContingencyEvents;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContingencyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = Company::query()->find($this->user()?->company_id);

        return $company !== null && ($this->user()?->can('update', $company) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }
}
