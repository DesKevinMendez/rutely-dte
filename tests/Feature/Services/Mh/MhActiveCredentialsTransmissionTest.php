<?php

use App\Environment;
use App\Models\Dte;
use App\Models\MhCredentials;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function createCompanyForMhActiveCredentialsTest(): string
{
    $departamentId = (string) Str::uuid();
    $municipalityId = (string) Str::uuid();
    $companyId = (string) Str::uuid();

    DB::table('departaments')->insert([
        'id' => $departamentId,
        'code' => '06',
        'name' => 'San Salvador',
    ]);

    DB::table('municipalities')->insert([
        'id' => $municipalityId,
        'departament_id' => $departamentId,
        'departament_code' => '06',
        'code' => '01',
        'name' => 'San Salvador Centro',
    ]);

    DB::table('companies')->insert([
        'id' => $companyId,
        'name' => 'Rutely MH Credentials Test',
        'address' => 'San Salvador, El Salvador',
        'phone' => '22223333',
        'nit' => '06142812901015',
        'nrc' => '1234567',
        'commercial_name' => 'Rutely MH Credentials Test',
        'economic_activity_code' => '62010',
        'establishment_type' => '01',
        'departament_id' => $departamentId,
        'municipality_id' => $municipalityId,
        'email' => 'billing@example.test',
        'mh_establishment_code' => '0001',
        'mh_pos_code' => '0001',
        'own_establishment_code' => '0001',
        'own_pos_code' => '0001',
        'environment' => Environment::SANDBOX->value,
    ]);

    return $companyId;
}

function createFacturaForMhActiveCredentialsTest(string $companyId, string $suffix): Dte
{
    return Dte::query()->create([
        'company_id' => $companyId,
        'generation_code' => "00000000-0000-4000-8000-0000000000{$suffix}",
        'control_number' => "DTE-01-00010001-0000000000000{$suffix}",
        'dte_type' => '01',
        'version' => '2',
        'environment' => Environment::SANDBOX->value,
        'status' => 'FIRMADO',
        'issuer_nit' => '06142812901015',
        'receiver_document' => '06141505921015',
        'total_amount' => 1234,
        'original_json' => [
            'identificacion' => [
                'tipoDte' => '01',
            ],
        ],
        'signed_json' => "signed-jws-{$suffix}",
    ]);
}

test('it sends each factura using the MH credential that is active at transmission time', function () {
    $companyId = createCompanyForMhActiveCredentialsTest();

    $firstCredentials = MhCredentials::query()->create([
        'company_id' => $companyId,
        'environment' => Environment::SANDBOX->value,
        'nit' => '06140000000001',
        'password' => 'first-secret',
        'active' => true,
    ]);

    $secondCredentials = MhCredentials::query()->create([
        'company_id' => $companyId,
        'environment' => Environment::SANDBOX->value,
        'nit' => '06140000000002',
        'password' => 'second-secret',
        'active' => false,
    ]);

    $authUrl = 'https://apitest.dtes.mh.gob.sv/seguridad/auth';
    $receptionUrl = 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte';

    Http::preventStrayRequests();
    Http::fake([
        $authUrl => Http::sequence()
            ->push(['body' => ['token' => 'Bearer token-first']], 200)
            ->push(['body' => ['token' => 'Bearer token-second']], 200),
        $receptionUrl => Http::sequence()
            ->push([
                'estado' => 'PROCESADO',
                'selloRecibido' => 'SEAL-FIRST',
                'observaciones' => [],
            ], 200)
            ->push([
                'estado' => 'PROCESADO',
                'selloRecibido' => 'SEAL-SECOND',
                'observaciones' => [],
            ], 200),
    ]);

    $transmission = app(MhTransmissionService::class);

    $firstResult = $transmission->transmitDte(
        createFacturaForMhActiveCredentialsTest($companyId, '01'),
    );

    expect($firstResult['estado'])->toBe('PROCESADO')
        ->and($firstCredentials->fresh()->active)->toBeTrue()
        ->and($secondCredentials->fresh()->active)->toBeFalse();

    $firstCredentials->update(['active' => false]);
    $secondCredentials->update(['active' => true]);

    $secondResult = $transmission->transmitDte(
        createFacturaForMhActiveCredentialsTest($companyId, '02'),
    );

    expect($secondResult['estado'])->toBe('PROCESADO')
        ->and($firstCredentials->fresh()->active)->toBeFalse()
        ->and($secondCredentials->fresh()->active)->toBeTrue();

    $authRequests = Http::recorded(
        fn (Request $request) => $request->url() === $authUrl,
    );
    $receptionRequests = Http::recorded(
        fn (Request $request) => $request->url() === $receptionUrl,
    );

    expect($authRequests)->toHaveCount(2)
        ->and($receptionRequests)->toHaveCount(2);

    $firstAuthRequest = $authRequests->values()[0][0];
    $secondAuthRequest = $authRequests->values()[1][0];
    $firstReceptionRequest = $receptionRequests->values()[0][0];
    $secondReceptionRequest = $receptionRequests->values()[1][0];

    expect($firstAuthRequest->data())->toMatchArray([
        'user' => '06140000000001',
        'pwd' => 'first-secret',
    ])->and($secondAuthRequest->data())->toMatchArray([
        'user' => '06140000000002',
        'pwd' => 'second-secret',
    ])->and($firstReceptionRequest->hasHeader('Authorization', 'Bearer token-first'))->toBeTrue()
        ->and($secondReceptionRequest->hasHeader('Authorization', 'Bearer token-second'))->toBeTrue()
        ->and($firstReceptionRequest->data()['tipoDte'])->toBe('01')
        ->and($secondReceptionRequest->data()['tipoDte'])->toBe('01');
});
