<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'intento_pago_id',
        'usuario_id',
        'serie',
        'folio',
        'uuid',
        'estado',
        'modo_sifei',
        'uso_cfdi',
        'forma_pago_sat',
        'correo_facturacion',
        'subtotal',
        'iva',
        'total',
        'xml_path',
        'pdf_path',
        'xml_staging_path',
        'pdf_staging_path',
        'timbrado_en',
        'archivos_subidos_en',
        'correo_encolado_en',
        'codigo_error',
        'mensaje_error',
        'xml_pendiente',
    ];

    protected function casts(): array
    {
        return [
            'folio' => 'integer',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
            'timbrado_en' => 'datetime',
            'archivos_subidos_en' => 'datetime',
            'correo_encolado_en' => 'datetime',
        ];
    }

    public function intentoPago()
    {
        return $this->belongsTo(IntentoPago::class);
    }
}
