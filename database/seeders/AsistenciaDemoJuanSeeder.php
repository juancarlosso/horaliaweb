<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class AsistenciaDemoJuanSeeder extends Seeder
{
    protected const EMPLOYEE_NAME = 'JUAN ALBERTO DE LOS REYES CUEVAS';

    public function run(): void
    {
        $employees = Personal::query()->where('nombre', static::EMPLOYEE_NAME)->get();
        if ($employees->count() !== 1) {
            throw new RuntimeException(sprintf(
                'Se esperaba encontrar exactamente un empleado llamado "%s"; se encontraron %d.',
                static::EMPLOYEE_NAME,
                $employees->count(),
            ));
        }

        $personal = $employees->first();
        $workDays = array_filter(explode('@', (string) $personal->laborados));
        $schedules = $personal->horarios()->get()->keyBy('dia');
        if (!$workDays || $schedules->isEmpty()) {
            throw new RuntimeException('El empleado no tiene días laborables y horarios configurados.');
        }

        $monthStart = now()->startOfMonth();
        $yesterday = today()->subDay();
        $company = $personal->empresa;
        $entryTolerance = (int) ($company?->minutos_tolerancia_entrada ?? 0);
        $inserted = 0;

        for ($date = $monthStart->copy(); $date->lte($yesterday); $date->addDay()) {
            $day = (string) $date->dayOfWeekIso;
            $schedule = in_array($day, $workDays, true) ? $schedules->get((int) $day) : null;
            if (!$schedule || !$schedule->entrada || !$schedule->salida) {
                continue;
            }

            // Leave a few scheduled days without a record to make the sample less uniform.
            if (random_int(1, 100) <= 8) {
                continue;
            }

            $scheduledEntry = Carbon::parse($schedule->entrada)->setDateFrom($date);
            $scheduledExit = Carbon::parse($schedule->salida)->setDateFrom($date);
            $isLate = random_int(1, 100) <= 28;
            $arrival = $isLate
                ? $scheduledEntry->copy()->addMinutes($entryTolerance + random_int(1, 17))
                : $scheduledEntry->copy()->subMinutes(random_int(0, 8));
            $leavesEarly = random_int(1, 100) <= 10;
            $departure = $leavesEarly
                ? $scheduledExit->copy()->subMinutes(random_int(1, 18))
                : $scheduledExit->copy()->addMinutes(random_int(0, 20));
            $cutoff = $scheduledEntry->copy()->addMinutes($entryTolerance);

            $attendance = Asistencia::query()->firstOrCreate(
                ['personal_id' => $personal->id, 'fecha' => $date->toDateString()],
                [
                    'llegada' => $arrival,
                    'salida' => $departure,
                    'minutos_tarde' => $arrival->greaterThan($cutoff) ? $cutoff->diffInMinutes($arrival) : 0,
                    'minutos_salida_temprano' => $departure->lessThan($scheduledExit) ? $departure->diffInMinutes($scheduledExit) : 0,
                ],
            );

            if ($attendance->wasRecentlyCreated) {
                $inserted++;
            }
        }

        $this->command?->info(sprintf(
            '%d asistencias de demostración agregadas para %s en %s. Los registros existentes se conservaron.',
            $inserted,
            static::EMPLOYEE_NAME,
            $monthStart->locale('es')->translatedFormat('F Y'),
        ));
    }
}
