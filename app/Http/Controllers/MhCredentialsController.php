<?php

namespace App\Http\Controllers;

use App\Environment;
use App\Http\Requests\Mh\ShowMhCredentialsRequest;
use App\Http\Http\Requests\Mh\StoreMhCredentialsRequest;
use App\Models\MhCredentials;
use App\Response\CommonResponse;

class MhCredentialsController extends Controller
{
    public function store(StoreMhCredentialsRequest $request): CommonResponse
    {
        $data = $request->validated();

        $credentials = MhCredentials::query()->updateOrCreate(
            [
                'company_id' => $request->user()->company_id,
                'environment' => $data['environment'],
            ],
            [
                'nit' => $data['nit'],
                'password' => $data['pwd'],
                'active' => true,
            ],
        );

        return new CommonResponse($this->metadata($credentials->refresh()));
    }

    public function show(ShowMhCredentialsRequest $request): CommonResponse
    {
        $environment = $request->validated('environment', Environment::SANDBOX->value);
        $credentials = MhCredentials::query()
            ->where('company_id', $request->user()->company_id)
            ->where('environment', $environment)
            ->latest('updated_at')
            ->firstOrFail();

        return new CommonResponse($this->metadata($credentials));
    }

    /** @return array<string, mixed> */
    private function metadata(MhCredentials $credentials): array
    {
        return [
            'id' => $credentials->id,
            'environment' => $credentials->environment->value,
            'nit' => $credentials->nit,
            'active' => $credentials->active,
            'updated_at' => $credentials->updated_at?->toJSON(),
        ];
    }
}
