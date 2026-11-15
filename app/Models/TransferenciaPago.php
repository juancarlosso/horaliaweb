<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferenciaPago extends Model
{
    protected $table = 'transferencias_pago';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_renovacion' => 'date', 'cantidad' => 'decimal:2', 'cargado_en' => 'datetime', 'revisado_en' => 'datetime', 'correo_admin_enviado_en' => 'datetime'];
    }

    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function usuarioCarga() { return $this->belongsTo(User::class, 'usuario_carga_id'); }
    public function comprobantes() { return $this->hasMany(TransferenciaComprobante::class, 'transferencia_pago_id'); }
    public function comprobanteActual() { return $this->hasOne(TransferenciaComprobante::class, 'transferencia_pago_id')->latestOfMany(); }
}
