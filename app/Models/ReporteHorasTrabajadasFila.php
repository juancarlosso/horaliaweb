<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteHorasTrabajadasFila extends Model
{
    public const COLUMNAS_DURACION = [
        'minutos_trabajados' => 'Horas trabajadas en la semana',
        'minutos_sanitario' => 'Pausas baño',
        'minutos_comida' => 'Pausas comida',
        'minutos_descanso' => 'Pausas descanso',
        'minutos_otras_pausas' => 'Otras pausas',
        'minutos_pausas' => 'Pausas totales reportadas',
    ];

    protected $table = 'reportes_horas_trabajadas_filas';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['semana' => 'immutable_date'];
    }
}
