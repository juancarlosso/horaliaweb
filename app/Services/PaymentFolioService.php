<?php

namespace App\Services;

use App\Models\IntentoPago;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentFolioService
{
    private const MAX_ATTEMPTS = 10;

    public function assign(IntentoPago $payment): string
    {
        if ($payment->resultado !== 'exitoso') {
            throw new RuntimeException('Solo los pagos confirmados pueden recibir un folio.');
        }

        if ($payment->folio) {
            return $payment->folio;
        }

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $folio = $this->generateCandidate();

            try {
                DB::transaction(fn () => $payment->forceFill(['folio' => $folio])->save());
                return $folio;
            } catch (QueryException $exception) {
                if (!$this->isFolioCollision($exception)) {
                    throw $exception;
                }

                $payment->unsetRelation('empresa');
                $payment->refresh();
                if ($payment->folio) {
                    return $payment->folio;
                }
            }
        }

        throw new RuntimeException('No fue posible asignar un folio único después de varios intentos.');
    }

    protected function generateCandidate(): string
    {
        return sprintf('HRL-%s%05d', chr(random_int(65, 90)), random_int(0, 99999));
    }

    private function isFolioCollision(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($state, ['23000', '23505'], true)
            && str_contains($message, 'folio');
    }
}
