<?php

namespace App\Services\Dte\Documents;

use App\Exceptions\DteIssuerProfileException;
use App\IssuableDteType;
use App\Models\Company;
use App\Models\Departament;
use App\Models\District;
use App\Models\EconomicActivity;
use App\Models\Municipalities;
use App\Services\Dte\MhCatalog;
use Carbon\CarbonInterface;

/**
 * Builds the JSON of one DTE type exactly as its official MH schema defines it.
 */
abstract class DteDocumentBuilder
{
    public static function for(IssuableDteType $type): self
    {
        return match ($type) {
            IssuableDteType::CONSUMER_INVOICE => new ConsumerInvoiceBuilder,
            IssuableDteType::TAX_CREDIT => new TaxCreditBuilder,
            IssuableDteType::EXCLUDED_SUBJECT => new ExcludedSubjectInvoiceBuilder,
        };
    }

    /**
     * The identity document of a receiver, whichever document shape it comes from.
     *
     * @param  array<string, mixed>  $receiver
     * @return array{type: ?string, number: ?string}
     */
    public static function receiverIdentity(array $receiver): array
    {
        $nit = $receiver['nit'] ?? null;

        if ($nit !== null) {
            return ['type' => MhCatalog::NIT_DOCUMENT_TYPE, 'number' => $nit];
        }

        return [
            'type' => $receiver['tipoDocumento'] ?? null,
            'number' => $receiver['numDocumento'] ?? null,
        ];
    }

    /**
     * @throws DteIssuerProfileException when the company lacks data the schema requires.
     */
    public function ensureIssuerCanEmit(Company $company): void
    {
        if ($company->district_id === null) {
            throw new DteIssuerProfileException('La empresa no tiene un distrito configurado, es obligatorio para emitir DTE.');
        }

        if (preg_match(MhCatalog::ESTABLISHMENT_CODE_PATTERN, (string) $company->mh_establishment_code) !== 1) {
            throw new DteIssuerProfileException('El código de establecimiento de la empresa debe iniciar con M, B, S o P seguido de 3 números.');
        }

        if (preg_match(MhCatalog::POS_CODE_PATTERN, (string) $company->mh_pos_code) !== 1) {
            throw new DteIssuerProfileException('El código de punto de venta de la empresa debe iniciar con P seguido de 3 números.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function build(
        Company $company,
        array $payload,
        string $controlNumber,
        string $generationCode,
        CarbonInterface $issuedAt,
    ): array {
        $items = $this->items($payload['items']);

        return [
            'identificacion' => [
                'version' => $this->version(),
                'ambiente' => (string) $company->environment,
                'tipoDte' => $this->type()->value,
                'numeroControl' => $controlNumber,
                'codigoGeneracion' => $generationCode,
                'tipoModelo' => 1,
                'tipoOperacion' => 1,
                'tipoContingencia' => null,
                'motivoContin' => null,
                'fecEmi' => $issuedAt->format('Y-m-d'),
                'horEmi' => $issuedAt->format('H:i:s'),
                'tipoMoneda' => 'USD',
            ],
            ...$this->relatedDocuments(),
            'emisor' => $this->issuer($company),
            'receptor' => $this->receiver($payload['receptor'] ?? []),
            'cuerpoDocumento' => $items,
            'resumen' => $this->summary($items, $payload),
            'apendice' => null,
        ];
    }

    /**
     * The line value after its discount, with the 8 decimals the schema allows in the body.
     *
     * @param  array<string, mixed>  $item
     */
    public function lineAmount(array $item): float
    {
        $gross = (float) $item['cantidad'] * (float) $item['precioUni'];

        return round($gross - (float) ($item['montoDescu'] ?? 0), 8);
    }

    /** @param  array<string, mixed>  $payload */
    public function totalToPay(array $payload): float
    {
        return $this->summary($this->items($payload['items']), $payload)['totalPagar'];
    }

    /** @param  array<string, mixed>  $payload */
    public function requiresReceiverIdentification(array $payload): bool
    {
        return false;
    }

    abstract protected function version(): int;

    abstract protected function type(): IssuableDteType;

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    abstract protected function items(array $items): array;

    /**
     * @param  array<string, mixed>  $receiver
     * @return array<string, mixed>
     */
    abstract protected function receiver(array $receiver): array;

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    abstract protected function summary(array $items, array $payload): array;

    /** @return array<string, null> */
    protected function relatedDocuments(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    protected function issuer(Company $company): array
    {
        return [
            'nit' => $company->nit,
            'nrc' => $company->nrc,
            'nombre' => $company->name,
            'codActividad' => $company->economic_activity_code,
            'descActividad' => EconomicActivity::query()
                ->where('code', $company->economic_activity_code)
                ->value('description'),
            'direccion' => [
                'departamento' => Departament::query()->find($company->departament_id)?->code,
                'municipio' => Municipalities::query()->find($company->municipality_id)?->code,
                'distrito' => District::query()->find($company->district_id)?->code,
                'complemento' => $company->address,
            ],
            'telefono' => $company->phone,
            'correo' => $company->email,
            'codEstable' => $company->own_establishment_code,
            'codPuntoVenta' => $company->own_pos_code,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function itemFields(array $item, int $index): array
    {
        return [
            'numItem' => $index + 1,
            'tipoItem' => (int) ($item['tipoItem'] ?? MhCatalog::SERVICE_ITEM_TYPE),
            'cantidad' => (float) $item['cantidad'],
            'codigo' => $item['codigo'] ?? null,
            'uniMedida' => (int) ($item['uniMedida'] ?? MhCatalog::OTHER_UNIT_OF_MEASURE),
            'descripcion' => $item['descripcion'],
            'precioUni' => (float) $item['precioUni'],
            'montoDescu' => (float) ($item['montoDescu'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $receiver
     * @return array<string, string>|null
     */
    protected function receiverAddress(array $receiver): ?array
    {
        return ($receiver['direccion'] ?? null) ?: null;
    }

    /** @param  array<int, array<string, mixed>>  $items */
    protected function roundedSum(array $items, string $column): float
    {
        return round(array_sum(array_column($items, $column)), 2);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{condicionOperacion: int, pagos: array<int, array<string, mixed>>, observaciones: ?string}
     */
    protected function paymentFields(array $payload, float $amountDue): array
    {
        return [
            'condicionOperacion' => (int) data_get($payload, 'resumen.condicionOperacion', MhCatalog::CASH_CONDITION),
            'pagos' => $this->payments($payload, $amountDue),
            'observaciones' => data_get($payload, 'resumen.observaciones'),
        ];
    }

    protected function amountInWords(float $amount): string
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function payments(array $payload, float $amountDue): array
    {
        $payments = data_get($payload, 'resumen.pagos');

        if ($payments === null) {
            return [[
                'codigo' => MhCatalog::CASH_PAYMENT_METHOD,
                'montoPago' => $amountDue,
                'referencia' => null,
                'plazo' => null,
                'periodo' => null,
            ]];
        }

        return collect($payments)->values()->map(fn (array $payment): array => [
            'codigo' => $payment['codigo'],
            'montoPago' => (float) $payment['montoPago'],
            'referencia' => $payment['referencia'] ?? null,
            'plazo' => $payment['plazo'] ?? null,
            'periodo' => $payment['periodo'] ?? null,
        ])->all();
    }
}
