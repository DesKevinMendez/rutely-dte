<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\UpdateCompanyEnvironmentRequest;
use App\Models\Company;
use App\Models\User;
use App\Response\CommonResponse;
use Illuminate\Support\Facades\Gate;

class CompanyEnvironmentController extends Controller
{
    public function show(Company $company): CommonResponse
    {
        Gate::authorize('view', $company);

        return new CommonResponse([
            'environment' => $company->environment,
        ]);
    }

    public function update(UpdateCompanyEnvironmentRequest $request, Company $company): CommonResponse
    {
        $company->forceFill([
            'environment' => $request->validated('environment'),
        ])->save();

        /** @var User $user */
        $user = $request->user();
        $user->load(['roles', 'company']);

        return new CommonResponse([
            'user' => $user->makeHidden('status'),
        ]);
    }
}
