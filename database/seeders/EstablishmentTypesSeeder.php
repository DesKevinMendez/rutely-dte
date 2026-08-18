<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class EstablishmentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => '01', 'description' => 'Sucursal'],
            ['code' => '02', 'description' => 'Casa Matriz'],
            ['code' => '04', 'description' => 'Bodega'],
            ['code' => '07', 'description' => 'Patio'],
        ];

        $now = now();

        $rows = array_map(
            fn (array $type): array => [
                'id' => Uuid::uuid5(
                    Uuid::NAMESPACE_URL,
                    "https://rutely.biz/dte/catalogs/establishment-types/{$type['code']}"
                )->toString(),
                ...$type,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $types
        );

        DB::table('establishment_types')->upsert(
            $rows,
            ['id'],
            ['code', 'description', 'updated_at']
        );
    }
}
