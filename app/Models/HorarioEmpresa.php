<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioEmpresa extends Model
{
    protected $table = 'horarios_empresas';

    protected $fillable = ['nombre_horario', 'empresa_id', 'hora_entrada', 'hora_salida'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
