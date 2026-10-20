<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\Personal;
use App\Support\HoraliaTimezones;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistroAsistenciaService
{
    public function preview(Personal $personal, ?array $expectedScope = null): string
    {
        $personal = Personal::query()->with(['empresa', 'centro'])->findOrFail($personal->id);
        $this->assertEligible($personal, $expectedScope);

        $timezone = HoraliaTimezones::forCenter($personal->centro?->timezone);
        $localToday = CarbonImmutable::now('UTC')->setTimezone($timezone)->toDateString();
        $attendance = Asistencia::query()
            ->where('personal_id', $personal->id)
            ->whereDate('fecha', $localToday)
            ->first();

        if ($attendance?->llegada && ! $attendance->salida) {
            $this->assertNoOpenPause($attendance);
            return 'salida';
        }

        if ($attendance?->salida) {
            throw ValidationException::withMessages(['pin' => 'La asistencia de hoy ya está completa.']);
        }

        $overnight = $this->pendingOvernightShift($personal, $localToday)
            ?? $this->pendingCrossTimezoneShift($personal, $localToday, $timezone);
        if ($overnight) {
            $this->assertNoOpenPause($overnight[0]);
            return 'salida';
        }

        $this->scheduleForDate($personal, Carbon::parse($localToday));

        return 'entrada';
    }

    public function register(Personal $personal, string $ip, ?array $coordinates = null, ?string $photoPath = null, ?string $expectedKind = null, ?string $range = null, ?array $expectedScope = null): string
    {
        return DB::transaction(function () use ($personal, $ip, $coordinates, $photoPath, $expectedKind, $range, $expectedScope) {
            $personal = Personal::query()->with(['empresa', 'centro'])->lockForUpdate()->findOrFail($personal->id);
            $this->assertEligible($personal, $expectedScope);
            $center = Centro::query()->whereKey($personal->centro_id)->lockForUpdate()->first();
            if (! $center) {
                throw ValidationException::withMessages(['pin' => 'No se pudo registrar la asistencia.']);
            }
            $personal->setRelation('centro', $center);
            $this->assertEligible($personal, $expectedScope);
            $timezone = HoraliaTimezones::forCenter($center->timezone);
            $utcNow = CarbonImmutable::now('UTC');
            $localNow = $utcNow->setTimezone($timezone);
            $now = Carbon::parse($localNow->format('Y-m-d H:i:s'), $timezone);
            $today = $localNow->toDateString();
            $attendance = Asistencia::query()->where('personal_id', $personal->id)->whereDate('fecha', $today)->lockForUpdate()->first();
            if (! $attendance) {
                $overnightShift = $this->pendingOvernightShift($personal, $today);
                $overnightShift ??= $this->pendingCrossTimezoneShift($personal, $today, $timezone);
                if ($overnightShift) {
                    if ($expectedKind === 'entrada') {
                        throw ValidationException::withMessages(['pin' => 'Primero registra la salida de tu jornada anterior.']);
                    }

                    [$attendance, $schedule] = $overnightShift;
                    $this->assertNoOpenPause($attendance);
                    $scheduledExit = $this->scheduledExitForAttendance($schedule, $attendance, $timezone);
                    $earlyMinutes = $now->lessThan($scheduledExit) ? (int) $now->diffInMinutes($scheduledExit) : 0;
                    $attendance->update([
                        'salida' => $now, 'latitud_salida' => $coordinates['latitud_salida'] ?? null,
                        'longitud_salida' => $coordinates['longitud_salida'] ?? null, 'ip_salida' => $ip,
                        'minutos_salida_temprano' => $earlyMinutes,
                        'rango_salida' => $range ?? ($coordinates ? null : 'Checador PIN'), 'foto_salida' => $photoPath,
                        'salida_utc' => $utcNow->format('Y-m-d H:i:s'),
                        'centro_trabajo_salida_id' => $center->id, 'timezone_salida' => $timezone,
                    ]);

                    return 'salida';
                }

                if ($expectedKind === 'salida') {
                    throw ValidationException::withMessages(['pin' => 'Primero registra tu entrada de hoy.']);
                }

                $schedule = $this->scheduleForDate($personal, Carbon::parse($today, $timezone));
                $scheduledEntry = Carbon::parse($today.' '.$schedule->entrada, $timezone);
                $cutoff = $scheduledEntry->copy()->addMinutes((int) ($personal->empresa->minutos_tolerancia_entrada ?? 0));
                $lateMinutes = $now->greaterThan($cutoff) ? (int) $cutoff->diffInMinutes($now) : 0;
                Asistencia::create([
                    'personal_id' => $personal->id, 'fecha' => $today, 'llegada' => $now,
                    'llegada_utc' => $utcNow->format('Y-m-d H:i:s'),
                    'centro_trabajo_entrada_id' => $center->id, 'timezone_entrada' => $timezone,
                    'latitud' => $coordinates['latitud'] ?? null, 'longitud' => $coordinates['longitud'] ?? null,
                    'ip' => $ip, 'minutos_tarde' => $lateMinutes,
                    'rango_entrada' => $range ?? ($coordinates ? null : 'Checador PIN'), 'foto_entrada' => $photoPath,
                ]);

                return 'entrada';
            }

            if (! $attendance->llegada) {
                throw ValidationException::withMessages(['pin' => 'No se pudo registrar la asistencia.']);
            }
            if ($attendance->salida) {
                throw ValidationException::withMessages(['pin' => 'La asistencia de hoy ya está completa.']);
            }
            if ($expectedKind === 'entrada') {
                throw ValidationException::withMessages(['pin' => 'La entrada de hoy ya está registrada.']);
            }

            $schedule = $this->scheduleForDate($personal, $attendance->fecha);
            $this->assertNoOpenPause($attendance);
            $scheduledExit = $this->scheduledExitForAttendance($schedule, $attendance, $timezone);
            $earlyMinutes = $now->lessThan($scheduledExit) ? (int) $now->diffInMinutes($scheduledExit) : 0;
            $attendance->update([
                'salida' => $now, 'latitud_salida' => $coordinates['latitud_salida'] ?? null,
                'longitud_salida' => $coordinates['longitud_salida'] ?? null, 'ip_salida' => $ip,
                'minutos_salida_temprano' => $earlyMinutes,
                'rango_salida' => $range ?? ($coordinates ? null : 'Checador PIN'), 'foto_salida' => $photoPath,
                'salida_utc' => $utcNow->format('Y-m-d H:i:s'),
                'centro_trabajo_salida_id' => $center->id, 'timezone_salida' => $timezone,
            ]);

            return 'salida';
        });
    }

    private function assertNoOpenPause(Asistencia $attendance): void
    {
        if ($attendance->pausas()->whereNull('fin')->exists()) {
            throw ValidationException::withMessages(['pin' => 'Finaliza tu pausa en curso antes de registrar la salida.']);
        }
    }

    private function assertEligible(Personal $personal, ?array $expectedScope): void
    {
        $centerBound = $expectedScope && array_key_exists('centro_id', $expectedScope);
        if (! $personal->activo || ! $personal->empresa?->activa
            || (! $expectedScope && ! $personal->centro?->activo)
            || ($centerBound && (! $personal->centro?->activo || (int) $personal->centro_id !== (int) $expectedScope['centro_id']))
            || ($expectedScope && (int) $personal->empresa_id !== (int) $expectedScope['empresa_id'])) {
            throw ValidationException::withMessages(['pin' => 'No se pudo registrar la asistencia.']);
        }
    }

    public function pendingOvernightAttendance(Personal $personal): ?Asistencia
    {
        $timezone = HoraliaTimezones::forCenter($personal->centro?->timezone);
        $today = CarbonImmutable::now('UTC')->setTimezone($timezone)->toDateString();
        $shift = $this->pendingOvernightShift($personal, $today)
            ?? $this->pendingCrossTimezoneShift($personal, $today, $timezone);

        return $shift[0] ?? null;
    }

    private function pendingOvernightShift(Personal $personal, ?string $localToday = null): ?array
    {
        $attendance = Asistencia::query()
            ->where('personal_id', $personal->id)
            ->whereNotNull('llegada')
            ->whereNull('salida')
            ->whereDate('fecha', '<', $localToday ?? CarbonImmutable::now('UTC')->setTimezone(HoraliaTimezones::forCenter($personal->centro?->timezone))->toDateString())
            ->orderByDesc('fecha')
            ->first();

        if (! $attendance) {
            return null;
        }

        $schedule = $this->scheduleForDate($personal, $attendance->fecha, false);
        if (! $schedule || ! $this->scheduleEndsNextDay($schedule)) {
            return null;
        }

        return [$attendance, $schedule];
    }

    private function pendingCrossTimezoneShift(Personal $personal, string $localToday, string $timezone): ?array
    {
        $currentCenterId = (int) $personal->centro_id;
        $localStart = CarbonImmutable::parse($localToday, $timezone)->startOfDay();
        $utcStart = $localStart->setTimezone('UTC')->format('Y-m-d H:i:s');
        $utcEnd = $localStart->addDay()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $candidates = Asistencia::query()
            ->where('personal_id', $personal->id)
            ->whereNotNull('llegada_utc')
            ->whereNotNull('centro_trabajo_entrada_id')
            ->where('centro_trabajo_entrada_id', '<>', $currentCenterId)
            ->whereNull('salida')
            ->where('llegada_utc', '>=', $utcStart)
            ->where('llegada_utc', '<', $utcEnd)
            ->orderByDesc('llegada_utc')
            ->limit(2)
            ->get();

        if ($candidates->count() !== 1) {
            return null;
        }

        $attendance = $candidates->first();
        $schedule = $this->scheduleForDate($personal, Carbon::parse($attendance->fecha), false);

        return $schedule ? [$attendance, $schedule] : null;
    }

    private function scheduleForDate(Personal $personal, Carbon $date, bool $required = true)
    {
        $day = $date->dayOfWeekIso;
        $laborados = array_filter(explode('@', (string) $personal->laborados));
        $schedule = $personal->horarios()->where('dia', $day)->first();
        if (! in_array((string) $day, $laborados, true) || ! $schedule) {
            if (! $required) {
                return null;
            }
            throw ValidationException::withMessages(['pin' => 'No tienes un horario laboral configurado para hoy.']);
        }

        return $schedule;
    }

    private function scheduleEndsNextDay($schedule): bool
    {
        return substr((string) $schedule->salida, 0, 8) <= substr((string) $schedule->entrada, 0, 8);
    }

    private function scheduledExit($schedule, Carbon $shiftDate, string $timezone): Carbon
    {
        $scheduledExit = Carbon::parse($shiftDate->toDateString().' '.$schedule->salida, $timezone);
        if ($this->scheduleEndsNextDay($schedule)) {
            $scheduledExit->addDay();
        }

        return $scheduledExit;
    }

    private function scheduledExitForAttendance($schedule, Asistencia $attendance, string $timezone): Carbon
    {
        return $this->scheduledExit($schedule, Carbon::parse($attendance->fecha), $timezone);
    }
}
