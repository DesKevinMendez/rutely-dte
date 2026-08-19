<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'generation_code',
    'environment',
    'contingency_type',
    'reason',
    'start_date_at',
    'end_date_at',
    'original_json',
    'signed_json',
    'received_seal',
    'status',
    'observations',
])]
class ContingencyEvent extends Model
{
    /** @use HasFactory<\Database\Factories\ContingencyEventFactory> */
    use HasFactory, HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date_at' => 'datetime',
            'end_date_at' => 'datetime',
            'original_json' => 'array',
        ];
    }

    /** @return HasMany<Dte, $this> */
    public function dtes(): HasMany
    {
        return $this->hasMany(Dte::class);
    }
}
