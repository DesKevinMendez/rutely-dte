<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DteInvalidation extends Model
{
    /** @use HasFactory<\Database\Factories\DteInvalidationFactory> */
    use HasFactory, HasUuids;
}
