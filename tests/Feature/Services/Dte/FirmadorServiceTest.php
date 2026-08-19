<?php

use App\Environment;
use App\Models\MhCertificates;
use App\Services\Dte\FirmadorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Rutely\DteSigned\DteSigner;
use Rutely\DteSigned\Tests\Support\OfficialMhFixture;

function createCompanyForSigning(string $nit): string
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
        'name' => 'Rutely Test',
        'address' => 'San Salvador, El Salvador',
        'phone' => '22223333',
        'nit' => $nit,
        'commercial_name' => 'Rutely Test',
        'economic_activity_code' => '62010',
        'establishment_type' => '01',
        'departament_id' => $departamentId,
        'municipality_id' => $municipalityId,
        'email' => 'billing@example.test',
        'mh_establishment_code' => 'M001',
        'mh_pos_code' => 'P001',
        'own_establishment_code' => 'M001',
        'own_pos_code' => 'P001',
        'environment' => Environment::SANDBOX->value,
    ]);

    return $companyId;
}

function storeCertificateForSigning(
    string $companyId,
    Environment $environment = Environment::SANDBOX,
    bool $active = true,
): MhCertificates {
    return MhCertificates::query()->create([
        'company_id' => $companyId,
        'environment' => $environment,
        'nit' => OfficialMhFixture::NIT,
        'encrypted_certificate' => OfficialMhFixture::certificateXml(),
        'encrypted_private_key_password' => OfficialMhFixture::PASSWORD,
        'active' => $active,
    ]);
}

function dteForSigning(): array
{
    return [
        'identificacion' => [
            'version' => 1,
            'ambiente' => '00',
            'tipoDte' => '01',
            'numeroControl' => 'DTE-01-M001P001-000000000000001',
            'codigoGeneracion' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
        ],
        'emisor' => [
            'nit' => OfficialMhFixture::NIT,
            'nombre' => 'CHAMBA CABAL',
        ],
        'resumen' => [
            'totalPagar' => 12.34,
            'observaciones' => null,
        ],
        'extension' => null,
        'apendice' => [],
    ];
}

test('it signs a DTE using the certificate stored for the company and environment', function () {
    $companyId = createCompanyForSigning(OfficialMhFixture::NIT);
    $storedCertificate = storeCertificateForSigning($companyId);
    $dte = dteForSigning();

    $expected = DteSigner::fromCertificateXml(
        OfficialMhFixture::certificateXml(),
        OfficialMhFixture::PASSWORD,
    )->sign($dte);

    $signed = app(FirmadorService::class)->signDocument(
        $companyId,
        $dte,
        Environment::SANDBOX,
        OfficialMhFixture::NIT,
    );

    $rawCertificate = DB::table('mh_certificates')->where('id', $storedCertificate->id)->first();

    expect($signed)->toBe($expected)
        ->and(substr_count($signed, '.'))->toBe(2)
        ->and($rawCertificate->encrypted_certificate)->not->toBe(OfficialMhFixture::certificateXml())
        ->and($rawCertificate->encrypted_private_key_password)->not->toBe(OfficialMhFixture::PASSWORD)
        ->and($storedCertificate->fresh()->encrypted_certificate)->toBe(OfficialMhFixture::certificateXml())
        ->and($storedCertificate->fresh()->encrypted_private_key_password)->toBe(OfficialMhFixture::PASSWORD);
});

test('it fails closed when the company has no signing material for the environment', function () {
    expect(fn () => app(FirmadorService::class)->signDocument(
        (string) Str::uuid(),
        dteForSigning(),
        Environment::SANDBOX,
        OfficialMhFixture::NIT,
    ))->toThrow(
        RuntimeException::class,
        'No hay material de firma configurado para la empresa y el ambiente seleccionados.',
    );
});

test('it rejects an inactive certificate', function () {
    $companyId = createCompanyForSigning(OfficialMhFixture::NIT);
    storeCertificateForSigning($companyId, active: false);

    expect(fn () => app(FirmadorService::class)->signDocument(
        $companyId,
        dteForSigning(),
        Environment::SANDBOX,
        OfficialMhFixture::NIT,
    ))->toThrow(RuntimeException::class, 'El certificado configurado está inactivo.');
});

test('it rejects a certificate whose NIT does not match the issuer', function () {
    $companyId = createCompanyForSigning('06142812901015');
    storeCertificateForSigning($companyId);

    expect(fn () => app(FirmadorService::class)->signDocument(
        $companyId,
        dteForSigning(),
        Environment::SANDBOX,
        '0614-281290-101-5',
    ))->toThrow(RuntimeException::class, 'El NIT del certificado no coincide con el NIT de la empresa.');
});
