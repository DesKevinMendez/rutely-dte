<?php

namespace App\Models;

use Database\Factories\DteCorrelativeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'key', 'last_value'])]
class DteCorrelative extends Model
{
    /** @use HasFactory<DteCorrelativeFactory> */
    use HasFactory, HasUuids;

    /**
     * Normativa 2.0, rule 7.1.2: one consecutive per establishment, restarted every fiscal year.
     */
    public static function keyFor(Company $company, int $fiscalYear): string
    {
        return sprintf('%d-%s', $fiscalYear, $company->mh_establishment_code);
    }
}
