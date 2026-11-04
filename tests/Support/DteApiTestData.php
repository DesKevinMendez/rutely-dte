<?php

namespace Tests\Support;

use App\Environment;
use App\Models\Company;
use App\Models\Dte;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DteApiTestData
{
    /** @param array<string, mixed> $overrides */
    public static function company(array $overrides = []): Company
    {
        $departamentId = DB::table('departaments')->where('code', '06')->value('id');
        if ($departamentId === null) {
            $departamentId = (string) Str::uuid();
            DB::table('departaments')->insert([
                'id' => $departamentId,
                'code' => '06',
                'name' => 'San Salvador',
            ]);
        }

        $municipalityId = DB::table('municipalities')
            ->where('departament_id', $departamentId)
            ->where('code', '01')
            ->value('id');
        if ($municipalityId === null) {
            $municipalityId = (string) Str::uuid();
            DB::table('municipalities')->insert([
                'id' => $municipalityId,
                'departament_id' => $departamentId,
                'departament_code' => '06',
                'code' => '01',
                'name' => 'San Salvador Centro',
            ]);
        }

        $districtId = DB::table('districts')
            ->where('municipality_id', $municipalityId)
            ->where('code', '01')
            ->value('id');
        if ($districtId === null) {
            $districtId = (string) Str::uuid();
            DB::table('districts')->insert([
                'id' => $districtId,
                'departament_id' => $departamentId,
                'municipality_id' => $municipalityId,
                'code' => '01',
                'name' => 'San Salvador',
            ]);
        }

        if (! DB::table('economic_activities')->where('code', '62010')->exists()) {
            DB::table('economic_activities')->insert([
                'id' => (string) Str::uuid(),
                'code' => '62010',
                'description' => 'Programación informática',
            ]);
        }

        if (! DB::table('establishment_types')->where('code', '01')->exists()) {
            DB::table('establishment_types')->insert([
                'id' => (string) Str::uuid(),
                'code' => '01',
                'description' => 'Sucursal / Agencia',
            ]);
        }

        return Company::query()->create(array_merge([
            'name' => 'Rutely, S.A. de C.V.',
            'address' => 'San Salvador',
            'phone' => '22223333',
            'nit' => '06142812901015',
            'nrc' => '1234567',
            'commercial_name' => 'Rutely',
            'economic_activity_code' => '62010',
            'establishment_type' => '01',
            'departament_id' => $departamentId,
            'municipality_id' => $municipalityId,
            'district_id' => $districtId,
            'email' => 'billing@rutely.biz',
            'mh_establishment_code' => '0001',
            'mh_pos_code' => '0001',
            'own_establishment_code' => 'M001',
            'own_pos_code' => 'P001',
        ], $overrides));
    }

    public static function user(Company $company, Role $role = Role::ADMIN): User
    {
        return User::factory()->withRole($role)->create([
            'company_id' => $company->id,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    public static function dte(Company $company, array $overrides = []): Dte
    {
        $generationCode = strtoupper((string) Str::uuid());

        return Dte::query()->create(array_merge([
            'company_id' => $company->id,
            'generation_code' => $generationCode,
            'control_number' => 'DTE-01-00010001-000000000000001',
            'dte_type' => '01',
            'version' => '2',
            'environment' => Environment::SANDBOX->value,
            'status' => 'PROCESADO',
            'issuer_nit' => $company->nit,
            'receiver_document' => '06141505921015',
            'total_amount' => 1130,
            'original_json' => [
                'identificacion' => [
                    'tipoDte' => '01',
                    'codigoGeneracion' => $generationCode,
                    'numeroControl' => 'DTE-01-00010001-000000000000001',
                    'fecEmi' => '2026-08-18',
                ],
                'emisor' => [
                    'nit' => $company->nit,
                    'nombre' => $company->name,
                    'telefono' => $company->phone,
                    'correo' => $company->email,
                ],
                'receptor' => [
                    'tipoDocumento' => '36',
                    'numDocumento' => '06141505921015',
                    'nombre' => 'Cliente',
                    'telefono' => '77778888',
                    'correo' => 'cliente@example.com',
                ],
            ],
            'signed_json' => 'signed-jws',
            'received_seal' => 'SELLO-MH',
            'mh_response_json' => ['estado' => 'PROCESADO'],
        ], $overrides));
    }

    /** @return array<string, mixed> */
    public static function validDtePayload(): array
    {
        return [
            'tipoDte' => '01',
            'items' => [[
                'descripcion' => 'Servicio de monitoreo GPS',
                'cantidad' => 1,
                'precioUni' => 10,
                'montoDescu' => 0,
                'tipoItem' => 2,
                'codigo' => 'GPS-001',
                'uniMedida' => 59,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    public static function validTaxCreditPayload(): array
    {
        return [
            'tipoDte' => '03',
            'receptor' => [
                'nit' => '06141505921015',
                'nrc' => '9876543',
                'nombre' => 'Comercial Los Pinos, S.A. de C.V.',
                'codActividad' => '62010',
                'descActividad' => 'Programación informática',
                'nombreComercial' => 'Los Pinos',
                'direccion' => self::receiverAddress(),
                'telefono' => '22225551',
                'correo' => 'compras@lospinos.example',
            ],
            'items' => self::validDtePayload()['items'],
        ];
    }

    /** @return array<string, mixed> */
    public static function validExcludedSubjectPayload(): array
    {
        return [
            'tipoDte' => '14',
            'receptor' => [
                'tipoDocumento' => '13',
                'numDocumento' => '012345678',
                'nombre' => 'María Pérez',
                'codActividad' => '62010',
                'descActividad' => 'Programación informática',
                'direccion' => self::receiverAddress(),
                'telefono' => '77778888',
                'correo' => 'maria@example.com',
            ],
            'items' => self::validDtePayload()['items'],
        ];
    }

    /** @return array{departamento: string, municipio: string, distrito: string, complemento: string} */
    public static function receiverAddress(): array
    {
        return [
            'departamento' => '06',
            'municipio' => '01',
            'distrito' => '01',
            'complemento' => 'Colonia Escalón, San Salvador',
        ];
    }

    /** @return array<string, mixed> */
    public static function mhResult(): array
    {
        return [
            'estado' => 'PROCESADO',
            'selloRecibido' => str_repeat('A', 40),
            'observaciones' => [],
        ];
    }
}
