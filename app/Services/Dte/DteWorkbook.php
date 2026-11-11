<?php

namespace App\Services\Dte;

use App\Environment;
use App\Models\Company;
use App\Models\Dte;
use App\Models\DteType;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use RuntimeException;
use Throwable;
use XMLWriter;
use ZipArchive;

class DteWorkbook
{
    private const SHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const COLUMN_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const HEADER = 1;

    private const DATE = 2;

    private const AMOUNT = 3;

    private const TITLE = 4;

    private const DETAIL_COLUMNS = [
        ['Fecha de emisión', 21, self::DATE],
        ['Número de control', 36],
        ['Tipo', 9],
        ['Documento', 36],
        ['Ambiente', 17],
        ['Estado', 19],
        ['NIT del emisor', 20],
        ['Cliente / proveedor', 46],
        ['NIT / DUI / documento', 24],
        ['NRC', 13],
        ['Código de generación', 42],
        ['Sello de recepción', 47],
        ['Moneda', 12],
        ['Ventas no sujetas', 22, self::AMOUNT],
        ['Ventas exentas', 20, self::AMOUNT],
        ['Ventas gravadas DTE', 25, self::AMOUNT],
        ['Compra sujeto excluido', 27, self::AMOUNT],
        ['Descuento', 16, self::AMOUNT],
        ['IVA', 16, self::AMOUNT],
        ['IVA retenido', 18, self::AMOUNT],
        ['IVA percibido', 18, self::AMOUNT],
        ['Retención de renta', 23, self::AMOUNT],
        ['Monto de operación', 24, self::AMOUNT],
        ['Total DTE', 18, self::AMOUNT],
    ];

    private const SUMMARY_COLUMNS = [
        ['Mes de emisión', 20],
        ['Ambiente', 17],
        ['Tipo', 9],
        ['Documento', 36],
        ['Estado', 19],
        ['Moneda', 12],
        ['Cantidad', 13],
        ['Total DTE', 18, self::AMOUNT],
    ];

    /** @param iterable<Dte> $dtes */
    public function create(Company $company, iterable $dtes, ?string $date, ?string $from, ?string $to): string
    {
        $detail = tmpfile();
        $summary = tmpfile();

        $detailPath = $detail === false ? null : (stream_get_meta_data($detail)['uri'] ?? null);
        $summaryPath = $summary === false ? null : (stream_get_meta_data($summary)['uri'] ?? null);

        if ($detailPath === null || $summaryPath === null) {
            throw new RuntimeException('No se pudo preparar el archivo de DTE.');
        }

        $groups = [];
        $types = DteType::query()->pluck('description', 'code')->all();
        $this->writeSheet($detailPath, self::DETAIL_COLUMNS, $this->detailRows($dtes, $types, $groups), frozenColumns: 2);

        ksort($groups);
        $count = array_sum(array_column($groups, 6));
        $issuedAt = now('America/El_Salvador')->format('d/m/Y H:i');
        $emissionPeriod = match (true) {
            $date !== null => "Fecha de emisión: {$date}",
            $from !== null && $to !== null => "Fechas de emisión: {$from} al {$to}",
            default => 'Todas las fechas de emisión',
        };

        $this->writeSheet($summaryPath, self::SUMMARY_COLUMNS, $groups, [
            ['Reporte de DTE'],
            [$company->name.' · NIT '.$company->nit],
            [$emissionPeriod],
            ["Exportado el {$issuedAt} (El Salvador) · Documentos: {$count}"],
            ['Los totales conservan el signo de cada DTE. En facturas 01, las ventas gravadas incluyen IVA.'],
            [],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'dte-xlsx-');

        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo de DTE.');
        }

        try {
            $this->writeArchive($path, $summaryPath, $detailPath);
        } catch (Throwable $exception) {
            unlink($path);

            throw $exception;
        }

        return $path;
    }

