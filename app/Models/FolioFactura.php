<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioFactura extends Model
{
    protected $table = 'folios_factura';

    protected $fillable = ['serie', 'ultimo_folio'];

    protected function casts(): array
    {
        return ['ultimo_folio' => 'integer'];
    }
}
