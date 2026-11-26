<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteGeneradoFila extends Model
{
    protected $table = 'reportes_generados_filas';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['datos' => 'array'];
    }
}
