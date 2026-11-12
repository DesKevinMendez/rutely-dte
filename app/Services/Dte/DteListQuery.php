<?php

namespace App\Services\Dte;

use App\Models\Dte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\QueryBuilder;

class DteListQuery
{
    /** @return QueryBuilder<Dte> */
    public function forCompany(?string $companyId, Request $request): QueryBuilder
    {
        $emissionDate = 'original_json->identificacion->fecEmi';

        return QueryBuilder::for(Dte::query()->where('company_id', $companyId), $request)
            ->allowedFilters(
                AllowedFilter::exact('tipoDte', 'dte_type'),
                AllowedFilter::exact('estado', 'status'),
                AllowedFilter::exact('date', $emissionDate),
                AllowedFilter::operator('from', FilterOperator::GREATER_THAN_OR_EQUAL, internalName: $emissionDate),
                AllowedFilter::operator('to', FilterOperator::LESS_THAN_OR_EQUAL, internalName: $emissionDate),
                AllowedFilter::exact('environment'),
                AllowedFilter::exact('receiver_document'),
                AllowedFilter::partial('generation_code'),
                AllowedFilter::partial('control_number'),
                AllowedFilter::callback('query', function (Builder $query, mixed $value): void {
                    $search = (string) $value;
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('generation_code', 'like', "%{$search}%")
                            ->orWhere('control_number', 'like', "%{$search}%")
                            ->orWhere('receiver_document', 'like', "%{$search}%");
                    });
                }),
            )
            ->allowedSorts('created_at', 'total_amount', 'status', 'dte_type')
            ->defaultSort('-created_at');
    }
}
