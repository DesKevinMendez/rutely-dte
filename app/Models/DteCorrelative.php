<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'key', 'last_value'])]
class DteCorrelative extends Model
{
    /** @use HasFactory<\Database\Factories\DteCorrelativeFactory> */
    use HasFactory, HasUuids;
}
