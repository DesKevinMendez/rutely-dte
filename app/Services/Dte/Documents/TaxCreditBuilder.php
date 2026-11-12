<?php

namespace App\Services\Dte\Documents;

use App\IssuableDteType;
use App\Services\Dte\MhCatalog;

/**
 * Comprobante de Crédito Fiscal (fe-ccf-v4). Prices exclude IVA, which is added as tribute 20.
 */
class TaxCreditBuilder extends SaleDocumentBuilder
{
    /** Normativa 2.0, Anexo IV #141: the perceived IVA is 1% of the taxed sales. */
    private const PERCEIVED_IVA_RATE = 0.01;

    /** @param  array<string, mixed>  $payload */
    public function perceivedIva(array $payload): float
    {
        return round($this->roundedSum($this->items($payload['items']), 'ventaGravada') * self::PERCEIVED_IVA_RATE, 2);
    }

    protected function version(): int
    {
        return 4;
    }

    protected function type(): IssuableDteType
    {
        return IssuableDteType::TAX_CREDIT;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function items(array $items): array
    {
        return collect($items)->values()->map(function (array $item, int $index): array {
            $taxedSale = $this->lineAmount($item);

            return [
                ...$this->saleItemFields($item, $index, $taxedSale),
                'tributos' => $taxedSale > 0 ? [MhCatalog::IVA_TAX_CODE] : null,
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $receiver
     * @return array<string, mixed>
     */
    protected function receiver(array $receiver): array
    {
        return [
            'nit' => $receiver['nit'],
            'nrc' => $receiver['nrc'],
            'nombre' => $receiver['nombre'],
            'codActividad' => $receiver['codActividad'],
            'descActividad' => $receiver['descActividad'],
            'nombreComercial' => $receiver['nombreComercial'] ?? null,
            'direccion' => $this->receiverAddress($receiver),
            'telefono' => $receiver['telefono'] ?? null,
            'correo' => $receiver['correo'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function summary(array $items, array $payload): array
    {
        $totalTaxed = $this->roundedSum($items, 'ventaGravada');
        $iva = round($totalTaxed * self::IVA_RATE, 2);
        $operationTotal = round($totalTaxed + $iva, 2);
        $perceivedIva = (float) data_get($payload, 'resumen.ivaPerci', 0);
        $withheldIva = (float) data_get($payload, 'resumen.ivaRete', 0);
        $amountDue = round($operationTotal + $perceivedIva - $withheldIva, 2);

        $taxes = $iva > 0 ? [[
            'codigo' => MhCatalog::IVA_TAX_CODE,
            'descripcion' => MhCatalog::IVA_TAX_DESCRIPTION,
            'valor' => $iva,
        ]] : null;

        return [
            ...$this->saleSummaryFields($items, $payload, $taxes, $withheldIva, $operationTotal, $amountDue),
            'ivaPerci' => $perceivedIva,
        ];
    }
}
