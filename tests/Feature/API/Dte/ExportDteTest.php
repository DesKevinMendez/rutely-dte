<?php

use App\Environment;
use App\Models\User;
use App\Role;
use Database\Seeders\DteTypeSeeder;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Support\DteApiTestData;

test('guest cannot download the DTE workbook', function () {
    $this->getJson(route('api.v1.dtes.export'))->assertUnauthorized();
});

test('a company API token cannot download the dashboard workbook', function () {
    $company = DteApiTestData::company();
    $token = $company->createToken('External ERP', ['list:dte'])->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.dtes.export'))->assertForbidden();
});

test('a user without a DTE role cannot download the workbook', function () {
    $user = DteApiTestData::user(DteApiTestData::company());
    $user->syncRoles([]);
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.dtes.export'))->assertForbidden();
});

test('a user without a company is forbidden before period validation', function () {
    Sanctum::actingAs(User::factory()->withRole(Role::SUPERADMIN)->create());

    $filters = ['filter' => ['from' => 'invalid']];
    $this->getJson(route('api.v1.dtes.index', $filters))->assertForbidden();
    $this->getJson(route('api.v1.dtes.export', $filters))->assertForbidden();
});

test('a company member can download an empty workbook for a selected emission date', function (Role $role) {
    Sanctum::actingAs(DteApiTestData::user(DteApiTestData::company(), $role));

    $response = $this->getJson(route('api.v1.dtes.export', [
        'filter' => ['date' => '2026-08-18'],
    ]))->assertOk()
        ->assertDownload('Reporte-DTE.xlsx')
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');

    [$summary, $detail] = dteWorkbookSheets($response->baseResponse);

    expect((string) $summary->sheetData->row[0]->c->is->t)->toBe('Reporte de DTE')
        ->and((string) $summary->sheetData->row[2]->c->is->t)->toBe('Fecha de emisión: 2026-08-18')
        ->and((string) $summary->sheetData->row[3]->c->is->t)->toContain('Documentos: 0')
        ->and($detail->sheetData->row)->toHaveCount(1)
        ->and((string) $detail->autoFilter['ref'])->toBe('A1:X1');
})->with([
    'admin' => Role::ADMIN,
    'user' => Role::USER,
    'superadmin' => Role::SUPERADMIN,
]);

