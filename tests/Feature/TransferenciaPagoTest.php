<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\TransferenciaPago;
use App\Models\TransferenciaComprobante;
use App\Models\IntentoPago;
use App\Models\User;
use App\Services\MembershipRenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransferenciaPagoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_existing_companies_default_to_card_and_reference_is_stable(): void
    {
        $company = $this->company('AAA010101AAA');
        $this->assertSame('TARJETA', $company->metodo_pago);
        $reference = $company->referenciaTransferencia();
        $this->assertMatchesRegularExpression('/^H'.$company->id.'[A-Z]$/', $reference);
        $company->forceFill(['metodo_pago' => 'TARJETA'])->save();
        $company->forceFill(['metodo_pago' => 'TRANSFERENCIA'])->save();
        $this->assertSame($reference, $company->fresh()->referenciaTransferencia());
    }

    public function test_automatic_renewals_do_not_charge_transfer_companies(): void
    {
        $company = $this->company('BBB010101BBB');
        $company->forceFill(['metodo_pago' => 'TRANSFERENCIA', 'fecha_renovacion' => today()->subDay(), 'activa' => true])->save();
        $result = app(MembershipRenewalService::class)->processAutomatic($company->id);
        $this->assertSame('transfer_pending', $result['status']);
        $this->assertDatabaseCount('intentos_pago', 0);
    }

    public function test_only_superadministrator_can_open_transfer_review(): void
    {
        $companyAdmin = User::factory()->create(['profile' => 2]);
        $this->actingAs($companyAdmin)->get(route('transferencias.admin.index'))->assertForbidden();
        $superAdmin = User::factory()->create(['profile' => 1]);
        $this->actingAs($superAdmin)->get(route('transferencias.admin.index'))->assertOk();
    }

    public function test_review_list_defaults_to_pending_and_filters_by_state_and_concept(): void
    {
        $company = $this->company('KKK010101KKK');
        $pending = TransferenciaPago::create(['empresa_id' => $company->id, 'fecha_renovacion' => today(), 'cantidad' => 150, 'concepto' => 'HORALIA PENDIENTE', 'estado' => 'pendiente_de_confirmar']);
        TransferenciaPago::create(['empresa_id' => $company->id, 'fecha_renovacion' => today()->subMonth(), 'cantidad' => 150, 'concepto' => 'HORALIA RECHAZADO', 'estado' => 'rechazado']);
        $admin = User::factory()->create(['profile' => 1]);
        $this->actingAs($admin)->get(route('transferencias.admin.index'))->assertOk()->assertSee('HORALIA PENDIENTE')->assertDontSee('HORALIA RECHAZADO')->assertSee('Revisar');
        $this->get(route('transferencias.admin.index', ['estado' => 'rechazado', 'buscar' => 'RECHAZADO']))->assertOk()->assertSee('HORALIA RECHAZADO')->assertDontSee('HORALIA PENDIENTE');
        $other = User::factory()->create(['profile' => 2]);
        $this->actingAs($other)->post(route('transferencias.admin.review', $pending->id), ['accion' => 'rechazar', 'motivo' => 'No coincide'])->assertForbidden();
    }

    public function test_superadministrator_can_view_and_download_confirmed_proof_privately(): void
    {
        Storage::fake('wasabi');
        $company = $this->company('OOO010101OOO');
        $transfer = TransferenciaPago::create(['empresa_id' => $company->id, 'fecha_renovacion' => today(), 'cantidad' => 150, 'concepto' => 'HORALIA TEST', 'estado' => 'confirmado']);
        Storage::disk('wasabi')->put('transferencias/test.pdf', '%PDF-1.4');
        $proof = TransferenciaComprobante::create(['transferencia_pago_id' => $transfer->id, 'ruta_privada' => 'transferencias/test.pdf', 'nombre_original' => 'comprobante.pdf', 'mime' => 'application/pdf', 'tamano' => 8]);
        $this->actingAs(User::factory()->create(['profile' => 1]))->get(route('transferencias.admin.index', ['estado' => 'confirmado']))->assertOk()->assertSee('Descargar comprobante')->assertSee(route('transferencias.admin.file', $proof->id));
        $this->get(route('transferencias.admin.file', $proof->id))->assertOk()->assertHeader('Content-Disposition', 'inline; filename="comprobante.pdf"');
        $this->get(route('transferencias.admin.file', ['proof' => $proof->id, 'descargar' => 1]))->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="comprobante.pdf"');
        $this->actingAs(User::factory()->create(['profile' => 2]))->get(route('transferencias.admin.file', ['proof' => $proof->id, 'descargar' => 1]))->assertForbidden();
    }

    public function test_company_administrator_can_change_payment_mode_only_for_assigned_companies(): void
    {
        config(['constantes.pagos_transferencia.beneficiario' => 'Horalia Test', 'constantes.pagos_transferencia.banco' => 'Banco test', 'constantes.pagos_transferencia.clabe' => '012345678901234567', 'constantes.pagos_transferencia.correo_admin' => 'pagos@horalia.test']);
        $admin = User::factory()->create(['profile' => 2]);
        $company = $this->company('GGG010101GGG');
        $otherCompany = $this->company('JJJ010101JJJ');
        $admin->empresas()->attach($company->id, ['control_total' => true]);
        $this->actingAs($admin)->get(route('payment-methods.index', ['empresa_id' => $company->id]))->assertOk()->assertSee('Modalidad de pago');
        $this->put(route('payment-methods.mode'), ['empresa_id' => $company->id, 'metodo_pago' => 'TRANSFERENCIA'])->assertRedirect();
        $this->assertSame('TRANSFERENCIA', $company->fresh()->metodo_pago);
        $this->put(route('payment-methods.mode'), ['empresa_id' => $otherCompany->id, 'metodo_pago' => 'TRANSFERENCIA'])->assertNotFound();
        $this->assertSame('TARJETA', $otherCompany->fresh()->metodo_pago);
    }

    public function test_superadministrator_can_switch_company_to_transfer(): void
    {
        config(['constantes.pagos_transferencia.beneficiario' => 'Horalia Test', 'constantes.pagos_transferencia.banco' => 'Banco test', 'constantes.pagos_transferencia.clabe' => '012345678901234567', 'constantes.pagos_transferencia.correo_admin' => 'pagos@horalia.test']);
        $superAdmin = User::factory()->create(['profile' => 1]);
        $company = $this->company('HHH010101HHH');
        $this->actingAs($superAdmin)->get(route('payment-methods.index', ['empresa_id' => $company->id]))
            ->assertOk()->assertSee('Modalidad de pago')->assertSee('this.form.requestSubmit()', false)->assertDontSee('Guardar modalidad');
        $this->get(route('empresas.edit', $company))->assertOk()->assertDontSee('id="metodo_pago"', false);
        $this->actingAs($superAdmin)->put(route('payment-methods.mode'), ['empresa_id' => $company->id, 'metodo_pago' => 'TRANSFERENCIA'])
            ->assertRedirect(route('payment-methods.index', ['empresa_id' => $company->id]));
        $this->assertSame('TRANSFERENCIA', $company->fresh()->metodo_pago);
    }

    public function test_transfer_cannot_be_enabled_without_real_bank_and_notification_configuration(): void
    {
        config(['constantes.pagos_transferencia.beneficiario' => null, 'constantes.pagos_transferencia.banco' => null, 'constantes.pagos_transferencia.clabe' => null, 'constantes.pagos_transferencia.correo_admin' => null]);
        $superAdmin = User::factory()->create(['profile' => 1]);
        $company = $this->company('III010101III');
        $this->actingAs($superAdmin)->from(route('payment-methods.index', ['empresa_id' => $company->id]))
            ->put(route('payment-methods.mode'), ['empresa_id' => $company->id, 'metodo_pago' => 'TRANSFERENCIA'])
            ->assertRedirect(route('payment-methods.index', ['empresa_id' => $company->id]))->assertSessionHasErrors('metodo_pago');
        $this->assertSame('TARJETA', $company->fresh()->metodo_pago);
    }

    public function test_transfer_migration_can_resume_when_the_last_table_exists_without_foreign_keys(): void
    {
        Schema::drop('transferencia_condiciones_aceptadas');
        Schema::create('transferencia_condiciones_aceptadas', function ($table): void {
            $table->id();
            $table->foreignId('transferencia_pago_id');
            $table->foreignId('empresa_id');
            $table->foreignId('usuario_id')->nullable();
            $table->string('version', 100);
            $table->text('texto');
            $table->timestamp('aceptado_en');
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_11_14_090100_create_transfer_payment_tables.php');
        $migration->up();

        $foreignKeys = Schema::getForeignKeys('transferencia_condiciones_aceptadas');
        $this->assertCount(3, $foreignKeys);
        $this->assertSame(['empresa_id', 'transferencia_pago_id', 'usuario_id'], collect($foreignKeys)->pluck('columns')->flatten()->sort()->values()->all());
    }

    public function test_company_transfer_page_is_due_only_and_shows_configured_instructions(): void
    {
        config(['constantes.pagos_transferencia.beneficiario' => 'Beneficiario de prueba', 'constantes.pagos_transferencia.banco' => 'Banco de prueba', 'constantes.pagos_transferencia.clabe' => '012345678901234567']);
        $admin = User::factory()->create(['profile' => 2]);
        $company = $this->company('CCC010101CCC');
        $admin->empresas()->attach($company->id, ['control_total' => true]);
        $company->forceFill(['metodo_pago' => 'TRANSFERENCIA'])->save();
        $this->actingAs($admin)->get(route('transferencias.upload-form', $company->id))->assertNotFound();
        $company->forceFill(['fecha_renovacion' => today()->subDay()])->save();
        $this->get(route('transferencias.upload-form', $company->id))
            ->assertOk()->assertSee('Beneficiario de prueba')->assertSee('Banco de prueba')->assertSee('012345678901234567')
            ->assertSee('Concepto obligatorio')->assertSee('48 horas hábiles')->assertSee('$150.00 MXN');
    }

    public function test_card_payment_method_screen_remains_available_for_existing_companies(): void
    {
        config(['services.stripe.key' => 'sk_test_example', 'services.stripe.publishable_key' => 'pk_test_example']);
        $admin = User::factory()->create(['profile' => 2]);
        $company = $this->company('FFF010101FFF');
        $admin->empresas()->attach($company->id, ['control_total' => true]);
        $this->actingAs($admin)->get(route('payment-methods.index', ['empresa_id' => $company->id]))
            ->assertOk()->assertSee('Agregar tarjeta')->assertSee('stripe.svg')->assertDontSee('Pendiente de configuración')->assertDontSee('Cambiar modalidad');
    }

    public function test_upload_only_changes_transfer_to_pending_and_does_not_renew(): void
    {
        Storage::fake('wasabi');
        Mail::fake();
        config(['constantes.pagos_transferencia.correo_admin' => 'pagos@horalia.test']);
        $admin = User::factory()->create(['profile' => 2]);
        $company = $this->company('DDD010101DDD');
        $admin->empresas()->attach($company->id, ['control_total' => true]);
        $company->forceFill(['metodo_pago' => 'TRANSFERENCIA', 'fecha_renovacion' => today()->subDay()])->save();
        $this->actingAs($admin)->get(route('transferencias.upload-form', $company->id))->assertOk();
        $transfer = TransferenciaPago::where('empresa_id', $company->id)->firstOrFail();

        $this->post(route('transferencias.upload', $company->id), [
            'terms_accepted' => '1',
            'comprobante' => UploadedFile::fake()->create('comprobante.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('payment-history.index'));

        $this->assertSame('pendiente_de_confirmar', $transfer->fresh()->estado);
        $this->assertDatabaseCount('intentos_pago', 0);
        Mail::assertQueued(\App\Mail\TransferenciaPendienteMail::class);
    }

    public function test_confirmation_renews_once_and_notifies_all_company_administrators(): void
    {
        Storage::fake('wasabi');
        Mail::fake();
        $company = $this->company('EEE010101EEE');
        $company->forceFill(['metodo_pago' => 'TRANSFERENCIA', 'fecha_renovacion' => today()->subDay(), 'precio' => '150.00', 'activa' => true])->save();
        $firstAdmin = User::factory()->create(['profile' => 2]);
        $secondAdmin = User::factory()->create(['profile' => 2]);
        $firstAdmin->empresas()->attach($company->id, ['control_total' => true]);
        $secondAdmin->empresas()->attach($company->id, ['control_total' => false]);
        $reviewer = User::factory()->create(['profile' => 1]);
        $transfer = TransferenciaPago::query()->create(['empresa_id' => $company->id, 'fecha_renovacion' => $company->fecha_renovacion, 'cantidad' => '150.00', 'concepto' => 'HORALIA H'.$company->id.'A', 'estado' => 'pendiente_de_confirmar', 'cargado_en' => now()]);
        Storage::disk('wasabi')->put('transferencias/test.pdf', '%PDF-1.4');
        TransferenciaComprobante::query()->create(['transferencia_pago_id' => $transfer->id, 'ruta_privada' => 'transferencias/test.pdf', 'nombre_original' => 'test.pdf', 'mime' => 'application/pdf', 'tamano' => 8]);

        $this->actingAs($reviewer)->post(route('transferencias.admin.review', $transfer->id), ['accion' => 'confirmar', 'importe_recibido' => '150.00', 'concepto_recibido' => $transfer->concepto])->assertRedirect();
        $this->assertSame('confirmado', $transfer->fresh()->estado);
        $this->assertTrue($company->fresh()->activa);
        $this->assertSame(today()->subDay()->addMonthNoOverflow()->toDateString(), $company->fresh()->fecha_renovacion->toDateString());
        $this->assertDatabaseHas('intentos_pago', ['empresa_id' => $company->id, 'origen' => 'transferencia', 'resultado' => 'exitoso']);
        $this->post(route('transferencias.admin.review', $transfer->id), ['accion' => 'confirmar', 'importe_recibido' => '150.00', 'concepto_recibido' => $transfer->concepto])->assertConflict();
        Mail::assertQueued(\App\Mail\TransferenciaConfirmadaMail::class, 2);
    }

    public function test_confirming_transfer_reactivates_inactive_company_from_confirmation_date(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-14 12:00:00'));
        Storage::fake('wasabi');
        Mail::fake();
        $company = $this->company('LLL010101LLL');
        $company->forceFill(['metodo_pago' => 'TRANSFERENCIA', 'fecha_renovacion' => '2026-10-10', 'activa' => false, 'intentos' => 5])->save();
        $transfer = TransferenciaPago::create(['empresa_id' => $company->id, 'fecha_renovacion' => '2026-10-10', 'cantidad' => 150, 'concepto' => 'HORALIA H1F', 'estado' => 'pendiente_de_confirmar']);
        TransferenciaComprobante::create(['transferencia_pago_id' => $transfer->id, 'ruta_privada' => 'transferencias/test.pdf', 'nombre_original' => 'test.pdf', 'mime' => 'application/pdf', 'tamano' => 8]);
        $this->actingAs(User::factory()->create(['profile' => 1]))->post(route('transferencias.admin.review', $transfer->id), ['accion' => 'confirmar', 'importe_recibido' => '150', 'concepto_recibido' => $transfer->concepto])->assertRedirect();
        $this->assertTrue($company->fresh()->activa);
        $this->assertSame('2026-12-14', $company->fresh()->fecha_renovacion->toDateString());
        $this->assertSame(0, (int) $company->fresh()->intentos);
    }

    public function test_confirmed_card_payment_uses_active_status_to_calculate_next_date(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-14 12:00:00'));
        Mail::fake();
        $stripe = \Mockery::mock(\App\Services\StripeCardService::class);
        $this->app->instance(\App\Services\StripeCardService::class, $stripe);
        foreach ([true => '2026-11-10', false => '2026-12-14'] as $active => $expected) {
            $company = $this->company($active ? 'MMM010101MMM' : 'NNN010101NNN');
            $company->forceFill(['activa' => (bool) $active, 'fecha_renovacion' => '2026-10-10', 'stripe_customer_id' => 'cus_'.$company->id])->save();
            $intentId = 'pi_'.$company->id;
            IntentoPago::create(['empresa_id' => $company->id, 'fecha_renovacion' => '2026-10-10', 'tarjeta_id' => 'pm_test', 'origen' => 'manual', 'concepto' => 'MEMBRESIA HORALIA', 'cantidad' => 150, 'resultado' => 'pendiente', 'transaccion_id' => $intentId, 'idempotency_key' => 'test_'.$company->id, 'intentado_en' => now()]);
            $stripe->shouldReceive('paymentIntent')->once()->with($intentId)->andReturn(['customer' => 'cus_'.$company->id, 'payment_method' => 'pm_test', 'metadata' => ['empresa_id' => $company->id], 'amount' => 15000, 'status' => 'succeeded']);
            $this->assertSame('succeeded', app(MembershipRenewalService::class)->completeManualPayment($company, $intentId)['status']);
            $this->assertSame($expected, $company->fresh()->fecha_renovacion->toDateString());
        }
    }

    private function company(string $rfc): Empresa
    {
        return Empresa::query()->create(['rfc' => $rfc, 'razon_social' => 'Empresa de prueba']);
    }

    private function companyPayload(Empresa $company): array
    {
        return ['nombre' => $company->nombre ?: 'Empresa de prueba', 'rfc' => $company->rfc, 'razon_social' => 'Empresa de prueba', 'minutos_tolerancia_entrada' => 10, 'metros_distancia_entrada' => 20, 'incluir_pausas_tiempo_trabajado' => '0'];
    }
}
