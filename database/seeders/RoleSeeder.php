<?php

namespace Database\Seeders;

use App\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as PermissionRole;

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
