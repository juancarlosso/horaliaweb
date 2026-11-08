<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestivoOficial extends Model
{
    protected $table = 'festivos_oficiales';

    protected $fillable = ['fecha', 'conmemoracion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }
}
