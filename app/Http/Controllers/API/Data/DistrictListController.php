<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\District;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DistrictListController extends Controller
{
    public function __invoke(CatalogIndexRequest $request): CommonCollection
    {
        $districts = QueryBuilder::for(District::class)
            ->select([
                'id',
                DB::raw('departament_id as department_id'),
                'municipality_id',
                'code',
                'name',
            ])
            ->allowedFilters(
                AllowedFilter::exact('id'),
                AllowedFilter::exact('department_id', 'departament_id'),
                AllowedFilter::exact('municipality_id'),
                AllowedFilter::exact('code'),
                AllowedFilter::partial('name'),
            )
            ->orderBy('name')
            ->orderBy('code')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($districts);
    }
}
