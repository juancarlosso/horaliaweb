<?php

namespace Tests\Unit;

use App\Models\IntentoPago;
use App\Services\PaymentFolioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PaymentFolioServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social')->nullable();
        });
        Schema::create('intentos_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('folio', 10)->nullable()->unique();
            $table->date('fecha_renovacion');
            $table->string('tarjeta_id')->nullable();
            $table->string('origen');
            $table->string('concepto');
            $table->decimal('cantidad', 10, 2);
            $table->string('moneda', 3);
            $table->string('resultado', 20);
            $table->string('idempotency_key')->unique();
            $table->timestamp('intentado_en');
            $table->timestamps();
        });
        \DB::table('empresas')->insert([['id' => 1], ['id' => 2]]);
    }

    public function test_confirmed_payment_gets_a_random_shaped_folio_only_once(): void
    {
        $payment = $this->payment(1, 'exitoso');
        $service = new PaymentFolioService();

        $folio = $service->assign($payment);

        $this->assertMatchesRegularExpression('/^HRL-[A-Z][0-9]{5}$/', $folio);
        $this->assertSame($folio, $service->assign($payment->fresh()));
        $this->assertSame(1, IntentoPago::query()->where('folio', $folio)->count());
    }

    public function test_unique_index_collision_retries_with_a_new_candidate(): void
    {
        $this->payment(1, 'exitoso', 'HRL-A00001');
        $payment = $this->payment(2, 'exitoso');
        $service = new class extends PaymentFolioService {
            private array $candidates = ['HRL-A00001', 'HRL-B04782'];

            protected function generateCandidate(): string
            {
                return array_shift($this->candidates);
            }
        };

        $this->assertSame('HRL-B04782', $service->assign($payment));
        $this->assertSame(2, IntentoPago::query()->whereNotNull('folio')->count());
    }

    public function test_failed_attempt_cannot_receive_a_payment_folio(): void
    {
        $payment = $this->payment(1, 'fallido');

        $this->expectException(RuntimeException::class);
        (new PaymentFolioService())->assign($payment);
    }

    public function test_assigned_folio_cannot_be_changed_or_removed(): void
    {
        $payment = $this->payment(1, 'exitoso');
        (new PaymentFolioService())->assign($payment);

        $this->expectException(\LogicException::class);
        $payment->folio = null;
    }

    private function payment(int $companyId, string $result, ?string $folio = null): IntentoPago
    {
        $payment = IntentoPago::query()->create([
            'empresa_id' => $companyId,
            'fecha_renovacion' => '2026-10-01',
            'origen' => 'manual',
            'concepto' => 'MEMBRESIA HORALIA',
            'cantidad' => '150.00',
            'moneda' => 'mxn',
            'resultado' => $result,
            'idempotency_key' => uniqid('test-', true),
            'intentado_en' => now(),
        ]);

        if ($folio !== null) {
            $payment->forceFill(['folio' => $folio])->save();
        }

        return $payment;
    }
}
