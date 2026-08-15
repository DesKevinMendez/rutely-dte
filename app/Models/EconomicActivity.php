<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EconomicActivity extends Model
{
    /** @use HasFactory<\Database\Factories\EconomicActivityFactory> */
    use HasFactory, HasUuids;
}
