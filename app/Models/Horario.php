<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = ['dia', 'entrada', 'salida', 'personal_id', 'horario_id'];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function horarioEmpresa()
    {
        return $this->belongsTo(HorarioEmpresa::class, 'horario_id');
    }
}
