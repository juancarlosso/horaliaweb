<?php

namespace Tests\Feature;

use App\Exports\ReporteHorasTrabajadasExport;
use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\ReporteHorasTrabajadas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ReporteHorasTrabajadasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_worker_publishes_a_weekly_summary_and_the_same_data_in_excel(): void
    {
        $disk = Storage::fake('local');
        $company = Empresa::query()->create([
            'rfc' => 'AAA010101AAA',
            'nombre' => 'Empresa de prueba',
            'razon_social' => 'Empresa de prueba',
            'domicilio_calle' => 'Av. Principal',
            'incluir_pausas_tiempo_trabajado' => false,
        ]);
        $other = Empresa::query()->create(['rfc' => 'BBB010101BBB', 'razon_social' => 'Otra empresa']);
        $employee = Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'María Pérez']);
        $colleague = Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'María Pérez']);
        Asistencia::query()->create([
            'empresa_id' => $company->id, 'personal_id' => $colleague->id, 'fecha' => '2026-11-04',
            'llegada' => '2026-11-04 08:00:00', 'salida' => '2026-11-04 12:00:00',
        ]);
        $this->actingAs(User::factory()->create(['profile' => 1]));

        $shift = function (string $date, ?string $exit = '20:00:00', ?int $companyId = null) use ($company, $employee): Asistencia {
            return Asistencia::query()->create([
                'empresa_id' => $companyId ?? $company->id,
                'personal_id' => $employee->id,
                'fecha' => $date,
                'llegada' => "$date 06:00:00",
                'salida' => $exit ? "$date $exit" : null,
            ]);
        };
        $first = $shift('2026-11-04');
        $shift('2026-11-05');
        $incomplete = $shift('2026-11-06', null);
        $shift('2026-11-08', companyId: $other->id);
        $shift('2026-11-09', '16:00:00')->pausas()->create([
            'tipo' => 'descanso', 'inicio' => '2026-11-09 13:00:00', 'inicio_utc' => '2026-11-09 19:00:00',
        ]);
        $shift('2026-11-10');
        $shift('2026-11-03');

        foreach (['sanitario' => '12:10:30', 'comida' => '12:30:30', 'descanso' => '12:05:00'] as $type => $end) {
            $first->pausas()->create([
                'tipo' => $type, 'inicio' => '2026-11-04 06:00:00', 'fin' => '2026-11-04 07:00:00',
                'inicio_utc' => '2026-11-04 12:00:00', 'fin_utc' => "2026-11-04 $end",
            ]);
        }
        $incomplete->pausas()->create([
            'tipo' => 'permiso', 'inicio' => '2026-11-06 06:00:00', 'fin' => '2026-11-06 06:15:00',
            'inicio_utc' => '2026-11-06 12:00:00', 'fin_utc' => '2026-11-06 12:15:00',
        ]);

        $this->get(route('reportes.horas-trabajadas.index'))->assertOk()->assertSee('Horas trabajadas')->assertSee('Generar reporte');
        $this->post(route('reportes.horas-trabajadas.store'), [
            'empresa_id' => $company->id, 'desde' => '2026-11-04', 'hasta' => '2026-11-09',
        ])->assertRedirect();
        $report = ReporteHorasTrabajadas::query()->sole();
        $show = route('reportes.horas-trabajadas.show', $report);
        $download = route('reportes.horas-trabajadas.download', $report);

        $this->assertDatabaseHas('jobs', ['queue' => 'reportes']);
        $this->get(route('reportes.horas-trabajadas.index'))
            ->assertSee('<span class="badge-st st-pending">Pendiente</span>', false);
        $this->get($show)->assertOk()->assertSee('Reporte en proceso')->assertDontSee('Exportar a Excel');
        $this->get($download)->assertNotFound();
        $this->artisan('queue:work', ['connection' => 'reportes', '--queue' => 'reportes', '--once' => true])->assertSuccessful();

        $this->assertSame(ReporteHorasTrabajadas::LISTO, $report->refresh()->estado);
        $this->get(route('reportes.horas-trabajadas.index'))
            ->assertSee('<span class="badge-st st-active">Listo</span>', false);
        $this->assertDatabaseCount('jobs', 0);
        $rows = $report->filas()->orderBy('id')->get();
        $this->assertCount(3, $rows);
        $this->assertSame('2026-11-02', $rows[0]->semana->toDateString());
        $this->assertEquals(3, $rows[0]->dias_registrados);
        $this->assertEquals(1, $rows[0]->dias_indeterminados);
        $this->assertEquals(1634, $rows[0]->minutos_trabajados);
        $this->assertEquals(10, $rows[0]->minutos_sanitario);
        $this->assertEquals(30, $rows[0]->minutos_comida);
        $this->assertEquals(5, $rows[0]->minutos_descanso);
        $this->assertEquals(15, $rows[0]->minutos_otras_pausas);
        $this->assertEquals(61, $rows[0]->minutos_pausas);
        $this->assertEquals(1, $rows[1]->dias_indeterminados);
        $this->assertEquals(0, $rows[1]->minutos_pausas);
        $this->assertSame('2026-11-02', $rows[2]->semana->toDateString());
        $this->assertEquals(240, $rows[2]->minutos_trabajados);

        $this->get($show, ['X-Report-Fragment' => '1'])->assertOk()
            ->assertSeeInOrder(['Volver al listado', 'Exportar a Excel', 'Horas trabajadas', 'María Pérez', '27:14'])
            ->assertDontSee('Las horas trabajadas corresponden')->assertDontSee('data-report-pending');
        $this->get($show)->assertOk()
            ->assertSeeInOrder(['Empresa de prueba</strong>', '(04/11/2026 al 09/11/2026)', '</p>'], escape: false)
            ->assertDontSee('RFC: AAA010101AAA')->assertDontSee('Av. Principal');
        $this->get($download)->assertDownload('horas_trabajadas_2026-11-04_2026-11-09.xlsx');

        $xlsx = new ZipArchive;
        $this->assertTrue($xlsx->open($disk->path($report->archivo())));
        $xml = simplexml_load_string($xlsx->getFromName('xl/worksheets/sheet1.xml'));
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $this->assertSame('HORAS TRABAJADAS', (string) $xml->xpath('//x:c[@r="A1"]/x:is/x:t')[0]);
        $this->assertNotEmpty($xml->xpath('//x:mergeCell[@ref="A1:I1"]'));
        $this->assertEquals(
            hash_file('sha256', public_path('assets/logos/logoLN.png')),
            hash('sha256', $xlsx->getFromName('xl/media/image1.png')),
        );
        $this->assertStringContainsString('<xdr:col>9</xdr:col>', $xlsx->getFromName('xl/drawings/drawing1.xml'));
        $this->assertSame('RFC: AAA010101AAA', (string) $xml->xpath('//x:c[@r="A3"]/x:is/x:t')[0]);
        $this->assertSame('Av. Principal', (string) $xml->xpath('//x:c[@r="A4"]/x:is/x:t')[0]);
        $this->assertSame('María Pérez', (string) $xml->xpath('//x:c[@r="A10"]/x:is/x:t')[0]);
        $this->assertEqualsWithDelta(1634 / 1440, (float) $xml->xpath('//x:c[@r="E10"]/x:v')[0], 0.0000001);
        $this->assertStringContainsString('formatCode="[h]:mm"', $xlsx->getFromName('xl/styles.xml'));
        $xlsx->close();

        $this->actingAs(User::factory()->create(['profile' => 1]));
        $this->get($show)->assertNotFound();
        $this->get($download)->assertNotFound();
    }

    public function test_results_use_the_shared_table_and_search_before_paginating(): void
    {
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $user = User::factory()->create(['profile' => 1]);
        $this->actingAs($user);
        $report = ReporteHorasTrabajadas::query()->create([
            'empresa_id' => $company->id, 'user_id' => $user->id,
            'desde' => '2026-11-01', 'hasta' => '2026-11-30', 'estado' => ReporteHorasTrabajadas::LISTO,
        ]);
        foreach (range(1, 12) as $index) {
            $report->filas()->insert([
                'reporte_id' => $report->id, 'personal' => $index === 12 ? 'Elena Velázquez' : 'Persona '.$index,
                'semana' => $index === 12 ? '2026-11-09' : '2026-11-02', 'dias_registrados' => 1, 'dias_indeterminados' => 0,
                'minutos_trabajados' => $index * 60, 'minutos_sanitario' => 0, 'minutos_comida' => 0,
                'minutos_descanso' => 0, 'minutos_otras_pausas' => 0, 'minutos_pausas' => 0,
            ]);
        }
        $show = route('reportes.horas-trabajadas.show', $report);
        $fragment = ['X-Datatable-Fragment' => '1'];

        $this->get($show.'?per_page=10')->assertOk()->assertSee('data-datatable-source', false)
            ->assertSee('data-per-page="10"', false)->assertSee('Siguiente')->assertDontSee('Elena Velázquez');
        $this->get($show.'?per_page=10&page=2', $fragment)->assertOk()->assertSee('Elena Velázquez')->assertSee('Anterior');
        foreach (['ELENA', '09/11/2026'] as $term) {
            $this->get($show.'?per_page=10&q='.urlencode($term), $fragment)->assertOk()
                ->assertSee('data-table-total="12" data-table-filtered="1"', false)->assertSee('Elena Velázquez');
        }
        $this->get($show.'?per_page=10&sort=minutos_trabajados&direction=desc', $fragment)
            ->assertOk()->assertSeeInOrder(['Elena Velázquez', '12:00', 'Persona 11', '11:00']);
        $this->get($show.'?q=inexistente', $fragment)->assertOk()
            ->assertSee('data-table-total="12" data-table-filtered="0"', false);
    }

    public function test_excel_prefers_the_company_logo_and_keeps_it_on_the_right(): void
    {
        $logoPath = 'empresas/logos/prueba.png';
        $logo = file_get_contents(public_path('assets/logos/logoLB.png'));
        Storage::fake('public')->put($logoPath, $logo);
        $company = Empresa::query()->create([
            'rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba', 'logo' => $logoPath,
        ]);
        $user = User::factory()->create(['profile' => 1]);
        $report = ReporteHorasTrabajadas::query()->create([
            'empresa_id' => $company->id, 'user_id' => $user->id,
            'desde' => '2026-11-01', 'hasta' => '2026-11-30', 'estado' => ReporteHorasTrabajadas::LISTO,
        ]);

        $path = app(ReporteHorasTrabajadasExport::class)->generate($report);
        $xlsx = new ZipArchive;
        try {
            $this->assertTrue($xlsx->open($path));
            $this->assertSame(hash('sha256', $logo), hash('sha256', $xlsx->getFromName('xl/media/image1.png')));
            $this->assertStringContainsString('<xdr:col>9</xdr:col>', $xlsx->getFromName('xl/drawings/drawing1.xml'));
            $xlsx->close();
        } finally {
            unlink($path);
        }
    }

    public function test_company_roles_and_the_inclusive_ninety_day_limit_are_enforced(): void
    {
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $user = User::factory()->create(['profile' => 2]);
        $user->empresas()->attach($company, ['control_total' => false]);
        $this->actingAs($user);
        $route = route('reportes.horas-trabajadas.store');
        $filters = ['empresa_id' => $company->id, 'desde' => '2026-01-01', 'hasta' => '2026-03-31'];

        $this->postJson($route, $filters)->assertForbidden();
        $user->empresas()->updateExistingPivot($company, ['control_total' => true]);
        $this->postJson($route, array_replace($filters, ['hasta' => '2026-04-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');
        $this->postJson($route, $filters)->assertRedirect();

        $report = ReporteHorasTrabajadas::query()->sole();
        $user->empresas()->detach($company);
        $this->get(route('reportes.horas-trabajadas.show', $report))->assertForbidden();
        $this->get(route('reportes.horas-trabajadas.download', $report))->assertForbidden();

        $this->actingAs(User::factory()->create(['profile' => 3]));
        $this->get(route('reportes.horas-trabajadas.index'))->assertForbidden();
        $this->postJson($route, $filters)->assertForbidden();
    }

    public function test_permanent_export_failure_leaves_the_report_retryable_from_the_screen(): void
    {
        $this->travelTo('2026-12-01 18:00:00 UTC');
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa de prueba']);
        $this->actingAs(User::factory()->create(['profile' => 1]));
        $this->mock(ReporteHorasTrabajadasExport::class)->shouldReceive('generate')
            ->twice()->andThrow(new RuntimeException('Error de prueba'));

        $this->post(route('reportes.horas-trabajadas.store'), [
            'empresa_id' => $company->id, 'desde' => '2026-11-01', 'hasta' => '2026-11-30',
        ])->assertRedirect();
        $report = ReporteHorasTrabajadas::query()->sole();
        $this->artisan('queue:work', ['connection' => 'reportes', '--once' => true])->assertSuccessful();
        $this->assertSame(ReporteHorasTrabajadas::PROCESANDO, $report->refresh()->estado);
        $this->get(route('reportes.horas-trabajadas.index'))
            ->assertSee('<span class="badge-st st-processing">Procesando</span>', false);
        $this->travel(16)->seconds();
        $this->artisan('queue:work', ['connection' => 'reportes', '--once' => true])->assertSuccessful();

        $this->assertSame(ReporteHorasTrabajadas::FALLIDO, $report->refresh()->estado);
        $this->get(route('reportes.horas-trabajadas.index'))
            ->assertSee('<span class="badge-st st-terminated">Fallido</span>', false);
        $this->get(route('reportes.horas-trabajadas.show', $report))->assertOk()
            ->assertSee('Intentar de nuevo')->assertDontSee('Exportar a Excel');
    }
}
