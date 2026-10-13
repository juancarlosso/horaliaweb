<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\IntentoPago;

class EmployeeSubscriptionPricing
{
    public const EMPLOYEES_PER_BLOCK = 200;

    public function blocksFor(int $employeeCount): int
    {
        return max(1, (int) ceil(max(0, $employeeCount) / self::EMPLOYEES_PER_BLOCK));
    }

    public function priceForCount(int $employeeCount): string
    {
        $unitPrice = (float) config('constantes.precio_mensual_por_empresa_mxn', 150);

        return number_format($this->blocksFor($employeeCount) * $unitPrice, 2, '.', '');
    }

    public function employeeCount(Empresa $empresa): int
    {
        return $empresa->personal()->count();
    }

    /**
     * Freeze the cycle price on its first payment attempt. Retries keep the
     * amount already recorded for that cycle, even if headcount later changes.
     */
    public function prepareRenewal(Empresa $empresa, string $cycleDate): string
    {
        $frozen = IntentoPago::query()
            ->where('empresa_id', $empresa->id)
            ->whereDate('fecha_renovacion', $cycleDate)
            ->orderBy('id')
            ->value('cantidad');

        $price = $frozen !== null
            ? number_format((float) $frozen, 2, '.', '')
            : $this->priceForCount($this->employeeCount($empresa));

        if ((float) $empresa->precio !== (float) $price) {
            $empresa->forceFill(['precio' => $price])->save();
        }

        return $price;
    }
}
