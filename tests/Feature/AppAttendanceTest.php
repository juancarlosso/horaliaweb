<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\AsistenciaPausa;
use App\Models\Centro;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use App\Services\AttendancePhotoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_app_is_limited_to_one_company_while_current_app_marks_across_companies(): void
    {
        [$user, $firstCompany] = $this->administrator();
        $secondCompany = Empresa::query()->create([
            'rfc' => strtoupper(fake()->unique()->bothify('????######???')),
            'razon_social' => 'Segunda empresa móvil',
            'activa' => true,
        ]);
        $user->empresas()->attach($secondCompany->id, ['control_total' => true]);

        $weekday = Carbon::now('America/Mexico_City')->dayOfWeekIso;
        $employees = [];
        foreach ([[$firstCompany, '11111'], [$secondCompany, '22222']] as [$company, $pin]) {
            $center = Centro::query()->create([
                'empresa_id' => $company->id,
                'nombre' => 'Centro '.$company->id,
                'activo' => true,
                'geolocalizacion' => '19.4326,-99.1332',
                'timezone' => 'America/Mexico_City',
            ]);
            $employee = Personal::query()->create([
                'empresa_id' => $company->id,
                'centro_id' => $center->id,
                'nombre' => 'Colaborador '.$company->id,
                'pin' => $pin,
                'activo' => true,
                'laborados' => (string) $weekday,
            ]);
            $employee->horarios()->create([
                'dia' => $weekday,
                'entrada' => '08:00:00',
                'salida' => '17:00:00',
            ]);
            $employees[$company->id] = $employee;
        }
        $this->travelTo(Carbon::now('America/Mexico_City')->setTime(9, 0));

        $legacyLogin = $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk();
        $legacyToken = $legacyLogin->json('data.token');
        $legacyCompanyId = (int) $legacyLogin->json('data.empresa.id');
        $otherCompanyId = $legacyCompanyId === (int) $firstCompany->id
            ? (int) $secondCompany->id
            : (int) $firstCompany->id;
        $otherPin = $employees[$otherCompanyId]->pin;
        $this->assertDatabaseHas('personal', ['pin' => $otherPin, 'empresa_id' => $otherCompanyId, 'activo' => true]);
        $this->assertSame(1, Personal::query()->where('pin', $otherPin)->where('activo', true)
            ->where('empresa_id', $otherCompanyId)
            ->whereHas('empresa', fn ($query) => $query->where('activa', true))
            ->whereHas('centro', fn ($query) => $query->where('activo', true)->whereColumn('centros.empresa_id', 'personal.empresa_id'))
            ->count());

        $this->withToken($legacyToken)->postJson('/api/app/attendance/verify-pin', ['pin' => $otherPin])
            ->assertUnprocessable()->assertJsonPath('message', 'PIN no reconocido.');

        Auth::forgetGuards();
        $currentLogin = $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'supports_multi_company' => true,
        ])->assertOk();
        $this->assertCount(2, $currentLogin->json('data.empresas'));
        $currentToken = $currentLogin->json('data.token');
        Auth::forgetGuards();
        $verified = $this->withToken($currentToken)
            ->postJson('/api/app/attendance/verify-pin', ['pin' => $otherPin])
            ->assertOk()->assertJsonPath('data.employee', $employees[$otherCompanyId]->nombre);

        $this->mock(AttendancePhotoService::class)
            ->shouldReceive('uploadBase64Jpeg')->once()->andReturn('asistencias/test/legacy-multi-company.jpg');
        $this->withToken($currentToken)->postJson('/api/app/attendance/mark', [
            'pin' => $otherPin,
            'proof' => $verified->json('data.proof'),
            'latitud' => 19.4326,
            'longitud' => -99.1332,
            'foto' => 'data:image/jpeg;base64,ZmFrZQ==',
        ])->assertOk()->assertJsonPath('data.employee', $employees[$otherCompanyId]->nombre);

        $this->assertDatabaseHas('asistencias', [
            'personal_id' => $employees[$otherCompanyId]->id,
        ]);
    }

    public function test_mobile_attendance_uses_the_pin_personal_center_and_records_through_shared_service(): void
    {
        [$user, $company] = $this->administrator();
        $center = Centro::query()->create([
            'empresa_id' => $company->id,
            'nombre' => 'Centro de prueba',
            'activo' => true,
            'geolocalizacion' => '19.4326,-99.1332',
            'timezone' => 'America/Mexico_City',
        ]);
        $personal = Personal::query()->create([
            'empresa_id' => $company->id,
            'centro_id' => $center->id,
            'nombre' => 'Colaborador de prueba',
            'pin' => '12345',
            'activo' => true,
            'laborados' => (string) now('America/Mexico_City')->dayOfWeekIso,
        ]);
        $personal->horarios()->create([
            'dia' => now('America/Mexico_City')->dayOfWeekIso,
            'entrada' => '08:00:00',
            'salida' => '17:00:00',
        ]);
        $this->travelTo(Carbon::now('America/Mexico_City')->setTime(9, 0));

        $token = $this->login($user);
        $verified = $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => '12345']);
        $verified->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'entrada')
            ->assertJsonPath('data.employee', 'Colaborador de prueba');

        $this->mock(AttendancePhotoService::class)
            ->shouldReceive('uploadBase64Jpeg')->once()->andReturn('asistencias/test/entrada.jpg');

        $this->withToken($token)->postJson('/api/app/attendance/mark', [
            'pin' => '12345',
            'proof' => $verified->json('data.proof'),
            'latitud' => 40.7128,
            'longitud' => -74.0060,
            'foto' => 'data:image/jpeg;base64,ZmFrZQ==',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'entrada')
            ->assertJsonPath('data.employee', 'Colaborador de prueba');

        $attendance = Asistencia::query()->where('personal_id', $personal->id)->firstOrFail();
        $this->assertSame($center->id, $attendance->centro_trabajo_entrada_id);
        $this->assertSame('asistencias/test/entrada.jpg', $attendance->foto_entrada);
        $this->assertEqualsWithDelta(40.7128, (float) $attendance->latitud, 0.00001);
        $this->assertEqualsWithDelta(-74.0060, (float) $attendance->longitud, 0.00001);
        $this->assertSame('PRESENCIAL', $attendance->modalidad);
        $this->assertSame('Fuera de rango', $attendance->rango_entrada);
        $this->assertGreaterThan(100000, (float) $attendance->distancia_entrada_metros);

        Auth::forgetGuards();
        $this->withToken($token)->postJson('/api/app/attendance/mark', [
            'pin' => '12345', 'proof' => $verified->json('data.proof'),
            'latitud' => 19.4326, 'longitud' => -99.1332,
            'foto' => 'data:image/jpeg;base64,ZmFrZQ==',
        ])->assertUnprocessable();
    }

    public function test_home_office_keeps_real_gps_and_historical_modality_even_if_schedule_changes(): void
    {
        [$user, $company] = $this->administrator();
        $center = Centro::query()->create([
            'empresa_id' => $company->id, 'nombre' => 'Centro Home Office', 'activo' => true,
            'geolocalizacion' => '19.4326,-99.1332', 'timezone' => 'America/Mexico_City',
        ]);
        $weekday = Carbon::now('America/Mexico_City')->dayOfWeekIso;
        $personal = Personal::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $center->id, 'nombre' => 'Colaborador remoto',
            'pin' => '22222', 'activo' => true, 'laborados' => (string) $weekday,
        ]);
        $schedule = $personal->horarios()->create([
            'dia' => $weekday, 'entrada' => '08:00:00', 'salida' => '17:00:00', 'modalidad' => 'HOME_OFFICE',
        ]);
        $this->travelTo(Carbon::now('America/Mexico_City')->setTime(9, 0));
        $token = $this->login($user);
        $verified = $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => '22222'])->assertOk();
        $this->mock(AttendancePhotoService::class)->shouldReceive('uploadBase64Jpeg')->once()->andReturn('asistencias/test/home-office.jpg');

        $this->withToken($token)->postJson('/api/app/attendance/mark', [
            'pin' => '22222', 'proof' => $verified->json('data.proof'), 'latitud' => 40.7128,
            'longitud' => -74.0060, 'foto' => 'data:image/jpeg;base64,ZmFrZQ==', 'modalidad' => 'PRESENCIAL',
        ])->assertOk();

        $attendance = Asistencia::query()->where('personal_id', $personal->id)->firstOrFail();
        $this->assertSame('HOME_OFFICE', $attendance->modalidad);
        $this->assertSame('En rango', $attendance->rango_entrada);
        $this->assertGreaterThan(100000, (float) $attendance->distancia_entrada_metros);
        $this->assertEqualsWithDelta(40.7128, (float) $attendance->latitud, 0.00001);
        $schedule->update(['modalidad' => 'PRESENCIAL']);

        Carbon::setTestNow(Carbon::now('America/Mexico_City')->setTime(18, 0));
        Auth::forgetGuards();
        $exitVerification = $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => '22222'])->assertOk();
        $this->withToken($token)->postJson('/api/app/attendance/mark', [
            'pin' => '22222', 'proof' => $exitVerification->json('data.proof'), 'latitud' => 40.7128,
            'longitud' => -74.0060,
        ])->assertOk();

        $attendance->refresh();
        $this->assertSame('HOME_OFFICE', $attendance->modalidad);
        $this->assertSame('En rango', $attendance->rango_salida);
        $this->assertGreaterThan(100000, (float) $attendance->distancia_salida_metros);
    }

    public function test_mobile_attendance_accepts_active_centers_without_valid_coordinates(): void
    {
        $this->travelTo('2026-07-06 09:00:00 America/Mexico_City');
        [$user, $company] = $this->administrator();
        $center = Centro::query()->create([
            'empresa_id' => $company->id, 'nombre' => 'Sin GPS', 'activo' => true,
            'geolocalizacion' => 'Constitucion y ramon figueroa',
        ]);
        $personal = Personal::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $center->id, 'nombre' => 'Sin GPS',
            'pin' => '54321', 'activo' => true, 'laborados' => '1',
        ]);
        $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00:00', 'salida' => '17:00:00']);
        $token = $this->login($user);

        $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => '99999'])
            ->assertUnprocessable()->assertJsonPath('message', 'PIN no reconocido.');

        foreach (['entrada', 'salida'] as $kind) {
            Auth::forgetGuards();
            $verified = $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => $personal->pin])
                ->assertOk()->assertJsonPath('data.kind', $kind);

            Auth::forgetGuards();
            $this->withToken($token)->postJson('/api/app/attendance/mark', [
                'pin' => $personal->pin, 'proof' => $verified->json('data.proof'),
                'latitud' => 19.4326, 'longitud' => -99.1332,
            ])->assertOk()->assertJsonPath('data.kind', $kind);
            $center->update(['geolocalizacion' => null]);
        }

        $this->assertDatabaseHas('asistencias', [
            'personal_id' => $personal->id,
            'rango_entrada' => null, 'rango_salida' => null,
            'latitud' => 19.4326, 'longitud' => -99.1332,
            'latitud_salida' => 19.4326, 'longitud_salida' => -99.1332,
            'distancia_entrada_metros' => null, 'distancia_salida_metros' => null,
        ]);
    }

    public function test_mobile_pause_flow_uses_shared_pause_service_and_server_times(): void
    {
        [$user, $company] = $this->administrator();
        $center = Centro::query()->create([
            'empresa_id' => $company->id, 'nombre' => 'Centro de pausas', 'activo' => true,
            'geolocalizacion' => '19.4326,-99.1332', 'timezone' => 'America/Mexico_City',
        ]);
        $weekday = Carbon::now('America/Mexico_City')->dayOfWeekIso;
        $personal = Personal::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $center->id,
            'nombre' => 'Colaborador de pausas', 'pin' => '77777', 'activo' => true, 'laborados' => (string) $weekday,
        ]);
        $personal->horarios()->create(['dia' => $weekday, 'entrada' => '08:00:00', 'salida' => '17:00:00']);
        $this->travelTo(Carbon::now('America/Mexico_City')->setTime(9, 0));
        app(\App\Services\RegistroAsistenciaService::class)->register(
            $personal, '127.0.0.1', ['latitud' => 19.4326, 'longitud' => -99.1332], expectedKind: 'entrada',
            expectedScope: ['empresa_id' => $company->id, 'centro_id' => $center->id],
        );
        $token = $this->login($user);

        $verified = $this->withToken($token)->postJson('/api/app/pauses/verify-pin', ['pin' => '77777']);
        $verified->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee', 'Colaborador de pausas')
            ->assertJsonPath('data.active_pause', null)
            ->assertJsonPath('data.types.sanitario.nombre', 'SANITARIO');

        $started = $this->withToken($token)->postJson('/api/app/pauses/start', [
            'pin' => '77777', 'proof' => $verified->json('data.proof'), 'tipo' => 'sanitario',
        ]);
        $started->assertOk()->assertJsonPath('data.type', 'SANITARIO');
        $this->assertSame(1, AsistenciaPausa::query()->whereNull('fin')->count());

        $this->travel(17)->minutes();
        Auth::forgetGuards();
        $active = $this->withToken($token)->postJson('/api/app/pauses/verify-pin', ['pin' => '77777']);
        $active->assertOk()->assertJsonPath('data.active_pause.type', 'SANITARIO');
        $finished = $this->withToken($token)->postJson('/api/app/pauses/finish', [
            'pin' => '77777', 'proof' => $active->json('data.proof'),
        ]);
        $finished->assertOk()->assertJsonPath('data.duration', '00:17');
        $this->assertSame(0, AsistenciaPausa::query()->whereNull('fin')->count());
    }

    private function administrator(): array
    {
        $company = Empresa::query()->create([
            'rfc' => strtoupper(fake()->unique()->bothify('????######???')),
            'razon_social' => 'Empresa móvil',
            'activa' => true,
        ]);
        $user = User::factory()->create(['profile' => 2, 'password' => Hash::make('correct-password')]);
        $user->empresas()->attach($company->id, ['control_total' => true]);

        return [$user, $company];
    }

    private function login(User $user): string
    {
        $response = $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk();

        return $response->json('data.token');
    }
}
