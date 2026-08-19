<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'generation_code',
    'control_number',
    'dte_type',
    'version',
    'environment',
    'status',
    'issuer_nit',
    'receiver_document',
    'total_amount',
    'original_json',
    'signed_json',
    'received_seal',
    'pdf_url',
    'observations',
    'mh_response_json',
    'receiver_id',
    'contingency_event_id',
])]
class Dte extends Model
{
    /** @use HasFactory<\Database\Factories\DteFactory> */
    use HasFactory, HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'original_json' => 'array',
            'mh_response_json' => 'array',
            'total_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Receivers, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Receivers::class);
    }

    /** @return HasMany<DteInvalidation, $this> */
    public function invalidations(): HasMany
    {
        return $this->hasMany(DteInvalidation::class);
    }
}
