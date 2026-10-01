<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentoPago extends Model
{
    protected $table = 'intentos_pago';

    protected $fillable = [
        'empresa_id',
        'fecha_renovacion',
        'tarjeta_id',
        'origen',
        'concepto',
        'cantidad',
        'moneda',
        'resultado',
        'descripcion',
        'codigo_respuesta',
        'transaccion_id',
        'idempotency_key',
        'numero_ejecucion',
        'intentado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_renovacion' => 'date',
            'cantidad' => 'decimal:2',
            'numero_ejecucion' => 'integer',
            'intentado_en' => 'datetime',
        ];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
