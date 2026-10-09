<?php

namespace App\Services;

use InvalidArgumentException;

class PaymentInvoiceAmounts
{
    /**
     * Split a historical tax-inclusive total into integer cents using configured IVA.
     * The payment method remains PUE because this payment has already been collected.
     *
     * @return array{subtotal_cents:int,iva_cents:int,total_cents:int,subtotal:string,iva:string,total:string,metodo_pago:string,iva_rate:int}
     */
    public function fromTaxInclusiveTotal(string $amount): array
    {
        $ivaRate = (int) config('constantes.tasa_iva', 16);
        if ($ivaRate < 0 || $ivaRate >= 100) {
            throw new InvalidArgumentException('La tasa de IVA configurada debe estar entre 0 y 99.');
        }

        $amount = trim($amount);
        if (!preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('El importe del pago no tiene un formato decimal válido.');
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0', STR_PAD_RIGHT);
        $totalCents = ($whole * 100) + $fraction;
        $ivaDenominator = 100 + $ivaRate;
        $subtotalCents = intdiv(
            ($totalCents * 100) + intdiv($ivaDenominator, 2),
            $ivaDenominator
        );
        $ivaCents = $totalCents - $subtotalCents;

        return [
            'subtotal_cents' => $subtotalCents,
            'iva_cents' => $ivaCents,
            'total_cents' => $totalCents,
            'subtotal' => $this->formatCents($subtotalCents),
            'iva' => $this->formatCents($ivaCents),
            'total' => $this->formatCents($totalCents),
            'metodo_pago' => 'PUE',
            'iva_rate' => $ivaRate,
        ];
    }

    private function formatCents(int $amount): string
    {
        return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
