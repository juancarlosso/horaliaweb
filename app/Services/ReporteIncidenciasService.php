<?php

namespace App\Services;

use App\Support\HoraliaTimezones;
use App\Models\Asistencia;
use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use App\Models\Incapacidad;
use App\Models\Personal;
use App\Models\VacacionFecha;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReporteIncidenciasService
{
    public const TYPES = ['retardos', 'posibles_faltas', 'salidas_pendientes', 'vacaciones', 'vacaciones_trabajadas', 'festivos_trabajados', 'incapacidades', 'incapacidades_trabajadas'];

    public const LABELS = [
        'retardos' => 'Retardo', 'posibles_faltas' => 'Falta', 'salidas_pendientes' => 'Salida Pendiente',
        'vacaciones' => 'Vacaciones', 'vacaciones_trabajadas' => 'Vacaciones Trabajadas', 'festivos_trabajados' => 'Festivo Trabajado',
        'incapacidades' => 'Incapacidad', 'incapacidades_trabajadas' => 'Incapacidad trabajada',
    ];

    public const DESCRIPTIONS = [
        'retardos' => 'El empleado registró su entrada después de la hora permitida, considerando su horario asignado y la tolerancia configurada.',
        'posibles_faltas' => 'El empleado tenía una jornada programada, pero no existe una entrada registrada para ese día.',
        'salidas_pendientes' => 'El empleado registró su entrada, pero no su salida. Se considera que sí asistió a trabajar, aunque la jornada permanece abierta.',
        'vacaciones' => 'El empleado tiene vacaciones registradas para ese día y no se detectó asistencia. Solo se consideran los días que corresponden a vacaciones según su calendario laboral.',
        'vacaciones_trabajadas' => 'El empleado registró una entrada durante un día de vacaciones. Esta incidencia es informativa y no modifica automáticamente las vacaciones registradas.',
        'festivos_trabajados' => 'El empleado registró asistencia durante un día festivo oficial o de la empresa, independientemente de que estuviera programado para trabajar.',
        'incapacidades' => 'Jornada laboral congelada en el snapshot de una incapacidad vigente, sin entrada registrada. No se considera posible falta.',
        'incapacidades_trabajadas' => 'Jornada laboral congelada en el snapshot de una incapacidad vigente con entrada registrada; la asistencia se conserva sin modificar checadas.',
    ];

    public function generate(int $companyId, string $from, string $through, array $types): Collection
    {
        $employees = Personal::query()->where('empresa_id', $companyId)->with(['centro', 'horarios'])->orderBy('nombre')->get();
        if ($employees->isEmpty()) return collect();

        $employeeIds = $employees->modelKeys();
        $attendance = Asistencia::query()
            ->whereIn('personal_id', $employeeIds)
            ->where('fecha', '>=', $from)
            ->where('fecha', '<', CarbonImmutable::parse($through)->addDay()->toDateString())
            ->whereNotNull('llegada')
            ->get(['personal_id', 'fecha', 'llegada', 'salida', 'minutos_tarde'])
            ->keyBy(fn ($row) => $row->personal_id.'|'.$row->fecha->toDateString());
        $holidays = collect();
        foreach (FestivoOficial::query()->whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $through)->get() as $holiday) {
            $holidays->put($holiday->fecha->toDateString(), ['Oficial: '.$holiday->conmemoracion]);
        }
        foreach (FestivoEmpresa::query()->where('empresa_id', $companyId)->whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $through)->get() as $holiday) {
            $holidays->put($holiday->fecha->toDateString(), array_merge($holidays->get($holiday->fecha->toDateString(), []), ['Empresa: '.$holiday->conmemoracion]));
        }
        $vacations = VacacionFecha::query()->whereHas('vacacion', fn ($q) => $q->where('empresa_id', $companyId)->where('estado', 'registrado'))
            ->whereHas('vacacion', fn ($q) => $q->whereIn('personal_id', $employeeIds))
            ->whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $through)->with('vacacion')->get()->groupBy(fn ($row) => $row->vacacion->personal_id.'|'.$row->fecha->toDateString());
        $incapacityDates = [];
        foreach (Incapacidad::query()->where('empresa_id', $companyId)->whereIn('personal_id', $employeeIds)->where('estado', 'vigente')->whereDate('fecha_inicio', '<=', $through)->whereDate('fecha_fin', '>=', $from)->get() as $incapacity) {
            foreach (($incapacity->snapshot['fechas'] ?? []) as $snapshotDate) {
                if (!empty($snapshotDate['laborable']) && $snapshotDate['fecha'] >= $from && $snapshotDate['fecha'] <= $through) {
                    $incapacityDates[$incapacity->personal_id.'|'.$snapshotDate['fecha']][] = $incapacity;
                }
            }
        }

        $rows = collect();
        $startDate = CarbonImmutable::parse($from);
        foreach ($employees as $employee) {
            $timezone = HoraliaTimezones::forCenter($employee->centro?->timezone);
            $lastCompleteDay = CarbonImmutable::now('UTC')->setTimezone($timezone)->startOfDay()->subDay()->toDateString();
            $end = min($through, $lastCompleteDay);
            if ($end < $from) continue;
            $endDate = CarbonImmutable::parse($end);
            $workdays = array_filter(explode('@', (string) $employee->laborados));
            $schedules = $employee->horarios->keyBy('dia');
            for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
                $day = $date->toDateString(); $key = $employee->id.'|'.$day; $schedule = $schedules->get($date->dayOfWeekIso);
                $scheduled = $schedule && in_array((string) $date->dayOfWeekIso, $workdays, true);
                $record = $attendance->get($key);
                $vacation = $vacations->has($key); $holidayNames = $holidays->get($day, []);
                $incapacity = !empty($incapacityDates[$key]);
                $holidayWork = (bool) ($schedule?->trabaja_festivos);
                $obligated = $scheduled && (empty($holidayNames) || $holidayWork);
                $withinEmployment = !$employee->fecha_baja || $day <= $employee->fecha_baja->setTimezone($timezone)->toDateString();
                $base = ['fecha' => $day, 'empleado' => $employee->nombre, 'codigo' => $employee->codigo, 'employee_id' => $employee->id];

                if ($record && $obligated && (int) $record->minutos_tarde > 0 && in_array('retardos', $types, true)) {
                    $rows->push($base + ['tipo' => 'retardos', 'detalle' => 'Programada: '.substr((string) $schedule?->entrada, 0, 5).'; entrada: '.$record->llegada->format('H:i').'; '.$record->minutos_tarde.' min de retardo.']);
                }
                if ($record && !$record->salida && in_array('salidas_pendientes', $types, true)) {
                    $rows->push($base + ['tipo' => 'salidas_pendientes', 'detalle' => 'Entrada: '.$record->llegada->format('H:i').'; salida pendiente.']);
                }
                if ($vacation && $record && in_array('vacaciones_trabajadas', $types, true)) {
                    $rows->push($base + ['tipo' => 'vacaciones_trabajadas', 'detalle' => 'Entrada registrada: '.$record->llegada->format('H:i').'; vacaciones contabilizadas para '.$day.'.']);
                } elseif ($vacation && !$record && in_array('vacaciones', $types, true)) {
                    $rows->push($base + ['tipo' => 'vacaciones', 'detalle' => 'Día contabilizado como vacaciones; sin entrada registrada.']);
                }
                if ($incapacity && !$record && in_array('incapacidades', $types, true)) {
                    $rows->push($base + ['tipo' => 'incapacidades', 'detalle' => 'Jornada cubierta por incapacidad vigente; sin entrada registrada.'.($vacation ? ' También coincide con vacaciones registradas; ambos historiales se conservan.' : '')]);
                } elseif ($incapacity && $record && in_array('incapacidades_trabajadas', $types, true)) {
                    $rows->push($base + ['tipo' => 'incapacidades_trabajadas', 'detalle' => 'Jornada cubierta por incapacidad vigente; entrada registrada: '.$record->llegada->format('H:i').'. La asistencia se conserva.'.($vacation ? ' También coincide con vacaciones registradas; ambos historiales se conservan.' : '')]);
                }
                if ($record && $holidayNames && in_array('festivos_trabajados', $types, true)) {
                    $rows->push($base + ['tipo' => 'festivos_trabajados', 'detalle' => implode('; ', $holidayNames).'; entrada: '.$record->llegada->format('H:i').($obligated ? '; jornada programada.' : '; sin obligación programada.')]);
                }
                if ($obligated && $withinEmployment && !$record && !$vacation && !$incapacity && in_array('posibles_faltas', $types, true)) {
                    $rows->push($base + ['tipo' => 'posibles_faltas', 'detalle' => 'Horario programado: '.substr((string) $schedule->entrada, 0, 5).'–'.substr((string) $schedule->salida, 0, 5).'; sin entrada registrada.']);
                }
            }
        }
        return $rows->sortBy([['fecha', 'asc'], ['empleado', 'asc'], ['tipo', 'asc']])->values();
    }
}
