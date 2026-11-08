<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
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
