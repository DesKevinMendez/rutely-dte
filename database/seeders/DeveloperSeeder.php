<?php

namespace Database\Seeders;

use App\Models\Company;
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
    }
}
