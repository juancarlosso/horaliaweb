<?php

namespace App\Services;

use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use App\Models\Personal;
use App\Models\Vacacion;
use App\Models\VacacionDetalle;
use App\Models\VacacionFecha;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacacionesService
{
    /** Return a dated breakdown, using the same ISO weekday, laborados and trabaja_festivos fields as attendance. */
    public function calculate(Personal $personal, string $from, string $through): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($through)->startOfDay();
        if ($end->lt($start)) throw ValidationException::withMessages(['fecha_fin' => 'La fecha final no puede ser anterior a la inicial.']);

        $dates = [];
        $included = [];
        $days = array_filter(explode('@', (string) $personal->laborados));
        $schedules = $personal->horarios()->get()->keyBy('dia');
        $official = FestivoOficial::query()->whereBetween('fecha', [$from, $through])->get(['fecha', 'conmemoracion'])
            ->keyBy(fn ($holiday) => CarbonImmutable::parse($holiday->fecha)->toDateString());
        $company = FestivoEmpresa::query()->where('empresa_id', $personal->empresa_id)->whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $through)->get(['fecha', 'conmemoracion'])
            ->keyBy(fn ($holiday) => CarbonImmutable::parse($holiday->fecha)->toDateString());

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $schedule = $schedules->get($date->dayOfWeekIso);
            $holidayNames = collect([$official->get($key)?->conmemoracion, $company->get($key)?->conmemoracion])->filter()->unique()->values();
            $counted = $schedule && in_array((string) $date->dayOfWeekIso, $days, true)
                && ($holidayNames->isEmpty() || (bool) $schedule->trabaja_festivos);
            $reason = $counted ? 'Jornada programada' : ($holidayNames->isNotEmpty() && !($schedule?->trabaja_festivos) ? 'Festivo con descanso' : 'Día sin jornada programada');
            $entry = ['fecha' => $key, 'cuenta' => (bool) $counted, 'motivo' => $reason, 'festivos' => $holidayNames->all()];
            $dates[] = $entry;
            if ($counted) $included[] = $key;
        }

        return ['dias' => $dates, 'fechas_contabilizadas' => $included, 'total' => count($included)];
    }

    public function register(array $data, int $userId): Vacacion
    {
        return DB::transaction(function () use ($data, $userId) {
            $personal = Personal::query()->where('empresa_id', $data['empresa_id'])->whereKey($data['personal_id'])->lockForUpdate()->firstOrFail();
            $approver = Personal::query()->where('empresa_id', $data['empresa_id'])->whereKey($data['autorizado_por_personal_id'])->firstOrFail();
            $calculation = $this->calculate($personal, $data['fecha_inicio'], $data['fecha_fin']);
            if (!$calculation['total']) throw ValidationException::withMessages(['fecha_inicio' => 'El periodo no contiene días laborables contabilizables.']);

            $overlap = Vacacion::query()->where('personal_id', $personal->id)->where('estado', 'registrado')
                ->whereDate('fecha_inicio', '<=', $data['fecha_fin'])->whereDate('fecha_fin', '>=', $data['fecha_inicio'])->exists();
            if ($overlap) throw ValidationException::withMessages(['fecha_inicio' => 'El periodo se traslapa con vacaciones vigentes del colaborador.']);

            $vacation = Vacacion::create([
                'empresa_id' => $data['empresa_id'], 'personal_id' => $personal->id,
                'autorizado_por_personal_id' => $approver->id, 'registrado_por_user_id' => $userId,
                'fecha_inicio' => $data['fecha_inicio'], 'fecha_fin' => $data['fecha_fin'],
                'total_dias' => $calculation['total'], 'estado' => 'registrado',
            ]);
            foreach ($calculation['dias'] as $day) {
                VacacionDetalle::create([
                    'vacacion_id' => $vacation->id,
                    'fecha' => $day['fecha'],
                    'cuenta' => $day['cuenta'],
                    'motivo' => $day['motivo'],
                    'festivos' => $day['festivos'] ?: null,
                ]);
                if ($day['cuenta']) {
                    VacacionFecha::create(['vacacion_id' => $vacation->id, 'fecha' => $day['fecha']]);
                }
            }
            return $vacation;
        });
    }
}
