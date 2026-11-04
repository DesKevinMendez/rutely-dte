<?php

use App\Environment;
use App\Models\Company;
use App\Models\MhCredentials;
use App\Services\Mh\MhAuthService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\DteApiTestData;

const MH_AUTH_URL = 'https://apitest.dtes.mh.gob.sv/seguridad/auth';

function companyWithMhCredentials(string $password = 'Hacienda-Prueba-01'): Company
{
    $company = DteApiTestData::company();

    MhCredentials::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX->value,
        'nit' => $company->nit,
        'password' => $password,
        'active' => true,
    ]);

    return $company;
}

test('authenticates with the NIT without hyphens and reuses the token', function () {
    $company = companyWithMhCredentials();
    Http::fake([MH_AUTH_URL => Http::response(['status' => 'OK', 'body' => ['token' => 'Bearer mh-token']])]);
    $service = app(MhAuthService::class);

    expect($service->token($company->id, Environment::SANDBOX))->toBe('mh-token')
        ->and($service->token($company->id, Environment::SANDBOX))->toBe('mh-token');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === MH_AUTH_URL
        && $request->isForm()
        && $request['user'] === '06142812901015'
        && $request['pwd'] === 'Hacienda-Prueba-01');
});

test('does not retry credentials that Hacienda already rejected', function (array $body, int $status) {
    $company = companyWithMhCredentials();
    Http::fake([MH_AUTH_URL => Http::response($body, $status)]);
    $service = app(MhAuthService::class);

    expect(fn () => $service->token($company->id, Environment::SANDBOX))
        ->toThrow(RuntimeException::class, 'Hacienda rechazó las credenciales');
    expect(fn () => $service->token($company->id, Environment::SANDBOX))
        ->toThrow(RuntimeException::class, 'Hacienda ya rechazó estas credenciales');

    Http::assertSentCount(1);
})->with([
    'controlled error' => [['status' => 'ERROR', 'error' => 'Unauthorized', 'message' => 'Usuario no valido'], 200],
    'unauthorized' => [['status' => 'ERROR', 'message' => 'Usuario no valido'], 401],
]);

test('authenticates again once the rejected credentials are updated', function () {
    $company = companyWithMhCredentials();
    Http::fakeSequence(MH_AUTH_URL)
        ->push(['status' => 'ERROR', 'message' => 'Usuario no valido'])
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token']]);
    $service = app(MhAuthService::class);

    expect(fn () => $service->token($company->id, Environment::SANDBOX))->toThrow(RuntimeException::class);

    MhCredentials::query()->where('company_id', $company->id)->firstOrFail()->update(['password' => 'Hacienda-Nueva-02']);

    expect($service->token($company->id, Environment::SANDBOX))->toBe('mh-token');

    Http::assertSentCount(2);
});

test('a failure of the service does not block the credentials', function (Closure $failure) {
    $company = companyWithMhCredentials();
    Http::fakeSequence(MH_AUTH_URL)
        ->pushResponse($failure())
        ->push(['status' => 'OK', 'body' => ['token' => 'mh-token']]);
    $service = app(MhAuthService::class);

    expect(fn () => $service->token($company->id, Environment::SANDBOX))->toThrow(Exception::class);
    expect($service->token($company->id, Environment::SANDBOX))->toBe('mh-token');
})->with([
    'server error' => fn () => fn () => Http::response('Service Unavailable', 503),
    'connection failure' => fn () => fn () => Http::failedConnection(),
]);

test('a company has no token without active credentials', function () {
    $company = DteApiTestData::company();
    Http::fake();

    expect(fn () => app(MhAuthService::class)->token($company->id, Environment::SANDBOX))
        ->toThrow(RuntimeException::class, 'No hay credenciales de Hacienda configuradas');

    Http::assertNothingSent();
});
