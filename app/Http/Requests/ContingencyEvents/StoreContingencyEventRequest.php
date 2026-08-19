<?php

namespace App\Http\Requests\ContingencyEvents;

use App\Models\ContingencyEvent;
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
            'dtes.*.tipoDte' => ['required', 'string', Rule::in(['01', '03', '04', '05', '06', '07', '08', '09', '11', '14', '15', '17', '18'])],
            'fInicio' => ['required', 'date_format:Y-m-d'],
            'hInicio' => ['required', 'date_format:H:i:s'],
            'fFin' => ['required', 'date_format:Y-m-d'],
            'hFin' => ['required', 'date_format:H:i:s'],
            'tipoContingencia' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'motivoContingencia' => ['sometimes', 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'dtes' => 'DTE',
            'dtes.*.codigoGeneracion' => 'código de generación',
            'dtes.*.tipoDte' => 'tipo de DTE',
            'fInicio' => 'fecha de inicio',
            'hInicio' => 'hora de inicio',
            'fFin' => 'fecha de fin',
            'hFin' => 'hora de fin',
            'tipoContingencia' => 'tipo de contingencia',
            'motivoContingencia' => 'motivo de contingencia',
        ];
    }
}
