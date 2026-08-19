<?php

namespace App\Http\Requests\Dte;

use App\Models\Dte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDteInvalidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dte = $this->route('dte');

        return $dte instanceof Dte && ($this->user()?->can('invalidate', $dte) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipoAnulacion' => ['sometimes', 'integer', Rule::in([1, 2, 3])],
            'motivoAnulacion' => ['sometimes', 'string', 'min:5', 'max:500'],
            'nombreResponsable' => ['sometimes', 'string', 'max:255'],
            'tipDocResponsable' => ['sometimes', 'string', 'max:10'],
            'numDocResponsable' => ['sometimes', 'string', 'max:50'],
            'nombreSolicita' => ['sometimes', 'string', 'max:255'],
            'tipDocSolicita' => ['sometimes', 'string', 'max:10'],
            'numDocSolicita' => ['sometimes', 'string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'tipoAnulacion' => 'tipo de anulación',
            'motivoAnulacion' => 'motivo de anulación',
            'nombreResponsable' => 'nombre del responsable',
            'tipDocResponsable' => 'tipo de documento del responsable',
            'numDocResponsable' => 'número de documento del responsable',
            'nombreSolicita' => 'nombre del solicitante',
            'tipDocSolicita' => 'tipo de documento del solicitante',
            'numDocSolicita' => 'número de documento del solicitante',
        ];
    }
}
