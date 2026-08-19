<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'company_id',
    'transmittable_type',
    'transmittable_id',
    'operation',
    'attempt',
    'request_json',
    'response_json',
    'http_status',
    'status',
    'error',
    'sent_at',
    'responded_at',
])]
class MhTransmission extends Model
{
    use HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'request_json' => 'array',
            'response_json' => 'array',
            'attempt' => 'integer',
            'http_status' => 'integer',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function transmittable(): MorphTo
    {
        return $this->morphTo();
    }
}
