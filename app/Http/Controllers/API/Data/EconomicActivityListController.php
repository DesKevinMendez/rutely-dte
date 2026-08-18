<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\EconomicActivity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EconomicActivityListController extends Controller
{
    public function __invoke(CatalogIndexRequest $request): CommonCollection
    {
        $activities = QueryBuilder::for(EconomicActivity::class)
            ->select(['id', 'code', 'description'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('code'),
                AllowedFilter::partial('description'),
            ])
            ->orderBy('code')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($activities);
    }
}
