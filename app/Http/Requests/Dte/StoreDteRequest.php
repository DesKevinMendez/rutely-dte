<?php

namespace App\Http\Requests\Dte;

use App\Environment;
use App\Models\Dte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Dte::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipoDte' => ['sometimes', 'string', Rule::in(['01', '03', '04', '05', '06', '07', '08', '09', '11', '14', '15', '17', '18'])],
            'ambiente' => ['sometimes', 'string', Rule::enum(Environment::class)],
            'emisor' => ['sometimes', 'array'],
            'emisor.nit' => ['sometimes', 'string', 'regex:/^(?:\d{14}|\d{4}-\d{6}-\d{3}-\d)$/'],
            'emisor.nrc' => ['sometimes', 'nullable', 'string'],
            'emisor.nombre' => ['sometimes', 'string', 'min:3'],
            'emisor.codActividad' => ['sometimes', 'string', 'size:5'],
            'emisor.descActividad' => ['sometimes', 'string', 'min:3'],
            'emisor.nombreComercial' => ['sometimes', 'nullable', 'string'],
            'emisor.tipoEstablecimiento' => ['sometimes', 'string', Rule::in(['01', '02', '04', '07'])],
            'emisor.direccion' => ['sometimes', 'array'],
            'emisor.direccion.departamento' => ['required_with:emisor.direccion', 'string', 'size:2'],
            'emisor.direccion.municipio' => ['required_with:emisor.direccion', 'string', 'size:2'],
            'emisor.direccion.complemento' => ['required_with:emisor.direccion', 'string', 'min:3'],
            'emisor.telefono' => ['sometimes', 'nullable', 'string'],
            'emisor.correo' => ['sometimes', 'nullable', 'email'],
            'receptor' => ['sometimes', 'array'],
            'receptor.tipoDocumento' => ['sometimes', 'string', Rule::in(['13', '36', '03', '37'])],
            'receptor.numDocumento' => ['sometimes', 'nullable', 'string'],
            'receptor.nrc' => ['sometimes', 'nullable', 'string'],
            'receptor.nombre' => ['sometimes', 'nullable', 'string'],
            'receptor.codActividad' => ['sometimes', 'nullable', 'string'],
            'receptor.descActividad' => ['sometimes', 'nullable', 'string'],
            'receptor.direccion' => ['sometimes', 'array'],
            'receptor.direccion.departamento' => ['required_with:receptor.direccion', 'string', 'size:2'],
            'receptor.direccion.municipio' => ['required_with:receptor.direccion', 'string', 'size:2'],
            'receptor.direccion.complemento' => ['required_with:receptor.direccion', 'string', 'min:3'],
            'receptor.telefono' => ['sometimes', 'nullable', 'string'],
            'receptor.correo' => ['sometimes', 'nullable', 'email'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.descripcion' => ['required', 'string', 'min:1'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precioUni' => ['required', 'numeric', 'gte:0'],
            'items.*.montoDescu' => ['sometimes', 'numeric', 'gte:0'],
            'items.*.tipoItem' => ['sometimes', 'integer', Rule::in([1, 2, 3, 4])],
            'items.*.codigo' => ['sometimes', 'nullable', 'string'],
            'items.*.uniMedida' => ['sometimes', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'tipoDte' => 'tipo de DTE',
            'ambiente' => 'ambiente',
            'emisor.nit' => 'NIT del emisor',
            'emisor.nombre' => 'nombre del emisor',
            'emisor.codActividad' => 'código de actividad del emisor',
            'emisor.descActividad' => 'descripción de actividad del emisor',
            'emisor.tipoEstablecimiento' => 'tipo de establecimiento del emisor',
            'emisor.direccion.departamento' => 'departamento del emisor',
            'emisor.direccion.municipio' => 'municipio del emisor',
            'emisor.direccion.complemento' => 'complemento de dirección del emisor',
            'emisor.correo' => 'correo del emisor',
            'receptor.tipoDocumento' => 'tipo de documento del receptor',
            'receptor.direccion.departamento' => 'departamento del receptor',
            'receptor.direccion.municipio' => 'municipio del receptor',
            'receptor.direccion.complemento' => 'complemento de dirección del receptor',
            'receptor.correo' => 'correo del receptor',
            'items' => 'ítems',
            'items.*.descripcion' => 'descripción del ítem',
            'items.*.cantidad' => 'cantidad del ítem',
            'items.*.precioUni' => 'precio unitario del ítem',
            'items.*.montoDescu' => 'monto de descuento del ítem',
            'items.*.tipoItem' => 'tipo de ítem',
            'items.*.codigo' => 'código del ítem',
            'items.*.uniMedida' => 'unidad de medida del ítem',
        ];
    }
}
