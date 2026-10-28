<?php

namespace App\Models;

use Database\Factories\ItemTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    /** @use HasFactory<ItemTypeFactory> */
    use HasFactory, HasUuids;
}
