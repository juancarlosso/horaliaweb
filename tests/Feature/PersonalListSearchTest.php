<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\Puesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PersonalListSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_name_search_paginates_matches_across_accessible_companies(): void
    {
        config(['constantes.itemsPorPagina' => 1]);
        $administrator = User::factory()->create(['profile' => 2]);
        $north = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa Norte', 'nombre' => 'Empresa Norte']);
        $south = Empresa::query()->create(['rfc' => 'BBB010101BBB', 'razon_social' => 'Empresa Sur', 'nombre' => 'Empresa Sur']);
        $private = Empresa::query()->create(['rfc' => 'CCC010101CCC', 'razon_social' => 'Empresa Externa', 'nombre' => 'Empresa Externa']);
        $administrator->empresas()->attach([$north->id, $south->id], ['control_total' => true]);

        foreach ([
            [$north, 'Ana López'],
            [$north, 'Juan Carlos Acosta'],
            [$south, 'Juan Carlos Bautista'],
            [$private, 'Juan Carlos Chávez'],
        ] as [$company, $name]) {
            Personal::query()->create(['empresa_id' => $company->id, 'nombre' => $name]);
        }

        $this->actingAs($administrator)->get(route('personal.index', ['q' => 'JUAN CARLOS']))
            ->assertOk()
            ->assertSee('Juan Carlos Acosta')
            ->assertSee('2 coincidencias')
            ->assertDontSee('personal-search-form')
            ->assertDontSee('Ana López')
            ->assertDontSee('Juan Carlos Bautista')
            ->assertDontSee('Juan Carlos Chávez');

        $this->get(route('personal.index', ['q' => 'JUAN CARLOS', 'page' => 2]), ['X-Datatable-Fragment' => '1'])
            ->assertOk()
            ->assertSee('Juan Carlos Bautista')
            ->assertSee('Página 2 de 2')
            ->assertDontSee('Juan Carlos Chávez')
            ->assertDontSee('personal-filters');

        $this->get(route('personal.index', ['q' => 'JUAN CARLOS', 'empresa_id' => $south->id]))
            ->assertOk()
            ->assertSee('Juan Carlos Bautista')
            ->assertSee('1 coincidencia')
            ->assertDontSee('Juan Carlos Acosta');

        $this->get(route('personal.index', ['q' => '%']))
            ->assertOk()
            ->assertSee('No se encontraron colaboradores');

        $this->get(route('personal.index', ['q' => 'JUAN CARLOS', 'per_page' => 10, 'sort' => 'empresa', 'direction' => 'desc']), ['X-Datatable-Fragment' => '1'])
            ->assertOk()
            ->assertSeeInOrder(['Juan Carlos Bautista', 'Juan Carlos Acosta'])
            ->assertDontSee('Juan Carlos Chávez');

        $this->get(route('personal.index', ['q' => 'JUA']), ['X-Datatable-Fragment' => '1'])
            ->assertOk()
            ->assertSee('Juan Carlos Acosta')
            ->assertDontSee('Juan Carlos Chávez');

        $this->getJson(route('personal.index', ['per_page' => 2000, 'sort' => 'pin']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page', 'sort']);
    }

    public function test_excel_exports_the_complete_personnel_list_and_schedules_only_for_manageable_companies(): void
    {
        $this->travelTo('2026-11-03 18:00:00 UTC');
        $admin = User::factory()->create(['profile' => 2]);
        $north = Empresa::create(['rfc' => 'AAA010101AAA', 'nombre' => 'Empresa Norte', 'razon_social' => 'Empresa Norte']);
        $south = Empresa::create(['rfc' => 'BBB010101BBB', 'nombre' => 'Empresa Sur', 'razon_social' => 'Empresa Sur']);
        $readOnly = Empresa::create(['rfc' => 'CCC010101CCC', 'nombre' => 'Empresa Solo Lectura', 'razon_social' => 'Empresa Solo Lectura']);
        $admin->empresas()->attach([$north->id, $south->id], ['control_total' => true]);
        $admin->empresas()->attach($readOnly->id, ['control_total' => false]);
        $department = Departamento::create(['empresa_id' => $north->id, 'nombre' => 'Operaciones']);
        $position = Puesto::create(['empresa_id' => $north->id, 'nombre' => 'Coordinación']);
        $employee = Personal::create([
            'empresa_id' => $north->id, 'departamento_id' => $department->id, 'puesto_id' => $position->id,
            'nombre' => 'Ana López', 'codigo' => '0007', 'pin' => '00123', 'sexo' => 'femenino',
            'email' => 'ana@example.test', 'telefono' => '+525500012345', 'activo' => true,
        ]);
        $employee->horarios()->create(['dia' => 1, 'entrada' => '08:00:00', 'salida' => '18:00:00', 'modalidad' => 'HOME_OFFICE']);
        $employee->horarios()->create(['dia' => 7, 'entrada' => '22:00:00', 'salida' => '06:00:00', 'modalidad' => 'PRESENCIAL']);
        Personal::create(['empresa_id' => $north->id, 'nombre' => '=Inactivo Norte', 'activo' => false, 'fecha_baja' => '2026-11-12 15:00:00']);
        Personal::create(['empresa_id' => $south->id, 'nombre' => 'Colaborador Sur']);
        Personal::create(['empresa_id' => $readOnly->id, 'nombre' => 'Colaborador solo lectura', 'pin' => '00456']);

        $this->actingAs($admin)->get(route('personal.index', ['empresa_id' => $north->id]))->assertOk()
            ->assertSeeInOrder(['Agregar personal', 'Generar Excel con el Personal'])
            ->assertSee(route('personal.export', ['empresa_id' => $north->id]));
        $this->get(route('personal.index', ['empresa_id' => $readOnly->id]))->assertOk()->assertDontSee('Generar Excel con el Personal');
        $this->get(route('personal.export', ['empresa_id' => $readOnly->id]))->assertForbidden();
        $this->get(route('personal.export', ['empresa_id' => 999]))->assertForbidden();

        $selected = $this->personalWorksheet(['empresa_id' => $north->id, 'q' => 'Ana', 'per_page' => 1]);
        $this->assertEquals([
            ['Fecha de alta', 'Código', 'PIN', 'Nombre', 'Sexo', 'Email', 'Teléfono', 'Departamento',
                'Puesto', 'Estatus', 'Fecha de Baja', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
            ['46329', '0007', '00123', 'Ana López', 'Femenino', 'ana@example.test', '+525500012345',
                'Operaciones', 'Coordinación', 'Activo', '', '08:00 - 18:00 HO', '', '', '', '', '', '22:00 - 06:00 PR'],
        ], array_slice($selected, 0, 2));
        $this->assertCount(3, $selected);
        $this->assertSame('=Inactivo Norte', $selected[2][3]);
        $this->assertSame('Inactivo', $selected[2][9]);
        $this->assertSame('46338', $selected[2][10]);

        $all = $this->personalWorksheet();
        $this->assertSame(['Ana López', '=Inactivo Norte', 'Colaborador Sur'], array_column(array_slice($all, 1), 3));
        $this->actingAs(User::factory()->create(['profile' => 3]));
        $this->get(route('personal.export'))->assertForbidden();
    }

    private function personalWorksheet(array $filters = []): array
    {
        $response = $this->get(route('personal.export', $filters))->assertDownload('personal.xlsx');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        try {
            $this->assertTrue($zip->open($path));
            $sheet = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
            $sheet->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $this->assertEmpty($sheet->xpath('//s:f'));
            $this->assertStringContainsString('formatCode="dd/mm/yyyy"', $zip->getFromName('xl/styles.xml'));
            $this->assertStringContainsString('numFmtId="49"', $zip->getFromName('xl/styles.xml'));

            return array_map(
                fn ($row) => array_map(fn ($cell) => (string) ($cell->is->t ?? $cell->v), iterator_to_array($row->c, false)),
                iterator_to_array($sheet->sheetData->row, false),
            );
        } finally {
            $zip->close();
            unlink($path);
        }
    }
}
