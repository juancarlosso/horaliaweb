<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporteHorasTrabajadas extends ReporteGenerado
{
    public function filas(): HasMany
    {
        return $this->hasMany(ReporteHorasTrabajadasFila::class, 'reporte_id');
    }
}
