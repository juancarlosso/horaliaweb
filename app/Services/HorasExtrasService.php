<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Support\HoraliaTimezones;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class HorasExtrasService
{
    public function __construct(private RegistroAsistenciaService $registro) {}

    public function asistencias(int $empresa, bool $soloActivos = false): Builder
    {
        return Asistencia::query()
            ->where(fn ($query) => $query->where('empresa_id', $empresa)->orWhereNull('empresa_id'))
            ->whereHas('personal', fn ($query) => $query->where('empresa_id', $empresa)
                ->when($soloActivos, fn ($query) => $query->where('activo', true)))
            ->whereNotNull('llegada')->whereNotNull('salida')
            ->with(['personal.empresa', 'personal.horarios', 'personal.centro', 'centroEntrada']);
    }

    public function pendientes(int $empresa, string $desde, string $hastaExclusivo): Builder
    {
        $asistencias = $this->asistencias($empresa, soloActivos: true);
        // SQLite puede guardar una fecha de Eloquent como texto con hora de medianoche.
        [$inicio, $fin] = match ($asistencias->getConnection()->getDriverName()) {
            'sqlite' => ["substr(asistencias.fecha, 1, 10)", "strftime('%Y-%m-%d', asistencias.fecha, '+1 day')"],
            'pgsql' => ['asistencias.fecha', 'asistencias.fecha + 1'],
            'sqlsrv' => ['asistencias.fecha', 'DATEADD(day, 1, asistencias.fecha)'],
            default => ['asistencias.fecha', 'asistencias.fecha + INTERVAL 1 DAY'],
        };

        return $asistencias
            ->where('asistencias.fecha', '>=', $desde)
            ->where('asistencias.fecha', '<', $hastaExclusivo)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('horas_extras')
                ->whereColumn('horas_extras.personal_id', 'asistencias.personal_id')
                ->whereRaw("horas_extras.fecha >= {$inicio}")
                ->whereRaw("horas_extras.fecha < {$fin}"));
    }

    public function detectar(Asistencia $asistencia): ?array
    {
        $personal = $asistencia->personal;
        $centro = $asistencia->centroEntrada ?? $personal->centro;
        if ($centro && (int) $centro->empresa_id !== (int) $personal->empresa_id) {
            return null;
        }

        $zona = HoraliaTimezones::forCenter($asistencia->timezone_entrada ?? $centro?->timezone);
        $zonaSalida = HoraliaTimezones::forCenter($asistencia->timezone_salida ?? $zona);
        $fecha = CarbonImmutable::parse($asistencia->fecha->toDateString(), $zona);
        $entrada = $this->instante($asistencia, 'llegada', $zona);
        $salida = $this->instante($asistencia, 'salida', $zonaSalida);

        if (! $entrada || ! $salida || $salida->lte($entrada) || $salida->isFuture() || $fecha->isAfter(CarbonImmutable::today($zona))) {
            return null;
        }

        $dia = $fecha->dayOfWeekIso;
        $descanso = ! in_array((string) $dia, explode('@', (string) $personal->laborados), true);
        $horario = $descanso ? null : $personal->horarios->firstWhere('dia', $dia);
        $festivos = $this->registro->holidayCommemorations($personal, $fecha);
        $especial = $descanso || ($festivos->isNotEmpty() && $horario && ! $horario->trabaja_festivos);
        if (! $descanso && ! $horario) {
            return null;
        }

        $inicioProgramado = $horario ? CarbonImmutable::parse($fecha->toDateString().' '.$horario->entrada, $zona) : null;
        $finProgramado = $horario ? $this->registro->scheduledExit($horario, $fecha->toDateString(), $zona) : null;
        $adicionales = $finProgramado ? max(0, (int) $finProgramado->diffInMinutes($salida)) : 0;
        $minutos = $especial ? (int) $entrada->diffInMinutes($salida) : $adicionales;
        if ($minutos === 0 && ($especial || $festivos->isEmpty())) {
            return null;
        }

        $condiciones = [];
        if ($festivos->isNotEmpty()) {
            $condiciones[] = 'Festivo trabajado';
        }
        if ($descanso) {
            $condiciones[] = 'Descanso trabajado';
        }
        if (! $especial && $adicionales > 0) {
            $condiciones[] = 'Tiempo adicional';
        }

        return [
            'empresa' => $personal->empresa->nombre,
            'centro' => $centro?->nombre,
            'empleado' => $personal->nombre,
            'codigo' => $personal->codigo,
            'fecha' => $fecha->toDateString(),
            'zona' => $zona,
            'zona_salida' => $zonaSalida,
            'entrada' => $entrada->setTimezone($zona)->format('d/m/Y H:i:s T'),
            'salida' => $salida->setTimezone($zonaSalida)->format('d/m/Y H:i:s T'),
            'programado' => $horario ? $inicioProgramado->format('d/m/Y H:i').' a '.$finProgramado->format('d/m/Y H:i') : null,
            'trabaja_festivos' => $horario?->trabaja_festivos,
            'festivos' => $festivos->all(),
            'condiciones' => $condiciones,
            'criterio' => $especial ? 'Tiempo entre entrada y salida en día especial' : 'Tiempo posterior a la salida programada',
            'minutos' => $minutos,
        ];
    }

    private function instante(Asistencia $asistencia, string $campo, string $zona): ?CarbonImmutable
    {
        $utc = $asistencia->getRawOriginal($campo.'_utc');
        $local = $asistencia->getRawOriginal($campo);

        return $utc ? CarbonImmutable::parse($utc, 'UTC') : ($local ? CarbonImmutable::parse($local, $zona) : null);
    }
}
