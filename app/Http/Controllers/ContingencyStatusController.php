<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContingencyEvents\UpdateContingencyStatusRequest;
use App\Models\Company;
use App\Response\CommonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class ContingencyStatusController extends Controller
{
    public function show(Request $request): CommonResponse
    {
        $company = Company::query()->findOrFail($request->user()->company_id);
        Gate::authorize('view', $company);

        return new CommonResponse($this->status($company));
    }

    public function update(UpdateContingencyStatusRequest $request): CommonResponse
    {
        $company = Company::query()->findOrFail($request->user()->company_id);
        Cache::forever($this->cacheKey($company), (bool) $request->boolean('active'));

        return new CommonResponse($this->status($company));
    }

    /** @return array{contingency_active: bool, circuit_state: string} */
    private function status(Company $company): array
    {
        $active = (bool) Cache::get($this->cacheKey($company), false);

        return [
            'contingency_active' => $active,
            'circuit_state' => $active ? 'MANUAL_OPEN' : 'CLOSED',
        ];
    }

    private function cacheKey(Company $company): string
    {
        return "mh-contingency:{$company->id}";
    }
}
