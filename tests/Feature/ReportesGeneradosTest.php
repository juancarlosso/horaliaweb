<?php

namespace Tests\Feature;

use App\Exports\AsistenciaReporteExport;
use App\Exports\ReporteDepartamentoExport;
use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\ReporteGenerado;
use App\Models\ReporteHorasTrabajadas;
use App\Models\User;
use App\Services\ReporteAsistenciasSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ReportesGeneradosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_each_report_type_has_its_own_history_and_publishes_queued_results(): void
    {
        $disk = Storage::fake('reportes', config('filesystems.disks.reportes'));
        $company = Empresa::create(['rfc' => 'AAA010101AAA', 'nombre' => 'Empresa de prueba', 'razon_social' => 'Empresa de prueba']);
        $user = User::factory()->create(['profile' => 2]);
        $user->empresas()->attach($company, ['control_total' => true]);
        $this->actingAs($user);
        $this->travelTo(now()->setDate(2026, 11, 26)->setTime(20, 0));
        config(['constantes.itemsPorPagina' => 1]);

        $department = Departamento::create(['empresa_id' => $company->id, 'nombre' => 'Operación']);
        Departamento::create(['empresa_id' => $company->id, 'nombre' => 'Ventas']);
        $employee = Personal::create(['empresa_id' => $company->id, 'departamento_id' => $department->id, 'nombre' => 'Ana Pérez', 'codigo' => 'ANA-01']);
        $other = Personal::create(['empresa_id' => $company->id, 'nombre' => 'Juan López']);
        $shift = Asistencia::create([
            'empresa_id' => $company->id, 'personal_id' => $employee->id, 'fecha' => '2026-11-03',
            'llegada' => '2026-11-03 08:10:00', 'salida' => '2026-11-03 16:55:00',
            'minutos_tarde' => 10, 'minutos_salida_temprano' => 5, 'rango_entrada' => 'Fuera de rango',
        ]);
        $shift->pausas()->create([
            'tipo' => 'comida', 'inicio' => '2026-11-03 13:00:00', 'fin' => '2026-11-03 13:30:00',
            'inicio_utc' => '2026-11-03 19:00:00', 'fin_utc' => '2026-11-03 19:30:00',
        ]);
        $openShift = Asistencia::create([
            'empresa_id' => $company->id, 'personal_id' => $other->id,
            'fecha' => '2026-11-04', 'llegada' => '2026-11-04 09:00:00',
        ]);
        $filters = ['empresa_id' => $company->id, 'desde' => '2026-11-01', 'hasta' => '2026-11-04'];

        $hours = ReporteHorasTrabajadas::create($filters + ['user_id' => $user->id, 'estado' => ReporteGenerado::PENDIENTE]);
        $reports = [];
        foreach ([
            ReporteGenerado::GENERAL => ['form' => 'reportes.general'],
            ReporteGenerado::INCIDENCIAS => ['form' => 'reportes.incidencias', 'tipos' => ['salidas_pendientes']],
            ReporteGenerado::DEPARTAMENTO => ['form' => 'reportes.departamento'],
        ] as $type => $options) {
            $store = route('reportes.generados.store', $type);
            $this->get(route($options['form']))->assertOk()->assertSee('method="POST" action="'.$store.'"', false);
            $this->post($store, $filters + $options)->assertRedirect();
            $report = ReporteGenerado::where('tipo', $type)->sole();
            $this->get(route('reportes.generados.show', $report))
                ->assertOk()->assertSee('Reporte en proceso')->assertDontSee('Exportar a Excel');
            $this->get(route('reportes.generados.download', $report))->assertNotFound();
            $reports[$type] = $report;
        }

        $this->assertDatabaseCount('jobs', 3);
        foreach ($reports as $report) {
            $this->artisan('queue:work', ['connection' => 'reportes', '--once' => true])->assertSuccessful();
        }

        $employee->update(['nombre' => 'Nombre modificado']);
        $department->update(['nombre' => 'Departamento modificado']);
        $shift->update(['minutos_tarde' => 0]);
        $openShift->update(['salida' => '2026-11-04 17:00:00']);

        $this->get('/home/reportes/generados')->assertNotFound();
        foreach ($reports as $report) {
            $this->assertSame(ReporteGenerado::LISTO, $report->refresh()->estado);
            $disk->assertExists($report->archivo());
            $this->assertSame(0640, fileperms($disk->path($report->archivo())) & 0777);
            $download = $this->get(route('reportes.generados.download', $report))->assertDownload($report->nombreArchivo());
            $this->assertSame($disk->get($report->archivo()), $download->streamedContent());
        }
        $this->get(route('reportes.horas-trabajadas.index'))->assertOk()
            ->assertSee(route('reportes.horas-trabajadas.show', $hours))
            ->assertDontSee('data-sidebar-tooltip="Mis reportes"', false)
            ->assertSee('badge-st st-pending')
            ->assertViewHas('reports', fn ($history) => $history->pluck('id')->all() === [$hours->id]);
        $this->get(route('reportes.horas-trabajadas.show', $hours))->assertOk()
            ->assertSee('href="'.route('reportes.horas-trabajadas.index').'#historial-reportes"', false);
        foreach ($reports as $type => $report) {
            $this->get(route('reportes.'.$type))->assertOk()
                ->assertSee(route('reportes.generados.show', $report))
                ->assertViewHas('reports', fn ($history) => $history->pluck('id')->all() === [$report->id]);
            $this->get(route('reportes.generados.show', $report))->assertOk()
                ->assertSee('href="'.route('reportes.'.$type).'#historial-reportes"', false)
                ->assertSee('Volver al listado');
        }

        $general = $reports[ReporteGenerado::GENERAL];
        $url = route('reportes.generados.show', $general);
        $this->get($url)->assertOk()->assertSee('Juan López')->assertSee('Siguiente')->assertSee('Exportar a Excel');
        $this->get($url.'?q=ANA-01&sort=retardo&direction=desc', ['X-Datatable-Fragment' => '1'])->assertOk()
            ->assertSee('Ana Pérez')->assertSee('10 min')->assertSee('08:15')->assertDontSee('Juan López');
        $this->getJson(route('reportes.generados.pausas', [$general, $shift->id]))
            ->assertOk()->assertJsonPath('pausas.0.tipo', 'COMIDA')->assertJsonPath('pausas.0.duracion', '00:30');
        $this->assertWorkbook($disk->path($general->archivo()), ['Asistencias', 'Pausas'], ['Juan López', 'Ana Pérez', 'COMIDA']);

        $incidencias = $reports[ReporteGenerado::INCIDENCIAS];
        $this->get(route('reportes.generados.show', $incidencias).'?q=salida+pendiente&sort=tipo')->assertOk()
            ->assertSeeInOrder(['Volver al listado', 'Exportar a Excel', 'Empresa de prueba', '(01/11/2026 al 04/11/2026)', 'Buscar incidencias…'])
            ->assertSee('Juan López')->assertSee('Salida Pendiente')->assertDontSee('Ana Pérez');
        $this->assertWorkbook($disk->path($incidencias->archivo()), ['Incidencias'], ['Juan López', 'Salida Pendiente']);

        $departments = $reports[ReporteGenerado::DEPARTAMENTO];
        $this->get(route('reportes.generados.show', $departments))->assertOk()
            ->assertSee('Exportar a Excel')->assertSee('Operación')->assertSee('Ventas')->assertSee('Sin departamento')->assertDontSee('Departamento modificado');
        $this->get(route('reportes.generados.departamento', [$departments, 'tipo' => 'late', 'departamento_id' => $department->id]))
            ->assertOk()->assertSee('Ana Pérez')->assertSee('10 min')->assertDontSee('Juan López');
        $this->get(route('reportes.generados.departamento', [$departments, 'tipo' => 'without_exit', 'sin_departamento' => 1]))
            ->assertOk()->assertSee('Juan López')->assertSee('1 registro encontrado');
        $this->assertSame(['total' => 2, 'late' => 1, 'early_departure' => 1, 'out_of_range' => 1, 'without_exit' => 1], $departments->resumen['totales']);
        $this->assertWorkbook($disk->path($departments->archivo()), ['Resumen', 'Detalle'], ['Operación', 'Ventas', 'Ana Pérez', 'Juan López', 'Sin departamento']);

        $this->actingAs(User::factory()->create(['profile' => 1]));
        $this->get($url)->assertNotFound();
        $this->get(route('reportes.generados.download', $general))->assertNotFound();
    }

    public function test_general_and_department_excel_place_the_company_or_horalia_logo_in_the_final_column(): void
    {
        $companyLogo = file_get_contents(public_path('assets/logos/logoLB.png'));
        $horaliaLogo = file_get_contents(public_path('assets/logos/logoLN.png'));
        $public = Storage::fake('public');
        $public->put('empresas/logos/empresa.png', $companyLogo);
        $public->put('empresas/logos/corrupto.png', substr($companyLogo, 0, 128));

        foreach ([
            [null, $horaliaLogo],
            ['empresas/logos/ausente.png', $horaliaLogo],
            ['empresas/logos/corrupto.png', $horaliaLogo],
            ['empresas/logos/empresa.png', $companyLogo],
        ] as [$logo, $expected]) {
            $company = new Empresa(['nombre' => 'Empresa de prueba', 'logo' => $logo]);
            $departmentReport = new ReporteGenerado([
                'tipo' => ReporteGenerado::DEPARTAMENTO,
                'desde' => '2026-11-01',
                'hasta' => '2026-11-25',
                'resumen' => ['departamentos' => [], 'totales' => array_fill_keys(array_keys(ReporteAsistenciasSnapshot::INDICADORES), 0)],
            ]);
            $departmentReport->setRelation('empresa', $company);

            $paths = [
                [app(AsistenciaReporteExport::class)->generateFromRecords($company, '2026-11-01', '2026-11-25', [], []), 19, 'S'],
                [app(ReporteDepartamentoExport::class)->generate($departmentReport, app(ReporteAsistenciasSnapshot::class)), 5, 'E'],
            ];
            foreach ($paths as [$path, $logoColumn, $metadataEnd]) {
                $zip = new ZipArchive;
                try {
                    $this->assertTrue($zip->open($path));
                    $this->assertSame(hash('sha256', $expected), hash('sha256', $zip->getFromName('xl/media/image1.png')));
                    $this->assertStringContainsString('<xdr:col>'.$logoColumn.'</xdr:col>', $zip->getFromName('xl/drawings/drawing1.xml'));
                    $this->assertStringContainsString('ref="A1:'.$metadataEnd.'1"', $zip->getFromName('xl/worksheets/sheet1.xml'));
                } finally {
                    $zip->close();
                    unlink($path);
                }
            }
        }
    }

    public function test_queued_reports_keep_company_permissions_and_the_date_limit(): void
    {
        $company = Empresa::create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $user = User::factory()->create(['profile' => 2]);
        $user->empresas()->attach($company, ['control_total' => false]);
        $this->actingAs($user);
        $filters = ['empresa_id' => $company->id, 'desde' => '2026-01-01', 'hasta' => '2026-03-31', 'tipos' => ['retardos']];
        foreach (['general', 'incidencias', 'departamento'] as $type) {
            $this->postJson(route('reportes.generados.store', $type), array_replace($filters, ['hasta' => '2026-04-01']))
                ->assertUnprocessable()->assertJsonValidationErrors('hasta');
        }
        $this->postJson(route('reportes.generados.store', 'incidencias'), $filters)->assertForbidden();
        $this->postJson(route('reportes.generados.store', 'general'), $filters)->assertRedirect();
        $this->postJson(route('reportes.generados.store', 'departamento'), $filters)->assertRedirect();
        $this->assertDatabaseCount('jobs', 2);

        $general = ReporteGenerado::where('tipo', 'general')->sole();
        $user->empresas()->detach($company);
        $this->get(route('reportes.generados.show', $general))->assertForbidden();
        $this->get(route('reportes.general'))->assertOk()->assertDontSee(route('reportes.generados.show', $general));
    }

    public function test_failed_generation_cleans_partial_rows_and_offers_retry(): void
    {
        $this->travelTo('2026-12-01 18:00:00 UTC');
        $company = Empresa::create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $this->actingAs(User::factory()->create(['profile' => 1]));
        $this->mock(ReporteDepartamentoExport::class)->shouldReceive('generate')->twice()->andThrow(new RuntimeException('Prueba de error'));
        $this->post(route('reportes.generados.store', 'departamento'), [
            'empresa_id' => $company->id, 'desde' => '2026-11-01', 'hasta' => '2026-11-30',
        ])->assertRedirect();

        $report = ReporteGenerado::sole();
        $this->artisan('queue:work', ['connection' => 'reportes', '--once' => true])->assertSuccessful();
        $this->assertSame(ReporteGenerado::PROCESANDO, $report->refresh()->estado);
        $this->travel(16)->seconds();
        $this->artisan('queue:work', ['connection' => 'reportes', '--once' => true])->assertSuccessful();
        $this->assertSame(ReporteGenerado::FALLIDO, $report->refresh()->estado);
        $this->assertNull($report->resumen);
        $this->assertDatabaseCount('reportes_generados_filas', 0);
        $this->get(route('reportes.generados.show', $report))->assertOk()->assertSee('Intentar de nuevo')->assertDontSee('Exportar a Excel');
        $this->get(route('reportes.departamento'))->assertOk()->assertSee('badge-st st-terminated');
    }

    private function assertWorkbook(string $path, array $sheetNames, array $values): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        try {
            $book = $zip->getFromName('xl/workbook.xml');
            $xml = '';
            foreach ($sheetNames as $offset => $sheet) {
                $this->assertStringContainsString('name="'.$sheet.'"', $book);
                $sheetXml = $zip->getFromName('xl/worksheets/sheet'.($offset + 1).'.xml');
                $this->assertNotFalse(simplexml_load_string($sheetXml));
                $xml .= $sheetXml;
            }
            foreach ($values as $value) {
                $this->assertStringContainsString($value, $xml);
            }
        } finally {
            $zip->close();
        }
    }
}
