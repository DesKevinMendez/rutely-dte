<?php

namespace Database\Seeders;

use App\Models\Role as PermissionRole;
use App\Role as AppRole;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (AppRole::cases() as $role) {
            PermissionRole::findOrCreate($role->value, 'web');
        }
    }
}
