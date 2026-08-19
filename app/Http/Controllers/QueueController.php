<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\MhTransmission;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QueueController extends Controller
{
    public function index(CatalogIndexRequest $request): CommonCollection
    {
        Gate::authorize('viewAny', MhTransmission::class);

        $transmissions = QueryBuilder::for(
            MhTransmission::query()
                ->where('company_id', $request->user()->company_id)
                ->whereIn('status', ['pending', 'failed']),
        )
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('operation'),
            ])
            ->allowedSorts(['created_at', 'attempt', 'status', 'operation'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 50));

        return CommonCollection::make($transmissions);
    }
}