    /**
     * @param  iterable<Dte>  $dtes
     * @param  array<array-key, string>  $types
     * @param  array<string, array{string, string, string, string, string, string, int, float}>  $groups
     * @return Generator<int, list<int|float|string|null>>
     */
    private function detailRows(iterable $dtes, array $types, array &$groups): Generator
    {
        foreach ($dtes as $dte) {
            $document = $dte->getAttribute('original_json');
            $document = is_array($document) ? $document : [];
            $date = $this->emissionDate(data_get($document, 'identificacion.fecEmi'));
            $month = $date?->format('Y-m') ?? 'Sin fecha';
            $currency = $this->text(data_get($document, 'identificacion.tipoMoneda')) ?? 'USD';
            $type = $dte->dte_type;
            $typeName = $types[$type] ?? $type;
            $environment = match ($dte->environment) {
                Environment::SANDBOX->value => 'Pruebas',
                Environment::PRODUCTION->value => 'Producción',
                default => $dte->environment,
            };
            $party = $document['sujetoExcluido'] ?? $document['receptor'] ?? [];
            $party = is_array($party) ? $party : [];
            $summary = $document['resumen'] ?? [];
            $summary = is_array($summary) ? $summary : [];
            $operationAmount = $this->number($summary['montoTotalOperacion'] ?? null);
            $total = $this->number($summary['totalPagar'] ?? null) ?? $operationAmount ?? ($dte->total_amount / 100);

            $groupKey = implode('|', [$month, $dte->environment, $type, $dte->status, $currency]);
            $groups[$groupKey] ??= [$month, $environment, $type, $typeName, $dte->status, $currency, 0, 0.0];
            $groups[$groupKey][6]++;
            $groups[$groupKey][7] = round($groups[$groupKey][7] + $total, 2);

            yield [
                $date === null ? null : 25569 + intdiv($date->getTimestamp(), 86400),
                $dte->control_number,
                $type,
                $typeName,
                $environment,
                $dte->status,
                $dte->issuer_nit,
                $this->text($party['nombre'] ?? null),
                $this->text($party['nit'] ?? $party['numDocumento'] ?? $dte->receiver_document),
                $this->text($party['nrc'] ?? null),
                $dte->generation_code,
                $dte->received_seal,
                $currency,
                $this->number($summary['totalNoSuj'] ?? null),
                $this->number($summary['totalExenta'] ?? null),
                $this->number($summary['totalGravada'] ?? null),
                $this->number($summary['totalCompra'] ?? null),
                $this->number($summary['totalDescu'] ?? null),
                $this->iva($summary),
                $this->number($summary['ivaRete'] ?? $summary['ivaRete1'] ?? null),
                $this->number($summary['ivaPerci'] ?? $summary['ivaPerci1'] ?? null),
                $this->number($summary['reteRenta'] ?? null),
                $operationAmount,
                $total,
            ];
        }
    }

