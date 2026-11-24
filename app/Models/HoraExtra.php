<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoraExtra extends Model
{
    protected $table = 'horas_extras';

    public const UPDATED_AT = null;

    protected $fillable = [
        'asistencia_id', 'empresa_id', 'personal_id', 'fecha', 'detectados', 'autorizados',
        'autorizador_id', 'autorizador_personal_id', 'capturista_id', 'motivo', 'observaciones', 'jornada',
    ];

    public function asignarFolio(): void
    {
        if ($this->folio !== null) {
            return;
        }

        $letras = chr(random_int(65, 90)).chr(random_int(65, 90));
        $folio = 'HE-'.$letras.str_pad((string) $this->getKey(), 5, '0', STR_PAD_LEFT);

        $this->forceFill(['folio' => $folio])->save();
    }

    public function constanciaFirmada(): HasOne
    {
        return $this->hasOne(PersonalDocumento::class, 'hora_extra_id');
    }

    protected function casts(): array
    {
        return ['fecha' => 'date', 'jornada' => 'array', 'detectados' => 'integer', 'autorizados' => 'integer'];
    }
}
