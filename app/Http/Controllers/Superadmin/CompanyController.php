<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Company;

class CompanyController extends Controller
{
    public function index(CatalogIndexRequest $request): CommonCollection
    {
        $companies = Company::query()
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($companies);
    }
}
