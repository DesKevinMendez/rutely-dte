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
    return User::factory()->create([
        'company_id' => $company->id,
        'role' => Role::ADMIN->value,
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
        ->and($response->json('data.record.last_used_at'))->toBeNull()
        ->and($plainTextToken)->toBeString()->not->toBeEmpty();

    [$plainTextId, $secret] = explode('|', $plainTextToken, 2);
    $storedToken = DB::table('personal_access_tokens')->where('id', $tokenId)->first();

    expect($storedToken)->not->toBeNull()
        ->and((string) $tokenId)->toBe($plainTextId)
        ->and($storedToken->tokenable_type)->toBe($company->getMorphClass())
        ->and($storedToken->tokenable_id)->toBe($company->id)
        ->and($storedToken->name)->toBe('ERP principal')
        ->and(json_decode($storedToken->abilities, true))->toBe(['create:dte'])
        ->and($storedToken->token)->toBe(hash('sha256', $secret))
        ->and($storedToken->token)->not->toBe($plainTextToken);
});

test('requires a name when creating a company API token', function () {
    $company = apiTokenTestCompany('2');
    Sanctum::actingAs(apiTokenTestUser($company));

    $this->postJson(route('api.v1.tokens.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('lists only Sanctum tokens owned by the authenticated company', function () {
    $company = apiTokenTestCompany('3');
    $otherCompany = apiTokenTestCompany('4');

    $company->createToken('ERP principal', ['create:dte']);
    $company->createToken('Sistema contable', ['create:dte']);
    $otherCompany->createToken('Token de otra empresa', ['create:dte']);

    Sanctum::actingAs(apiTokenTestUser($company));

    $response = $this->getJson(route('api.v1.tokens.index', ['per_page' => 100]))
        ->assertOk()
        ->assertJsonPath('pagination.total', 2)
        ->assertJsonPath('data.0.name', 'Sistema contable')
        ->assertJsonPath('data.1.name', 'ERP principal')
        ->assertJsonMissing(['name' => 'Token de otra empresa']);

    foreach ($response->json('data') as $token) {
        expect($token)->toHaveKeys(['id', 'name', 'last_used_at', 'created_at'])
            ->and(array_key_exists('token', $token))->toBeFalse()
            ->and(array_key_exists('abilities', $token))->toBeFalse()
            ->and(array_key_exists('tokenable_id', $token))->toBeFalse()
            ->and(array_key_exists('tokenable_type', $token))->toBeFalse();
    }
});
