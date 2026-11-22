<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDataTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_search_filters_the_full_period_before_pagination(): void
    {
        config(['constantes.itemsPorPagina' => 1]);
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa']);
        $external = Empresa::query()->create(['rfc' => 'BBB010101BBB', 'razon_social' => 'Empresa externa']);
        $admin = User::factory()->create(['profile' => 2]);
        $admin->empresas()->attach($company, ['control_total' => true]);
        foreach ([
            [$company, 'Ana Alcázar', 'ANA-01', '2026-01-02'],
            [$company, 'Juan Cabrera', 'JUAN-01', '2026-01-01'],
            [$external, 'Juan Externo', 'JUAN-02', '2026-01-01'],
            [$company, 'Juan Fuera de periodo', 'JUAN-03', '2026-01-03'],
        ] as [$owner, $name, $code, $day]) {
            $employee = Personal::query()->create(['empresa_id' => $owner->id, 'nombre' => $name, 'codigo' => $code]);
            Asistencia::query()->create(['empresa_id' => $owner->id, 'personal_id' => $employee->id, 'fecha' => $day, 'llegada' => $day.' 08:00:00']);
        }

        $filters = ['empresa_id' => $company->id, 'desde' => '2026-01-01', 'hasta' => '2026-01-02'];
        $fragment = ['X-Datatable-Fragment' => '1'];

        $this->actingAs($admin)->get(route('reportes.general', $filters), $fragment)
            ->assertOk()->assertSee('Ana Alcázar')->assertDontSee('Juan Cabrera');

        foreach ([
            'reportes.general' => ['q' => 'JUAN-01'],
            'reportes.incidencias' => ['q' => 'juan cab', 'tipos' => ['salidas_pendientes']],
        ] as $route => $extra) {
            $this->get(route($route, $filters + $extra), $fragment)
                ->assertOk()
                ->assertSee('data-table-total="2" data-table-filtered="1"', false)
                ->assertSee('Juan Cabrera')
                ->assertDontSee('Ana Alcázar')
                ->assertDontSee('Juan Externo')
                ->assertDontSee('Juan Fuera de periodo');
        }
    }
}
