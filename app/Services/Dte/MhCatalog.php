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

    /** Normativa 2.0, Anexo IV: 9 or 14 digits, without hyphens or other special characters. */
    public const NIT_PATTERN = '/^(?:[0-9]{14}|[0-9]{9})$/';

    /** Normativa 2.0, Anexo IV: 9 digits, without hyphens or other special characters. */
    public const DUI_PATTERN = '/^[0-9]{9}$/';

    /** Normativa 2.0, Anexo II: 2 to 8 digits, without hyphens. */
    public const NRC_PATTERN = '/^[0-9]{2,8}$/';

    /** CAT-022: NIT. */
    public const NIT_DOCUMENT_TYPE = '36';

    /** CAT-022: DUI. */
    public const DUI_DOCUMENT_TYPE = '13';

    /** Aviso DGII of 2024-09-05: below this amount a consumer cannot be asked for an identity document. */
    public const CONSUMER_IDENTIFICATION_AMOUNT = 25000;

    /** CAT-015: Impuesto al Valor Agregado 13%. */
    public const IVA_TAX_CODE = '20';

    /** CAT-015 description of the IVA tax. */
    public const IVA_TAX_DESCRIPTION = 'Impuesto al Valor Agregado 13%';

    /** CAT-016: 1 Contado, 2 A crédito, 3 Otro. */
    public const OPERATION_CONDITIONS = [1, 2, 3];

    /** CAT-016: Contado. */
    public const CASH_CONDITION = 1;

    /** CAT-017: Billetes y monedas. */
    public const CASH_PAYMENT_METHOD = '01';

    /** CAT-018: Plazo. */
    public const PAYMENT_TERMS = ['01', '02', '03'];

    /** CAT-011: Servicios. */
    public const SERVICE_ITEM_TYPE = 2;

    /** CAT-011: Otros tributos por ítem. It needs codTributo, which the API doesn't support yet. */
    public const OTHER_TAXES_ITEM_TYPE = 4;

    /** CAT-014: Otra. Normativa 2.0, Anexo IV: the unit of a service. */
    public const OTHER_UNIT_OF_MEASURE = 99;

    /** CAT-014: Unidad de Medida. */
    public const UNITS_OF_MEASURE = [
        1, 2, 6, 9, 10, 13, 15, 18, 20, 22, 23, 24, 26, 30, 32, 33, 34, 36, 37, 38,
        39, 40, 42, 43, 44, 45, 46, 47, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 99,
    ];
}
