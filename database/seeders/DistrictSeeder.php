<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = array_merge(
            require __DIR__.'/data/districts_1.php',
            require __DIR__.'/data/districts_2.php',
        );

        $departamentIds = DB::table('departaments')->pluck('id', 'code');

        $municipalityIds = DB::table('municipalities')
            ->get(['id', 'departament_code', 'code'])
            ->mapWithKeys(
                fn (object $municipality): array => [
                    "{$municipality->departament_code}:{$municipality->code}" => $municipality->id,
                ]
            );

        $now = now();

        $rows = array_map(function (array $district) use ($departamentIds, $municipalityIds, $now): array {
            $departamentId = $departamentIds[$district['departament_code']] ?? null;
            $municipalityKey = "{$district['departament_code']}:{$district['municipality_code']}";
            $municipalityId = $municipalityIds[$municipalityKey] ?? null;

            if ($departamentId === null || $municipalityId === null) {
                throw new RuntimeException(
                    "Missing territorial parent for district {$district['departament_code']}-{$district['code']}."
                );
            }

            return [
                'id' => Str::uuid()->toString(),
                'departament_id' => $departamentId,
                'municipality_id' => $municipalityId,
                'code' => $district['code'],
                'name' => $district['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $districts);

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('districts')->insert($chunk);
        }
    }
}
