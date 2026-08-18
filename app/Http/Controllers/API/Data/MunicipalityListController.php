<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Municipalities;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MunicipalityListController extends Controller
{
    public function __invoke(CatalogIndexRequest $request): CommonCollection
    {
        $municipalities = QueryBuilder::for(Municipalities::class)
            ->select([
                'id',
                DB::raw('departament_id as department_id'),
                DB::raw('departament_code as department_code'),
                'code',
                'name',
            ])
            ->allowedFilters(
                AllowedFilter::exact('id'),
                AllowedFilter::exact('department_id', 'departament_id'),
                AllowedFilter::exact('department_code', 'departament_code'),
                AllowedFilter::exact('code'),
                AllowedFilter::partial('name'),
            )
            ->orderBy('departament_code')
            ->orderBy('code')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($municipalities);
    }
}
