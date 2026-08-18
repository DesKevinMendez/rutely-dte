<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Departament;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentListController extends Controller
{
    public function __invoke(CatalogIndexRequest $request): CommonCollection
    {
        $departments = QueryBuilder::for(Departament::class)
            ->select(['id', 'code', 'name'])
            ->allowedFilters(
                AllowedFilter::exact('id'),
                AllowedFilter::exact('code'),
                AllowedFilter::partial('name'),
            )
            ->orderBy('code')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($departments);
    }
}
