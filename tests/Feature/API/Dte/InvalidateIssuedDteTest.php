<?php

use App\Models\Dte;
use App\Models\DteInvalidation;
use App\Role;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\DteTypeSeeder;
use Database\Seeders\ItemTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DteApiTestData;
use Tests\Support\MhSchema;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->seed(DteTypeSeeder::class);
    $this->seed(ItemTypeSeeder::class);
});

function invalidationPayload(array $overrides = []): array
{
    return [
        'tipoAnulacion' => 2,
        'motivoAnulacion' => null,
        'nombreResponsable' => 'Responsable DTE',
        'tipDocResponsable' => '13',
        'numDocResponsable' => '012345678',
        'nombreSolicita' => 'Solicitante DTE',
        'tipDocSolicita' => '13',
        'numDocSolicita' => '012345678',
        ...$overrides,
    ];
}

test('the invalidation of an issued document carries the receiver identity of its type', function (array $payload, ?string $type, ?string $number) {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $issued = $this->postJson(route('api.v1.dtes.store'), $payload)->assertCreated();
    $dte = Dte::query()->findOrFail($issued->json('data.record.id'));

    $this->postJson(route('api.v1.dtes.invalidations.store', $dte), invalidationPayload())->assertOk();

    $document = DteInvalidation::query()->where('dte_id', $dte->id)->firstOrFail()->signed_json;
    $document = signedDocument($document);

    expect(MhSchema::errors($document, MhSchema::INVALIDATION))->toBe([])
        ->and($document['documento']['tipoDte'])->toBe($dte->dte_type)
        ->and($document['documento']['tipoDocumento'])->toBe($type)
        ->and($document['documento']['numDocumento'])->toBe($number)
        ->and($document['documento']['codigoGeneracionR'])->toBeNull()
        ->and($document['motivo']['motivoAnulacion'])->toBeNull()
        ->and($dte->fresh()->status)->toBe('INVALIDADO');
})->with([
    'anonymous consumer invoice' => [DteApiTestData::validDtePayload(), null, null],
    'tax credit' => [DteApiTestData::validTaxCreditPayload(), '36', '06141505921015'],
    'excluded subject' => [DteApiTestData::validExcludedSubjectPayload(), '13', '012345678'],
]);

test('an invalidation due to an error references the replacement document in uppercase', function () {
    Sanctum::actingAs(DteApiTestData::user(companyReadyToIssue(), Role::USER));
    $issued = $this->postJson(route('api.v1.dtes.store'), DteApiTestData::validDtePayload())->assertCreated();
    $dte = Dte::query()->findOrFail($issued->json('data.record.id'));
    $replacementCode = fake()->uuid();

    $this->postJson(route('api.v1.dtes.invalidations.store', $dte), invalidationPayload([
        'tipoAnulacion' => 1,
        'motivoAnulacion' => 'Error en el nombre del receptor',
        'codigoGeneracionR' => $replacementCode,
    ]))->assertOk();

    $invalidation = DteInvalidation::query()->where('dte_id', $dte->id)->firstOrFail();

    expect(MhSchema::errors(signedDocument($invalidation->signed_json), MhSchema::INVALIDATION))->toBe([])
        ->and($invalidation->original_json['documento']['codigoGeneracionR'])->toBe(strtoupper($replacementCode))
        ->and($invalidation->original_json['motivo']['motivoAnulacion'])->toBe('Error en el nombre del receptor')
        ->and($invalidation->reason)->toBe('Error en el nombre del receptor');
});

test('the replacement document follows the invalidation type', function (array $overrides, string $message) {
    $company = DteApiTestData::company();
    $dte = DteApiTestData::dte($company);
    Sanctum::actingAs(DteApiTestData::user($company, Role::USER));

    $this->postJson(route('api.v1.dtes.invalidations.store', $dte), invalidationPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['codigoGeneracionR' => $message]);
})->with([
    'required for an error' => [['tipoAnulacion' => 1], 'El campo código de generación del documento que reemplaza es obligatorio cuando tipo de anulación es 1.'],
    'required for other' => [['tipoAnulacion' => 3], 'El campo código de generación del documento que reemplaza es obligatorio cuando tipo de anulación es 3.'],
    'prohibited when rescinding' => [['tipoAnulacion' => 2, 'codigoGeneracionR' => '4f0e6d2a-6a3b-4a55-9d1c-3a7e2c1b8f10'], 'El campo código de generación del documento que reemplaza está prohibido cuando tipo de anulación es 2.'],
    'must be a UUID' => [['tipoAnulacion' => 1, 'codigoGeneracionR' => 'abc'], 'El campo código de generación del documento que reemplaza debe ser un UUID válido.'],
]);
