<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class MhCredentials extends Model
{
    /** @use HasFactory<\Database\Factories\MhCredentialsFactory> */
    use HasFactory, HasUuids;
}
