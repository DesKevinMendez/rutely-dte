<?php

namespace App;

/**
 * DTE types (CAT-002) this system can issue. Other CAT-002 codes stay in the
 * catalog but are rejected at issuance until they have a document builder.
 */
enum IssuableDteType: string
{
    case CONSUMER_INVOICE = '01';
    case TAX_CREDIT = '03';
    case EXCLUDED_SUBJECT = '14';
}
