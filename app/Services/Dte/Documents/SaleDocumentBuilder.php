<?php

namespace App\Services\Dte\Documents;

use App\Exceptions\DteIssuerProfileException;
use App\Models\Company;

/**
 * Fields shared by the sale documents: Factura (fe-f-v2) and Crédito Fiscal (fe-ccf-v4).
 */
abstract class SaleDocumentBuilder extends DteDocumentBuilder
{
    protected const IVA_RATE = 0.13;

    public function ensureIssuerCanEmit(Company $company): void
    {
        parent::ensureIssuerCanEmit($company);

        if (blank($company->nrc)) {
            throw new DteIssuerProfileException('La empresa no tiene un NRC configurado, es obligatorio para emitir este tipo de DTE.');
        }
    }

    /** @return array<string, null> */
    protected function relatedDocuments(): array
    {
        return [
            'documentoRelacionado' => null,
            'otrosDocumentos' => null,
            'ventaTercero' => null,
        ];
    }

    /** @return array<string, mixed> */
    protected function issuer(Company $company): array
    {
        return [
            ...parent::issuer($company),
            'nombreComercial' => $company->commercial_name,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function saleItemFields(array $item, int $index, float $taxedSale): array
    {
        return [
            ...$this->itemFields($item, $index),
            'numeroDocumento' => null,
            'codTributo' => null,
            'ventaNoSuj' => 0.0,
            'ventaExenta' => 0.0,
            'ventaGravada' => $taxedSale,
            'psv' => 0.0,
            'noGravado' => 0.0,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>|null  $taxes
     * @return array<string, mixed>
     */
    protected function saleSummaryFields(
        array $items,
        array $payload,
        ?array $taxes,
        float $withheldIva,
        float $operationTotal,
        float $amountDue,
    ): array {
        $totalTaxed = $this->roundedSum($items, 'ventaGravada');

        return [
            'totalNoSuj' => 0.0,
            'totalExenta' => 0.0,
            'totalGravada' => $totalTaxed,
            'subTotalVentas' => $totalTaxed,
            'descuNoSuj' => 0.0,
            'descuExenta' => 0.0,
            'descuGravada' => 0.0,
            'porcentajeDescuento' => 0.0,
            'totalDescu' => $this->roundedSum($items, 'montoDescu'),
            'tributos' => $taxes,
            'subTotal' => $totalTaxed,
            'ivaRete' => $withheldIva,
            'montoTotalOperacion' => $operationTotal,
            'totalNoGravado' => 0.0,
            'totalPagar' => $amountDue,
            'totalLetras' => $this->amountInWords($amountDue),
            'saldoFavor' => 0.0,
            'numPagoElectronico' => null,
            ...$this->paymentFields($payload, $amountDue),
        ];
    }
}
