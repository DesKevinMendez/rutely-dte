<?php

declare(strict_types=1);

use Rutely\DteSigned\Certificate\MhCertificate;
use Rutely\DteSigned\Crypto\Base64Url;
use Rutely\DteSigned\Crypto\Rs512Signer;
use Rutely\DteSigned\Tests\Support\OfficialMhFixture;

it('creates a compact JWS with the RS512 protected header', function (): void {
    $certificate = MhCertificate::fromXml(
        OfficialMhFixture::certificateXml(),
        OfficialMhFixture::PASSWORD,
    );
    $jws = (new Rs512Signer())->sign('{"test":true}', $certificate->privateKeyPem());
    $parts = explode('.', $jws);

    expect($parts)->toHaveCount(3)
        ->and(Base64Url::decode($parts[0]))->toBe('{"alg":"RS512"}')
        ->and(Base64Url::decode($parts[1]))->toBe('{"test":true}');
});
