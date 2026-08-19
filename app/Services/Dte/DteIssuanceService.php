<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\Departament;
use App\Models\Dte;
use App\Models\DteCorrelative;
use App\Models\EconomicActivity;
use App\Models\Municipalities;
use App\Models\Receivers;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DteIssuanceService
{
    public function __construct(
        private readonly FirmadorService $firmador,
        private readonly MhTransmissionService $transmission,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array{record: Dte, mhResult: array<string, mixed>}
     */
    public function issue(string $companyId, array $payload): array
    {
        $company = Company::query()->findOrFail($companyId);
        $dteType = (string) ($payload['tipoDte'] ?? '01');
        $environment = (string) $company->environment;
        $correlative = $this->nextCorrelative($company, $dteType);
        $generationCode = strtoupper((string) Str::uuid());
        $controlNumber = $this->controlNumber($company, $dteType, $correlative);
        $now = now('America/El_Salvador');

        $items = $this->buildItems($payload['items']);
        $totalTaxed = round(array_sum(array_column($items, 'ventaGravada')), 2);
        $totalIva = round($totalTaxed * 0.13, 2);
        $totalPayable = round($totalTaxed + $totalIva, 2);
        $receiverPayload = $this->receiverPayload($payload['receptor'] ?? []);
        $receiver = $this->persistReceiver($companyId, $receiverPayload);
        $departmentCode = Departament::query()->find($company->departament_id)?->code;
        $municipalityCode = Municipalities::query()->find($company->municipality_id)?->code;
        $activityDescription = EconomicActivity::query()
            ->where('code', $company->economic_activity_code)
            ->value('description');

        $document = [
            'identificacion' => [
                'version' => $dteType === '03' ? 4 : 2,
                'ambiente' => $environment,
                'tipoDte' => $dteType,
                'numeroControl' => $controlNumber,
                'codigoGeneracion' => $generationCode,
                'tipoModelo' => 1,
                'tipoOperacion' => 1,
                'fecEmi' => $now->format('Y-m-d'),
                'horEmi' => $now->format('H:i:s'),
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
            'receptor' => $receiverPayload,
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
                    'valor' => $totalIva,
                ]],
                'subTotal' => $totalTaxed,
                'ivaRete1' => 0,
                'reteRenta' => 0,
                'montoTotalOperacion' => $totalPayable,
                'totalNoGravado' => 0,
                'totalPagar' => $totalPayable,
                'totalLetras' => $this->numberToWords($totalPayable),
                'totalIva' => $totalIva,
                'condicionOperacion' => 1,
                'pagos' => [[
                    'codigo' => '01',
                    'montoPago' => $totalPayable,
                ]],
            ],
        ];

        $dte = Dte::query()->create([
            'company_id' => $companyId,
            'generation_code' => $generationCode,
            'control_number' => $controlNumber,
            'dte_type' => $dteType,
            'version' => (string) $document['identificacion']['version'],
            'environment' => $environment,
            'status' => 'BORRADOR',
            'issuer_nit' => $company->nit,
            'receiver_document' => $receiverPayload['numDocumento'] ?? null,
            'receiver_id' => $receiver?->id,
            'total_amount' => (int) round($totalPayable * 100),
            'original_json' => $document,
        ]);

        $signedDocument = $this->firmador->signDocument($companyId, $document, $environment, $company->nit);
        $dte->update([
            'status' => 'FIRMADO',
            'signed_json' => $signedDocument,
        ]);

        $mhResult = $this->transmission->transmitDte($dte->refresh());
        $dte->update([
            'status' => ($mhResult['estado'] ?? null) === 'PROCESADO' ? 'PROCESADO' : 'RECHAZADO',
            'received_seal' => $mhResult['selloRecibido'] ?? null,
            'observations' => json_encode($mhResult['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
            'mh_response_json' => $mhResult,
        ]);

        return [
            'record' => $dte->refresh(),
            'mhResult' => $mhResult,
        ];
    }

    private function nextCorrelative(Company $company, string $dteType): int
    {
        $key = sprintf('%s-%s%s', $dteType, $company->own_establishment_code, $company->own_pos_code);

        return DB::transaction(function () use ($company, $key): int {
            $row = DteCorrelative::query()->firstOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                ['last_value' => '0'],
            );
            $row = DteCorrelative::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $next = ((int) $row->last_value) + 1;
            $row->update(['last_value' => (string) $next]);

            return $next;
        });
    }

    private function controlNumber(Company $company, string $dteType, int $correlative): string
    {
        return sprintf(
            'DTE-%s-%s%s-%s',
            str_pad($dteType, 2, '0', STR_PAD_LEFT),
            strtoupper(str_pad($company->own_establishment_code, 4, '0', STR_PAD_LEFT)),
            strtoupper(str_pad($company->own_pos_code, 4, '0', STR_PAD_LEFT)),
            str_pad((string) $correlative, 15, '0', STR_PAD_LEFT),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(array $items): array
    {
        return collect($items)->values()->map(function (array $item, int $index): array {
            $quantity = (float) $item['cantidad'];
            $unitPrice = (float) $item['precioUni'];
            $discount = (float) ($item['montoDescu'] ?? 0);
            $taxedSale = round(($quantity * $unitPrice) - $discount, 2);

            return [
                'numItem' => $index + 1,
                'tipoItem' => (int) ($item['tipoItem'] ?? 2),
                'cantidad' => $quantity,
                'codigo' => $item['codigo'] ?? 'ITEM-'.($index + 1),
                'uniMedida' => (int) ($item['uniMedida'] ?? 59),
                'descripcion' => $item['descripcion'],
                'precioUni' => $unitPrice,
                'montoDescu' => $discount,
                'ventaNoSuj' => 0,
                'ventaExenta' => 0,
                'ventaGravada' => $taxedSale,
                'tributos' => ['20'],
                'psv' => 0,
                'noGravado' => 0,
            ];
        })->all();
    }

    /** @param array<string, mixed> $receiver */
    private function receiverPayload(array $receiver): array
    {
        return [
            'tipoDocumento' => $receiver['tipoDocumento'] ?? '36',
            'numDocumento' => $receiver['numDocumento'] ?? '06141505921015',
            'nrc' => $receiver['nrc'] ?? '9876543',
            'nombre' => $receiver['nombre'] ?? 'CLIENTE FINAL S.A. DE C.V.',
            'codActividad' => $receiver['codActividad'] ?? '46900',
            'descActividad' => $receiver['descActividad'] ?? 'Servicios Generales',
            'direccion' => $receiver['direccion'] ?? [
                'departamento' => '06',
                'municipio' => '14',
                'complemento' => 'Av. Principal',
            ],
            'telefono' => $receiver['telefono'] ?? '77889900',
            'correo' => $receiver['correo'] ?? 'cliente@gmail.com',
        ];
    }

    /** @param array<string, mixed> $receiver */
    private function persistReceiver(string $companyId, array $receiver): ?Receivers
    {
        $documentNumber = $receiver['numDocumento'] ?? null;
        if (! is_string($documentNumber) || $documentNumber === '') {
            return null;
        }

        $departmentId = Departament::query()->where('code', data_get($receiver, 'direccion.departamento'))->value('id');
        $municipalityId = Municipalities::query()
            ->where('departament_id', $departmentId)
            ->where('code', data_get($receiver, 'direccion.municipio'))
            ->value('id');

        return Receivers::query()->updateOrCreate(
            ['company_id' => $companyId, 'document_number' => $documentNumber],
            [
                'document_type' => $receiver['tipoDocumento'] ?? '36',
                'nrc' => $receiver['nrc'] ?? null,
                'name' => $receiver['nombre'] ?? $documentNumber,
                'economic_activity_code' => $receiver['codActividad'] ?? null,
                'economic_activity_description' => $receiver['descActividad'] ?? null,
                'departament_id' => $departmentId,
                'municipality_id' => $municipalityId,
                'address_complement' => data_get($receiver, 'direccion.complemento'),
                'phone' => $receiver['telefono'] ?? null,
                'email' => $receiver['correo'] ?? null,
            ],
        );
    }

    private function numberToWords(float $amount): string
    {
        $cents = (int) round(fmod($amount, 1) * 100);
        $integer = (int) floor($amount);
        $units = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $tens = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $teens = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $convert = function (int $number) use ($units, $tens, $teens, $hundreds): string {
            if ($number === 0) {
                return '';
            }
            if ($number === 100) {
                return 'CIEN';
            }

            $hundred = intdiv($number, 100);
            $rest = $number % 100;
            $ten = intdiv($rest, 10);
            $unit = $rest % 10;
            $text = $hundred > 0 ? $hundreds[$hundred].' ' : '';

            if ($rest >= 10 && $rest < 20) {
                return trim($text.$teens[$rest - 10]);
            }

            if ($ten > 0) {
                $text .= $tens[$ten];
                if ($unit > 0) {
                    $text .= ' Y '.$units[$unit];
                }
            } elseif ($unit > 0) {
                $text .= $units[$unit];
            }

            return trim($text);
        };

        if ($integer === 0) {
            return sprintf('CERO %02d/100 USD', $cents);
        }

        $parts = [];
        $millions = intdiv($integer, 1_000_000);
        $thousands = intdiv($integer % 1_000_000, 1_000);
        $remainder = $integer % 1_000;

        if ($millions > 0) {
            $parts[] = $millions === 1 ? 'UN MILLON' : $convert($millions).' MILLONES';
        }
        if ($thousands > 0) {
            $parts[] = $thousands === 1 ? 'MIL' : $convert($thousands).' MIL';
        }
        if ($remainder > 0) {
            $parts[] = $convert($remainder);
        }

        return trim(implode(' ', $parts)).sprintf(' %02d/100 USD', $cents);
    }
}
