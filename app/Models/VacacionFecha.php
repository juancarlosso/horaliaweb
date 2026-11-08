<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacacionFecha extends Model
{
    protected $table = 'vacaciones_fechas';
    protected $fillable = ['vacacion_id', 'fecha'];
    protected function casts(): array { return ['fecha' => 'date']; }
    public function vacacion() { return $this->belongsTo(Vacacion::class, 'vacacion_id'); }
}
