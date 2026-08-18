<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class MunicipalitiesSeeder extends Seeder
{
    public function run(): void
    {
        $municipalities = [
            ['departament_code' => '01', 'code' => '13', 'name' => 'AHUACHAPAN NORTE'],
            ['departament_code' => '01', 'code' => '14', 'name' => 'AHUACHAPAN CENTRO'],
            ['departament_code' => '01', 'code' => '15', 'name' => 'AHUACHAPAN SUR'],
            ['departament_code' => '02', 'code' => '14', 'name' => 'SANTA ANA NORTE'],
            ['departament_code' => '02', 'code' => '15', 'name' => 'SANTA ANA CENTRO'],
            ['departament_code' => '02', 'code' => '16', 'name' => 'SANTA ANA ESTE'],
            ['departament_code' => '02', 'code' => '17', 'name' => 'SANTA ANA OESTE'],
            ['departament_code' => '03', 'code' => '17', 'name' => 'SONSONATE NORTE'],
            ['departament_code' => '03', 'code' => '18', 'name' => 'SONSONATE CENTRO'],
            ['departament_code' => '03', 'code' => '19', 'name' => 'SONSONATE ESTE'],
            ['departament_code' => '03', 'code' => '20', 'name' => 'SONSONATE OESTE'],
            ['departament_code' => '04', 'code' => '34', 'name' => 'CHALATENANGO NORTE'],
            ['departament_code' => '04', 'code' => '35', 'name' => 'CHALATENANGO CENTRO'],
            ['departament_code' => '04', 'code' => '36', 'name' => 'CHALATENANGO SUR'],
            ['departament_code' => '05', 'code' => '23', 'name' => 'LA LIBERTAD NORTE'],
            ['departament_code' => '05', 'code' => '24', 'name' => 'LA LIBERTAD CENTRO'],
            ['departament_code' => '05', 'code' => '25', 'name' => 'LA LIBERTAD OESTE'],
            ['departament_code' => '05', 'code' => '26', 'name' => 'LA LIBERTAD ESTE'],
            ['departament_code' => '05', 'code' => '27', 'name' => 'LA LIBERTAD COSTA'],
            ['departament_code' => '05', 'code' => '28', 'name' => 'LA LIBERTAD SUR'],
            ['departament_code' => '06', 'code' => '20', 'name' => 'SAN SALVADOR NORTE'],
            ['departament_code' => '06', 'code' => '21', 'name' => 'SAN SALVADOR OESTE'],
            ['departament_code' => '06', 'code' => '22', 'name' => 'SAN SALVADOR ESTE'],
            ['departament_code' => '06', 'code' => '23', 'name' => 'SAN SALVADOR CENTRO'],
            ['departament_code' => '06', 'code' => '24', 'name' => 'SAN SALVADOR SUR'],
            ['departament_code' => '07', 'code' => '17', 'name' => 'CUSCATLAN NORTE'],
            ['departament_code' => '07', 'code' => '18', 'name' => 'CUSCATLAN SUR'],
            ['departament_code' => '08', 'code' => '23', 'name' => 'LA PAZ OESTE'],
            ['departament_code' => '08', 'code' => '24', 'name' => 'LA PAZ CENTRO'],
            ['departament_code' => '08', 'code' => '25', 'name' => 'LA PAZ ESTE'],
            ['departament_code' => '09', 'code' => '10', 'name' => 'CABAÑAS ESTE'],
            ['departament_code' => '09', 'code' => '11', 'name' => 'CABAÑAS OESTE'],
            ['departament_code' => '10', 'code' => '14', 'name' => 'SAN VICENTE NORTE'],
            ['departament_code' => '10', 'code' => '15', 'name' => 'SAN VICENTE SUR'],
            ['departament_code' => '11', 'code' => '24', 'name' => 'USULUTAN NORTE'],
            ['departament_code' => '11', 'code' => '25', 'name' => 'USULUTAN ESTE'],
            ['departament_code' => '11', 'code' => '26', 'name' => 'USULUTAN OESTE'],
            ['departament_code' => '12', 'code' => '21', 'name' => 'SAN MIGUEL NORTE'],
            ['departament_code' => '12', 'code' => '22', 'name' => 'SAN MIGUEL CENTRO'],
            ['departament_code' => '12', 'code' => '23', 'name' => 'SAN MIGUEL OESTE'],
            ['departament_code' => '13', 'code' => '27', 'name' => 'MORAZAN NORTE'],
            ['departament_code' => '13', 'code' => '28', 'name' => 'MORAZAN SUR'],
            ['departament_code' => '14', 'code' => '19', 'name' => 'LA UNION NORTE'],
            ['departament_code' => '14', 'code' => '20', 'name' => 'LA UNION SUR'],
        ];

        $departamentIds = DB::table('departaments')->pluck('id', 'code');
        $now = now();

        $rows = array_map(function (array $municipality) use ($departamentIds, $now): array {
            $departamentId = $departamentIds[$municipality['departament_code']] ?? null;

            if ($departamentId === null) {
                throw new RuntimeException(
                    "Missing departament {$municipality['departament_code']} while seeding municipalities."
                );
            }

            return [
                'id' => Uuid::uuid5(
                    Uuid::NAMESPACE_URL,
                    "https://rutely.biz/dte/catalogs/municipalities/{$municipality['departament_code']}/{$municipality['code']}"
                )->toString(),
                'departament_id' => $departamentId,
                'departament_code' => $municipality['departament_code'],
                'code' => $municipality['code'],
                'name' => $municipality['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $municipalities);

        DB::table('municipalities')->upsert(
            $rows,
            ['id'],
            ['departament_id', 'departament_code', 'code', 'name', 'updated_at']
        );
    }
}
