<?php

namespace App\Console\Commands;

use App\Models\IntentoPago;
use App\Services\PaymentFolioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackfillPaymentFolios extends Command
{
    protected $signature = 'horalia:asignar-folios-pago {--chunk=200 : Cantidad de pagos por lote} {--dry-run : Solo muestra cuántos pagos confirmados necesitan folio}';

    protected $description = 'Asigna folios internos a pagos históricos confirmados sin folio';

    public function handle(PaymentFolioService $folios): int
    {
        $chunk = max(1, min(1000, (int) $this->option('chunk')));
        $pending = IntentoPago::query()->where('resultado', 'exitoso')->whereNull('folio')->count();
        if ($this->option('dry-run')) {
            $this->info("Pagos confirmados pendientes de folio: {$pending}. No se modificó información.");
            return self::SUCCESS;
        }

        $processed = 0;
        $errors = 0;

        IntentoPago::query()
            ->where('resultado', 'exitoso')
            ->whereNull('folio')
            ->orderBy('id')
            ->chunkById($chunk, function ($payments) use ($folios, &$processed, &$errors) {
                foreach ($payments as $payment) {
                    try {
                        DB::transaction(fn () => $folios->assign($payment));
                        $processed++;
                    } catch (Throwable $exception) {
                        $errors++;
                        $this->error("Pago #{$payment->id}: no se asignó folio (" . get_class($exception) . ').');
                    }
                }
            });

        $this->info("Pagos regularizados: {$processed}; errores: {$errors}.");

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
