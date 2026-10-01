<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecadorSesion extends Model
{
    protected $table = 'checador_sesiones';

    protected $fillable = ['empresa_id', 'centro_id', 'user_id', 'token', 'token_hash', 'activated_at', 'expires_at', 'activation_ip', 'activation_user_agent'];

    protected function casts(): array
    {
        return ['token' => 'encrypted', 'activated_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function centro()
    {
        return $this->belongsTo(Centro::class);
    }

}
