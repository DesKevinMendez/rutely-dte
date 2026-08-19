<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiToken\StoreApiTokenRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Company;
use App\Response\CommonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index(Request $request): CommonCollection
    {
        $company = Company::query()->findOrFail($request->user()->company_id);
        $tokens = $company->tokens()
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        $tokens->through(
            fn (PersonalAccessToken $token): array => $this->metadata($token),
        );

        return CommonCollection::make($tokens);
    }

    public function store(StoreApiTokenRequest $request): CommonResponse
    {
        $company = Company::query()->findOrFail($request->user()->company_id);
        $token = $company->createToken(
            $request->validated('name'),
            ['create:dte'],
        );

        return new CommonResponse([
            'record' => $this->metadata($token->accessToken),
            'plain_text_token' => $token->plainTextToken,
        ], 201);
    }

    /** @return array<string, int|string|null> */
    private function metadata(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->getKey(),
            'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toJSON(),
            'created_at' => $token->created_at?->toJSON(),
        ];
    }
}
