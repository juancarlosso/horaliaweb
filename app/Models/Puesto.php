<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    protected $fillable = ['nombre', 'activo', 'empresa_id'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
