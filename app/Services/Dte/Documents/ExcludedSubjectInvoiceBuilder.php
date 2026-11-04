<?php

namespace App\Services\Dte\Documents;

use App\IssuableDteType;

/**
 * Factura de Sujeto Excluido (fe-fse-v2). A purchase without IVA, with optional income tax withholding.
 */
class ExcludedSubjectInvoiceBuilder extends DteDocumentBuilder
{
    protected function version(): int
    {
        return 2;
    }

    protected function type(): IssuableDteType
    {
        return IssuableDteType::EXCLUDED_SUBJECT;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function items(array $items): array
    {
        return collect($items)->values()->map(fn (array $item, int $index): array => [
            ...$this->itemFields($item, $index),
            'compra' => $this->lineAmount($item),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $receiver
     * @return array<string, mixed>
     */
    protected function receiver(array $receiver): array
    {
        return [
            'tipoDocumento' => $receiver['tipoDocumento'],
            'numDocumento' => $receiver['numDocumento'],
            'nombre' => $receiver['nombre'],
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
        $totalPurchases = $this->roundedSum($items, 'compra');
        $withheldIncomeTax = (float) data_get($payload, 'resumen.reteRenta', 0);
        $amountDue = round($totalPurchases - $withheldIncomeTax, 2);

        return [
            'totalCompra' => $totalPurchases,
            'descu' => 0.0,
            'totalDescu' => $this->roundedSum($items, 'montoDescu'),
            'subTotal' => $totalPurchases,
            'reteRenta' => $withheldIncomeTax,
            'totalPagar' => $amountDue,
            'totalLetras' => $this->amountInWords($amountDue),
            ...$this->paymentFields($payload, $amountDue),
        ];
    }
}
