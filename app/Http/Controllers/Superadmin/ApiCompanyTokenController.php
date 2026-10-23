<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiToken\StoreApiTokenRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Company;
use App\Response\CommonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiCompanyTokenController extends Controller
{
    public function index(Request $request, Company $company): CommonCollection
    {
        $tokens = $company->tokens()
            ->latest('id')
            ->paginate($request->integer('per_page', 15));
        $tokens->through(fn (PersonalAccessToken $token): array => $this->metadata($token));

        return CommonCollection::make($tokens);
    }

    public function store(StoreApiTokenRequest $request, Company $company): CommonResponse
    {
        $token = $company->createToken(
            $request->validated('name'),
            $request->validated('abilities') ?? ['create:dte', 'list:dte'],
        );

        return new CommonResponse([
            'record' => $this->metadata($token->accessToken),
            'plain_text_token' => $token->plainTextToken,
        ], 201);
    }

    /** @return array{id: int|string, name: string, abilities: array<int, string>, token_preview: string, last_used_at: string|null, created_at: string|null} */
    private function metadata(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->getKey(),
            'name' => $token->name,
            'abilities' => $token->abilities ?? [],
            'token_preview' => $token->token_preview,
            'last_used_at' => $token->last_used_at?->toJSON(),
            'created_at' => $token->created_at?->toJSON(),
        ];
    }
}
