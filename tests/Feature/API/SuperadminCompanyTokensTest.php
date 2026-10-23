<?php

use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;

test('superadmin can list only tokens owned by the selected company', function (): void {
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Otra empresa, S.A. de C.V.',
        'nit' => '06142812901016',
        'email' => 'otra@example.com',
    ]);
    $firstToken = $company->createToken('ERP principal', ['create:dte'])->accessToken;
    $secondToken = $company->createToken('ERP lectura', ['list:dte'])->accessToken;
    $otherCompany->createToken('Token externo', ['list:dte']);

    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $this->getJson(route('api.v1.superadmin.companies.tokens.index', [
        'company' => $company,
        'per_page' => 100,
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'id' => $secondToken->getKey(),
                    'name' => 'ERP lectura',
                    'abilities' => ['list:dte'],
                    'token_preview' => $secondToken->token_preview,
                    'last_used_at' => null,
                    'created_at' => $secondToken->created_at?->toJSON(),
                ],
                [
                    'id' => $firstToken->getKey(),
                    'name' => 'ERP principal',
                    'abilities' => ['create:dte'],
                    'token_preview' => $firstToken->token_preview,
                    'last_used_at' => null,
                    'created_at' => $firstToken->created_at?->toJSON(),
                ],
            ],
            'pagination' => [
                'total' => 2,
                'per_page' => 100,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 2,
            ],
        ]);
});

test('superadmin can create a token for the selected company with selected abilities', function (): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create([
        'company_id' => null,
    ]));

    $response = $this->postJson(
        route('api.v1.superadmin.companies.tokens.store', $company),
        [
            'name' => 'ERP de lectura',
            'abilities' => ['list:dte'],
        ],
    )->assertCreated();

    $tokenId = $response->json('data.record.id');
    $storedToken = DB::table('personal_access_tokens')->where('id', $tokenId)->first();

    expect($response->json('data.record'))->toMatchArray([
        'id' => $tokenId,
        'name' => 'ERP de lectura',
        'abilities' => ['list:dte'],
        'last_used_at' => null,
    ])
        ->and($response->json('data.plain_text_token'))->toBeString()->not->toBeEmpty()
        ->and($storedToken)->not->toBeNull()
        ->and($response->json('data.record.token_preview'))
        ->toBe($tokenId.'|'.substr($storedToken->token, 0, 4).'***'.substr($storedToken->token, -4))
        ->and($storedToken->tokenable_type)->toBe($company->getMorphClass())
        ->and($storedToken->tokenable_id)->toBe($company->id)
        ->and($storedToken->name)->toBe('ERP de lectura')
        ->and(json_decode($storedToken->abilities, true))->toBe(['list:dte']);
});

test('only superadmins can access company tokens', function (Role $role): void {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, $role));

    $this->getJson(route('api.v1.superadmin.companies.tokens.index', $company))
        ->assertForbidden();
    $this->postJson(route('api.v1.superadmin.companies.tokens.store', $company), [
        'name' => 'ERP de lectura',
        'abilities' => ['list:dte'],
    ])->assertForbidden();
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
]);

test('company token endpoints require authentication', function (): void {
    $company = DteApiTestData::company();

    $this->getJson(route('api.v1.superadmin.companies.tokens.index', $company))
        ->assertUnauthorized();
    $this->postJson(route('api.v1.superadmin.companies.tokens.store', $company), [])
        ->assertUnauthorized();
});
