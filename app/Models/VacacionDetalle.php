<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacacionDetalle extends Model
{
    protected $table = 'vacaciones_detalles';
    protected $fillable = ['vacacion_id', 'fecha', 'cuenta', 'motivo', 'festivos'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'cuenta' => 'boolean', 'festivos' => 'array'];
    }

    public function vacacion()
    {
        return $this->belongsTo(Vacacion::class, 'vacacion_id');
    }
}
