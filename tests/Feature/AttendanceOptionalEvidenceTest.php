<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\ChecadorSesion;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use App\Services\AttendancePhotoService;
use App\Services\RegistroAsistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceOptionalEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_entry_can_be_registered_without_camera_or_geolocation(): void
    {
        [$personal, $user] = $this->employee();
        $this->travelTo('2026-07-06 09:00:00 America/Mexico_City');

        $this->actingAs($user)->post(route('asistencia.entrada'))
            ->assertRedirect();

        $attendance = Asistencia::query()->where('personal_id', $personal->id)->firstOrFail();
        $this->assertNull($attendance->latitud);
        $this->assertNull($attendance->longitud);
        $this->assertNull($attendance->foto_entrada);
        $this->assertSame('Sin geolocalización', $attendance->rango_entrada);
    }

    public function test_exit_can_be_registered_without_camera_or_geolocation(): void
    {
        [$personal, $user] = $this->employee();
        $this->travelTo('2026-07-06 09:00:00 America/Mexico_City');
        app(RegistroAsistenciaService::class)->register($personal, '127.0.0.1', expectedKind: 'entrada');
        $this->travelTo('2026-07-06 18:00:00 America/Mexico_City');

        $this->actingAs($user)->post(route('asistencia.salida'))
            ->assertRedirect();

        $attendance = Asistencia::query()->where('personal_id', $personal->id)->firstOrFail();
        $this->assertNull($attendance->latitud_salida);
        $this->assertNull($attendance->longitud_salida);
        $this->assertNull($attendance->foto_salida);
        $this->assertSame('Sin geolocalización', $attendance->rango_salida);
    }

    public function test_web_kiosk_records_device_location_when_center_has_a_street_address(): void
    {
        [$personal, $user] = $this->employee();
        $this->travelTo('2026-07-06 09:00:00 America/Mexico_City');
        $personal->centro->update(['geolocalizacion' => 'Constitucion y ramon figueroa']);
        $token = str_repeat('t', 64);
        ChecadorSesion::query()->create([
            'empresa_id' => $personal->empresa_id, 'centro_id' => $personal->centro_id, 'user_id' => $user->id,
            'token' => $token, 'token_hash' => hash('sha256', $token), 'duracion_dias' => 1,
            'activated_at' => now(), 'expires_at' => now()->addDay(),
        ]);
        $this->withHeaders(['X-Checador-Token' => $token]);
        $payload = ['pin' => $personal->pin, 'foto' => 'data:image/jpeg;base64,ZmFrZQ=='];
        $this->mock(AttendancePhotoService::class)->shouldReceive('uploadBase64Jpeg')
            ->twice()->andReturn('asistencias/test/entrada.jpg', 'asistencias/test/salida.jpg');

        foreach (['entrada', 'salida'] as $kind) {
            $verified = $this->postJson(route('checador.verify-pin'), ['pin' => $personal->pin])
                ->assertOk()->assertJsonPath('kind', $kind);
            $mark = $payload + ['proof' => $verified->json('proof')];
            $this->postJson(route('checador.mark'), $mark)
                ->assertUnprocessable()->assertJsonValidationErrors(['latitud', 'longitud']);
            $this->postJson(route('checador.mark'), $mark + ['latitud' => 19.4326, 'longitud' => -99.1332])
                ->assertOk()->assertJsonPath('kind', $kind);
        }

        $this->assertDatabaseHas('asistencias', [
            'personal_id' => $personal->id,
            'rango_entrada' => null, 'rango_salida' => null,
            'latitud' => 19.4326, 'longitud' => -99.1332,
            'latitud_salida' => 19.4326, 'longitud_salida' => -99.1332,
            'distancia_entrada_metros' => null, 'distancia_salida_metros' => null,
            'foto_entrada' => 'asistencias/test/entrada.jpg', 'foto_salida' => 'asistencias/test/salida.jpg',
        ]);
    }

    private function employee(): array
    {
        $company = Empresa::query()->create([
            'rfc' => 'OPT010101AAA', 'razon_social' => 'Empresa de prueba',
            'direccion' => 'Calle de prueba 1', 'telefono' => '5500000000', 'activa' => true,
        ]);
        $center = Centro::query()->create([
            'empresa_id' => $company->id, 'nombre' => 'Centro de prueba', 'activo' => true,
            'geolocalizacion' => '19.4326,-99.1332', 'timezone' => 'America/Mexico_City',
        ]);
        $personal = Personal::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $center->id, 'nombre' => 'Colaborador',
            'pin' => '12345', 'activo' => true, 'laborados' => '1',
        ]);
        $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00:00', 'salida' => '17:00:00']);
        $user = User::factory()->create(['profile' => 3, 'personal_id' => $personal->id]);
        $user->empresas()->attach($company->id, ['control_total' => false]);

        return [$personal, $user];
    }
}
