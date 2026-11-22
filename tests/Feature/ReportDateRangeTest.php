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
}
