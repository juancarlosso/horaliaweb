<?php

namespace Tests\Feature;

use App\Mail\MembershipRenewalMail;
use App\Mail\TransferRenewalMail;
use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\TransferenciaPago;
use App\Models\User;
use App\Services\MembershipRenewalService;
use App\Services\StripeCardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransferMembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.name' => 'Horalia']);
        Mail::fake();
        $this->mock(StripeCardService::class)->shouldNotReceive('chargeMembership');
    }

    public function test_transfer_renewal_counts_calendar_days_across_new_year_and_the_weekend(): void
    {
        config([
            'constantes.pagos_transferencia.beneficiario' => 'Beneficiario de prueba',
            'constantes.pagos_transferencia.banco' => 'Banco de prueba',
            'constantes.pagos_transferencia.clabe' => '012345678901234567',
            'constantes.pagos_transferencia.cuenta' => '0123456789',
            'constantes.precio_mensual_por_empresa_mxn' => 225,
        ]);
        $company = $this->company('2026-12-30');
        $admin = User::factory()->create(['profile' => 2, 'email' => 'admin@horalia.test']);
        $admin->empresas()->attach($company);
        User::factory()->create(['profile' => 2, 'email' => 'admin+2@horalia.test'])->empresas()->attach($company);

        $this->runRenewalsOn('2026-12-29');
        Mail::assertNothingOutgoing();
        $this->assertTrue($company->fresh()->activa);
        $this->assertDatabaseCount('transferencias_pago', 0);

        foreach (['2026-12-30', '2026-12-31', '2027-01-01', '2027-01-02', '2027-01-03'] as $offset => $date) {
            $this->runRenewalsOn($date);
            $this->runRenewalsOn($date);

            Mail::assertSent(TransferRenewalMail::class, 2 * ($offset + 1));
            $this->assertTrue($company->fresh()->activa);

            if ($offset === 0) {
                $payment = $company->transferencias()->sole();
                $this->assertTransferScreens($company, $admin);

                $mail = Mail::sent(TransferRenewalMail::class)->first();
                $mail->assertHasSubject('Horalia | Es momento de renovar tu servicio')
                    ->assertSeeInHtml('Beneficiario de prueba')
                    ->assertSeeInHtml('Banco de prueba')
                    ->assertSeeInHtml('012345678901234567')
                    ->assertSeeInHtml('Número de cuenta')
                    ->assertSeeInHtml($payment->concepto)
                    ->assertSeeInHtml('$225.00')
                    ->assertSeeInHtml('30/12/2026')
                    ->assertSeeInHtml('03/01/2027')
                    ->assertSeeInHtml('04/01/2027')
                    ->assertSeeInHtml('Quedan 5 días naturales')
                    ->assertSeeInHtml('48 horas hábiles')
                    ->assertSeeInHtml('09:00')
                    ->assertSeeInHtml('18:00')
                    ->assertSeeInHtml(route('transferencias.upload-form', $company->id));

                $payment->update(['estado' => 'pendiente_de_confirmar']);
                $this->runRenewalsOn($date);
                Mail::assertSent(TransferRenewalMail::class, 2);
            } elseif ($offset === 1) {
                Mail::sent(TransferRenewalMail::class)->last()
                    ->assertHasSubject('Horalia | Quedan 4 días para confirmar el pago · Tu comprobante está en revisión')
                    ->assertSeeInHtml('está en proceso de validación')
                    ->assertSeeInHtml('Quedan 4 días naturales')
                    ->assertSeeInHtml('04/01/2027')
                    ->assertDontSeeInHtml('012345678901234567')
                    ->assertDontSeeInHtml('Datos para tu transferencia');
                $payment->update(['estado' => 'rechazado', 'motivo_rechazo' => 'El importe difiere']);
            } elseif ($offset === 2) {
                Mail::sent(TransferRenewalMail::class)->last()
                    ->assertHasSubject('Horalia | Quedan 3 días para confirmar el pago · Tu comprobante fue rechazado')
                    ->assertSeeInHtml('El importe difiere')
                    ->assertSeeInHtml('antes de hacer otro pago')
                    ->assertSeeInHtml('012345678901234567');
            }
        }

        Mail::sent(TransferRenewalMail::class)->last()
            ->assertSeeInHtml('Hoy es el último día')
            ->assertSeeInHtml('04/01/2027');

        $this->runRenewalsOn('2027-01-04');
        $this->assertFalse($company->fresh()->activa);
        $this->assertSame(0, $company->fresh()->intentos);
        $this->assertDatabaseCount('transferencia_avisos_renovacion', 10);
        $this->assertDatabaseCount('intentos_pago', 0);
        Mail::assertSent(TransferRenewalMail::class, 10);
    }

    public function test_day_six_suspends_every_unpaid_state_and_confirmation_reactivates_only_for_nonpayment(): void
    {
        $unpaid = $this->company('2026-11-27');
        $pending = $this->company('2026-11-27');
        $payment = $this->proof($pending);
        $rejected = $this->company('2026-11-27');
        $rejected->transferencias()->create([
            'fecha_renovacion' => '2026-11-27', 'cantidad' => '150.00', 'concepto' => 'HORALIA RECHAZADO', 'estado' => 'rechazado',
        ]);
        $cancelled = $this->company('2026-11-27');
        $cancelled->forceFill(['cancelar_al_renovar' => true])->save();
        $cancelledPayment = $this->proof($cancelled);
        $disabled = $this->company('2026-11-27');
        $disabled->update(['activa' => false]);
        $disabledPayment = $this->proof($disabled);

        $this->runRenewalsOn('2026-12-01');
        $this->assertTrue($pending->fresh()->activa);
        $this->assertFalse($cancelled->fresh()->activa);
        $this->runRenewalsOn('2026-12-02');

        foreach ([$unpaid, $pending, $rejected] as $company) {
            $company->refresh();
            $this->assertFalse($company->activa);
            $this->assertSame('2026-11-27', $company->periodo_suspendido_por_transferencia->toDateString());
            $this->assertSame(0, $company->intentos);
        }

        $this->actingAs(User::factory()->create(['profile' => 1]));
        $this->approve($payment);
        $this->approve($cancelledPayment);
        $this->approve($disabledPayment);

        $this->assertTrue($pending->fresh()->activa);
        $this->assertSame('2027-01-02', $pending->fresh()->fecha_renovacion->toDateString());
        $this->assertNull($pending->fresh()->periodo_suspendido_por_transferencia);
        $this->assertFalse($cancelled->fresh()->activa);
        $this->assertTrue($cancelled->fresh()->cancelar_al_renovar);
        $this->assertFalse($disabled->fresh()->activa);
        $this->assertDatabaseCount('intentos_pago', 3);
    }

    public function test_approval_during_grace_stops_notices_and_old_candidates_cannot_suspend_the_new_period(): void
    {
        $company = $this->company('2026-11-27');
        User::factory()->create(['profile' => 2])->empresas()->attach($company);

        $this->runRenewalsOn('2026-11-28');
        $payment = $this->proof($company);
        Mail::assertSent(TransferRenewalMail::class, 1);

        $renewals = app(MembershipRenewalService::class);
        $this->assertContains($company->id, $renewals->pendingCompanies());
        $this->actingAs(User::factory()->create(['profile' => 1]));
        $this->approve($payment);
        $this->assertSame('skipped', $renewals->processAutomatic($company->id)['status']);
        $this->runRenewalsOn('2026-12-02');

        $this->assertSame('2026-12-27', $company->fresh()->fecha_renovacion->toDateString());
        $this->assertTrue($company->fresh()->activa);
        $this->assertDatabaseCount('intentos_pago', 1);
        Mail::assertSent(TransferRenewalMail::class, 1);
    }

    public function test_verified_payment_is_checked_at_expiration_even_if_the_company_date_has_not_advanced(): void
    {
        $company = $this->company('2026-11-27');
        $this->travelTo(Carbon::parse('2026-12-02 09:00'));
        $renewals = app(MembershipRenewalService::class);
        $this->assertContains($company->id, $renewals->pendingCompanies());

        IntentoPago::create([
            'empresa_id' => $company->id, 'fecha_renovacion' => '2026-11-27', 'origen' => 'transferencia',
            'concepto' => 'HORALIA PRUEBA', 'cantidad' => 150, 'resultado' => 'exitoso',
            'idempotency_key' => 'transferencia_confirmada_en_paralelo', 'intentado_en' => now(),
        ]);

        $this->assertSame('already_paid', $renewals->processAutomatic($company->id)['status']);
        $this->assertTrue($company->fresh()->activa);
        Mail::assertNothingOutgoing();
    }

    public function test_card_renewal_still_increments_attempts_and_deactivates_on_the_fifth_failure(): void
    {
        $company = $this->company('2026-11-27', 'TARJETA');
        $company->update(['intentos' => 3]);
        User::factory()->create(['profile' => 2])->empresas()->attach($company);
        app(StripeCardService::class)->shouldReceive('cards')->twice()->andReturn([]);

        $this->runRenewalsOn('2026-11-27');
        $this->assertTrue($company->fresh()->activa);
        $this->assertSame(4, $company->fresh()->intentos);
        $this->runRenewalsOn('2026-11-28');
        $this->assertFalse($company->fresh()->activa);
        $this->assertSame(5, $company->fresh()->intentos);
        $this->assertDatabaseCount('intentos_pago', 2);
        Mail::assertQueued(MembershipRenewalMail::class, 2);
        Mail::assertNotSent(TransferRenewalMail::class);
    }

    private function assertTransferScreens(Empresa $company, User $admin): void
    {
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = 'localhost';
        try {
            $this->actingAs($admin)->get(route('payment-methods.index', ['empresa_id' => $company->id]))
                ->assertOk()->assertSee('Dispones de cinco días naturales')->assertSee('Si al sexto día');
            $this->get(route('transferencias.upload-form', $company->id))
                ->assertOk()->assertSee('Dispones de cinco días naturales')->assertSee('$225.00 MXN');
        } finally {
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
    }

    private function runRenewalsOn(string $date): void
    {
        $this->travelTo(Carbon::parse($date.' 09:00'));
        $this->artisan('horalia:renovar-suscripciones')->assertSuccessful();
    }

    private function company(string $renewalDate, string $method = 'TRANSFERENCIA'): Empresa
    {
        $company = Empresa::query()->create([
            'rfc' => strtoupper(fake()->unique()->bothify('???######???')),
            'razon_social' => 'Empresa de prueba',
        ]);
        $company->forceFill(['fecha_renovacion' => $renewalDate, 'metodo_pago' => $method, 'activa' => true])->save();

        return $company;
    }

    private function proof(Empresa $company): TransferenciaPago
    {
        $payment = $company->transferencias()->firstOrCreate(['fecha_renovacion' => $company->fecha_renovacion], [
            'cantidad' => '150.00', 'concepto' => 'HORALIA '.$company->referenciaTransferencia(),
        ]);
        $payment->update(['estado' => 'pendiente_de_confirmar']);
        $payment->comprobantes()->create([
            'ruta_privada' => 'transferencias/test.pdf', 'nombre_original' => 'test.pdf', 'mime' => 'application/pdf', 'tamano' => 8,
        ]);

        return $payment;
    }

    private function approve(TransferenciaPago $payment): void
    {
        $this->post(route('transferencias.admin.review', $payment->id), [
            'accion' => 'confirmar', 'importe_recibido' => $payment->cantidad, 'concepto_recibido' => $payment->concepto,
        ])->assertRedirect();
        $this->assertSame('confirmado', $payment->fresh()->estado);
    }
}
