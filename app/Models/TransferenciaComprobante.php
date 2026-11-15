<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferenciaComprobante extends Model
{
    protected $table = 'transferencia_comprobantes';
    protected $guarded = ['id'];
    public function transferencia() { return $this->belongsTo(TransferenciaPago::class, 'transferencia_pago_id'); }
}
