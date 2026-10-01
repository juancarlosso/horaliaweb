<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SolventiaSeptiembreAsistenciasSeeder extends Seeder
{
    private const COMPANY_NAME = 'SOLVENTIA SOFTWARE SAS';
    private const YEAR = 2026;
    private const MONTH = 9;

    public function run(): void
    {
        $company = Empresa::query()->where('razon_social', self::COMPANY_NAME)->first();
        if (!$company) {
            throw new RuntimeException('No existe SOLVENTIA SOFTWARE SAS. Ejecuta primero SolventiaSoftwareSeeder.');
        }

        $employees = Personal::query()
            ->where('empresa_id', $company->id)
            ->where('email', 'like', 'empleado%@solventia.example.test')
            ->with('horarios')
            ->get();
        if ($employees->isEmpty()) {
            throw new RuntimeException('No se encontraron los empleados creados por SolventiaSoftwareSeeder.');
        }

        $inserted = 0;
        DB::transaction(function () use ($employees, $company, &$inserted): void {
            foreach ($employees as $employee) {
                $scheduledDays = array_filter(explode('@', (string) $employee->laborados));
                $schedules = $employee->horarios->keyBy('dia');

                for ($date = Carbon::create(self::YEAR, self::MONTH, 1); $date->month === self::MONTH; $date->addDay()) {
                    $day = (string) $date->dayOfWeekIso;
                    $schedule = in_array($day, $scheduledDays, true) ? $schedules->get((int) $day) : null;
                    if (!$schedule || !$schedule->entrada || !$schedule->salida) {
                        continue;
                    }

                    $scheduledEntry = Carbon::parse($schedule->entrada)->setDateFrom($date);
                    $scheduledExit = Carbon::parse($schedule->salida)->setDateFrom($date);
                    $entry = random_int(1, 100) <= 25
                        ? $scheduledEntry->copy()->addMinutes((int) $company->minutos_tolerancia_entrada + random_int(1, 20))
                        : $scheduledEntry->copy()->subMinutes(random_int(0, 10));
                    $exit = random_int(1, 100) <= 12
                        ? $scheduledExit->copy()->subMinutes(random_int(1, 25))
                        : $scheduledExit->copy()->addMinutes(random_int(0, 20));
                    $cutoff = $scheduledEntry->copy()->addMinutes((int) $company->minutos_tolerancia_entrada);

                    $attendance = Asistencia::query()->firstOrCreate(
                        ['personal_id' => $employee->id, 'fecha' => $date->toDateString()],
                        [
                            'llegada' => $entry,
                            'salida' => $exit,
                            'minutos_tarde' => $entry->greaterThan($cutoff) ? (int) $cutoff->diffInMinutes($entry) : 0,
                            'minutos_salida_temprano' => $exit->lessThan($scheduledExit) ? (int) $exit->diffInMinutes($scheduledExit) : 0,
                            'latitud' => 19.30 + random_int(-1000, 1000) / 100000,
                            'longitud' => -99.15 + random_int(-1000, 1000) / 100000,
                            'ip' => '192.0.2.' . random_int(1, 254),
                            'latitud_salida' => 19.30 + random_int(-1000, 1000) / 100000,
                            'longitud_salida' => -99.15 + random_int(-1000, 1000) / 100000,
                            'ip_salida' => '192.0.2.' . random_int(1, 254),
                            'rango_entrada' => 'GPS',
                            'rango_salida' => 'GPS',
                        ],
                    );

                    if ($attendance->wasRecentlyCreated) {
                        $inserted++;
                    }
                }
            }
        });

        $this->command?->info(sprintf(
            '%d asistencias de entrada y salida agregadas para %d empleados de SOLVENTIA SOFTWARE SAS en septiembre de 2026. Los registros existentes se conservaron.',
            $inserted,
            $employees->count(),
        ));
    }
}
