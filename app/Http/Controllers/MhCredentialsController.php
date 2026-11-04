<?php

namespace App\Http\Controllers;

use App\Environment;
use App\Exceptions\MhCredentialsRejectedException;
use App\Http\Requests\Mh\ShowMhCredentialsRequest;
use App\Http\Requests\Mh\StoreMhCredentialsRequest;
use App\Models\MhCredentials;
use App\Response\CommonResponse;
use App\Services\Mh\MhAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MhCredentialsController extends Controller
{
    public function __construct(private readonly MhAuthService $authService) {}

    public function store(StoreMhCredentialsRequest $request): CommonResponse
    {
        $data = $request->validated();

        try {
            $token = $this->authService->login($data['nit'], $data['pwd'], $data['environment']);
        } catch (MhCredentialsRejectedException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'pwd' => __('validation.custom.pwd.mh_rejected'),
            ]);
        } catch (ConnectionException|RuntimeException $exception) {
            report($exception);

            return new CommonResponse(null, 503, __('validation.custom.pwd.mh_unavailable'));
        }

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

        $credentials->refresh();
        $this->authService->rememberToken($credentials, $token);

        return new CommonResponse($this->metadata($credentials));
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
            'login_status' => $this->authService->loginStatus($credentials)->value,
            'updated_at' => $credentials->updated_at?->toJSON(),
        ];
    }
}
