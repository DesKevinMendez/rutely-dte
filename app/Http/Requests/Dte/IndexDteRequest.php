<?php

namespace App\Http\Requests\Dte;

use App\Http\Requests\CatalogIndexRequest;
use App\Models\Company;
use App\Models\Dte;
use App\Models\User;

class IndexDteRequest extends CatalogIndexRequest
{
    public function authorize(): bool
    {
        /** @var User|Company|null $principal */
        $principal = $this->user();

        return $principal instanceof Company
            || ($principal instanceof User && $principal->can('viewAny', Dte::class));
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'filter' => ['sometimes', 'array'],
            'filter.date' => ['bail', 'sometimes', 'date_format:Y-m-d', 'prohibits:filter.from,filter.to'],
            'filter.from' => ['bail', 'required_with:filter.to', 'date_format:Y-m-d'],
            'filter.to' => ['bail', 'required_with:filter.from', 'date_format:Y-m-d', 'after_or_equal:filter.from'],
        ];
    }
}
