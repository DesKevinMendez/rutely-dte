<?php

use App\Models\Dte;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DteSeeder;

test('developer seeder creates processed demo DTEs for the admin company', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@dte.com')->firstOrFail();
    $dtes = Dte::query()->where('company_id', $admin->company_id)->get();

    expect($dtes)->toHaveCount(5)
        ->and($dtes->every(fn (Dte $dte): bool => $dte->status === 'PROCESADO'
            && $dte->received_seal !== null
            && data_get($dte->mh_response_json, 'estado') === 'PROCESADO'))->toBeTrue()
        ->and($dtes->pluck('dte_type')->unique()->sort()->values()->all())->toBe(['01', '03']);

    $this->seed(DteSeeder::class);

    expect(Dte::query()->where('company_id', $admin->company_id)->count())->toBe(5);
});
