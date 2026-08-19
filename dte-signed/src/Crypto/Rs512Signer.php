<?php

declare(strict_types=1);

namespace Rutely\DteSigned\Crypto;

use OpenSSLAsymmetricKey;
use Rutely\DteSigned\Exceptions\SigningException;

final class Rs512Signer
{
    private const PROTECTED_HEADER = '{"alg":"RS512"}';

    public function sign(string $payload, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if (! $privateKey instanceof OpenSSLAsymmetricKey) {
            throw new SigningException('No fue posible cargar la llave privada RSA.');
        }

        $header = Base64Url::encode(self::PROTECTED_HEADER);
        $encodedPayload = Base64Url::encode($payload);
        $signingInput = $header.'.'.$encodedPayload;

        $signed = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA512);

        if ($signed !== true) {
            throw new SigningException('No fue posible generar la firma RS512.');
        }

        return $signingInput.'.'.Base64Url::encode($signature);
    }
}
