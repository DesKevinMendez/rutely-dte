<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\Company\StoreSuperadminCompanyRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Company;
use App\Response\CommonResponse;
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

    public function store(StoreSuperadminCompanyRequest $request): CommonResponse
    {
        $company = Company::query()->create([
            ...$request->validated(),
            'is_onboarded' => true,
        ]);

        return new CommonResponse($company->refresh(), 201);
    }
}
