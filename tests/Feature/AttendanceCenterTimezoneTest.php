<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use App\Services\RegistroAsistenciaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceCenterTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/Mexico_City']);
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_entry_uses_center_local_date_and_saves_the_same_utc_instant(): void
    {
        $personal = $this->employeeInZone('America/Tijuana');
        $personal->update(['laborados' => '7']);
        $personal->horarios()->update(['dia' => 7]);
        $instant = '2026-06-15 06:30:00 UTC';
        $this->travelTo($instant);

        app(RegistroAsistenciaService::class)->register($personal, '127.0.0.1', expectedKind: 'entrada');

        $attendance = DB::table('asistencias')->first();
        $this->assertSame('2026-06-14', substr($attendance->fecha, 0, 10));
        $this->assertSame('2026-06-14 23:30:00', $attendance->llegada);
        $this->assertSame('2026-06-15 06:30:00', $attendance->llegada_utc);
        $this->assertSame($personal->centro_id, $attendance->centro_trabajo_entrada_id);
        $this->assertSame('America/Tijuana', $attendance->timezone_entrada);
        $this->assertNull($attendance->timezone_salida);
    }

    public function test_exit_keeps_entry_values_and_records_the_new_center_timezone(): void
    {
        $personal = $this->employeeInZone('America/Tijuana');
        $registration = app(RegistroAsistenciaService::class);
        $this->travelTo('2026-06-15 15:00:00 UTC');
        $registration->register($personal, '127.0.0.1', expectedKind: 'entrada');
        $entry = Asistencia::query()->firstOrFail();
        $personal->update(['centro_id' => $this->center($personal->empresa_id, 'America/Cancun')->id]);

        $this->travelTo('2026-06-16 00:30:00 UTC');
        $registration->register($personal->fresh(), '127.0.0.1', expectedKind: 'salida');

        $attendance = $entry->fresh();
        $this->assertSame('2026-06-15', $attendance->fecha->toDateString());
        $this->assertSame('2026-06-15 08:00:00', $attendance->getRawOriginal('llegada'));
        $this->assertSame('2026-06-15 15:00:00', $attendance->getRawOriginal('llegada_utc'));
        $this->assertSame('2026-06-15 19:30:00', $attendance->getRawOriginal('salida'));
        $this->assertSame('2026-06-16 00:30:00', $attendance->getRawOriginal('salida_utc'));
        $this->assertSame($entry->centro_trabajo_entrada_id, $attendance->centro_trabajo_entrada_id);
        $this->assertNotSame($attendance->centro_trabajo_entrada_id, $attendance->centro_trabajo_salida_id);
        $this->assertSame('America/Tijuana', $attendance->timezone_entrada);
        $this->assertSame('America/Cancun', $attendance->timezone_salida);
    }

    public function test_changing_timezone_on_same_center_preserves_entry_timezone_and_single_attendance(): void
    {
        $personal = $this->employeeInZone('America/Mexico_City');
        $center = $personal->centro;
        $registration = app(RegistroAsistenciaService::class);
        $this->travelTo('2026-06-15 15:00:00 UTC');
        $registration->register($personal, '127.0.0.1', expectedKind: 'entrada');
        $attendance = Asistencia::query()->firstOrFail();

        $center->update(['timezone' => 'America/Matamoros']);
        $this->travelTo('2026-06-15 23:30:00 UTC');
        $registration->register($personal->fresh(), '127.0.0.1', expectedKind: 'salida');

        $stored = $attendance->fresh();
        $this->assertSame(1, Asistencia::query()->count());
        $this->assertSame('2026-06-15', $stored->fecha->toDateString());
        $this->assertSame($center->id, $stored->centro_trabajo_entrada_id);
        $this->assertSame($center->id, $stored->centro_trabajo_salida_id);
        $this->assertSame('America/Mexico_City', $stored->timezone_entrada);
        $this->assertSame('America/Matamoros', $stored->timezone_salida);
        $this->assertSame('2026-06-15 15:00:00', $stored->getRawOriginal('llegada_utc'));
        $this->assertSame('2026-06-15 23:30:00', $stored->getRawOriginal('salida_utc'));
        $this->assertSame('2026-06-15 09:00:00', $stored->getRawOriginal('llegada'));
        $this->assertSame('2026-06-15 18:30:00', $stored->getRawOriginal('salida'));
    }

    public function test_employee_can_exit_from_tijuana_after_entry_in_mexico_city_without_rewriting_entry(): void
    {
        $personal = $this->employeeInZone('America/Mexico_City');
        $entryCenterId = $personal->centro_id;
        $registration = app(RegistroAsistenciaService::class);
        $this->travelTo('2026-06-15 15:00:00 UTC');
        $registration->register($personal, '127.0.0.1', expectedKind: 'entrada');
        $attendance = Asistencia::query()->firstOrFail();
        $entry = $attendance->getRawOriginal('llegada');
        $personal->update(['centro_id' => $this->center($personal->empresa_id, 'America/Tijuana')->id]);

        $this->travelTo('2026-06-16 02:00:00 UTC');
        $registration->register($personal->fresh(), '127.0.0.1', expectedKind: 'salida');

        $stored = $attendance->fresh();
        $this->assertSame(1, Asistencia::query()->count());
        $this->assertSame('2026-06-15', $stored->fecha->toDateString());
        $this->assertSame($entryCenterId, $stored->centro_trabajo_entrada_id);
        $this->assertSame('America/Mexico_City', $stored->timezone_entrada);
        $this->assertSame('America/Tijuana', $stored->timezone_salida);
        $this->assertSame($entry, $stored->getRawOriginal('llegada'));
        $this->assertSame('2026-06-15 15:00:00', $stored->getRawOriginal('llegada_utc'));
        $this->assertSame('2026-06-16 02:00:00', $stored->getRawOriginal('salida_utc'));
    }

    public function test_repeated_entry_is_rejected_without_creating_partial_or_duplicate_attendance(): void
    {
        $personal = $this->employeeInZone('America/Mexico_City');
        $registration = app(RegistroAsistenciaService::class);
        $this->travelTo('2026-06-15 15:00:00 UTC');
        $registration->register($personal, '127.0.0.1', expectedKind: 'entrada');
        $attendance = Asistencia::query()->firstOrFail();
        $before = $attendance->getRawOriginal();

        try {
            $registration->register($personal->fresh(), '127.0.0.1', expectedKind: 'entrada');
            $this->fail('La entrada duplicada debe rechazarse.');
        } catch (ValidationException) {
            $this->assertSame(1, Asistencia::query()->count());
            $this->assertSame($before, $attendance->fresh()->getRawOriginal());
        }

        $this->travelTo('2026-06-16 01:00:00 UTC');
        $registration->register($personal->fresh(), '127.0.0.1', expectedKind: 'salida');
        $this->assertSame(1, Asistencia::query()->count());
        $this->assertNotNull($attendance->fresh()->salida);
    }

    public function test_exit_after_midnight_finds_same_movement_when_center_timezone_changes_the_local_date(): void
    {
        $personal = $this->employeeInZone('America/Tijuana');
        $personal->update(['laborados' => '7']);
        $personal->horarios()->update(['dia' => 7, 'entrada' => '23:00:00', 'salida' => '23:59:00']);
        $registration = app(RegistroAsistenciaService::class);
        $this->travelTo('2026-06-15 06:30:00 UTC');
        $registration->register($personal, '127.0.0.1', expectedKind: 'entrada');
        $attendance = Asistencia::query()->firstOrFail();
        $personal->update(['centro_id' => $this->center($personal->empresa_id, 'America/Cancun')->id]);

        $this->travelTo('2026-06-15 06:45:00 UTC');
        $this->assertSame('salida', $registration->preview($personal->fresh()));
        $registration->register($personal->fresh(), '127.0.0.1', expectedKind: 'salida');

        $stored = $attendance->fresh();
        $this->assertSame('2026-06-14', $stored->fecha->toDateString());
        $this->assertSame('2026-06-14 23:30:00', $stored->getRawOriginal('llegada'));
        $this->assertSame('2026-06-15 01:45:00', $stored->getRawOriginal('salida'));
        $this->assertSame('America/Tijuana', $stored->timezone_entrada);
        $this->assertSame('America/Cancun', $stored->timezone_salida);
    }

    public function test_migration_backfills_existing_movements_in_batches_without_changing_legacy_values(): void
    {
        $personal = $this->employeeInZone('America/Monterrey');
        $personalWithoutCenter = Personal::query()->create([
            'empresa_id' => $personal->empresa_id,
            'nombre' => 'Sin centro',
            'pin' => '54321',
            'activo' => true,
            'laborados' => '1',
        ]);
        $migration = require database_path('migrations/2026_10_18_120100_add_center_timezone_fields_to_asistencias_table.php');
        $migration->down();

        $timestamps = ['created_at' => '2026-01-02 03:04:05', 'updated_at' => '2026-02-03 04:05:06'];
        DB::table('asistencias')->insert([
            'personal_id' => $personal->id,
            'fecha' => '2026-01-01',
            'llegada' => '2026-01-01 08:00:00',
            'salida' => '2026-01-01 17:00:00',
            'minutos_tarde' => 7,
            'minutos_salida_temprano' => 3,
            ...$timestamps,
        ]);
        DB::table('asistencias')->insert([
            'personal_id' => $personal->id,
            'fecha' => '2026-01-02',
            'llegada' => null,
            'salida' => '2026-01-02 17:00:00',
            ...$timestamps,
        ]);
        DB::table('asistencias')->insert([
            'personal_id' => $personalWithoutCenter->id,
            'fecha' => '2026-01-03',
            'llegada' => '2026-01-03 08:00:00',
            'salida' => null,
            ...$timestamps,
        ]);

        $bulkRows = [];
        for ($index = 0; $index < 501; $index++) {
            $date = date('Y-m-d', strtotime("2026-02-01 +{$index} days"));
            $bulkRows[] = [
                'personal_id' => $personal->id,
                'fecha' => $date,
                'llegada' => $date.' 08:00:00',
                'salida' => null,
                ...$timestamps,
            ];
        }
        foreach (array_chunk($bulkRows, 200) as $rows) {
            DB::table('asistencias')->insert($rows);
        }

        Log::spy();
        $migration->up();

        $withBothMovements = DB::table('asistencias')->where('personal_id', $personal->id)->where('fecha', '2026-01-01')->first();
        $exitOnly = DB::table('asistencias')->where('personal_id', $personal->id)->where('fecha', '2026-01-02')->first();
        $withoutCenter = DB::table('asistencias')->where('personal_id', $personalWithoutCenter->id)->first();
        $this->assertSame($personal->centro_id, $withBothMovements->centro_trabajo_entrada_id);
        $this->assertSame($personal->centro_id, $withBothMovements->centro_trabajo_salida_id);
        $this->assertNull($exitOnly->centro_trabajo_entrada_id);
        $this->assertSame($personal->centro_id, $exitOnly->centro_trabajo_salida_id);
        $this->assertNull($withoutCenter->centro_trabajo_entrada_id);
        $this->assertNull($withoutCenter->centro_trabajo_salida_id);
        $this->assertSame(501, DB::table('asistencias')->whereBetween('fecha', ['2026-02-01', '2027-06-16'])->where('centro_trabajo_entrada_id', $personal->centro_id)->count());

        foreach ([$withBothMovements, $exitOnly, $withoutCenter] as $row) {
            $this->assertNull($row->timezone_entrada);
            $this->assertNull($row->timezone_salida);
            $this->assertNull($row->llegada_utc);
            $this->assertNull($row->salida_utc);
            $this->assertSame($timestamps['created_at'], $row->created_at);
            $this->assertSame($timestamps['updated_at'], $row->updated_at);
        }
        $this->assertSame('2026-01-01', substr($withBothMovements->fecha, 0, 10));
        $this->assertSame('2026-01-01 08:00:00', $withBothMovements->llegada);
        $this->assertSame('2026-01-01 17:00:00', $withBothMovements->salida);
        $this->assertSame('7', (string) $withBothMovements->minutos_tarde);
        $this->assertSame('3', (string) $withBothMovements->minutos_salida_temprano);
        Log::shouldHaveReceived('warning')->once()->with(
            'Algunas asistencias históricas no tienen empleado o centro actual válido; sus referencias quedaron nulas.',
            ['movimientos_sin_centro_valido' => 1],
        );
    }

    public function test_new_centers_default_to_mexico_city_timezone(): void
    {
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $center = Centro::query()->create(['empresa_id' => $company->id, 'nombre' => 'Centro nuevo']);

        $this->assertSame('America/Mexico_City', $center->fresh()->timezone);
    }

    public function test_center_edit_lists_iana_zones_and_explains_historical_scope(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $center = $this->center($company->id, 'America/Tijuana');

        $this->actingAs($administrator)->get(route('centros.edit', $center))
            ->assertOk()
            ->assertSee('value="America/Tijuana" selected', false)
            ->assertSee('Importante:')
            ->assertSee('Los registros anteriores conservarán sus fechas y horas originales.');
    }

    public function test_center_with_attendance_shows_friendly_deletion_alert_and_keeps_foreign_key_guard(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $personal = $this->employeeInZone('America/Mexico_City');
        $administrator->empresas()->attach($personal->empresa_id, ['control_total' => true]);
        $center = $personal->centro;
        Asistencia::query()->create([
            'personal_id' => $personal->id,
            'fecha' => '2026-06-15',
            'llegada' => '2026-06-15 08:00:00',
            'centro_trabajo_entrada_id' => $center->id,
            'timezone_entrada' => 'America/Mexico_City',
        ]);

        $this->followingRedirects()->actingAs($administrator)->delete(route('centros.destroy', $center))
            ->assertOk()
            ->assertSee('No es posible eliminar este centro de trabajo.')
            ->assertSee('Para proteger el historial de asistencia de tus colaboradores, estos registros deben conservarse.')
            ->assertSee('contacto@solventia.com.mx');
        $this->assertDatabaseHas('centros', ['id' => $center->id]);
        $this->assertDatabaseHas('asistencias', ['centro_trabajo_entrada_id' => $center->id]);

        try {
            DB::table('centros')->where('id', $center->id)->delete();
            $this->fail('La FK debe impedir borrar un centro asociado a una asistencia.');
        } catch (QueryException) {
            $this->assertDatabaseHas('centros', ['id' => $center->id]);
        }
    }

    public function test_general_report_and_excel_export_continue_using_stored_local_attendance_times(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $personal = $this->employeeInZone('America/Tijuana');
        $administrator->empresas()->attach($personal->empresa_id, ['control_total' => true]);
        Asistencia::query()->create([
            'personal_id' => $personal->id,
            'fecha' => '2026-06-14',
            'llegada' => '2026-06-14 23:30:00',
            'salida' => '2026-06-15 07:00:00',
            'llegada_utc' => '2026-06-15 06:30:00',
            'salida_utc' => '2026-06-15 14:00:00',
            'centro_trabajo_entrada_id' => $personal->centro_id,
            'centro_trabajo_salida_id' => $personal->centro_id,
            'timezone_entrada' => 'America/Tijuana',
            'timezone_salida' => 'America/Tijuana',
        ]);
        $filters = [
            'empresa_id' => $personal->empresa_id,
            'desde' => '2026-06-14',
            'hasta' => '2026-06-15',
        ];

        $this->actingAs($administrator)
            ->get(route('reportes.general', $filters))
            ->assertOk()
            ->assertSee('23:30')
            ->assertSee('07:00');

        $this->get(route('reportes.asistencia.exportar', $filters))
            ->assertOk()
            ->assertDownload('reporte_asistencia_2026-06-14_2026-06-15.xlsx');
    }

    private function employeeInZone(string $timezone): Personal
    {
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $center = $this->center($company->id, $timezone);
        $personal = Personal::query()->create([
            'empresa_id' => $company->id,
            'centro_id' => $center->id,
            'nombre' => 'Colaborador',
            'pin' => '12345',
            'activo' => true,
            'laborados' => '1',
        ]);
        $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00:00', 'salida' => '17:00:00']);

        return $personal->fresh(['empresa', 'centro']);
    }

    private function center(int $companyId, string $timezone): Centro
    {
        return Centro::query()->create([
            'empresa_id' => $companyId,
            'nombre' => $timezone,
            'activo' => true,
            'geolocalizacion' => '19.4,-99.1',
            'timezone' => $timezone,
        ]);
    }
}
