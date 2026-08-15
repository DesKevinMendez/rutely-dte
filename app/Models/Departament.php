<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Departament extends Model
{
    /** @use HasFactory<\Database\Factories\DepartamentFactory> */
    use HasFactory, HasUuids;
}
