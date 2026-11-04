<?php

namespace App\Services\Dte;

/**
 * Values taken from the official MH documentation (Catálogos - Facturación
 * Electrónica, Manual Funcional V 2.0 and Json Schemas) that have no table yet.
 */
class MhCatalog
{
    /** Manual Funcional V 2.0, section XI: positions 1 to 4 of the control number. */
    public const ESTABLISHMENT_CODE_PATTERN = '/^[MBSP][0-9]{3}$/';

    /** Manual Funcional V 2.0, section XI: positions 5 to 8 of the control number. */
    public const POS_CODE_PATTERN = '/^P[0-9]{3}$/';
}
