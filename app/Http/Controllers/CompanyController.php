<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Response\CommonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request): CommonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $company = DB::transaction(function () use ($user, $validated): Company {
            $company = Company::query()->create($validated);

            $user->update([
                'company_id' => $company->id,
            ]);

            return $company;
        });

        return new CommonResponse($company->refresh(), 201);
    }

    public function show(Company $company): CommonResponse
    {
        Gate::authorize('view', $company);

        return new CommonResponse($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company): CommonResponse
    {
        $company->update($request->validated());

        return new CommonResponse($company->refresh());
    }
}
