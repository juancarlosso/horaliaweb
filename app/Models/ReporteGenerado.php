<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporteGenerado extends Model
{
    use HasUuids;

    public const PENDIENTE = 'pendiente';

    public const PROCESANDO = 'procesando';

    public const LISTO = 'listo';

    public const FALLIDO = 'fallido';

    public const HORAS = 'horas-trabajadas';

    public const GENERAL = 'general';

    public const INCIDENCIAS = 'incidencias';

    public const DEPARTAMENTO = 'departamento';

    public const TITULOS = [
        self::HORAS => 'Horas trabajadas',
        self::GENERAL => 'General',
        self::INCIDENCIAS => 'Incidencias',
        self::DEPARTAMENTO => 'Por Departamento',
    ];

    protected $table = 'reportes_horas_trabajadas';

    protected $attributes = ['tipo' => self::HORAS];

    protected $fillable = ['user_id', 'empresa_id', 'desde', 'hasta', 'estado', 'tipo', 'filtros', 'resumen'];

    protected function casts(): array
    {
        return ['desde' => 'immutable_date', 'hasta' => 'immutable_date', 'filtros' => 'array', 'resumen' => 'array'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resultado(): HasMany
    {
        return $this->hasMany(ReporteGeneradoFila::class, 'reporte_id');
    }

    public function enProceso(): bool
    {
        return in_array($this->estado, [self::PENDIENTE, self::PROCESANDO], true);
    }

    public function archivo(): string
    {
        return 'reportes/'.$this->tipo.'/'.$this->id.'.xlsx';
    }

    public function nombreArchivo(): string
    {
        $from = $this->desde;
        $through = $this->hasta;
        if ($this->tipo === self::INCIDENCIAS) {
            return 'horalia_incidencias_'.$from->format('Ymd').'_'.$through->format('Ymd').'.xlsx';
        }

        $prefix = match ($this->tipo) {
            self::GENERAL => 'reporte_asistencia',
            self::DEPARTAMENTO => 'reporte_por_departamento',
            default => 'horas_trabajadas',
        };

        return $prefix.'_'.$from->toDateString().'_'.$through->toDateString().'.xlsx';
    }
}
