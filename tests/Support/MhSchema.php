<?php

namespace Tests\Support;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Validates documents against verbatim copies of the official MH Json Schemas
 * (svfe-json-schemas, published at factura.gob.sv on 2026-08-11).
 */
class MhSchema
{
    public const CONSUMER_INVOICE = 'fe-f-v2';

    public const TAX_CREDIT = 'fe-ccf-v4';

    public const EXCLUDED_SUBJECT = 'fe-fse-v2';

    public const INVALIDATION = 'invalidacion-schema-v3';

    /**
     * Whole numbers are encoded without a fraction because the validator reports
     * some of them as not multiple of 0.01 when they are floats (90.0 fails, 90 passes).
     *
     * @param  array<string, mixed>  $document
     * @return array<string, array<int, string>>
     */
    public static function errors(array $document, string $schema): array
    {
        $validator = new Validator;
        $validator->setMaxErrors(50);

        $data = json_decode((string) json_encode($document, JSON_THROW_ON_ERROR));
        $result = $validator->validate($data, (string) file_get_contents(__DIR__."/mh-schemas/{$schema}.json"));

        return $result->isValid() ? [] : (new ErrorFormatter)->format($result->error());
    }
}
