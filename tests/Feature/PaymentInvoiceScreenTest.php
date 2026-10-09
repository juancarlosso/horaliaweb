<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentInvoiceScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(12, 0));
    }

    public function test_invoice_screen_shows_fiscal_notice_and_summary_from_historical_amount(): void
    {
        [$administrator, $company, $payment] = $this->eligiblePayment();
        $company->forceFill(['precio' => '999.00'])->save();

        $this->actingAs($administrator)
            ->get(route('payment-history.invoice', $payment->id))
            ->assertOk()
            ->assertSee('Verifica tus datos fiscales')
            ->assertSee('Resumen de facturación')
            ->assertSee('Pago del 09/10/2026')
            ->assertSee('129.31')
            ->assertSee('20.69')
            ->assertSee('150.00 MXN')
            ->assertSee('No representa un CFDI emitido o timbrado.')
            ->assertSee('04 — Tarjeta de crédito')
            ->assertSee('28 — Tarjeta de débito')
            ->assertSee('Selecciona la forma de pago');
    }

    public function test_invoice_requires_a_sat_payment_form_and_persists_only_the_selected_code(): void
    {
        [$administrator, $company, $payment] = $this->eligiblePayment();
        $this->actingAs($administrator);

        $this->post(route('payment-history.invoice.store', $payment->id), ['forma_pago_sat' => ''])
            ->assertRedirect()
            ->assertSessionHasErrors('forma_pago_sat');
        $this->assertDatabaseHas('intentos_pago', ['id' => $payment->id, 'forma_pago_sat' => null, 'factura' => 0]);

        $this->post(route('payment-history.invoice.store', $payment->id), ['forma_pago_sat' => '01'])
            ->assertRedirect()
            ->assertSessionHasErrors('forma_pago_sat');

        $this->post(route('payment-history.invoice.store', $payment->id), ['forma_pago_sat' => '04'])
            ->assertRedirect(route('payment-history.invoice', $payment->id));
        $this->assertDatabaseHas('intentos_pago', ['id' => $payment->id, 'forma_pago_sat' => '04', 'factura' => 0]);

        $this->post(route('payment-history.invoice.store', $payment->id), [
            'forma_pago_sat' => '28',
            'uso_cfdi' => 'G03',
            'correo_facturacion' => 'facturacion@example.com',
        ])->assertRedirect(route('payment-history.invoice', $payment->id))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('intentos_pago', [
            'id' => $payment->id,
            'forma_pago_sat' => '28',
            'factura' => 0,
            'cantidad' => '150.00',
        ]);

        $this->get(route('payment-history.invoice', $payment->id))
            ->assertOk()
            ->assertSee('value="28" selected', false);
    }

    public function test_company_fiscal_data_errors_hide_generate_button_and_block_saving_payment_form(): void
    {
        [$administrator, $company, $payment] = $this->eligiblePayment();
        $company->forceFill(['rfc' => 'RFC-INVALIDO', 'regimen_fiscal' => null, 'domicilio_codigo_postal' => '123'])->save();

        $this->actingAs($administrator)
            ->get(route('payment-history.invoice', $payment->id))
            ->assertOk()
            ->assertSee('RFC de la empresa')
            ->assertSee('régimen fiscal válido')
            ->assertSee('código postal fiscal')
            ->assertDontSee('Generar Factura');

        $this->post(route('payment-history.invoice.store', $payment->id), ['forma_pago_sat' => '04'])
            ->assertRedirect(route('payment-history.invoice', $payment->id));

        $this->assertDatabaseHas('intentos_pago', ['id' => $payment->id, 'forma_pago_sat' => null, 'factura' => 0]);
    }

    public function test_invoice_screen_keeps_the_current_month_and_uninvoiced_payment_eligibility(): void
    {
        [$administrator, $company, $payment] = $this->eligiblePayment();
        $this->actingAs($administrator);

        $payment->forceFill(['intentado_en' => '2026-09-30 23:59:00'])->save();
        $this->get(route('payment-history.invoice', $payment->id))->assertNotFound();

        $payment->forceFill(['intentado_en' => '2026-10-09 10:00:00', 'factura' => 1])->save();
        $this->get(route('payment-history.invoice', $payment->id))->assertNotFound();
    }

    private function eligiblePayment(): array
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = Empresa::query()->create([
            'rfc' => 'AAA010101AAA',
            'razon_social' => 'Empresa de prueba',
            'regimen_fiscal' => '601',
            'domicilio_codigo_postal' => '01234',
            'telefono' => '5551234567',
            'correo_facturacion' => 'empresa@example.com',
        ]);
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $payment = IntentoPago::query()->create([
            'empresa_id' => $company->id,
            'fecha_renovacion' => '2026-10-01',
            'origen' => 'automatico',
            'concepto' => 'Pago del 09/10/2026',
            'cantidad' => '150.00',
            'moneda' => 'mxn',
            'resultado' => 'exitoso',
            'idempotency_key' => 'invoice-test-' . uniqid(),
            'intentado_en' => '2026-10-09 10:00:00',
        ]);
        $payment->forceFill(['folio' => 'HRL-X12345'])->save();

        return [$administrator, $company, $payment];
    }
}
