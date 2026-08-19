<?php

declare(strict_types=1);

use Rutely\DteSigned\Certificate\MhCertificate;
use Rutely\DteSigned\Exceptions\InvalidPasswordException;
use Rutely\DteSigned\Tests\Support\OfficialMhFixture;

it('loads an MH certificate and validates the SHA-512 private password', function (): void {
    $certificate = MhCertificate::fromXml(
        OfficialMhFixture::certificateXml(),
        OfficialMhFixture::PASSWORD,
    );

    expect($certificate->nit)->toBe(OfficialMhFixture::NIT)
        ->and($certificate->algorithm)->toBe('RSA')
        ->and($certificate->format)->toBe('PKCS#8')
        ->and($certificate->privateKeyPem())->toStartWith('-----BEGIN PRIVATE KEY-----');
});

it('rejects an invalid private key password', function (): void {
    MhCertificate::fromXml(OfficialMhFixture::certificateXml(), 'incorrecta');
})->throws(InvalidPasswordException::class, 'Password del certificado no es válido.');
