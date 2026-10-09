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
        'tarjeta_marca',
        'tarjeta_ultimos4',
        'forma_pago_sat',
        'origen',
        'concepto',
        'cantidad',
        'moneda',
        'resultado',
        'factura',
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
            'factura' => 'integer',
            'intentado_en' => 'datetime',
        ];
    }

    public function setFolioAttribute(?string $value): void
    {
        if (isset($this->attributes['folio']) && $this->attributes['folio'] !== null && $value !== $this->attributes['folio']) {
            throw new \LogicException('El folio de un pago confirmado es inmutable.');
        }

        $this->attributes['folio'] = $value;
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function facturaEmitida()
    {
        return $this->hasOne(Factura::class);
    }
}
