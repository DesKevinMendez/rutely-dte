<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => '13', 'description' => 'DUI'],
            ['code' => '36', 'description' => 'NIT'],
            ['code' => '03', 'description' => 'Pasaporte'],
            ['code' => '37', 'description' => 'Otro'],
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

        DB::table('document_types')->insert($rows);
    }
}
