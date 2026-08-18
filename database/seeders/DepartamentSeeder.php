<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartamentSeeder extends Seeder
{
    public function run(): void
    {
        $departaments = [
            ['code' => '01', 'name' => 'Ahuachapán'],
            ['code' => '02', 'name' => 'Santa Ana'],
            ['code' => '03', 'name' => 'Sonsonate'],
            ['code' => '04', 'name' => 'Chalatenango'],
            ['code' => '05', 'name' => 'La Libertad'],
            ['code' => '06', 'name' => 'San Salvador'],
            ['code' => '07', 'name' => 'Cuscatlán'],
            ['code' => '08', 'name' => 'La Paz'],
            ['code' => '09', 'name' => 'Cabañas'],
            ['code' => '10', 'name' => 'San Vicente'],
            ['code' => '11', 'name' => 'Usulután'],
            ['code' => '12', 'name' => 'San Miguel'],
            ['code' => '13', 'name' => 'Morazán'],
            ['code' => '14', 'name' => 'La Unión'],
        ];

        $now = now();

        $rows = array_map(
            fn (array $departament): array => [
                'id' => Str::uuid()->toString(),
                ...$departament,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $departaments
        );

        DB::table('departaments')->insert($rows);
    }
}
