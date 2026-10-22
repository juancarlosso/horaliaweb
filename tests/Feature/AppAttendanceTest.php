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
            'latitud' => 19.4326,
            'longitud' => -99.1332,
            'foto' => 'data:image/jpeg;base64,ZmFrZQ==',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kind', 'entrada')
            ->assertJsonPath('data.employee', 'Colaborador de prueba');

        $attendance = Asistencia::query()->where('personal_id', $personal->id)->firstOrFail();
        $this->assertSame($center->id, $attendance->centro_trabajo_entrada_id);
        $this->assertSame('asistencias/test/entrada.jpg', $attendance->foto_entrada);
        $this->assertEqualsWithDelta(19.4326, (float) $attendance->latitud, 0.00001);
        $this->assertEqualsWithDelta(-99.1332, (float) $attendance->longitud, 0.00001);

        Auth::forgetGuards();
        $this->withToken($token)->postJson('/api/app/attendance/mark', [
            'pin' => '12345', 'proof' => $verified->json('data.proof'),
            'latitud' => 19.4326, 'longitud' => -99.1332,
            'foto' => 'data:image/jpeg;base64,ZmFrZQ==',
        ])->assertUnprocessable();
    }

    public function test_mobile_attendance_rejects_unknown_pin_and_personnel_without_a_valid_center(): void
    {
        [$user, $company] = $this->administrator();
        $center = Centro::query()->create(['empresa_id' => $company->id, 'nombre' => 'Sin GPS', 'activo' => true]);
        Personal::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $center->id, 'nombre' => 'Sin GPS',
            'pin' => '54321', 'activo' => true, 'laborados' => '1@2@3@4@5@6@7',
        ]);
        $token = $this->login($user);

        $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => '99999'])
            ->assertUnprocessable()->assertJsonPath('message', 'PIN no reconocido.');
        Auth::forgetGuards();
        $this->withToken($token)->postJson('/api/app/attendance/verify-pin', ['pin' => '54321'])
            ->assertUnprocessable()->assertJsonPath('message', 'El centro de trabajo del colaborador no tiene geolocalización configurada. Contacta a tu administrador.');
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
