<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

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

    /**
     * Keep only the most recent attempt of each transmitted resource and operation,
     * so a retried transmission is listed once with its current attempt number.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function latestAttempts(Builder $query): void
    {
        $query->whereNotExists(function (QueryBuilder $newerAttempts): void {
            $newerAttempts->selectRaw('1')
                ->from($this->getTable().' as newer_attempts')
                ->whereColumn('newer_attempts.transmittable_type', $this->qualifyColumn('transmittable_type'))
                ->whereColumn('newer_attempts.transmittable_id', $this->qualifyColumn('transmittable_id'))
                ->whereColumn('newer_attempts.operation', $this->qualifyColumn('operation'))
                ->whereColumn('newer_attempts.attempt', '>', $this->qualifyColumn('attempt'));
        });
    }
}
