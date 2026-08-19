<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Dte;
use App\Models\DteCorrelative;
use App\Models\User;
use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartamentSeeder::class,
            MunicipalitiesSeeder::class,
            DistrictSeeder::class,
            EconomicActivitySeeder::class,
            EstablishmentTypesSeeder::class,
        ]);

        $departamentId = DB::table('departaments')
            ->where('code', '06')
            ->value('id');
        $municipalityId = DB::table('municipalities')
            ->where('departament_code', '06')
            ->where('code', '23')
            ->value('id');

        $company = Company::query()->updateOrCreate(
            ['nit' => '06142812901015'],
            [
                'name' => 'Rutely Playwright, S.A. de C.V.',
                'address' => 'San Salvador Centro',
                'phone' => '22223333',
                'nrc' => '1234567',
                'commercial_name' => 'Rutely Playwright',
                'economic_activity_code' => '62010',
                'establishment_type' => '01',
                'departament_id' => $departamentId,
                'municipality_id' => $municipalityId,
                'email' => 'billing.playwright@rutely.biz',
                'mh_establishment_code' => '0001',
                'mh_pos_code' => '0001',
                'own_establishment_code' => '0001',
                'own_pos_code' => '0001',
            ],
        );
        $company->forceFill([
            'is_onboarded' => '1',
            'environment' => '00',
        ])->save();

        User::query()->updateOrCreate(
            ['email' => 'playwright@rutely.biz'],
            [
                'name' => 'Playwright User',
                'password' => 'password',
                'role' => Role::ADMIN->value,
                'status' => true,
                'company_id' => $company->id,
            ],
        );

        $this->seedDashboardDtes($company);
    }

    private function seedDashboardDtes(Company $company): void
    {
        $dtes = [
            [
                'generation_code' => '11111111-1111-4111-8111-111111111111',
                'control_number' => 'DTE-01-00010001-000000000000001',
                'status' => 'PROCESADO',
                'total_amount' => 1130,
                'received_seal' => 'PLAYWRIGHT-SEAL-1',
            ],
            [
                'generation_code' => '22222222-2222-4222-8222-222222222222',
                'control_number' => 'DTE-01-00010001-000000000000002',
                'status' => 'RECHAZADO',
                'total_amount' => 2260,
                'received_seal' => null,
            ],
            [
                'generation_code' => '33333333-3333-4333-8333-333333333333',
                'control_number' => 'DTE-01-00010001-000000000000003',
                'status' => 'INVALIDADO',
                'total_amount' => 3390,
                'received_seal' => 'PLAYWRIGHT-SEAL-3',
            ],
        ];

        foreach ($dtes as $dte) {
            Dte::query()->updateOrCreate(
                ['generation_code' => $dte['generation_code']],
                [
                    'company_id' => $company->id,
                    'control_number' => $dte['control_number'],
                    'dte_type' => '01',
                    'version' => '2',
                    'environment' => '00',
                    'status' => $dte['status'],
                    'issuer_nit' => $company->nit,
                    'receiver_document' => '06141505921015',
                    'total_amount' => $dte['total_amount'],
                    'original_json' => [
                        'identificacion' => [
                            'tipoDte' => '01',
                            'codigoGeneracion' => $dte['generation_code'],
                            'numeroControl' => $dte['control_number'],
                        ],
                    ],
                    'signed_json' => $dte['status'] === 'RECHAZADO' ? null : 'playwright-signed-jws',
                    'received_seal' => $dte['received_seal'],
                ],
            );
        }

        DteCorrelative::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'key' => '01-00010001',
            ],
            ['last_value' => '3'],
        );
    }
}
