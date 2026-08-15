<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MhCertificates extends Model
{
    /** @use HasFactory<\Database\Factories\MhCertificatesFactory> */
    use HasFactory, HasUuids;
}
