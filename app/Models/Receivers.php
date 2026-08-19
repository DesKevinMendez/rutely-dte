<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'document_type',
    'document_number',
    'nrc',
    'name',
    'economic_activity_code',
    'economic_activity_description',
    'departament_id',
    'municipality_id',
    'district_id',
    'address_complement',
    'phone',
    'email',
])]
class Receivers extends Model
{
    /** @use HasFactory<\Database\Factories\ReceiversFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