    private function emissionDate(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) || is_int($value) ? (string) $value : null;
    }

    /** @param array<array-key, mixed> $summary */
    private function iva(array $summary): ?float
    {
        $iva = $this->number($summary['totalIva'] ?? null);

        foreach ((array) ($summary['tributos'] ?? []) as $tax) {
            if (($tax['codigo'] ?? null) === MhCatalog::IVA_TAX_CODE) {
                return $iva ?? $this->number($tax['valor'] ?? null);
            }
        }

        return $iva;
    }

    /**
     * @param  list<array{0: string, 1: int, 2?: int}>  $columns
     * @param  iterable<list<int|float|string|null>>  $rows
     * @param  list<list<string>>  $intro
     */
    private function writeSheet(string $path, array $columns, iterable $rows, array $intro = [], int $frozenColumns = 0): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir la hoja de DTE.');
        }

        $headerRow = count($intro) + 1;
        $lastColumn = self::COLUMN_LETTERS[count($columns) - 1];
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'worksheet', self::SHEET_NAMESPACE);
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->writeAttribute('showGridLines', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', (string) $headerRow);
        if ($frozenColumns > 0) {
            $xml->writeAttribute('xSplit', (string) $frozenColumns);
        }
        $xml->writeAttribute('topLeftCell', self::COLUMN_LETTERS[$frozenColumns].($headerRow + 1));
        $xml->writeAttribute('activePane', $frozenColumns > 0 ? 'bottomRight' : 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('cols');
        foreach ($columns as $index => $column) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $column[1]);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();

        $xml->startElement('sheetData');
        foreach ($intro as $index => $row) {
            $this->writeRow($xml, $index + 1, $row, $index === 0 ? [self::TITLE] : []);
        }
        $this->writeRow($xml, $headerRow, array_column($columns, 0), array_fill(0, count($columns), self::HEADER));

        $rowNumber = $headerRow;
        $styles = array_map(fn (array $column): int => $column[2] ?? 0, $columns);
        foreach ($rows as $row) {
            abort_if(++$rowNumber > 1_048_576, 422, __('validation.dte_export_max_rows'));
            $this->writeRow($xml, $rowNumber, $row, $styles);
        }
        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', "A{$headerRow}:{$lastColumn}{$rowNumber}");
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        if ($xml->flush() === -1) {
            throw new RuntimeException('No se pudo guardar la hoja de DTE.');
        }
    }

    /**
     * @param  list<int|float|string|null>  $values
     * @param  list<int>  $styles
     */
    private function writeRow(XMLWriter $xml, int $row, array $values, array $styles): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $row);
        if (in_array($styles[0] ?? 0, [self::HEADER, self::TITLE], true)) {
            $xml->writeAttribute('ht', '23');
            $xml->writeAttribute('customHeight', '1');
        }

        foreach ($values as $index => $value) {
            if ($value === null) {
                continue;
            }

            $xml->startElement('c');
            $xml->writeAttribute('r', self::COLUMN_LETTERS[$index].$row);
            $xml->writeAttribute('s', (string) ($styles[$index] ?? 0));
            if (is_string($value)) {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttribute('xml:space', 'preserve');
                $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';
                $xml->text(preg_replace('/_(?=x[0-9a-fA-F]{4}_)/', '_x005F_', $value) ?? '');
                $xml->endElement();
                $xml->endElement();
            } else {
                $xml->writeElement('v', (string) $value);
            }
            $xml->endElement();
        }

        $xml->endElement();
    }

    private function writeArchive(string $path, string $summary, string $detail): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo de DTE.');
        }

        try {
            foreach ($this->packageParts() as $name => $content) {
                if (! $zip->addFromString($name, $content)) {
                    throw new RuntimeException('No se pudo guardar la estructura del archivo de DTE.');
                }
            }

            if (! $zip->addFile($summary, 'xl/worksheets/sheet1.xml') || ! $zip->addFile($detail, 'xl/worksheets/sheet2.xml')) {
                throw new RuntimeException('No se pudieron guardar las hojas de DTE.');
            }
        } finally {
            $closed = $zip->close();
        }

        if (! $closed) {
            throw new RuntimeException('No se pudo guardar el archivo de DTE.');
        }
    }

    /** @return array<string, string> */
    private function packageParts(): array
    {
        return [
            '[Content_Types].xml' => <<<'XML'
                <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                    <Default Extension="xml" ContentType="application/xml"/>
                    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
                    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
                    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
                    <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
                </Types>
                XML,
            '_rels/.rels' => <<<'XML'
                <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
                </Relationships>
                XML,
            'xl/workbook.xml' => <<<'XML'
                <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                    <bookViews><workbookView activeTab="0"/></bookViews>
                    <sheets>
                        <sheet name="Resumen" sheetId="1" r:id="rId1"/>
                        <sheet name="Detalle DTE" sheetId="2" r:id="rId2"/>
                    </sheets>
                </workbook>
                XML,
            'xl/_rels/workbook.xml.rels' => <<<'XML'
                <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
                    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
                    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
                </Relationships>
                XML,
            'xl/styles.xml' => <<<'XML'
                <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                    <numFmts count="2">
                        <numFmt numFmtId="164" formatCode="dd/mm/yyyy"/>
                        <numFmt numFmtId="165" formatCode="#,##0.00;(#,##0.00);0.00"/>
                    </numFmts>
                    <fonts count="3">
                        <font><sz val="10"/><color rgb="FF172B4D"/><name val="Arial"/></font>
                        <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
                        <font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Arial"/></font>
                    </fonts>
                    <fills count="3">
                        <fill><patternFill patternType="none"/></fill>
                        <fill><patternFill patternType="gray125"/></fill>
                        <fill><patternFill patternType="solid"><fgColor rgb="FF24436A"/><bgColor indexed="64"/></patternFill></fill>
                    </fills>
                    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
                    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
                    <cellXfs count="5">
                        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
                        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
                        <xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
                        <xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
                        <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
                    </cellXfs>
                    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
                </styleSheet>
                XML,
        ];
    }
}
