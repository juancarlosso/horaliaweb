<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\ChecadorSesion;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use App\Services\AsistenciaPausaService;
use App\Services\RegistroAsistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use ZipArchive;
use Tests\TestCase;

class AsistenciaPausasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/Mexico_City']);
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_employee_can_start_and_finish_sequential_pauses_for_the_current_attendance(): void
    {
        $personal = $this->employee();
        $this->travelTo('2026-10-19 15:00:00 UTC');
        $attendance = $this->openAttendance($personal, '2026-10-19');
        $service = app(AsistenciaPausaService::class);

        $first = $service->start($personal, 'comida');
        $this->assertSame('2026-10-19 09:00:00', $first->getRawOriginal('inicio'));
        $this->assertSame('2026-10-19 15:00:00', $first->getRawOriginal('inicio_utc'));
        $this->assertSame($attendance->id, $first->asistencia_id);

        try {
            $service->start($personal, 'sanitario');
            $this->fail('No se debe permitir iniciar dos pausas simultáneas.');
        } catch (ValidationException) {
            $this->assertSame(1, $attendance->pausas()->count());
        }

        try {
            app(RegistroAsistenciaService::class)->register($personal, '127.0.0.1', expectedKind: 'salida');
            $this->fail('No se debe permitir salir con una pausa abierta.');
        } catch (ValidationException) {
            $this->assertNull($attendance->fresh()->salida);
        }

        $this->travelTo('2026-10-19 15:30:00 UTC');
        $service->finish($personal);
        $this->assertSame('2026-10-19 09:30:00', $first->fresh()->getRawOriginal('fin'));
        $second = $service->start($personal, 'sanitario');
        $this->assertSame('sanitario', $second->tipo);
        $this->assertSame(2, $attendance->pausas()->count());
    }

    public function test_previous_day_non_overnight_open_attendance_does_not_become_current_pause_jornada(): void
    {
        $personal = $this->employee();
        $this->travelTo('2026-10-20 15:00:00 UTC');
        $old = $this->openAttendance($personal, '2026-10-19');
        $service = app(AsistenciaPausaService::class);

        $this->assertNull($service->currentAttendance($personal));
        try {
            $service->start($personal, 'comida');
            $this->fail('Una asistencia anterior no debe aceptar pausas nuevas.');
        } catch (ValidationException) {
            $this->assertSame(0, $old->pausas()->count());
        }
    }

    public function test_employee_pause_page_shows_only_current_employee_and_current_attendance(): void
    {
        $personal = $this->employee();
        $user = User::factory()->create(['profile' => 3, 'personal_id' => $personal->id]);
        $this->travelTo('2026-10-19 15:00:00 UTC');
        $attendance = $this->openAttendance($personal, '2026-10-19');
        app(AsistenciaPausaService::class)->start($personal, 'comida');

        $this->actingAs($user)->get(route('pausas.index'))
            ->assertOk()
            ->assertSee('Pausas')
            ->assertSee('COMIDA')
            ->assertSee('FINALIZAR')
            ->assertSee('En curso desde')
            ->assertSee('Historial de pausas del turno actual');

        $otherEmployee = $this->employee('Otro colaborador');
        $this->assertNotSame($personal->id, $otherEmployee->id);
        $this->assertSame(1, $attendance->pausas()->count());

        $manager = User::factory()->create(['profile' => 2]);
        $this->actingAs($manager)->get(route('home'))->assertOk()->assertDontSee('href="'.route('pausas.index').'"', false);
    }

    public function test_profile_two_with_personal_link_can_use_own_pause_page_and_profile_two_without_link_cannot(): void
    {
        $personal = $this->employee();
        $this->travelTo('2026-10-19 15:00:00 UTC');
        $this->openAttendance($personal, '2026-10-19');

        $managerEmployee = User::factory()->create(['profile' => 2, 'personal_id' => $personal->id]);
        $this->actingAs($managerEmployee)->get(route('pausas.index'))
            ->assertOk()
            ->assertSee('COMIDA')
            ->assertSee('SANITARIO')
            ->assertSee('DESCANSO')
            ->assertSee('PERMISO');

        $managerWithoutPersonal = User::factory()->create(['profile' => 2, 'personal_id' => null]);
        $this->actingAs($managerWithoutPersonal)->get(route('pausas.index'))->assertForbidden();
        $this->actingAs($managerWithoutPersonal)->get(route('home'))
            ->assertOk()->assertDontSee('href="'.route('pausas.index').'"', false);
    }

    public function test_web_rejects_unknown_pause_types_and_ignores_a_tampered_attendance_id(): void
    {
        $personal = $this->employee();
        $other = $this->employee('Otra persona');
        $user = User::factory()->create(['profile' => 3, 'personal_id' => $personal->id]);
        $this->travelTo('2026-10-19 15:00:00 UTC');
        $ownAttendance = $this->openAttendance($personal, '2026-10-19');
        $otherAttendance = $this->openAttendance($other, '2026-10-19');

        $this->actingAs($user)->from(route('pausas.index'))
            ->post(route('pausas.start'), ['tipo' => 'INVENTADA', 'asistencia_id' => $otherAttendance->id])
            ->assertSessionHasErrors('tipo');
        $this->assertSame(0, $ownAttendance->pausas()->count());
        $this->assertSame(0, $otherAttendance->pausas()->count());

        $this->actingAs($user)->post(route('pausas.start'), [
            'tipo' => 'comida', 'asistencia_id' => $otherAttendance->id, 'personal_id' => $other->id,
        ])->assertRedirect(route('pausas.index'));
        $this->assertSame(1, $ownAttendance->pausas()->count());
        $this->assertSame(0, $otherAttendance->pausas()->count());
    }

    public function test_finishing_pause_before_its_start_is_rejected_without_negative_duration(): void
    {
        $personal = $this->employee();
        $this->travelTo('2026-10-19 15:00:00 UTC');
        $attendance = $this->openAttendance($personal, '2026-10-19');
        $pause = app(AsistenciaPausaService::class)->start($personal, 'comida');

        $this->travelTo('2026-10-19 14:59:00 UTC');
        try {
            app(AsistenciaPausaService::class)->finish($personal);
            $this->fail('No se debe guardar un fin anterior al inicio.');
        } catch (ValidationException) {
            $this->assertNull($pause->fresh()->fin_utc);
            $this->assertSame(1, $attendance->pausas()->whereNull('fin')->count());
        }
    }

    public function test_checador_pause_flow_uses_pin_proof_and_does_not_require_a_new_photo(): void
    {
        $personal = $this->employee();
        $admin = User::factory()->create(['profile' => 2]);
        $company = $personal->empresa;
        $token = str_repeat('t', 64);
        $session = ChecadorSesion::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $personal->centro_id, 'user_id' => $admin->id,
            'token' => $token, 'token_hash' => hash('sha256', $token),
            'activated_at' => now(), 'expires_at' => now()->addDay(), 'duracion_dias' => 1,
        ]);
        $this->travelTo('2026-10-19 15:00:00 UTC');
        $this->openAttendance($personal, '2026-10-19');
        $headers = ['X-Checador-Token' => $token];

        $verified = $this->withHeaders($headers)->postJson(route('checador.pauses.verify-pin'), ['pin' => $personal->pin]);
        $verified->assertOk()->assertJsonPath('employee', $personal->nombre)->assertJsonPath('active_pause', null);
        $proof = $verified->json('proof');

        $this->withHeaders($headers)->postJson(route('checador.pauses.start'), [
            'pin' => $personal->pin, 'proof' => $proof, 'tipo' => 'sanitario',
        ])->assertOk()->assertJsonPath('type', 'SANITARIO');
        $this->assertDatabaseHas('asistencia_pausas', ['asistencia_id' => 1, 'tipo' => 'sanitario', 'fin' => null]);

        $active = $this->withHeaders($headers)->postJson(route('checador.pauses.verify-pin'), ['pin' => $personal->pin]);
        $active->assertOk()->assertJsonPath('active_pause.tipo', 'SANITARIO');
        $this->withHeaders($headers)->postJson(route('checador.pauses.finish'), [
            'pin' => $personal->pin, 'proof' => $active->json('proof'),
        ])->assertOk()->assertJsonPath('message', 'Pausa de SANITARIO finalizada correctamente.');

        $this->assertDatabaseHas('checador_sesiones', ['id' => $session->id]);
        $this->assertDatabaseHas('asistencia_pausas', ['asistencia_id' => 1, 'tipo' => 'sanitario', 'fin' => '2026-10-19 09:00:00']);
    }

    public function test_report_calculates_pause_and_productive_time_and_modal_obeys_company_scope(): void
    {
        $personal = $this->employee();
        $admin = User::factory()->create(['profile' => 2]);
        $admin->empresas()->attach($personal->empresa_id, ['control_total' => true]);
        $attendance = Asistencia::query()->create([
            'personal_id' => $personal->id, 'fecha' => '2026-10-19',
            'llegada' => '2026-10-19 08:00:00', 'salida' => '2026-10-19 17:00:00',
            'llegada_utc' => '2026-10-19 14:00:00', 'salida_utc' => '2026-10-19 23:00:00',
        ]);
        $attendance->pausas()->create([
            'tipo' => 'comida', 'inicio' => '2026-10-19 11:00:00', 'fin' => '2026-10-19 11:30:00',
            'inicio_utc' => '2026-10-19 17:00:00', 'fin_utc' => '2026-10-19 17:30:00',
        ]);
        $attendance->pausas()->create([
            'tipo' => 'sanitario', 'inicio' => '2026-10-19 12:00:00', 'inicio_utc' => '2026-10-19 18:00:00',
        ]);
        $filters = ['empresa_id' => $personal->empresa_id, 'desde' => '2026-10-19', 'hasta' => '2026-10-20'];

        $reportResponse = $this->actingAs($admin)->get(route('reportes.general', $filters));
        $reportResponse
            ->assertOk()->assertSee('Indeterminado')->assertSee('Ver pausas');
        $detailRoute = route('reportes.asistencia.pausas', ['asistencia' => $attendance->id, ...$filters]);
        $this->get($detailRoute)
            ->assertOk()->assertJsonPath('pausas.0.tipo', 'SANITARIO')->assertJsonPath('pausas.0.duracion', '—')
            ->assertJsonPath('pausas.1.tipo', 'COMIDA')->assertJsonPath('pausas.1.duracion', '00:30');

        $export = $this->get(route('reportes.asistencia.exportar', $filters))->assertOk();
        $xlsx = new ZipArchive();
        $this->assertTrue($xlsx->open($export->baseResponse->getFile()->getPathname()));
        $this->assertStringContainsString('Tiempo de pausas', $xlsx->getFromName('xl/worksheets/sheet1.xml'));
        $this->assertStringContainsString('Pausas', $xlsx->getFromName('xl/workbook.xml'));
        $this->assertStringContainsString('COMIDA', $xlsx->getFromName('xl/worksheets/sheet2.xml'));
        $this->assertStringNotContainsString('Indeterminado', $xlsx->getFromName('xl/worksheets/sheet2.xml'));
        $xlsx->close();

        $attendance->pausas()->where('tipo', 'sanitario')->delete();
        $attendance->refresh()->load('pausas');
        $this->assertSame('00:30', Asistencia::formatoDuracion($attendance->tiempoPausasMinutos()));
        $this->assertSame('08:30', Asistencia::formatoDuracion($attendance->tiempoProductivoMinutos()));

        $otherAdmin = User::factory()->create(['profile' => 2]);
        $this->actingAs($otherAdmin)->get($detailRoute)->assertForbidden();
    }

    private function employee(string $name = 'Colaborador'): Personal
    {
        $company = Empresa::query()->create(['rfc' => 'AAA01010'.str_pad((string) (Empresa::query()->count() + 1), 3, '0', STR_PAD_LEFT), 'razon_social' => 'Empresa de prueba']);
        $center = Centro::query()->create(['empresa_id' => $company->id, 'nombre' => 'Centro de prueba', 'activo' => 1, 'timezone' => 'America/Mexico_City']);

        $personal = Personal::query()->create([
            'empresa_id' => $company->id, 'centro_id' => $center->id, 'nombre' => $name,
            'pin' => str_pad((string) (Personal::query()->count() + 1), 5, '0', STR_PAD_LEFT), 'activo' => true, 'laborados' => '1',
        ]);
        return $personal->fresh(['empresa', 'centro']);
    }

    private function openAttendance(Personal $personal, string $date): Asistencia
    {
        return Asistencia::query()->create([
            'personal_id' => $personal->id, 'fecha' => $date,
            'llegada' => $date.' 08:00:00', 'llegada_utc' => $date.' 15:00:00',
            'centro_trabajo_entrada_id' => $personal->centro_id,
            'timezone_entrada' => 'America/Mexico_City',
        ]);
    }
}
