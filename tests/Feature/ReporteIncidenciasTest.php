<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\Empresa;
use App\Models\FestivoOficial;
use App\Models\Personal;
use App\Models\User;
use App\Models\Vacacion;
use App\Models\VacacionFecha;
use App\Exports\ReporteIncidenciasExport;
use App\Services\ReporteIncidenciasService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteIncidenciasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->travelTo(Carbon::parse('2026-11-08 12:00:00', 'America/Mexico_City'));
    }

    public function test_service_classifies_lateness_pending_exit_and_possible_absence_without_treating_open_shift_as_absence(): void
    {
        [$company, $employee] = $this->employee('1@2@3@4@5');
        foreach (range(1, 5) as $weekday) $employee->horarios()->create(['dia' => $weekday, 'entrada' => '08:00', 'salida' => '17:00']);
        $employee->update(['fecha_baja' => '2026-11-03 17:00:00']);
        Asistencia::query()->create(['personal_id' => $employee->id, 'fecha' => '2026-11-02', 'llegada' => '2026-11-02 08:20:00', 'minutos_tarde' => 10]);

        $rows = app(ReporteIncidenciasService::class)->generate($company->id, '2026-11-02', '2026-11-09', ReporteIncidenciasService::TYPES);
        $dayTwo = $rows->where('fecha', '2026-11-02')->pluck('tipo')->all();
        $this->assertContains('retardos', $dayTwo);
        $this->assertContains('salidas_pendientes', $dayTwo);
        $this->assertNotContains('posibles_faltas', $dayTwo);
        $this->assertContains('posibles_faltas', $rows->where('fecha', '2026-11-03')->pluck('tipo')->all());
        $this->assertNotContains('posibles_faltas', $rows->where('fecha', '2026-11-04')->pluck('tipo')->all());
        $this->assertNotContains('posibles_faltas', $rows->whereIn('fecha', ['2026-11-08', '2026-11-09'])->pluck('tipo')->all());
    }

    public function test_holiday_without_work_obligation_is_not_a_possible_absence_and_work_setting_restores_obligation(): void
    {
        [, $employee] = $this->employee('1');
        $monday = $employee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        FestivoOficial::query()->create(['fecha' => '2026-11-02', 'conmemoracion' => 'Festivo oficial']);
        $service = app(ReporteIncidenciasService::class);
        $types = ['posibles_faltas', 'festivos_trabajados'];
        $this->assertNotContains('posibles_faltas', $service->generate($employee->empresa_id, '2026-11-02', '2026-11-02', $types)->pluck('tipo')->all());
        $monday->update(['trabaja_festivos' => true]);
        $this->assertContains('posibles_faltas', $service->generate($employee->empresa_id, '2026-11-02', '2026-11-02', $types)->pluck('tipo')->all());
    }

    public function test_vacation_and_holiday_work_are_informational_and_only_counted_vacation_dates_are_used(): void
    {
        [$company, $employee] = $this->employee('1@2@3@4@5');
        foreach (range(1, 5) as $weekday) $employee->horarios()->create(['dia' => $weekday, 'entrada' => '08:00', 'salida' => '17:00']);
        $user = User::factory()->create(['profile' => 1]);
        $vacation = Vacacion::query()->create(['empresa_id' => $company->id, 'personal_id' => $employee->id, 'autorizado_por_personal_id' => $employee->id, 'registrado_por_user_id' => $user->id, 'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-03', 'total_dias' => 1, 'estado' => 'registrado']);
        VacacionFecha::query()->create(['vacacion_id' => $vacation->id, 'fecha' => '2026-11-02']);
        Asistencia::query()->create(['personal_id' => $employee->id, 'fecha' => '2026-11-02', 'llegada' => '2026-11-02 08:00:00']);
        FestivoOficial::query()->create(['fecha' => '2026-11-04', 'conmemoracion' => 'Festivo de prueba']);
        Asistencia::query()->create(['personal_id' => $employee->id, 'fecha' => '2026-11-04', 'llegada' => '2026-11-04 08:00:00']);

        $rows = app(ReporteIncidenciasService::class)->generate($company->id, '2026-11-02', '2026-11-04', ReporteIncidenciasService::TYPES);
        $dayTwo = $rows->where('fecha', '2026-11-02')->pluck('tipo')->all();
        $this->assertContains('vacaciones_trabajadas', $dayTwo);
        $this->assertNotContains('vacaciones', $dayTwo);
        $this->assertContains('festivos_trabajados', $rows->where('fecha', '2026-11-04')->pluck('tipo')->all());
    }

    public function test_report_route_requires_authorized_company_and_selected_incidence_type(): void
    {
        [$company] = $this->employee('1');
        $user = User::factory()->create(['profile' => 1]);
        $base = ['empresa_id' => $company->id, 'desde' => '2026-11-02', 'hasta' => '2026-11-03', 'tipos' => ['posibles_faltas']];
        $this->actingAs($user)->get(route('reportes.incidencias', $base))->assertOk()->assertSee('Reporte de Incidencias');
        $this->get(route('reportes.incidencias.exportar', $base))->assertDownload('horalia_incidencias_20261102_20261103.xlsx');
        $this->get(route('reportes.incidencias', array_merge($base, ['empresa_id' => $company->id + 999])))->assertForbidden();
        $this->get(route('reportes.incidencias', ['empresa_id' => $company->id, 'desde' => '2026-11-02', 'hasta' => '2026-11-03']))->assertSessionHasErrors('tipos');
    }

    public function test_report_results_are_paginated_using_items_per_page_and_keep_filters_on_next_page(): void
    {
        [$company, $employee] = $this->employee('1@2@3@4@5');
        foreach (range(1, 5) as $weekday) $employee->horarios()->create(['dia' => $weekday, 'entrada' => '08:00', 'salida' => '17:00']);
        $filters = ['empresa_id' => $company->id, 'desde' => '2026-01-01', 'hasta' => '2026-11-07', 'tipos' => ['posibles_faltas']];

        $user = User::factory()->create(['profile' => 1]);
        $perPage = (int) config('constantes.itemsPorPagina');
        $total = 0;
        for ($date = Carbon::parse($filters['desde']); $date->lte(Carbon::parse($filters['hasta'])); $date->addDay()) {
            if ($date->isWeekday()) $total++;
        }
        $lastPage = (int) ceil($total / $perPage);
        $response = $this->actingAs($user)->get(route('reportes.incidencias', $filters + ['page' => 2]));
        $response->assertOk()->assertSee('Página 2 de '.$lastPage)->assertSee('Mostrando '.($perPage + 1).'–'.min($perPage * 2, $total).' de '.$total)
            ->assertSee('tipos%5B0%5D=posibles_faltas', false);
        $this->get(route('reportes.incidencias.exportar', $filters + ['page' => 3]))
            ->assertDownload('horalia_incidencias_20260101_20261107.xlsx');
    }

    public function test_incidents_workbook_uses_the_attendance_report_style_layout(): void
    {
        [$company] = $this->employee('1');
        $path = app(ReporteIncidenciasExport::class)->generate([], $company, '2026-11-01', '2026-11-07', ['retardos', 'posibles_faltas']);
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path));
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $styles = $zip->getFromName('xl/styles.xml');
            $this->assertStringContainsString('REPORTE DE INCIDENCIAS', $sheet);
            $this->assertStringContainsString('Periodo: 2026-11-01 al 2026-11-07', $sheet);
            $this->assertStringContainsString('ySplit="10"', $sheet);
            $this->assertStringContainsString('autoFilter', $sheet);
            $this->assertStringContainsString('FF536DF5', $styles);
            $this->assertStringContainsString('FFEF4444', $styles);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    private function employee(string $workdays): array
    {
        $company = Empresa::query()->create(['rfc' => 'HORA010101AAA', 'razon_social' => 'Empresa Horalia', 'direccion' => 'Calle de prueba', 'telefono' => '5500000000', 'activa' => true]);
        $center = Centro::query()->create(['empresa_id' => $company->id, 'nombre' => 'Centro de prueba', 'activo' => true, 'geolocalizacion' => '19.4326,-99.1332', 'timezone' => 'America/Mexico_City']);
        $employee = Personal::query()->create(['empresa_id' => $company->id, 'centro_id' => $center->id, 'nombre' => 'Colaborador', 'pin' => (string) random_int(10000, 99999), 'activo' => true, 'laborados' => $workdays]);
        return [$company, $employee];
    }
}
