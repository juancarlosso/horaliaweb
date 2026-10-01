<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEmpresa extends Model
{
    protected $table = 'users_empresas';

    protected $fillable = ['user_id', 'empresa_id', 'control_total'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
