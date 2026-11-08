<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestivoEmpresa extends Model
{
    protected $table = 'festivos_empresas';

    protected $fillable = ['empresa_id', 'fecha', 'conmemoracion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
