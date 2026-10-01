<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Departamento extends Model
{
    protected $fillable = ['nombre', 'politicas', 'activo', 'empresa_id'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function politicasUrl(): ?string
    {
        if (!$this->politicas) {
            return null;
        }

        if (str_starts_with($this->politicas, 'storage/')) {
            return asset($this->politicas);
        }

        return Storage::disk('public')->exists($this->politicas)
            ? Storage::disk('public')->url($this->politicas)
            : null;
    }
}
