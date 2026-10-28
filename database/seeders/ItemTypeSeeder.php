<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 1, 'description' => 'Bienes'],
            ['code' => 2, 'description' => 'Servicios'],
            ['code' => 3, 'description' => 'Ambos (Bienes y Servicios, incluye los dos inherente a los Productos o servicios)'],
            ['code' => 4, 'description' => 'Otros tributos por ítem'],
        ];

        $now = now();

        $rows = array_map(
            fn (array $type): array => [
                'id' => Str::uuid()->toString(),
                ...$type,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $types
        );

        DB::table('item_types')->insert($rows);
    }
}
