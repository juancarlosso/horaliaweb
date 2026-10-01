<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helper\Wasabi;

class Personal extends Model
{
    protected $table = 'personal';

    protected $fillable = [
        'codigo',
        'pin',
        'nombre',
        'email',
        'telefono',
        'foto',
        'activo',
        'empresa_id',
        'centro_id',
        'departamento_id',
        'puesto_id',
        'sexo',
        'laborados',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function centro()
    {
        return $this->belongsTo(Centro::class, 'centro_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function puesto()
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    public function usuario()
    {
        return $this->hasOne(User::class, 'personal_id');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'personal_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'personal_id');
    }

    public function fotoUrl(): ?string
    {
        if (!$this->foto) {
            return null;
        }

        if (str_starts_with($this->foto, 'http://') || str_starts_with($this->foto, 'https://')) {
            return $this->foto;
        }

        if (str_starts_with($this->foto, 'storage/')) {
            return asset($this->foto);
        }

        return Wasabi::url($this->foto);
    }
}
