<?php

use App\Environment;
use App\Models\MhCredentials;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\DteApiTestData;

test('a DTE is transmitted with the body the reception service requires', function () {
    $company = DteApiTestData::company();
    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => 'Hacienda-Prueba-01',
        'active' => true,
    ]);
    $dte = DteApiTestData::dte($company);

    Http::fake([
        'apitest.dtes.mh.gob.sv/seguridad/auth' => Http::response(['status' => 'OK', 'body' => ['token' => 'Bearer mh-token']]),
        'apitest.dtes.mh.gob.sv/fesv/recepciondte' => Http::response(['estado' => 'PROCESADO', 'selloRecibido' => 'SELLO-MH']),
    ]);

    $result = app(MhTransmissionService::class)->transmitDte($dte);

    expect($result)->toBe(['estado' => 'PROCESADO', 'selloRecibido' => 'SELLO-MH']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte'
        && $request->hasHeader('Authorization', 'Bearer mh-token')
        && $request->data() === [
            'idEnvio' => 1,
            'ambiente' => '00',
            'version' => 2,
            'tipoDte' => '01',
            'documento' => 'signed-jws',
            'codigoGeneracion' => $dte->generation_code,
        ]);
});
