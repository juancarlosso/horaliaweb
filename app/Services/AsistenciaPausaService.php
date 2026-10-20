<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\AsistenciaPausa;
use App\Models\Personal;
use App\Support\HoraliaTimezones;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaPausaService
{
    public function __construct(private RegistroAsistenciaService $registration) {}

    public function currentAttendance(Personal $personal): ?Asistencia
    {
        $personal->loadMissing(['empresa', 'centro']);
        $today = CarbonImmutable::now('UTC')->setTimezone(HoraliaTimezones::forCenter($personal->centro?->timezone))->toDateString();
        $todayAttendance = Asistencia::query()->where('personal_id', $personal->id)->whereDate('fecha', $today)->first();
        if ($todayAttendance) {
            return $todayAttendance->llegada && ! $todayAttendance->salida ? $todayAttendance : null;
        }

        return $this->registration->pendingOvernightAttendance($personal);
    }

    public function start(Personal $personal, string $type, ?array $expectedScope = null): AsistenciaPausa
    {
        return DB::transaction(function () use ($personal, $type, $expectedScope) {
            $personal = Personal::query()->with(['empresa', 'centro'])->lockForUpdate()->findOrFail($personal->id);
            $this->assertEligible($personal, $expectedScope);
            $attendance = $this->lockedCurrentAttendance($personal);
            $this->assertAttendanceOpen($attendance);
            if ($attendance->pausas()->whereNull('fin')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['pausa' => 'Ya tienes una pausa en curso. Finalízala antes de iniciar otra.']);
            }

            $utcNow = CarbonImmutable::now('UTC');
            $timezone = HoraliaTimezones::forCenter($personal->centro?->timezone);
            $localNow = $utcNow->setTimezone($timezone);

            return $attendance->pausas()->create([
                'tipo' => $type,
                'inicio' => $localNow->format('Y-m-d H:i:s'),
                'inicio_utc' => $utcNow->format('Y-m-d H:i:s'),
            ]);
        });
    }

    public function finish(Personal $personal, ?array $expectedScope = null): AsistenciaPausa
    {
        return DB::transaction(function () use ($personal, $expectedScope) {
            $personal = Personal::query()->with(['empresa', 'centro'])->lockForUpdate()->findOrFail($personal->id);
            $this->assertEligible($personal, $expectedScope);
            $attendance = $this->lockedCurrentAttendance($personal);
            $this->assertAttendanceOpen($attendance);
            $pause = $attendance->pausas()->whereNull('fin')->lockForUpdate()->first();
            if (! $pause) {
                throw ValidationException::withMessages(['pausa' => 'No tienes una pausa en curso para finalizar.']);
            }

            $utcNow = CarbonImmutable::now('UTC');
            $pauseStartUtc = $pause->getRawOriginal('inicio_utc');
            if ($pauseStartUtc && $utcNow->lt(CarbonImmutable::parse($pauseStartUtc, 'UTC'))) {
                throw ValidationException::withMessages(['pausa' => 'La hora de finalización no puede ser anterior al inicio de la pausa.']);
            }
            $timezone = HoraliaTimezones::forCenter($personal->centro?->timezone);
            $localNow = $utcNow->setTimezone($timezone);
            $pause->update(['fin' => $localNow->format('Y-m-d H:i:s'), 'fin_utc' => $utcNow->format('Y-m-d H:i:s')]);

            return $pause;
        });
    }

    private function lockedCurrentAttendance(Personal $personal): ?Asistencia
    {
        $attendance = $this->currentAttendance($personal);
        return $attendance ? Asistencia::query()->whereKey($attendance->id)->lockForUpdate()->first() : null;
    }

    private function assertEligible(Personal $personal, ?array $expectedScope = null): void
    {
        $centerBound = $expectedScope && array_key_exists('centro_id', $expectedScope);
        if (! $personal->activo || ! $personal->empresa?->activa || ! $personal->centro
            || ($expectedScope && (int) $personal->empresa_id !== (int) $expectedScope['empresa_id'])
            || ($centerBound && (int) $personal->centro_id !== (int) $expectedScope['centro_id'])) {
            throw ValidationException::withMessages(['pausa' => 'Tu cuenta, empresa o centro de trabajo no está disponible.']);
        }
    }

    private function assertAttendanceOpen(?Asistencia $attendance): void
    {
        if (! $attendance || ! $attendance->llegada || $attendance->salida) {
            throw ValidationException::withMessages(['pausa' => 'Necesitas una entrada vigente sin salida para registrar pausas.']);
        }
    }
}
