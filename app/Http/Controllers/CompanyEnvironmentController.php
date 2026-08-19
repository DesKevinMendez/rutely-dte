<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\UpdateCompanyEnvironmentRequest;
use App\Models\Company;
use App\Response\CommonResponse;
use Illuminate\Support\Facades\Gate;

class CompanyEnvironmentController extends Controller
{
    public function show(Company $company): CommonResponse
    {
        Gate::authorize('view', $company);

        return new CommonResponse([
            'data' => [
                'environment' => $company->environment,
            ],
        ]);
    }

    public function update(UpdateCompanyEnvironmentRequest $request, Company $company): CommonResponse
    {
        $company->forceFill($request->validated())->save();

        return new CommonResponse([
            'data' => [
                'environment' => $company->refresh()->environment,
            ],
        ]);
    }
}
