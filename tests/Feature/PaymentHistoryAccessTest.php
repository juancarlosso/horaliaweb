<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentHistoryAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_non_company_administrator_cannot_open_payment_history(): void
    {
        $this->actingAs(User::factory()->create(['profile' => 3]))
            ->get('/home/pagos')
            ->assertForbidden();
    }

    public function test_company_administrator_sees_only_payments_of_associated_companies(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $ownCompany = $this->company('AAA010101AAA', 'Empresa propia');
        $otherCompany = $this->company('BBB010101BBB', 'Empresa ajena');
        $administrator->empresas()->attach($ownCompany->id, ['control_total' => true]);
        $ownPayment = $this->payment($ownCompany, 'HRL-A04782', 'own-payment-key');
        $this->payment($otherCompany, 'HRL-B83921', 'other-payment-key');

        $this->actingAs($administrator)
            ->get('/home/pagos')
            ->assertOk()
            ->assertSee('class="hr-input select2"', false)
            ->assertSee('fa-light fa-clock-rotate-left', false)
            ->assertSee('HRL-A04782')
            ->assertSee('Empresa propia')
            ->assertDontSee('HRL-B83921')
            ->assertDontSee('Empresa ajena');

        $this->get('/home/pagos/' . $ownPayment->id)
            ->assertOk()
            ->assertSee('width:100%;max-width:none', false)
            ->assertSee('class="payment-method-field is-successful"', false)
            ->assertDontSee('Referencia del proveedor')
            ->assertSee('Regresar al historial')
            ->assertSee('<path d="M19 12H5m7 7-7-7 7-7"/>', false);

        $this->get('/home/pagos/' . $this->payment($otherCompany, 'HRL-C00001', 'other-detail-key')->id)
            ->assertNotFound();
    }

    public function test_history_filters_successful_payments_and_failed_attempts_separately(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = $this->company('CCC010101CCC', 'Empresa filtros');
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $success = $this->payment($company, 'HRL-D12345', 'success-filter-key');
        $failed = $this->payment($company, null, 'failed-filter-key', 'fallido');

        $this->actingAs($administrator)->get('/home/pagos?estado=exitosos')
            ->assertOk()->assertSee('HRL-D12345')->assertDontSee('Rechazado');

        $this->get('/home/pagos?estado=fallidos')
            ->assertOk()->assertDontSee('HRL-D12345')->assertSee('Rechazado');

        $this->get('/home/pagos/' . $failed->id)
            ->assertOk()
            ->assertSee('grid-column:2 / -1', false)
            ->assertSee('class="payment-method-field "', false)
            ->assertSee('class="payment-reason-field"', false)
            ->assertSee('class="payment-attempt-number-field"', false);

        $query = ['estado' => 'fallidos', 'desde' => '2026-10-01', 'hasta' => '2026-10-08', 'page' => 1];
        $this->get(route('payment-history.index', $query))
            ->assertOk()
            ->assertSee('estado=fallidos&amp;desde=2026-10-01&amp;hasta=2026-10-08&amp;page=1', false);

        $this->get(route('payment-history.show', ['payment' => $failed->id] + $query))
            ->assertOk()
            ->assertSee('href="' . e(route('payment-history.index', $query)) . '"', false);
    }

    public function test_company_filter_contains_only_associated_companies_and_scopes_results(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $firstCompany = $this->company('EEE010101EEE', 'Primera empresa');
        $selectedCompany = $this->company('FFF010101FFF', 'Empresa seleccionada');
        $unrelatedCompany = $this->company('GGG010101GGG', 'Empresa no asociada');
        $administrator->empresas()->attach([
            $firstCompany->id => ['control_total' => true],
            $selectedCompany->id => ['control_total' => true],
        ]);
        $this->payment($firstCompany, 'HRL-G11111', 'first-company-payment-key');
        $this->payment($selectedCompany, 'HRL-H22222', 'selected-company-payment-key');
        $this->payment($unrelatedCompany, 'HRL-I33333', 'unrelated-company-payment-key');

        $this->actingAs($administrator)->get('/home/pagos?empresa_id=' . $selectedCompany->id)
            ->assertOk()
            ->assertSee('grid-template-columns:minmax(280px,2fr)', false)
            ->assertSee('class="hr-input select2"', false)
            ->assertSee('Empresa seleccionada')
            ->assertDontSee('Empresa no asociada')
            ->assertSee('HRL-H22222')
            ->assertDontSee('HRL-G11111')
            ->assertDontSee('HRL-I33333');
    }

    public function test_default_date_range_is_month_start_through_today(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->setTime(12, 0));
        $administrator = User::factory()->create(['profile' => 2]);
        $company = $this->company('DDD010101DDD', 'Empresa periodo');
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $this->payment($company, 'HRL-E12345', 'old-filtered-out-key')
            ->forceFill(['intentado_en' => '2026-09-30 23:59:00'])->save();
        $this->payment($company, 'HRL-F12345', 'current-month-payment-key');

        $this->actingAs($administrator)->get('/home/pagos')
            ->assertOk()
            ->assertSee('value="2026-10-01"', false)
            ->assertSee('value="2026-10-08"', false)
            ->assertSee('HRL-F12345')
            ->assertDontSee('HRL-E12345');
    }

    private function company(string $rfc, string $name): Empresa
    {
        return Empresa::query()->create(['rfc' => $rfc, 'razon_social' => $name]);
    }

    private function payment(Empresa $company, ?string $folio, string $key, string $result = 'exitoso'): IntentoPago
    {
        $payment = IntentoPago::query()->create([
            'empresa_id' => $company->id,
            'fecha_renovacion' => '2026-10-01',
            'origen' => 'automatico',
            'concepto' => 'MEMBRESIA HORALIA',
            'cantidad' => '150.00',
            'moneda' => 'mxn',
            'resultado' => $result,
            'idempotency_key' => $key,
            'intentado_en' => now(),
        ]);

        if ($folio !== null) {
            $payment->forceFill(['folio' => $folio])->save();
        }

        return $payment;
    }
}
