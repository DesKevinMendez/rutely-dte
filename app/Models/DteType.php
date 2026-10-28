<?php

namespace App\Models;

use Database\Factories\DteTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DteType extends Model
{
    /** @use HasFactory<DteTypeFactory> */
    use HasFactory, HasUuids;
}
