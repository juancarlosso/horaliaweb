<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncapacidadComprobante extends Model
{
    protected $fillable = ['incapacidad_id', 'ruta_privada', 'nombre_original', 'mime', 'tamano', 'cargado_por_user_id'];
    public function incapacidad() { return $this->belongsTo(Incapacidad::class); }
}
