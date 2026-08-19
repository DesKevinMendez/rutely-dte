<?php

declare(strict_types=1);

namespace Rutely\DteSigned\Certificate;

use Rutely\DteSigned\Exceptions\InvalidCertificateException;
use Rutely\DteSigned\Exceptions\InvalidPasswordException;

final readonly class MhCertificate
{
    private function __construct(
        public ?string $nit,
        public string $algorithm,
        public string $format,
        private string $privateKeyDer,
    ) {
    }

    public static function fromXml(string $xml, string $password): self
    {
        if ($password === '') {
            throw new InvalidPasswordException('La contraseña de la llave privada es requerida.');
        }

        $privateKeyBlock = self::extractBlock($xml, 'privateKey');
        $encodedKey = self::extractValue($privateKeyBlock, 'encodied');
        $expectedPasswordHash = self::extractValue($privateKeyBlock, 'clave');

        if ($encodedKey === null || $encodedKey === '') {
            throw new InvalidCertificateException('El certificado no contiene la llave privada encodied.');
        }

        if ($expectedPasswordHash === null || $expectedPasswordHash === '') {
            throw new InvalidCertificateException('El certificado no contiene el hash de la contraseña privada.');
        }

        $passwordHash = hash('sha512', $password);

        if (! hash_equals(strtolower($expectedPasswordHash), strtolower($passwordHash))) {
            throw new InvalidPasswordException('Password del certificado no es válido.');
        }

        $normalizedKey = preg_replace('/\s+/', '', $encodedKey);
        $privateKeyDer = $normalizedKey === null ? false : base64_decode($normalizedKey, true);

        if ($privateKeyDer === false || $privateKeyDer === '') {
            throw new InvalidCertificateException('La llave privada del certificado no es Base64 PKCS#8 válida.');
        }

        $algorithm = self::extractValue($privateKeyBlock, 'algorithm') ?? 'RSA';
        $format = self::extractValue($privateKeyBlock, 'format') ?? 'PKCS#8';

        if (strcasecmp($algorithm, 'RSA') !== 0) {
            throw new InvalidCertificateException('El algoritmo de la llave privada debe ser RSA.');
        }

        return new self(
            nit: self::extractValue($xml, 'nit'),
            algorithm: $algorithm,
            format: $format,
            privateKeyDer: $privateKeyDer,
        );
    }

    public function privateKeyPem(): string
    {
        return "-----BEGIN PRIVATE KEY-----\n"
            .chunk_split(base64_encode($this->privateKeyDer), 64, "\n")
            ."-----END PRIVATE KEY-----\n";
    }

    private static function extractBlock(string $xml, string $tag): string
    {
        $pattern = sprintf('/<%1$s(?:\s[^>]*)?>(.*?)<\/%1$s>/is', preg_quote($tag, '/'));

        if (preg_match($pattern, $xml, $matches) !== 1) {
            throw new InvalidCertificateException(sprintf('El certificado no contiene el nodo <%s>.', $tag));
        }

        return $matches[1];
    }

    private static function extractValue(string $xml, string $tag): ?string
    {
        $pattern = sprintf('/<%1$s(?:\s[^>]*)?>(.*?)<\/%1$s>/is', preg_quote($tag, '/'));

        if (preg_match($pattern, $xml, $matches) !== 1) {
            return null;
        }

        return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }
}
