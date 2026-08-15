<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContingencyEvent extends Model
{
    /** @use HasFactory<\Database\Factories\ContingencyEventFactory> */
    use HasFactory, HasUuids;
}
