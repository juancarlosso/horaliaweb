<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyFiscalDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_company_administrator_can_save_and_reopen_optional_fiscal_details(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = Empresa::query()->create([
            'rfc' => 'AAA010101AAA',
            'razon_social' => 'Empresa fiscal',
            'direccion' => 'Dirección histórica',
        ]);
        $administrator->empresas()->attach($company->id, ['control_total' => true]);

        $payload = [
            'rfc' => $company->rfc,
            'razon_social' => $company->razon_social,
            'telefono' => '',
            'minutos_tolerancia_entrada' => 10,
            'metros_distancia_entrada' => 20,
            'domicilio_calle' => 'Av. Reforma',
            'domicilio_numero_exterior' => '12-A',
            'domicilio_numero_interior' => '',
            'domicilio_colonia' => 'Centro',
            'domicilio_codigo_postal' => '01234',
            'domicilio_municipio' => 'Cuauhtémoc',
            'domicilio_ciudad' => 'Ciudad de México',
            'domicilio_estado' => 'Ciudad de México',
            'regimen_fiscal' => '601',
            'correo_facturacion' => 'facturas@example.test',
        ];

        $this->actingAs($administrator)
            ->get(route('empresas.edit', $company))
            ->assertOk()
            ->assertDontSee('for="direccion"', false)
            ->assertSee('Domicilio fiscal')
            ->assertSee('601 — General de Ley Personas Morales');

        $this->put(route('empresas.update', $company), $payload)->assertRedirect(route('empresas.index'));

        $this->assertDatabaseHas('empresas', [
            'id' => $company->id,
            'direccion' => 'Dirección histórica',
            'domicilio_codigo_postal' => '01234',
            'domicilio_numero_exterior' => '12-A',
            'regimen_fiscal' => '601',
            'correo_facturacion' => 'facturas@example.test',
        ]);

        $this->get(route('empresas.edit', $company))
            ->assertOk()
            ->assertSee('value="01234"', false)
            ->assertSee('value="facturas@example.test"', false);
    }

    public function test_all_catalog_regimes_are_available_regardless_of_rfc_type(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa fiscal']);
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $payload = [
            'rfc' => $company->rfc,
            'razon_social' => $company->razon_social,
            'minutos_tolerancia_entrada' => 10,
            'metros_distancia_entrada' => 20,
            'domicilio_codigo_postal' => '1234',
            'domicilio_estado' => '',
            'regimen_fiscal' => '612',
            'correo_facturacion' => 'no-es-correo',
        ];

        $this->actingAs($administrator)
            ->from(route('empresas.edit', $company))
            ->put(route('empresas.update', $company), $payload)
            ->assertSessionHasErrors(['domicilio_codigo_postal', 'correo_facturacion'])
            ->assertSessionDoesntHaveErrors('regimen_fiscal');
    }

    public function test_company_without_control_total_cannot_change_fiscal_details(): void
    {
        $user = User::factory()->create(['profile' => 2]);
        $company = Empresa::query()->create(['rfc' => 'AAA010101AAA', 'razon_social' => 'Empresa ajena']);
        $user->empresas()->attach($company->id, ['control_total' => false]);

        $this->actingAs($user)
            ->put(route('empresas.update', $company), ['domicilio_calle' => 'Acceso indebido'])
            ->assertNotFound();

        $this->assertDatabaseMissing('empresas', ['id' => $company->id, 'domicilio_calle' => 'Acceso indebido']);
    }
}
