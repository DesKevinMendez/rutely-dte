<?php

declare(strict_types=1);

namespace App\Services\Dte;

use App\Environment;
use App\Models\MhCertificates;
use RuntimeException;
use Rutely\DteSigned\Certificate\MhCertificate;
use Rutely\DteSigned\DteSigner;

final class FirmadorService
{
    public function signDocument(
        string $companyId,
        array|object|string $dteJson,
        Environment|string $environment,
        string $issuerNit,
    ): string {
        $environmentValue = $environment instanceof Environment ? $environment->value : $environment;

        $storedCertificate = MhCertificates::query()
            ->where('company_id', $companyId)
            ->where('environment', $environmentValue)
            ->latest('updated_at')
            ->first();

        if ($storedCertificate === null) {
            throw new RuntimeException('No hay material de firma configurado para la empresa y el ambiente seleccionados.');
        }

        if (! $storedCertificate->active) {
            throw new RuntimeException('El certificado configurado está inactivo.');
        }

        $certificateXml = $storedCertificate->encrypted_certificate;
        $password = $storedCertificate->encrypted_private_key_password;

        if (! is_string($certificateXml) || ! is_string($password)) {
            throw new RuntimeException('El material de firma configurado no es válido.');
        }

        $certificate = MhCertificate::fromXml($certificateXml, $password);

        $this->assertCertificateIssuer($certificate, $issuerNit);

        $signer = new DteSigner($certificate);

        return is_string($dteJson)
            ? $signer->signJson($dteJson)
            : $signer->sign($dteJson);
    }

    private function assertCertificateIssuer(MhCertificate $certificate, string $issuerNit): void
    {
        if (
            $certificate->nit === null
            || $this->normalizeNit($certificate->nit) !== $this->normalizeNit($issuerNit)
        ) {
            throw new RuntimeException('El NIT del certificado no coincide con el NIT de la empresa.');
        }
    }

    private function normalizeNit(string $nit): string
    {
        return preg_replace('/\D+/', '', $nit) ?? '';
    }
}
