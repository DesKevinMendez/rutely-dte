<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'dte_id',
    'generation_code',
    'invalidation_type',
    'reason',
    'name_person_in_charge',
    'doc_type_person_in_charge',
    'doc_number_person_in_charge',
    'name_request',
    'doc_type_request',
    'doc_number_request',
    'original_json',
    'signed_json',
    'received_seal',
    'status',
    'observations',
    'environment',
])]
class DteInvalidation extends Model
{
    /** @use HasFactory<\Database\Factories\DteInvalidationFactory> */
    use HasFactory, HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'original_json' => 'array',
        ];
    }

    /** @return BelongsTo<Dte, $this> */
    public function dte(): BelongsTo
    {
        return $this->belongsTo(Dte::class);
    }
}
