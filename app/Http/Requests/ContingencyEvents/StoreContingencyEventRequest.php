<?php

namespace App\Http\Requests\ContingencyEvents;

use App\Models\ContingencyEvent;
use App\Models\DteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContingencyEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ContingencyEvent::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'dtes' => ['required', 'array', 'min:1', 'max:100'],
            'dtes.*.codigoGeneracion' => ['required', 'uuid'],
            'dtes.*.tipoDte' => ['required', 'string', Rule::exists(DteType::class, 'code')],
            'fInicio' => ['required', 'date_format:Y-m-d'],
            'hInicio' => ['required', 'date_format:H:i:s'],
            'fFin' => ['required', 'date_format:Y-m-d'],
            'hFin' => ['required', 'date_format:H:i:s'],
            'tipoContingencia' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'motivoContingencia' => ['sometimes', 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }
}
