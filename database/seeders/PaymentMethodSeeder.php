<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    /**
     * CAT-017 Forma de Pago, from Catálogos - Facturación Electrónica V1.1 (07/2026).
     *
     * @var array<int, array{code: string, description: string}>
     */
    public const METHODS = [
        ['code' => '01', 'description' => 'Billetes y monedas'],
        ['code' => '02', 'description' => 'Tarjeta Débito'],
        ['code' => '03', 'description' => 'Tarjeta Crédito'],
        ['code' => '04', 'description' => 'Cheque'],
        ['code' => '05', 'description' => 'Transferencia-Depósito Bancario'],
        ['code' => '08', 'description' => 'Dinero electrónico'],
        ['code' => '09', 'description' => 'Monedero electrónico'],
        ['code' => '11', 'description' => 'Bitcoin'],
        ['code' => '12', 'description' => 'Otras Criptomonedas'],
        ['code' => '13', 'description' => 'Cuentas por pagar del receptor'],
        ['code' => '14', 'description' => 'Giro bancario'],
        ['code' => '99', 'description' => 'Otros (se debe indicar el medio de pago)'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = array_map(
            fn (array $method): array => [
                'id' => Str::uuid()->toString(),
                ...$method,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::METHODS
        );

        DB::table('payment_methods')->upsert($rows, ['code'], ['description', 'updated_at']);
    }
}
