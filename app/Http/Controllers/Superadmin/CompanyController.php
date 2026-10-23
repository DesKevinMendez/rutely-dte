<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\Company\StoreSuperadminCompanyRequest;
use App\Http\Requests\Company\UpdateSuperadminCompanyRequest;
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
        $company = Company::query()->create($request->validated());

        return new CommonResponse($company->refresh(), 201);
    }

    public function show(Company $company): CommonResponse
    {
        return new CommonResponse($company);
    }

    public function update(UpdateSuperadminCompanyRequest $request, Company $company): CommonResponse
    {
        $company->update($request->validated());

        return new CommonResponse($company->refresh());
    }
}
