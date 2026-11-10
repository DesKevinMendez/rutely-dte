<?php

namespace App\Http\Requests\Dte;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuperadminDteCorrelativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['next_value' => ['required', 'integer', 'min:1', 'max:999999999999999']];
    }
}
