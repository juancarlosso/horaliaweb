<?php

namespace App\Models;

use App\Helper\Wasabi;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'personal_id', 'fecha', 'llegada', 'salida', 'minutos_tarde', 'minutos_salida_temprano',
        'latitud', 'longitud', 'ip', 'latitud_salida', 'longitud_salida', 'ip_salida',
        'rango_entrada', 'rango_salida', 'foto_entrada', 'foto_salida',
        'centro_trabajo_entrada_id', 'centro_trabajo_salida_id', 'timezone_entrada', 'timezone_salida',
        'llegada_utc', 'salida_utc',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'llegada' => 'datetime',
            'salida' => 'datetime',
            'minutos_tarde' => 'integer',
            'minutos_salida_temprano' => 'integer',
        ];
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function pausas()
    {
        return $this->hasMany(AsistenciaPausa::class, 'asistencia_id');
    }

    public function tiempoPausasMinutos(): ?int
    {
        $pausas = $this->relationLoaded('pausas') ? $this->pausas : $this->pausas()->get();
        if ($pausas->contains(fn ($pausa) => $pausa->fin === null)) {
            return null;
        }

        $seconds = (int) $pausas->sum(fn ($pausa) => $pausa->inicio_utc && $pausa->fin_utc
            ? $pausa->inicio_utc->diffInSeconds($pausa->fin_utc)
            : 0);

        return intdiv($seconds, 60);
    }

    public function tiempoProductivoMinutos(): ?int
    {
        $pauseMinutes = $this->tiempoPausasMinutos();
        if ($pauseMinutes === null || ! $this->salida) {
            return null;
        }
        $hasUtcPair = $this->llegada_utc && $this->salida_utc;
        $start = $hasUtcPair ? Carbon::parse($this->llegada_utc, 'UTC') : $this->llegada;
        $end = $hasUtcPair ? Carbon::parse($this->salida_utc, 'UTC') : $this->salida;
        if (! $start || ! $end) {
            return null;
        }

        $pauseSeconds = (int) $this->pausas->sum(fn ($pause) => $pause->inicio_utc && $pause->fin_utc
            ? $pause->inicio_utc->diffInSeconds($pause->fin_utc)
            : 0);

        return max(0, intdiv(max(0, (int) $start->diffInSeconds($end) - $pauseSeconds), 60));
    }

    public static function formatoDuracion(?int $minutes): string
    {
        if ($minutes === null) {
            return 'Indeterminado';
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function fotoEntradaUrl(): ?string
    {
        return Wasabi::url($this->foto_entrada);
    }

    public function fotoSalidaUrl(): ?string
    {
        return Wasabi::url($this->foto_salida);
    }
}
