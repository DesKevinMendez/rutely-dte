<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('create', Company::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'nit' => ['required', 'string', 'regex:/^([0-9]{14}|[0-9]{9})$/'],
            'nrc' => ['nullable', 'string', 'regex:/^[0-9]{2,8}$/'],
            'commercial_name' => ['required', 'string', 'max:150'],
            'economic_activity_code' => ['required', 'string', 'exists:economic_activities,code'],
            'establishment_type' => ['required', 'string', 'exists:establishment_types,code'],
            'departament_id' => ['required', 'uuid', 'exists:departaments,id'],
            'municipality_id' => ['required', 'uuid', 'exists:municipalities,id'],
            'district_id' => ['nullable', 'uuid', 'exists:districts,id'],
            'email' => ['required', 'email', 'max:255'],
            'mh_establishment_code' => ['required', 'string', 'size:4'],
            'mh_pos_code' => ['required', 'string', 'size:4'],
            'own_establishment_code' => ['required', 'string', 'size:4'],
            'own_pos_code' => ['required', 'string', 'size:4'],
        ];
    }
}
