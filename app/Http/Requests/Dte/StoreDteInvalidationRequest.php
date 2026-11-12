<?php

namespace App\Http\Requests\Dte;

use App\Models\Company;
use App\Models\DocumentType;
use App\Models\Dte;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Limits come from invalidacion-schema-v3; required fields from Manual Funcional V 2.0, section on invalidation.
 */
class StoreDteInvalidationRequest extends FormRequest
{
    /**
     * A company API token with create:dte (POS) can invalidate the documents of its own company that are not
     * invalidated yet; dashboard users go through the DTE policy.
     */
    public function authorize(): bool
    {
        $dte = $this->route('dte');

        if (! $dte instanceof Dte) {
            return false;
        }

        /** @var User|Company|null $principal */
        $principal = $this->user();

        if ($principal instanceof Company) {
            return $principal->getKey() === $dte->company_id && $dte->status !== 'INVALIDADO';
        }

        return $principal?->can('invalidate', $dte) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipoAnulacion' => ['bail', 'required', 'integer', Rule::in([1, 2, 3])],
            'motivoAnulacion' => ['sometimes', 'nullable', 'string', 'max:200'],
            'nombreResponsable' => ['required', 'string', 'max:100'],
            'tipDocResponsable' => ['bail', 'required', 'string', Rule::exists(DocumentType::class, 'code')],
            'numDocResponsable' => ['required', 'string', 'max:20'],
            'nombreSolicita' => ['required', 'string', 'max:100'],
            'tipDocSolicita' => ['bail', 'required', 'string', Rule::exists(DocumentType::class, 'code')],
            'numDocSolicita' => ['required', 'string', 'max:20'],
            'codigoGeneracionR' => ['bail', 'required_if:tipoAnulacion,1,3', 'prohibited_if:tipoAnulacion,2', 'nullable', 'uuid'],
        ];
    }
}
