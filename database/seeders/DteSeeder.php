<?php

namespace Database\Seeders;

use App\Environment;
use App\Models\Company;
use App\Models\Departament;
use App\Models\Dte;
use App\Models\DteCorrelative;
use App\Models\EconomicActivity;
use App\Models\Municipalities;
use App\Models\User;
use Illuminate\Database\Seeder;

class DteSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::query()->where('email', 'admin@dte.com')->firstOrFail();
        $company = Company::query()->findOrFail($adminUser->company_id);
        $environment = (string) ($company->environment ?? Environment::SANDBOX->value);
        $departmentCode = (string) Departament::query()->whereKey($company->departament_id)->value('code');
        $municipalityCode = (string) Municipalities::query()->whereKey($company->municipality_id)->value('code');
        $activityDescription = (string) EconomicActivity::query()
            ->where('code', $company->economic_activity_code)
            ->value('description');

        foreach ($this->documents() as $fixture) {
            $controlNumber = $this->controlNumber($company, $fixture);
            $payload = $this->documentPayload(
                $company,
                $fixture,
                $controlNumber,
                $environment,
                $departmentCode,
                $municipalityCode,
                $activityDescription,
            );
            $mhResponse = [
                'version' => 2,
                'ambiente' => $environment,
                'versionApp' => 2,
                'estado' => 'PROCESADO',
                'codigoGeneracion' => $fixture['generation_code'],
                'selloRecibido' => $fixture['seal'],
                'fhProcesamiento' => $fixture['date'].' '.$fixture['time'],
                'clasificaMsg' => '10',
                'codigoMsg' => '001',
                'descripcionMsg' => 'Documento de demostración procesado correctamente.',
                'observaciones' => [],
            ];

            Dte::query()->updateOrCreate(
                ['generation_code' => $fixture['generation_code']],
                [
                    'company_id' => $company->id,
                    'control_number' => $controlNumber,
                    'dte_type' => $fixture['dte_type'],
                    'version' => $fixture['dte_type'] === '03' ? '4' : '2',
                    'environment' => $environment,
                    'status' => 'PROCESADO',
                    'issuer_nit' => $company->nit,
                    'receiver_document' => $fixture['receiver']['numDocumento'],
                    'total_amount' => (int) round($payload['total'] * 100),
                    'original_json' => $payload['document'],
                    'signed_json' => 'demo-signed-jws-'.$fixture['generation_code'],
                    'received_seal' => $fixture['seal'],
                    'observations' => json_encode([], JSON_THROW_ON_ERROR),
                    'mh_response_json' => $mhResponse,
                ],
            );

            $this->syncCorrelative($company, $fixture);
        }
    }

    /**
     * @return list<array{
     *     generation_code: string,
     *     dte_type: string,
     *     sequence: int,
     *     date: string,
     *     time: string,
     *     seal: string,
     *     total_letras: string,
     *     receiver: array{
     *         tipoDocumento: string,
     *         numDocumento: string,
     *         nrc: string,
     *         nombre: string,
     *         codActividad: string,
     *         descActividad: string,
     *         direccion: array{departamento: string, municipio: string, complemento: string},
     *         telefono: string,
     *         correo: string
     *     },
     *     items: list<array{codigo: string, descripcion: string, cantidad: int|float, precioUni: int|float}>
     * }>
     */
    private function documents(): array
    {
        return [
            [
                'generation_code' => 'A1111111-1111-4111-8111-111111111111',
                'dte_type' => '01',
                'sequence' => 1,
                'date' => '2026-10-18',
                'time' => '08:32:00',
                'seal' => 'DEMO-SELLO-MH-ADM-0001',
                'total_letras' => 'ONCE DÓLARES CON 30/100 USD',
                'receiver' => [
                    'tipoDocumento' => '36',
                    'numDocumento' => '06141505921015',
                    'nrc' => '9876543',
                    'nombre' => 'Comercial Los Pinos, S.A. de C.V.',
                    'codActividad' => '47111',
                    'descActividad' => 'Venta al por menor en comercios no especializados',
                    'direccion' => [
                        'departamento' => '06',
                        'municipio' => '14',
                        'complemento' => 'Colonia Escalón, San Salvador',
                    ],
                    'telefono' => '22225551',
                    'correo' => 'compras@lospinos.example',
                ],
                'items' => [
                    ['codigo' => 'MON-001', 'descripcion' => 'Servicio de monitoreo GPS - octubre 2026', 'cantidad' => 1, 'precioUni' => 10],
                ],
            ],
            [
                'generation_code' => 'A2222222-2222-4222-8222-222222222222',
                'dte_type' => '01',
                'sequence' => 2,
                'date' => '2026-10-19',
                'time' => '10:14:00',
                'seal' => 'DEMO-SELLO-MH-ADM-0002',
                'total_letras' => 'VEINTIDÓS DÓLARES CON 60/100 USD',
                'receiver' => [
                    'tipoDocumento' => '36',
                    'numDocumento' => '06142301901014',
                    'nrc' => '6543210',
                    'nombre' => 'Distribuidora La Esperanza, S.A. de C.V.',
                    'codActividad' => '46900',
                    'descActividad' => 'Venta al por mayor de productos diversos',
                    'direccion' => [
                        'departamento' => '06',
                        'municipio' => '23',
                        'complemento' => 'Centro Comercial San Benito, local 12',
                    ],
                    'telefono' => '22225552',
                    'correo' => 'facturacion@laesperanza.example',
                ],
                'items' => [
                    ['codigo' => 'GPS-MONTHLY', 'descripcion' => 'Plan de monitoreo GPS mensual', 'cantidad' => 2, 'precioUni' => 8],
                    ['codigo' => 'INST-001', 'descripcion' => 'Instalación de dispositivo GPS', 'cantidad' => 1, 'precioUni' => 4],
                ],
            ],
            [
                'generation_code' => 'A3333333-3333-4333-8333-333333333333',
                'dte_type' => '01',
                'sequence' => 3,
                'date' => '2026-10-20',
                'time' => '11:05:00',
                'seal' => 'DEMO-SELLO-MH-ADM-0003',
                'total_letras' => 'CINCUENTA Y SEIS DÓLARES CON 50/100 USD',
                'receiver' => [
                    'tipoDocumento' => '36',
                    'numDocumento' => '06142812901017',
                    'nrc' => '1122334',
                    'nombre' => 'Inversiones Cuscatlán, S.A. de C.V.',
                    'codActividad' => '62010',
                    'descActividad' => 'Actividades de programación informática',
                    'direccion' => [
                        'departamento' => '06',
                        'municipio' => '14',
                        'complemento' => 'Alameda Manuel Enrique Araujo, edificio 3',
                    ],
                    'telefono' => '22225553',
                    'correo' => 'administracion@cuscatlan.example',
                ],
                'items' => [
                    ['codigo' => 'GPS-TRACKER', 'descripcion' => 'Dispositivo rastreador GPS', 'cantidad' => 1, 'precioUni' => 35],
                    ['codigo' => 'INST-002', 'descripcion' => 'Instalación y configuración', 'cantidad' => 1, 'precioUni' => 15],
                ],
            ],
            [
                'generation_code' => 'A4444444-4444-4444-8444-444444444444',
                'dte_type' => '01',
                'sequence' => 4,
                'date' => '2026-10-21',
                'time' => '14:40:00',
                'seal' => 'DEMO-SELLO-MH-ADM-0004',
                'total_letras' => 'CIENTO TRECE DÓLARES CON 00/100 USD',
                'receiver' => [
                    'tipoDocumento' => '36',
                    'numDocumento' => '06143219901018',
                    'nrc' => '2233445',
                    'nombre' => 'Talleres Automotrices del Centro, S.A. de C.V.',
                    'codActividad' => '45200',
                    'descActividad' => 'Mantenimiento y reparación de vehículos',
                    'direccion' => [
                        'departamento' => '06',
                        'municipio' => '23',
                        'complemento' => 'Boulevard Constitución, San Salvador',
                    ],
                    'telefono' => '22225554',
                    'correo' => 'pagos@tallerescentro.example',
                ],
                'items' => [
                    ['codigo' => 'LIC-SOFT', 'descripcion' => 'Licencia de plataforma de gestión de flota', 'cantidad' => 4, 'precioUni' => 25],
                ],
            ],
            [
                'generation_code' => 'A5555555-5555-4555-8555-555555555555',
                'dte_type' => '03',
                'sequence' => 1,
                'date' => '2026-10-22',
                'time' => '09:18:00',
                'seal' => 'DEMO-SELLO-MH-ADM-0005',
                'total_letras' => 'DOSCIENTOS VEINTISÉIS DÓLARES CON 00/100 USD',
                'receiver' => [
                    'tipoDocumento' => '36',
                    'numDocumento' => '06143844901019',
                    'nrc' => '3344556',
                    'nombre' => 'Transportes Metropolitanos, S.A. de C.V.',
                    'codActividad' => '49230',
                    'descActividad' => 'Transporte de carga por carretera',
                    'direccion' => [
                        'departamento' => '06',
                        'municipio' => '14',
                        'complemento' => 'Carretera Panamericana, km 8',
                    ],
                    'telefono' => '22225555',
                    'correo' => 'contabilidad@transmet.example',
                ],
                'items' => [
                    ['codigo' => 'GPS-PRO', 'descripcion' => 'Dispositivo GPS profesional', 'cantidad' => 2, 'precioUni' => 90],
                    ['codigo' => 'MON-PLUS', 'descripcion' => 'Servicio de monitoreo premium', 'cantidad' => 1, 'precioUni' => 20],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *     generation_code: string,
     *     dte_type: string,
     *     sequence: int,
     *     date: string,
     *     time: string,
     *     seal: string,
     *     total_letras: string,
     *     receiver: array<string, mixed>,
     *     items: list<array{codigo: string, descripcion: string, cantidad: int|float, precioUni: int|float}>
     * } $fixture
     * @return array{document: array<string, mixed>, total: float}
     */
    private function documentPayload(
        Company $company,
        array $fixture,
        string $controlNumber,
        string $environment,
        string $departmentCode,
        string $municipalityCode,
        string $activityDescription,
    ): array {
        $items = collect($fixture['items'])->values()->map(function (array $item, int $index): array {
            $quantity = (float) $item['cantidad'];
            $unitPrice = (float) $item['precioUni'];
            $taxedSale = round($quantity * $unitPrice, 2);

            return [
                'numItem' => $index + 1,
                'tipoItem' => 2,
                'cantidad' => $quantity,
                'codigo' => $item['codigo'],
                'uniMedida' => 59,
                'descripcion' => $item['descripcion'],
                'precioUni' => $unitPrice,
                'montoDescu' => 0,
                'ventaNoSuj' => 0,
                'ventaExenta' => 0,
                'ventaGravada' => $taxedSale,
                'tributos' => ['20'],
                'psv' => 0,
                'noGravado' => 0,
            ];
        })->all();
        $totalTaxed = round(array_sum(array_column($items, 'ventaGravada')), 2);
        $totalTax = round($totalTaxed * 0.13, 2);
        $total = round($totalTaxed + $totalTax, 2);

        return [
            'document' => [
                'identificacion' => [
                    'version' => $fixture['dte_type'] === '03' ? 4 : 2,
                    'ambiente' => $environment,
                    'tipoDte' => $fixture['dte_type'],
                    'numeroControl' => $controlNumber,
                    'codigoGeneracion' => $fixture['generation_code'],
                    'tipoModelo' => 1,
                    'tipoOperacion' => 1,
                    'fecEmi' => $fixture['date'],
                    'horEmi' => $fixture['time'],
                    'tipoMoneda' => 'USD',
                ],
                'emisor' => [
                    'nit' => $company->nit,
                    'nrc' => $company->nrc,
                    'nombre' => $company->name,
                    'codActividad' => $company->economic_activity_code,
                    'descActividad' => $activityDescription,
                    'nombreComercial' => $company->commercial_name,
                    'tipoEstablecimiento' => $company->establishment_type,
                    'direccion' => [
                        'departamento' => $departmentCode,
                        'municipio' => $municipalityCode,
                        'complemento' => $company->address,
                    ],
                    'telefono' => $company->phone,
                    'correo' => $company->email,
                ],
                'receptor' => $fixture['receiver'],
                'cuerpoDocumento' => $items,
                'resumen' => [
                    'totalNoSuj' => 0,
                    'totalExenta' => 0,
                    'totalGravada' => $totalTaxed,
                    'subTotalVentas' => $totalTaxed,
                    'descuNoSuj' => 0,
                    'descuExenta' => 0,
                    'descuGravada' => 0,
                    'porcentajeDescuento' => 0,
                    'totalDescu' => 0,
                    'tributos' => [[
                        'codigo' => '20',
                        'descripcion' => 'Impuesto al Valor Agregado 13%',
                        'valor' => $totalTax,
                    ]],
                    'subTotal' => $totalTaxed,
                    'ivaRete1' => 0,
                    'reteRenta' => 0,
                    'montoTotalOperacion' => $total,
                    'totalNoGravado' => 0,
                    'totalPagar' => $total,
                    'totalLetras' => $fixture['total_letras'],
                    'totalIva' => $totalTax,
                    'condicionOperacion' => 1,
                    'pagos' => [[
                        'codigo' => '01',
                        'montoPago' => $total,
                    ]],
                ],
            ],
            'total' => $total,
        ];
    }

    /**
     * @param  array{dte_type: string, sequence: int}  $fixture
     */
    private function controlNumber(Company $company, array $fixture): string
    {
        $establishment = strtoupper(str_pad((string) $company->own_establishment_code, 4, '0', STR_PAD_LEFT));
        $pointOfSale = strtoupper(str_pad((string) $company->own_pos_code, 4, '0', STR_PAD_LEFT));
        $sequence = str_pad((string) $fixture['sequence'], 15, '0', STR_PAD_LEFT);

        return sprintf('DTE-%s-%s%s-%s', $fixture['dte_type'], $establishment, $pointOfSale, $sequence);
    }

    /**
     * @param  array{dte_type: string, sequence: int}  $fixture
     */
    private function syncCorrelative(Company $company, array $fixture): void
    {
        $key = sprintf('%s-%s%s', $fixture['dte_type'], $company->own_establishment_code, $company->own_pos_code);
        $correlative = DteCorrelative::query()->firstOrCreate(
            ['company_id' => $company->id, 'key' => $key],
            ['last_value' => (string) $fixture['sequence']],
        );

        if ((int) $correlative->last_value < $fixture['sequence']) {
            $correlative->update(['last_value' => (string) $fixture['sequence']]);
        }
    }
}