test('the list and the export validate the date filters', function (array|string $filter, string $field, string $message) {
    Sanctum::actingAs(DteApiTestData::user(DteApiTestData::company()));

    foreach (['api.v1.dtes.index', 'api.v1.dtes.export'] as $route) {
        $this->getJson(route($route, ['filter' => $filter]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
    }
})->with([
    'invalid filter structure' => [
        '01',
        'filter',
        'El campo filtros debe ser un arreglo.',
    ],
    'invalid emission date' => [
        ['date' => '18/08/2026'],
        'filter.date',
        'El campo fecha de emisión debe tener el formato Y-m-d.',
    ],
    'missing start' => [
        ['to' => '2026-11-10'],
        'filter.from',
        'El campo fecha inicial es obligatorio cuando fecha final está presente.',
    ],
    'missing end' => [
        ['from' => '2026-10-18'],
        'filter.to',
        'El campo fecha final es obligatorio cuando fecha inicial está presente.',
    ],
    'invalid start' => [
        ['from' => '2026-10-32', 'to' => '2026-11-10'],
        'filter.from',
        'El campo fecha inicial debe tener el formato Y-m-d.',
    ],
    'invalid end' => [
        ['from' => '2026-10-18', 'to' => '10/11/2026'],
        'filter.to',
        'El campo fecha final debe tener el formato Y-m-d.',
    ],
    'reversed range' => [
        ['from' => '2026-11-10', 'to' => '2026-10-18'],
        'filter.to',
        'La fecha final debe ser igual o posterior a la fecha inicial.',
    ],
    'day and range' => [
        ['date' => '2026-11-10', 'from' => '2026-10-18', 'to' => '2026-11-10'],
        'filter.date',
        'Selecciona un día específico o un rango de fechas.',
    ],
]);

test('the export applies the same document type, status and emission date filters as the list', function () {
    $company = DteApiTestData::company();
    $included = DteApiTestData::dte($company);
    DteApiTestData::dte($company, [
        'original_json' => ['identificacion' => ['fecEmi' => '2026-08-19']],
    ]);
    DteApiTestData::dte($company, ['dte_type' => '03']);
    DteApiTestData::dte($company, ['status' => 'RECHAZADO']);
    Sanctum::actingAs(DteApiTestData::user($company));

    $filters = ['filter' => ['tipoDte' => '01', 'estado' => 'PROCESADO', 'date' => '2026-08-18']];
    $this->getJson(route('api.v1.dtes.index', $filters))->assertOk()->assertJson([
        'data' => [['generation_code' => $included->generation_code]],
        'pagination' => ['total' => 1],
    ]);
    $response = $this->getJson(route('api.v1.dtes.export', $filters))->assertOk()->assertDownload();

    [, $detail] = dteWorkbookSheets($response->baseResponse);

    expect($detail->sheetData->row)->toHaveCount(2)
        ->and(dteWorkbookRow($detail->sheetData->row[1]))->toMatchArray([
            'K' => $included->generation_code,
            'X' => '11.3',
        ]);
});

test('the list and workbook include both boundaries of an emission range across months', function () {
    $company = DteApiTestData::company();
    Sanctum::actingAs(DteApiTestData::user($company));

    $documents = [];
    foreach (['2026-10-17', '2026-10-18', '2026-10-22', '2026-11-10', '2026-11-11'] as $date) {
        $documents[$date] = DteApiTestData::dte($company, [
            'original_json' => ['identificacion' => ['fecEmi' => $date]],
        ])->generation_code;
    }

    $filters = ['filter' => ['from' => '2026-10-18', 'to' => '2026-11-10']];
    $list = $this->getJson(route('api.v1.dtes.index', $filters))->assertOk();
    $response = $this->getJson(route('api.v1.dtes.export', $filters))->assertOk()->assertDownload();
    [$summary, $detail] = dteWorkbookSheets($response->baseResponse);

    $included = [$documents['2026-10-18'], $documents['2026-10-22'], $documents['2026-11-10']];
    $downloaded = [];
    foreach ($detail->sheetData->row as $row) {
        if ((int) $row['r'] > 1) {
            $downloaded[] = dteWorkbookRow($row)['K'];
        }
    }

    expect(array_column($list->json('data'), 'generation_code'))->toEqualCanonicalizing($included)
        ->and($downloaded)->toEqualCanonicalizing($included)
        ->and((string) $summary->sheetData->row[2]->c->is->t)->toBe('Fechas de emisión: 2026-10-18 al 2026-11-10')
        ->and((string) $summary->sheetData->row[3]->c->is->t)->toContain('Documentos: 3');
});

test('a report without filters exports every company DTE across months and pages', function () {
    $this->seed(DteTypeSeeder::class);
    $company = DteApiTestData::company();
    $otherCompany = DteApiTestData::company([
        'name' => 'Otra empresa',
        'nit' => '06142812901016',
        'email' => 'otra@example.com',
    ]);
    DteApiTestData::dte($otherCompany, ['control_number' => 'OTRA-EMPRESA']);
    foreach (range(1, 12) as $sequence) {
        DteApiTestData::dte($company, ['control_number' => "FACTURA-{$sequence}"]);
    }
    DteApiTestData::dte($company, [
        'dte_type' => '03',
        'control_number' => 'CCF-001',
        'status' => 'INVALIDADO',
        'environment' => Environment::PRODUCTION->value,
        'original_json' => [
            'identificacion' => ['fecEmi' => '2026-08-19', 'tipoMoneda' => 'USD'],
            'receptor' => ['nombre' => '=SUM(1,2) & Asociados', 'nit' => '00123456789012', 'nrc' => '0001234'],
            'resumen' => [
                'totalNoSuj' => 0,
                'totalExenta' => 5,
                'totalGravada' => 100,
                'tributos' => [['codigo' => '20', 'valor' => 13]],
                'ivaRete1' => 1,
                'ivaPerci1' => 2,
                'montoTotalOperacion' => 118,
                'totalPagar' => 119,
            ],
        ],
    ]);
    DteApiTestData::dte($company, [
        'dte_type' => '14',
        'control_number' => 'FSE-001',
        'original_json' => [
            'identificacion' => ['fecEmi' => '2026-09-03'],
            'sujetoExcluido' => ['nombre' => 'María Pérez', 'numDocumento' => '001234567'],
            'resumen' => ['totalCompra' => 50, 'reteRenta' => 5, 'totalPagar' => 45],
        ],
    ]);
    Sanctum::actingAs(DteApiTestData::user($company));

    $response = $this->getJson(route('api.v1.dtes.export', [
        'page' => 2,
        'per_page' => 1,
    ]))->assertOk()->assertDownload();

    [$summary, $detail] = dteWorkbookSheets($response->baseResponse);
    $documentRows = [];
    foreach ($detail->sheetData->row as $row) {
        $documentRows[dteWorkbookRow($row)['B']] = $row;
    }

    expect($detail->sheetData->row)->toHaveCount(15)
        ->and($summary->sheetData->row)->toHaveCount(10)
        ->and((string) $summary->sheetData->row[2]->c->is->t)->toBe('Todas las fechas de emisión')
        ->and((string) $detail->autoFilter['ref'])->toBe('A1:X15')
        ->and((string) $detail->sheetViews->sheetView->pane['topLeftCell'])->toBe('C2')
        ->and(dteWorkbookRow($summary->sheetData->row[7]))->toMatchArray([
            'A' => '2026-08', 'B' => 'Pruebas', 'C' => '01', 'E' => 'PROCESADO', 'G' => '12', 'H' => '135.6',
        ])
        ->and(dteWorkbookRow($summary->sheetData->row[8]))->toMatchArray([
            'A' => '2026-08', 'B' => 'Producción', 'C' => '03', 'E' => 'INVALIDADO', 'G' => '1', 'H' => '119',
        ])
        ->and(dteWorkbookRow($summary->sheetData->row[9]))->toMatchArray([
            'A' => '2026-09', 'C' => '14', 'G' => '1', 'H' => '45',
        ])
        ->and(dteWorkbookRow($documentRows['CCF-001']))->toMatchArray([
            'D' => 'Comprobante de crédito fiscal',
            'H' => '=SUM(1,2) & Asociados', 'I' => '00123456789012', 'J' => '0001234',
            'N' => '0', 'O' => '5', 'P' => '100', 'S' => '13', 'T' => '1', 'U' => '2',
            'W' => '118', 'X' => '119',
        ])
        ->and(dteWorkbookRow($documentRows['FSE-001']))->toMatchArray([
            'H' => 'María Pérez', 'I' => '001234567',
            'Q' => '50', 'V' => '5', 'X' => '45',
        ]);

    $credit = $documentRows['CCF-001'];
    expect((string) $credit->c[0]['s'])->toBe('2')
        ->and((string) $credit->c[0]->v)->toBe('46253')
        ->and((string) $credit->c[7]['t'])->toBe('inlineStr')
        ->and($credit->asXML())->not->toContain('<f>')
        ->and($detail->asXML())->not->toContain('OTRA-EMPRESA', 'signed-jws');
});

/** @return array{SimpleXMLElement, SimpleXMLElement} */
function dteWorkbookSheets(BinaryFileResponse $response): array
{
    $path = $response->getFile()->getPathname();
    $archive = new ZipArchive;
    expect($archive->open($path, ZipArchive::RDONLY))->toBeTrue();

    try {
        $workbook = simplexml_load_string($archive->getFromName('xl/workbook.xml'));
        $summary = simplexml_load_string($archive->getFromName('xl/worksheets/sheet1.xml'));
        $detail = simplexml_load_string($archive->getFromName('xl/worksheets/sheet2.xml'));
        expect((string) $workbook->bookViews->workbookView['activeTab'])->toBe('0')
            ->and((string) $workbook->sheets->sheet[0]['name'])->toBe('Resumen')
            ->and((string) $workbook->sheets->sheet[1]['name'])->toBe('Detalle DTE');
        expect($summary)->toBeInstanceOf(SimpleXMLElement::class);
        expect($detail)->toBeInstanceOf(SimpleXMLElement::class);

        return [$summary, $detail];
    } finally {
        $archive->close();
        unlink($path);
    }
}

/** @return array<string, string> */
function dteWorkbookRow(SimpleXMLElement $row): array
{
    $values = [];
    foreach ($row->c as $cell) {
        $column = rtrim((string) $cell['r'], '0123456789');
        $values[$column] = (string) ($cell->is->t ?? $cell->v);
    }

    return $values;
}
