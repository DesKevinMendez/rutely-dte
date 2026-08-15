<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Receivers extends Model
{
    /** @use HasFactory<\Database\Factories\ReceiversFactory> */
    use HasFactory, HasUuids;
}
