<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'min:8', 'max:30'],
            'nit' => ['sometimes', 'required', 'string', 'regex:/^(?:\d{14}|\d{9})$/'],
            'nrc' => ['sometimes', 'nullable', 'string', 'regex:/^\d{2,8}$/'],
            'commercial_name' => ['sometimes', 'required', 'string', 'max:150'],
            'economic_activity_code' => ['sometimes', 'required', 'string', 'exists:economic_activities,code'],
            'establishment_type' => ['sometimes', 'required', 'string', 'exists:establishment_types,code'],
            'departament_id' => ['sometimes', 'required', 'uuid', 'exists:departaments,id'],
            'municipality_id' => ['sometimes', 'required', 'uuid', 'exists:municipalities,id'],
            'district_id' => ['sometimes', 'nullable', 'uuid', 'exists:districts,id'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'mh_establishment_code' => ['sometimes', 'required', 'string', 'size:4'],
            'mh_pos_code' => ['sometimes', 'required', 'string', 'size:4'],
            'own_establishment_code' => ['sometimes', 'required', 'string', 'size:4'],
            'own_pos_code' => ['sometimes', 'required', 'string', 'size:4'],
        ];
    }
}
