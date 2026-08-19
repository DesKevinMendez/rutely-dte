<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiToken\StoreApiTokenRequest;
use App\Models\Company;
use App\Response\CommonResponse;

class ApiTokenController extends Controller
{
    public function store(StoreApiTokenRequest $request): CommonResponse
    {
        $company = Company::query()->findOrFail($request->user()->company_id);
        $token = $company->createToken(
            $request->validated('name'),
            ['create:dte'],
        );

        return new CommonResponse([
            'record' => [
                'id' => $token->accessToken->getKey(),
                'name' => $token->accessToken->name,
                'last_used_at' => $token->accessToken->last_used_at?->toJSON(),
                'created_at' => $token->accessToken->created_at?->toJSON(),
            ],
            'plain_text_token' => $token->plainTextToken,
        ], 201);
    }
}
