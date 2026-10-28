<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DteTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => '01', 'description' => 'Factura'],
            ['code' => '03', 'description' => 'Comprobante de crédito fiscal'],
            ['code' => '04', 'description' => 'Nota de remisión'],
            ['code' => '05', 'description' => 'Nota de crédito'],
            ['code' => '06', 'description' => 'Nota de débito'],
            ['code' => '07', 'description' => 'Comprobante de retención'],
            ['code' => '08', 'description' => 'Comprobante de liquidación'],
            ['code' => '09', 'description' => 'Documento contable de liquidación'],
            ['code' => '11', 'description' => 'Facturas de exportación'],
            ['code' => '14', 'description' => 'Factura de sujeto excluido'],
            ['code' => '15', 'description' => 'Comprobante de donación'],
            ['code' => '17', 'description' => 'Evento de Operaciones Especiales'],
            ['code' => '18', 'description' => 'Evento de Retorno'],
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

        DB::table('dte_types')->upsert($rows, ['code'], ['description', 'updated_at']);
    }
}
