<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaPausa extends Model
{
    protected $table = 'asistencia_pausas';

    protected $fillable = ['asistencia_id', 'tipo', 'inicio', 'fin', 'inicio_utc', 'fin_utc'];

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime', 'inicio_utc' => 'datetime', 'fin_utc' => 'datetime'];
    }

    public function asistencia()
    {
        return $this->belongsTo(Asistencia::class, 'asistencia_id');
    }
}
