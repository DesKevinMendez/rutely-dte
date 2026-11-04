<?php

namespace App\Services\Dte\Documents;

use App\IssuableDteType;
use App\Services\Dte\MhCatalog;

/**
 * Factura Electrónica (fe-f-v2). Prices include IVA, which is broken down per item and in the summary.
 */
class ConsumerInvoiceBuilder extends SaleDocumentBuilder
{
    /** @param  array<string, mixed>  $payload */
    public function requiresReceiverIdentification(array $payload): bool
    {
        $operationTotal = $this->summary($this->items($payload['items']), $payload)['montoTotalOperacion'];

        return $operationTotal >= MhCatalog::CONSUMER_IDENTIFICATION_AMOUNT;
    }

    protected function version(): int
    {
        return 2;
    }

    protected function type(): IssuableDteType
    {
        return IssuableDteType::CONSUMER_INVOICE;
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
                'tributos' => null,
                'ivaItem' => round($taxedSale / (1 + self::IVA_RATE) * self::IVA_RATE, 8),
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
            'tipoDocumento' => $receiver['tipoDocumento'] ?? null,
            'numDocumento' => $receiver['numDocumento'] ?? null,
            'nrc' => $receiver['nrc'] ?? null,
            'nombre' => $receiver['nombre'] ?? null,
            'codActividad' => $receiver['codActividad'] ?? null,
            'descActividad' => $receiver['descActividad'] ?? null,
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
        $operationTotal = $this->roundedSum($items, 'ventaGravada');
        $withheldIva = (float) data_get($payload, 'resumen.ivaRete', 0);
        $amountDue = round($operationTotal - $withheldIva, 2);

        return [
            ...$this->saleSummaryFields($items, $payload, null, $withheldIva, $operationTotal, $amountDue),
            'totalIva' => $this->roundedSum($items, 'ivaItem'),
        ];
    }
}
