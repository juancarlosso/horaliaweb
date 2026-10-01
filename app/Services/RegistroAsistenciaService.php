<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistroAsistenciaService
{
    public function preview(Personal $personal, ?array $expectedScope = null): string
    {
        $personal = Personal::query()->with(['empresa', 'centro'])->findOrFail($personal->id);
        $this->assertEligible($personal, $expectedScope);
        $this->scheduleForToday($personal);

        $attendance = Asistencia::query()
            ->where('personal_id', $personal->id)
            ->whereDate('fecha', today())
            ->first();

        if (!$attendance) {
            return 'entrada';
        }
        if ($attendance->llegada && !$attendance->salida) {
            return 'salida';
        }

        throw ValidationException::withMessages(['pin' => 'La asistencia de hoy ya está completa.']);
    }

    public function register(Personal $personal, string $ip, ?array $coordinates = null, ?string $photoPath = null, ?string $expectedKind = null, ?string $range = null, ?array $expectedScope = null): string
    {
        return DB::transaction(function () use ($personal, $ip, $coordinates, $photoPath, $expectedKind, $range, $expectedScope) {
            $personal = Personal::query()->with(['empresa', 'centro'])->lockForUpdate()->findOrFail($personal->id);
            $this->assertEligible($personal, $expectedScope);
            $schedule = $this->scheduleForToday($personal);

            $today = today()->toDateString();
            $now = now();
            $attendance = Asistencia::query()->where('personal_id', $personal->id)->whereDate('fecha', $today)->lockForUpdate()->first();
            if (!$attendance) {
                if ($expectedKind === 'salida') {
                    throw ValidationException::withMessages(['pin' => 'Primero registra tu entrada de hoy.']);
                }
                $scheduledEntry = Carbon::parse($schedule->entrada)->setDateFrom($now);
                $cutoff = $scheduledEntry->copy()->addMinutes((int) ($personal->empresa->minutos_tolerancia_entrada ?? 0));
                $lateMinutes = $now->greaterThan($cutoff) ? (int) $cutoff->diffInMinutes($now) : 0;
                Asistencia::create([
                    'personal_id' => $personal->id, 'fecha' => $today, 'llegada' => $now,
                    'latitud' => $coordinates['latitud'] ?? null, 'longitud' => $coordinates['longitud'] ?? null,
                    'ip' => $ip, 'minutos_tarde' => $lateMinutes,
                    'rango_entrada' => $range ?? ($coordinates ? null : 'Checador PIN'), 'foto_entrada' => $photoPath,
                ]);

                return 'entrada';
            }

            if (!$attendance->llegada) {
                throw ValidationException::withMessages(['pin' => 'No se pudo registrar la asistencia.']);
            }
            if ($attendance->salida) {
                throw ValidationException::withMessages(['pin' => 'La asistencia de hoy ya está completa.']);
            }
            if ($expectedKind === 'entrada') {
                throw ValidationException::withMessages(['pin' => 'La entrada de hoy ya está registrada.']);
            }

            $scheduledExit = Carbon::parse($schedule->salida)->setDateFrom($now);
            $earlyMinutes = $now->lessThan($scheduledExit) ? (int) $now->diffInMinutes($scheduledExit) : 0;
            $attendance->update([
                'salida' => $now, 'latitud_salida' => $coordinates['latitud_salida'] ?? null,
                'longitud_salida' => $coordinates['longitud_salida'] ?? null, 'ip_salida' => $ip,
                'minutos_salida_temprano' => $earlyMinutes,
                'rango_salida' => $range ?? ($coordinates ? null : 'Checador PIN'), 'foto_salida' => $photoPath,
            ]);

            return 'salida';
        });
    }

    private function assertEligible(Personal $personal, ?array $expectedScope): void
    {
        $centerBound = $expectedScope && array_key_exists('centro_id', $expectedScope);
        if (!$personal->activo || !$personal->empresa?->activa
            || (!$expectedScope && !$personal->centro?->activo)
            || ($centerBound && (!$personal->centro?->activo || (int) $personal->centro_id !== (int) $expectedScope['centro_id']))
            || ($expectedScope && (int) $personal->empresa_id !== (int) $expectedScope['empresa_id'])) {
            throw ValidationException::withMessages(['pin' => 'No se pudo registrar la asistencia.']);
        }
    }

    private function scheduleForToday(Personal $personal)
    {
        $day = now()->dayOfWeekIso;
        $laborados = array_filter(explode('@', (string) $personal->laborados));
        $schedule = $personal->horarios()->where('dia', $day)->first();
        if (!in_array((string) $day, $laborados, true) || !$schedule) {
            throw ValidationException::withMessages(['pin' => 'No tienes un horario laboral configurado para hoy.']);
        }

        return $schedule;
    }
}
