<?php

namespace App\Services;

use App\Models\Empresa;

class MembershipRenewalDate
{
    public static function next(Empresa $empresa): string
    {
        $base = $empresa->activa && $empresa->fecha_renovacion
            ? $empresa->fecha_renovacion->copy()
            : today();

        return $base->addMonthNoOverflow()->toDateString();
    }
}
