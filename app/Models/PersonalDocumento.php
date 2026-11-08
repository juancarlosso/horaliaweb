<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalDocumento extends Model
{
    use SoftDeletes;

    protected $table = 'personal_documentos';

    protected $fillable = [
        'empresa_id', 'personal_id', 'tipo', 'nombre_personalizado', 'observaciones',
        'ruta_privada', 'nombre_original', 'mime', 'tamano', 'cargado_por', 'eliminado_por',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }

    public function cargador()
    {
        return $this->belongsTo(User::class, 'cargado_por');
    }

    public function eliminador()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }
}
