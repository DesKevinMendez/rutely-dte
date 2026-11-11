<?php

namespace App\Services\Dte;

/**
 * Tax identifiers in the format Hacienda receives them.
 */
class TaxIdentifier
{
    /**
     * Normativa 2.0, Anexo IV #21 and #41: the NRC goes without hyphens or leading zeros. One digit is kept so
     * a value made only of zeros still fails the length rule instead of becoming empty.
     */
    public static function nrc(string $value): string
    {
        return (string) preg_replace('/^0+(?=\d)/', '', str_replace('-', '', $value));
    }
}
