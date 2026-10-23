<?php

use App\Models\Company;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function apiTokenTestCompany(string $suffix = ''): Company
{
    $departamentId = (string) Str::uuid();
    $municipalityId = (string) Str::uuid();

    DB::table('departaments')->insert([
        'id' => $departamentId,
        'code' => '06'.$suffix,
        'name' => 'San Salvador '.$suffix,
    ]);
    DB::table('municipalities')->insert([
        'id' => $municipalityId,
        'departament_id' => $departamentId,
        'departament_code' => '06'.$suffix,
        'code' => '01'.$suffix,
        'name' => 'San Salvador Centro '.$suffix,
    ]);

    return Company::query()->create([
        'name' => 'Rutely Tokens '.$suffix,
        'address' => 'San Salvador',
        'phone' => '22223333',
        'nit' => '06142812901015'.$suffix,
        'nrc' => '1234567',
        'commercial_name' => 'Rutely Tokens '.$suffix,
        'economic_activity_code' => '62010',
        'establishment_type' => '01',
        'departament_id' => $departamentId,
        'municipality_id' => $municipalityId,
        'email' => 'tokens'.$suffix.'@example.test',
        'mh_establishment_code' => '0001',
        'mh_pos_code' => '0001',
        'own_establishment_code' => '0001',
        'own_pos_code' => '0001',
    ]);
}

function apiTokenTestUser(Company $company): User
{
    return User::factory()->withRole(Role::ADMIN)->create([
        'company_id' => $company->id,
    ]);
}

test('creates a Sanctum token owned by the authenticated company', function () {
    $company = apiTokenTestCompany();
    Sanctum::actingAs(apiTokenTestUser($company));

    $response = $this->postJson(route('api.v1.tokens.store'), [
        'name' => 'ERP principal',
    ])->assertCreated();

    $tokenId = $response->json('data.record.id');
    $plainTextToken = $response->json('data.plain_text_token');

    expect($response->json('data.record.name'))->toBe('ERP principal')
        ->and($response->json('data.record.abilities'))->toBe(['create:dte', 'list:dte'])
        ->and($response->json('data.record.last_used_at'))->toBeNull()
        ->and($plainTextToken)->toBeString()->not->toBeEmpty();

    [$plainTextId, $secret] = explode('|', $plainTextToken, 2);
    $storedToken = DB::table('personal_access_tokens')->where('id', $tokenId)->first();

    expect($storedToken)->not->toBeNull()
        ->and((string) $tokenId)->toBe($plainTextId)
        ->and($storedToken->tokenable_type)->toBe($company->getMorphClass())
        ->and($storedToken->tokenable_id)->toBe($company->id)
        ->and($storedToken->name)->toBe('ERP principal')
        ->and(json_decode($storedToken->abilities, true))->toBe(['create:dte', 'list:dte'])
        ->and($storedToken->token)->toBe(hash('sha256', $secret))
        ->and($storedToken->token)->not->toBe($plainTextToken)
        ->and($response->json('data.record.token_preview'))
        ->toBe($tokenId.'|'.substr($storedToken->token, 0, 4).'***'.substr($storedToken->token, -4));
});

test('creates an API token with only the requested abilities', function () {
    $company = apiTokenTestCompany('9');
    Sanctum::actingAs(apiTokenTestUser($company));

    $response = $this->postJson(route('api.v1.tokens.store'), [
        'name' => 'ERP solo lectura',
        'abilities' => ['list:dte'],
    ])->assertCreated();

    $tokenId = $response->json('data.record.id');
    $storedToken = DB::table('personal_access_tokens')->where('id', $tokenId)->first();

    expect($response->json('data.record.abilities'))->toBe(['list:dte'])
        ->and($response->json('data.record.token_preview'))
        ->toBe($tokenId.'|'.substr($storedToken->token, 0, 4).'***'.substr($storedToken->token, -4))
        ->and(json_decode($storedToken->abilities, true))->toBe(['list:dte']);
});

test('store API token requires a name', function () {
    $company = apiTokenTestCompany('2');
    Sanctum::actingAs(apiTokenTestUser($company));

    $this->postJson(route('api.v1.tokens.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'El campo nombre es obligatorio.',
        ]);
});

test('lists only Sanctum tokens owned by the authenticated company', function () {
    $company = apiTokenTestCompany('3');
    $otherCompany = apiTokenTestCompany('4');

    $firstToken = $company->createToken('ERP principal', ['create:dte'])->accessToken;
    $secondToken = $company->createToken('Sistema contable', ['create:dte'])->accessToken;
    $otherCompany->createToken('Token de otra empresa', ['create:dte']);

    Sanctum::actingAs(apiTokenTestUser($company));

    $this->getJson(route('api.v1.tokens.index', ['per_page' => 100]))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'id' => $secondToken->getKey(),
                    'name' => 'Sistema contable',
                    'abilities' => ['create:dte'],
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

test('store API token validates abilities one rule at a time', function (mixed $abilities, string $field, string $message): void {
    $company = apiTokenTestCompany('10');
    Sanctum::actingAs(apiTokenTestUser($company));

    $this->postJson(route('api.v1.tokens.store'), [
        'name' => 'ERP principal',
        'abilities' => $abilities,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'abilities must be an array' => ['list:dte', 'abilities', 'El campo permisos debe ser un arreglo.'],
    'abilities cannot be empty' => [[], 'abilities', 'El campo permisos debe contener al menos 1 elementos.'],
    'each ability is required' => [[null], 'abilities.0', 'El campo permiso es obligatorio.'],
    'each ability must be a string' => [[['list:dte']], 'abilities.0', 'El campo permiso debe ser una cadena de caracteres.'],
    'abilities must be distinct' => [['list:dte', 'list:dte'], 'abilities.1', 'El campo permiso tiene un valor duplicado.'],
    'each ability must be supported' => [['delete:dte'], 'abilities.0', 'El valor seleccionado para permiso no es válido.'],
]);

test('API token endpoints return 404 when the authenticated user has no company', function () {
    $user = User::factory()->withRole(Role::ADMIN)->create([
        'company_id' => null,
    ]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.tokens.index'))
        ->assertNotFound();

    $this->postJson(route('api.v1.tokens.store'), [
        'name' => 'ERP principal',
    ])->assertNotFound();
});

test('company token abilities are forbidden from internal endpoints', function () {
    $company = apiTokenTestCompany('5');
    $token = $company->createToken('External ERP', ['create:dte', 'list:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertForbidden();

    $this->withToken($token)
        ->getJson(route('api.v1.tokens.index'))
        ->assertForbidden();
});

test('company token without abilities is forbidden from internal endpoints', function () {
    $company = apiTokenTestCompany('6');
    $token = $company->createToken('External without abilities', [])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertForbidden();

    $this->withToken($token)
        ->getJson(route('api.v1.tokens.index'))
        ->assertForbidden();
});

test('dashboard wildcard user token can access internal endpoints', function () {
    $company = apiTokenTestCompany('7');
    $user = apiTokenTestUser($company);
    $token = $user->createToken('Dashboard')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

test('user token abilities do not restrict internal endpoints', function () {
    $company = apiTokenTestCompany('8');
    $user = apiTokenTestUser($company);
    $token = $user->createToken('Explicit ability', ['create:dte'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

test('internal endpoints still require authentication', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized();
});
