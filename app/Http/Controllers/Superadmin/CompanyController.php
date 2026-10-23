<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Company;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyController extends Controller
{
    public function index(CatalogIndexRequest $request): CommonCollection
    {
        $companies = QueryBuilder::for(Company::query())
            ->allowedFilters(AllowedFilter::partial('name'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($companies);
    }
}
