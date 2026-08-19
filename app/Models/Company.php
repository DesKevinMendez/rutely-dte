<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'address',
    'phone',
    'nit',
    'nrc',
    'commercial_name',
    'economic_activity_code',
    'establishment_type',
    'departament_id',
    'municipality_id',
    'district_id',
    'email',
    'mh_establishment_code',
    'mh_pos_code',
    'own_establishment_code',
    'own_pos_code',
])]
class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasApiTokens, HasFactory, HasUuids;
}
