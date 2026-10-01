<?php

namespace App\Models;

use App\Helper\Wasabi;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'personal_id', 'fecha', 'llegada', 'salida', 'minutos_tarde', 'minutos_salida_temprano',
        'latitud', 'longitud', 'ip', 'latitud_salida', 'longitud_salida', 'ip_salida',
        'rango_entrada', 'rango_salida', 'foto_entrada', 'foto_salida',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'llegada' => 'datetime',
            'salida' => 'datetime',
            'minutos_tarde' => 'integer',
            'minutos_salida_temprano' => 'integer',
        ];
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function fotoEntradaUrl(): ?string
    {
        return Wasabi::url($this->foto_entrada);
    }

    public function fotoSalidaUrl(): ?string
    {
        return Wasabi::url($this->foto_salida);
    }
}
