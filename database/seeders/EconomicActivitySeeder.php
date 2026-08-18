<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class EconomicActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = array_merge(
            require __DIR__.'/data/economic_activities_1.php',
            require __DIR__.'/data/economic_activities_2.php',
            require __DIR__.'/data/economic_activities_3.php',
            require __DIR__.'/data/economic_activities_4.php',
        );

        $now = now();

        $rows = array_map(
            fn (array $activity): array => [
                'id' => Uuid::uuid5(
                    Uuid::NAMESPACE_URL,
                    "https://rutely.biz/dte/catalogs/economic-activities/{$activity['code']}"
                )->toString(),
                ...$activity,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $activities
        );

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('economic_activities')->upsert(
                $chunk,
                ['id'],
                ['code', 'description', 'updated_at']
            );
        }
    }
}
