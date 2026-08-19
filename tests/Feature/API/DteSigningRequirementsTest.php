<?php

use App\Role;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\Support\DteApiTestData;

test('DTE creation fails when signing material is not configured for the company environment', function () {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->withoutExceptionHandling();

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('No hay material de firma configurado para la empresa y el ambiente seleccionados.');

    $this->postJson(
        route('api.v1.dtes.store'),
        DteApiTestData::validDtePayload(),
    );
});
