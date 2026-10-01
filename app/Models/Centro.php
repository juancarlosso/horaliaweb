<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Centro extends Model
{
    protected $fillable = ['nombre', 'activo', 'empresa_id', 'geolocalizacion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function personal()
    {
        return $this->hasMany(Personal::class, 'centro_id');
    }
}
