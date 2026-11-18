<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
