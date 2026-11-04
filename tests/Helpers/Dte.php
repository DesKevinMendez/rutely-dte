<?php

use App\Environment;
use App\Models\Company;
use App\Models\MhCertificates;
use App\Services\Mh\MhTransmissionService;
use Rutely\DteSigned\Tests\Support\OfficialMhFixture;
use Tests\Support\DteApiTestData;

use function Pest\Laravel\mock;

/**
 * A company that signs for real with the official test certificate. Only the
 * transmission to MH is replaced, so a test exercises the whole document chain.
 *
 * @param  array<string, mixed>  $overrides
 */
function companyReadyToIssue(array $overrides = []): Company
{
    $company = DteApiTestData::company(['nit' => OfficialMhFixture::NIT, ...$overrides]);

    MhCertificates::query()->create([
        'company_id' => $company->id,
        'environment' => Environment::SANDBOX,
        'nit' => OfficialMhFixture::NIT,
        'encrypted_certificate' => OfficialMhFixture::certificateXml(),
        'encrypted_private_key_password' => OfficialMhFixture::PASSWORD,
        'active' => true,
    ]);

    $transmission = mock(MhTransmissionService::class);
    $transmission->shouldReceive('transmitDte')->andReturn(DteApiTestData::mhResult());
    $transmission->shouldReceive('transmitInvalidation')->andReturn(DteApiTestData::mhResult());

    return $company;
}

/**
 * The document inside a signed JWS, exactly as it would reach MH.
 *
 * @return array<string, mixed>
 */
function signedDocument(string $jws): array
{
    $payload = base64_decode(strtr(explode('.', $jws)[1], '-_', '+/'));

    return json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
}
