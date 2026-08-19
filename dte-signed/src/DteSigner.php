<?php

declare(strict_types=1);

namespace Rutely\DteSigned;

use JsonException;
use Rutely\DteSigned\Certificate\MhCertificate;
use Rutely\DteSigned\Crypto\Rs512Signer;
use Rutely\DteSigned\Serialization\JacksonPrettyPrinter;

final readonly class DteSigner
{
    public function __construct(
        private MhCertificate $certificate,
        private JacksonPrettyPrinter $serializer = new JacksonPrettyPrinter(),
        private Rs512Signer $signer = new Rs512Signer(),
    ) {
    }

    public static function fromCertificateXml(string $certificateXml, string $password): self
    {
        return new self(MhCertificate::fromXml($certificateXml, $password));
    }

    /** @throws JsonException */
    public function sign(array|object $dte): string
    {
        return $this->signer->sign(
            $this->serializer->encode($dte),
            $this->certificate->privateKeyPem(),
        );
    }

    /** @throws JsonException */
    public function signJson(string $dteJson): string
    {
        $dte = json_decode($dteJson, flags: JSON_THROW_ON_ERROR);

        if (! is_object($dte)) {
            throw new JsonException('dteJson debe contener un objeto JSON.');
        }

        return $this->sign($dte);
    }
}
