<?php

namespace App\Http\Requests\Dte;

use App\Models\Dte;
use Illuminate\Support\Arr;

class ExportDteRequest extends IndexDteRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Dte::class);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['per_page']);
    }
}
