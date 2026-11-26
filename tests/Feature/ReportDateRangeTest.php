<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_general_department_and_incidence_reports_reject_more_than_ninety_days(): void
    {
        $this->travelTo('2026-04-02 18:00:00 UTC');
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $this->actingAs(User::factory()->create(['profile' => 1]));
        $filters = [
            'empresa_id' => $company->id,
            'desde' => '2026-01-01',
            'hasta' => '2026-04-01',
        ];

        foreach ([
            'reportes.general' => [],
            'reportes.asistencia.exportar' => [],
            'reportes.departamento' => [],
            'reportes.departamento.detalle' => ['tipo' => 'total'],
            'reportes.incidencias' => ['tipos' => ['retardos']],
            'reportes.incidencias.exportar' => ['tipos' => ['retardos']],
        ] as $route => $extras) {
            $this->getJson(route($route, $filters + $extras))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('hasta')
                ->assertJsonPath('errors.hasta.0', 'Selecciona un periodo de hasta 90 días, incluyendo la fecha inicial y final.');
        }

        $filters['hasta'] = '2026-03-31';
        $employee = Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Colaborador del último día']);
        Asistencia::query()->create(['empresa_id' => $company->id, 'personal_id' => $employee->id, 'fecha' => $filters['hasta']]);
        $this->get(route('reportes.general', $filters))->assertOk()->assertSee($employee->nombre);
        $this->get(route('reportes.departamento', $filters))->assertOk();
        $this->get(route('reportes.incidencias', $filters + ['tipos' => ['retardos']]))->assertOk();
        $this->get(route('reportes.asistencia.exportar', $filters))
            ->assertDownload('reporte_asistencia_2026-01-01_2026-03-31.xlsx');
    }

    public function test_every_report_stops_at_yesterday_in_the_application_timezone(): void
    {
        config(['app.timezone' => 'America/Mexico_City']);
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $this->actingAs(User::factory()->create(['profile' => 1]));
        $forms = ['reportes.general', 'reportes.incidencias', 'reportes.departamento', 'reportes.horas-trabajadas.index'];

        $this->travelTo('2026-12-01 00:15:00 UTC');
        foreach ($forms as $form) {
            $this->get(route($form))->assertOk()->assertSee('name="hasta" type="date" max="2026-11-29" value="2026-11-29"', false);
        }

        $this->travelTo('2026-12-01 06:15:00 UTC');
        foreach ($forms as $form) {
            $this->get(route($form))->assertOk()
                ->assertSee('name="desde" type="date" max="2026-11-30" value="2026-11-01"', false)
                ->assertSee('name="hasta" type="date" max="2026-11-30" value="2026-11-30"', false);
        }

        $filters = ['empresa_id' => $company->id, 'desde' => '2026-11-30', 'hasta' => '2026-12-01', 'tipos' => ['retardos']];
        $stores = [
            route('reportes.horas-trabajadas.store'),
            route('reportes.generados.store', 'general'),
            route('reportes.generados.store', 'incidencias'),
            route('reportes.generados.store', 'departamento'),
        ];

        foreach ($stores as $store) {
            $this->postJson($store, $filters)->assertUnprocessable()
                ->assertJsonPath('errors.hasta.0', 'La fecha final del reporte debe ser de ayer o anterior.');
        }
        $this->assertDatabaseCount('jobs', 0);

        foreach (['reportes.asistencia.exportar', 'reportes.incidencias.exportar', 'reportes.departamento'] as $legacyRoute) {
            $this->getJson(route($legacyRoute, array_replace($filters, ['hasta' => '2026-12-02'])))
                ->assertUnprocessable()->assertJsonValidationErrors('hasta');
        }

        foreach ($stores as $store) {
            $this->post($store, array_replace($filters, ['hasta' => '2026-11-30']))->assertRedirect();
        }
        $this->assertDatabaseCount('jobs', 4);
    }
}
