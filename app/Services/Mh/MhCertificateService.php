<?php

namespace App\Services\Mh;

use App\Models\Company;
use DomainException;
use Rutely\DteSigned\Certificate\MhCertificate;
use Throwable;

class MhCertificateService
{
    public function validateForCompany(string $companyId, string $xml, string $password = ''): string
    {
        try {
            $certificate = MhCertificate::fromXml($xml, $password);
        } catch (Throwable) {
            throw new DomainException('El certificado de Hacienda no es válido o la contraseña privada es incorrecta.');
        }

        $company = Company::query()->findOrFail($companyId);
        $certificateNit = preg_replace('/\D+/', '', (string) $certificate->nit) ?? '';
        $companyNit = preg_replace('/\D+/', '', (string) $company->nit) ?? '';

        if ($certificateNit === '' || $certificateNit !== $companyNit) {
            throw new DomainException('El NIT del certificado no coincide con el NIT de la empresa.');
        }

        return (string) $certificate->nit;
    }
}
