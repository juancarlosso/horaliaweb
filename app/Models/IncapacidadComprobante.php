<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncapacidadComprobante extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'incapacidad_id', 'ruta_privada', 'nombre_original', 'mime', 'tamano',
        'cargado_por_user_id', 'personal_documento_id',
    ];

    public function incapacidad(): BelongsTo
    {
        return $this->belongsTo(Incapacidad::class);
    }

    public function documentoPersonal(): BelongsTo
    {
        return $this->belongsTo(PersonalDocumento::class, 'personal_documento_id');
    }
}
