<?php

namespace App\Services;

use App\Mail\MembershipRenewalMail;
use App\Models\Empresa;
use App\Models\IntentoPago;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MembershipRenewalService
{
    public const CONCEPT = 'MEMBRESIA HORALIA';

    public function __construct(
        private readonly StripeCardService $stripe,
        private readonly PaymentFolioService $folios,
    )
    {
    }

    public function pendingCompanies(): array
    {
        return Empresa::query()
            ->where('activa', true)
            ->where('intentos', '<', 5)
            ->whereNotNull('fecha_renovacion')
            ->whereDate('fecha_renovacion', '<=', today())
            ->orderBy('fecha_renovacion')
            ->pluck('id')
            ->all();
    }

    public function processAutomatic(int $empresaId): array
    {
        $result = DB::transaction(function () use ($empresaId) {
            $empresa = Empresa::query()->whereKey($empresaId)->lockForUpdate()->first();
            if (!$empresa || !$empresa->activa || (int) $empresa->intentos >= 5 || !$empresa->fecha_renovacion || $empresa->fecha_renovacion->isFuture() || $empresa->fecha_renovacion->toDateString() > today()->toDateString()) {
                return ['status' => 'skipped', 'empresa_id' => $empresaId];
            }

            $cycleDate = $empresa->fecha_renovacion->toDateString();
            $existingSuccess = IntentoPago::query()
                ->where('empresa_id', $empresa->id)
                ->whereDate('fecha_renovacion', $cycleDate)
                ->where('resultado', 'exitoso')
                ->exists();
            if ($existingSuccess) {
                return ['status' => 'already_paid', 'empresa_id' => $empresa->id];
            }

            $manualPending = IntentoPago::query()
                ->where('empresa_id', $empresa->id)
                ->whereDate('fecha_renovacion', $cycleDate)
                ->where('origen', 'manual')
                ->where('resultado', 'pendiente')
                ->exists();
            if ($manualPending) {
                return ['status' => 'manual_pending', 'empresa_id' => $empresa->id];
            }

            $execution = min(5, (int) $empresa->intentos + 1);
            $pendingAutomatic = IntentoPago::query()
                ->where('empresa_id', $empresa->id)
                ->whereDate('fecha_renovacion', $cycleDate)
                ->where('origen', 'automatico')
                ->where('numero_ejecucion', $execution)
                ->where('resultado', 'pendiente')
                ->exists();
            $cards = $this->stripe->cards($empresa);
            $cards = $this->defaultFirst($cards, $empresa->stripe_default_payment_method_id);

            if (!$cards) {
                $key = "horalia:auto:{$empresa->id}:{$cycleDate}:{$execution}:no-card";
                $attempt = $this->pendingAttempt($empresa, $cycleDate, null, 'automatico', $key, $execution);
                $attempt->forceFill([
                    'resultado' => 'fallido',
                    'descripcion' => 'No hay tarjetas registradas para procesar el pago.',
                    'codigo_respuesta' => 'no_saved_cards',
                ])->save();
                Log::warning('Renovación automática sin tarjetas disponibles.', [
                    'empresa_id' => $empresa->id,
                    'numero_ejecucion' => $execution,
                ]);
            } else {
                foreach ($cards as $card) {
                    $paymentMethodId = (string) ($card['id'] ?? '');
                    if (!preg_match('/^pm_[A-Za-z0-9]+$/', $paymentMethodId)) {
                        continue;
                    }

                    $key = "horalia:auto:{$empresa->id}:{$cycleDate}:{$execution}:{$paymentMethodId}";
                    $attempt = $this->pendingAttempt($empresa, $cycleDate, $paymentMethodId, 'automatico', $key, $execution);
                    if ($attempt->resultado === 'exitoso') {
                        $this->folios->assign($attempt);
                        return ['status' => 'paid', 'empresa' => $empresa, 'amount' => $empresa->precio, 'renewal_date' => $cycleDate, 'new_date' => $empresa->fecha_renovacion->toDateString()];
                    }
                    if ($attempt->resultado === 'fallido') {
                        continue;
                    }

                    $charge = $this->stripe->chargeMembership($empresa, $paymentMethodId, (string) $empresa->precio, $key, true);
                    if ($charge['outcome'] === 'indeterminate') {
                        $attempt->forceFill([
                            'descripcion' => $charge['message'],
                            'codigo_respuesta' => $charge['code'],
                            'transaccion_id' => $charge['transaction_id'] ?? null,
                            'tarjeta_marca' => $charge['card_brand'] ?? null,
                            'tarjeta_ultimos4' => $charge['card_last4'] ?? null,
                        ])->save();
                        return ['status' => 'indeterminate', 'empresa_id' => $empresa->id, 'tarjeta_id' => $paymentMethodId];
                    }

                    $attempt->forceFill([
                        'resultado' => $charge['outcome'] === 'succeeded' ? 'exitoso' : 'fallido',
                        'descripcion' => $charge['message'],
                        'codigo_respuesta' => $charge['code'],
                        'transaccion_id' => $charge['transaction_id'] ?? null,
                        'tarjeta_marca' => $charge['card_brand'] ?? null,
                        'tarjeta_ultimos4' => $charge['card_last4'] ?? null,
                    ])->save();
                    if ($charge['outcome'] === 'succeeded') {
                        $this->folios->assign($attempt);
                    }
                    Log::info('Resultado de un intento individual de renovación.', [
                        'empresa_id' => $empresa->id,
                        'tarjeta_id' => $paymentMethodId,
                        'resultado' => $charge['outcome'],
                        'numero_ejecucion' => $execution,
                    ]);

                    if ($charge['outcome'] === 'succeeded') {
                        $newDate = $empresa->fecha_renovacion->copy()->addMonthNoOverflow()->toDateString();
                        $empresa->forceFill([
                            'fecha_renovacion' => $newDate,
                            'intentos' => 0,
                            'activa' => true,
                        ])->save();

                        return ['status' => 'paid', 'empresa' => $empresa, 'amount' => $empresa->precio, 'renewal_date' => $cycleDate, 'new_date' => $newDate];
                    }
                }
            }

            $empresa->intentos = min(5, (int) $empresa->intentos + 1);
            $deactivated = $empresa->intentos === 5;
            if ($deactivated) {
                $empresa->activa = false;
            }
            $empresa->save();

            return [
                'status' => 'failed',
                'empresa' => $empresa,
                'amount' => $empresa->precio,
                'renewal_date' => $cycleDate,
                'execution' => $empresa->intentos,
                'deactivated' => $deactivated,
            ];
        }, 3);

        if ($result['status'] === 'paid') {
            $this->notifyAdministrators($result['empresa'], 'paid', $result['amount'], CarbonImmutable::parse($result['new_date'])->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y'));
            Log::info('Renovación automática de membresía pagada.', [
                'empresa_id' => $result['empresa']->id,
                'fecha_renovacion' => $result['renewal_date'],
                'nueva_fecha_renovacion' => $result['new_date'],
            ]);
        } elseif ($result['status'] === 'failed') {
            Log::warning('Falló una ejecución automática de renovación.', [
                'empresa_id' => $result['empresa']->id,
                'fecha_renovacion' => $result['renewal_date'],
                'numero_ejecucion' => $result['execution'],
                'desactivada' => $result['deactivated'],
            ]);
            if ($result['deactivated']) {
                $this->notifyAdministrators($result['empresa'], 'deactivated', $result['amount']);
            } else {
                $this->notifyAdministrators($result['empresa'], 'retry', $result['amount']);
            }
        } elseif ($result['status'] === 'indeterminate') {
            Log::error('Stripe no confirmó el resultado de una renovación; se conserva el intento para reconciliarlo sin duplicar el cargo.', $result);
        }

        return $result;
    }

    public function payManually(Empresa $empresa, string $paymentMethodId): array
    {
        $result = DB::transaction(function () use ($empresa, $paymentMethodId) {
            $locked = Empresa::query()->whereKey($empresa->id)->lockForUpdate()->firstOrFail();
            if (!$locked->fecha_renovacion || $locked->fecha_renovacion->toDateString() > today()->toDateString()) {
                return ['status' => 'not_due', 'message' => 'La membresía de esta empresa no tiene un pago pendiente.'];
            }

            $cycleDate = $locked->fecha_renovacion->toDateString();
            if (IntentoPago::query()->where('empresa_id', $locked->id)->whereDate('fecha_renovacion', $cycleDate)->where('resultado', 'exitoso')->exists()) {
                return ['status' => 'already_paid', 'message' => 'Esta renovación ya fue pagada.'];
            }

            $automaticPending = IntentoPago::query()
                ->where('empresa_id', $locked->id)
                ->whereDate('fecha_renovacion', $cycleDate)
                ->where('origen', 'automatico')
                ->where('resultado', 'pendiente')
                ->first();
            if ($automaticPending) {
                return [
                    'status' => 'indeterminate',
                    'message' => 'Hay un cobro automático pendiente de confirmación. Espera a que se resuelva antes de intentar otro pago.',
                    'required_payment_method_id' => $automaticPending->tarjeta_id,
                    'pending_source' => 'automatico',
                ];
            }

            $unresolved = IntentoPago::query()
                ->where('empresa_id', $locked->id)
                ->whereDate('fecha_renovacion', $cycleDate)
                ->where('origen', 'manual')
                ->where('resultado', 'pendiente')
                ->latest('id')
                ->first();
            if ($unresolved && $unresolved->tarjeta_id !== $paymentMethodId) {
                return [
                    'status' => 'indeterminate',
                    'message' => 'Hay un intento pendiente de confirmación. Selecciona la misma tarjeta para consultar su resultado.',
                    'required_payment_method_id' => $unresolved->tarjeta_id,
                ];
            }

            $cards = $this->stripe->cards($locked);
            abort_unless(in_array($paymentMethodId, array_column($cards, 'id'), true), 404);

            $attempt = $unresolved;
            if (!$attempt) {
                $sequence = IntentoPago::query()
                    ->where('empresa_id', $locked->id)
                    ->whereDate('fecha_renovacion', $cycleDate)
                    ->where('origen', 'manual')
                    ->where('tarjeta_id', $paymentMethodId)
                    ->count() + 1;
                $idempotencyKey = "horalia:manual:{$locked->id}:{$cycleDate}:{$paymentMethodId}:{$sequence}";
                $attempt = $this->pendingAttempt($locked, $cycleDate, $paymentMethodId, 'manual', $idempotencyKey, null);
            }

            $idempotencyKey = $attempt->idempotency_key;
            $charge = $this->stripe->chargeMembership($locked, $paymentMethodId, (string) $locked->precio, $idempotencyKey, false);
            if ($charge['outcome'] === 'indeterminate') {
                $attempt->forceFill([
                    'descripcion' => $charge['message'],
                    'codigo_respuesta' => $charge['code'],
                    'transaccion_id' => $charge['transaction_id'] ?? null,
                    'tarjeta_marca' => $charge['card_brand'] ?? null,
                    'tarjeta_ultimos4' => $charge['card_last4'] ?? null,
                ])->save();

                return ['status' => 'indeterminate', 'message' => 'El proveedor aún no confirma el resultado. Reintenta con esta misma tarjeta en unos momentos.'];
            }

            if ($charge['outcome'] === 'requires_action') {
                $attempt->forceFill([
                    'descripcion' => $charge['message'],
                    'codigo_respuesta' => $charge['code'],
                    'transaccion_id' => $charge['transaction_id'] ?? null,
                    'tarjeta_marca' => $charge['card_brand'] ?? null,
                    'tarjeta_ultimos4' => $charge['card_last4'] ?? null,
                ])->save();

                return [
                    'status' => 'requires_action',
                    'message' => 'Tu banco solicita una confirmación segura para completar el pago.',
                    'payment_intent_id' => $charge['transaction_id'] ?? null,
                    'client_secret' => $charge['client_secret'] ?? null,
                ];
            }

            $attempt->forceFill([
                'resultado' => $charge['outcome'] === 'succeeded' ? 'exitoso' : 'fallido',
                'descripcion' => $charge['message'],
                'codigo_respuesta' => $charge['code'],
                'transaccion_id' => $charge['transaction_id'] ?? null,
                'tarjeta_marca' => $charge['card_brand'] ?? null,
                'tarjeta_ultimos4' => $charge['card_last4'] ?? null,
            ])->save();
            if ($charge['outcome'] === 'succeeded') {
                $this->folios->assign($attempt);
            }
            Log::info('Resultado de un intento manual de pago de membresía.', [
                'empresa_id' => $locked->id,
                'tarjeta_id' => $paymentMethodId,
                'resultado' => $charge['outcome'],
            ]);

            if ($charge['outcome'] !== 'succeeded') {
                Log::warning('Stripe rechazó un intento manual de pago de membresía.', [
                    'empresa_id' => $locked->id,
                    'payment_method_id' => $paymentMethodId,
                    'http_status' => $charge['http_status'] ?? null,
                    'error_type' => $charge['error_type'] ?? null,
                    'error_code' => $charge['code'] ?? null,
                    'decline_code' => $charge['decline_code'] ?? null,
                    'provider_message' => $charge['message'] ?? null,
                    'payment_intent_id' => $charge['transaction_id'] ?? null,
                ]);

                return ['status' => 'failed', 'message' => $this->manualPaymentFailureMessage($charge)];
            }

            $newDate = today()->addMonthNoOverflow()->toDateString();
            $locked->forceFill([
                'activa' => true,
                'intentos' => 0,
                'fecha_renovacion' => $newDate,
            ])->save();

            return [
                'status' => 'succeeded',
                'message' => 'El pago de la membresía se realizó correctamente.',
                'empresa' => $locked,
                'amount' => $locked->precio,
                'new_date' => $newDate,
            ];
        }, 3);

        if ($result['status'] === 'succeeded' && isset($result['empresa'])) {
            $this->notifyAdministrators($result['empresa'], 'paid', $result['amount'], CarbonImmutable::parse($result['new_date'])->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y'));
        }

        return $result;
    }

    public function completeManualPayment(Empresa $empresa, string $paymentIntentId): array
    {
        $intent = $this->stripe->paymentIntent($paymentIntentId);
        $result = DB::transaction(function () use ($empresa, $paymentIntentId, $intent) {
            $locked = Empresa::query()->whereKey($empresa->id)->lockForUpdate()->firstOrFail();
            $attempt = IntentoPago::query()
                ->where('empresa_id', $locked->id)
                ->where('transaccion_id', $paymentIntentId)
                ->where('origen', 'manual')
                ->lockForUpdate()
                ->firstOrFail();

            if (($intent['customer'] ?? null) !== $locked->stripe_customer_id
                || (string) ($intent['payment_method'] ?? '') !== (string) $attempt->tarjeta_id
                || (int) ($intent['metadata']['empresa_id'] ?? 0) !== (int) $locked->id
                || (int) ($intent['amount'] ?? 0) !== (int) round(((float) $attempt->cantidad) * 100)) {
                return ['status' => 'conflict', 'message' => 'No se pudo validar el pago con el proveedor.'];
            }

            if ($attempt->resultado === 'exitoso') {
                $this->folios->assign($attempt);
                return ['status' => 'succeeded', 'message' => 'El pago ya fue confirmado.', 'new_date' => $locked->fecha_renovacion?->toDateString()];
            }
            if ($attempt->resultado === 'fallido') {
                return ['status' => 'failed', 'message' => 'El proveedor no pudo completar el pago. Selecciona una tarjeta e inténtalo de nuevo.'];
            }

            if (($intent['status'] ?? null) === 'succeeded') {
                $newDate = today()->addMonthNoOverflow()->toDateString();
                $attempt->forceFill([
                    'resultado' => 'exitoso',
                    'descripcion' => 'Pago confirmado correctamente por el proveedor.',
                    'transaccion_id' => $paymentIntentId,
                ])->save();
                $this->folios->assign($attempt);
                $locked->forceFill(['activa' => true, 'intentos' => 0, 'fecha_renovacion' => $newDate])->save();

                return ['status' => 'succeeded', 'message' => 'El pago de la membresía se realizó correctamente.', 'empresa' => $locked, 'amount' => $attempt->cantidad, 'new_date' => $newDate];
            }

            if (in_array($intent['status'] ?? null, ['requires_payment_method', 'canceled'], true)) {
                $attempt->forceFill([
                    'resultado' => 'fallido',
                    'descripcion' => 'El proveedor no pudo completar el pago.',
                    'codigo_respuesta' => $intent['last_payment_error']['decline_code'] ?? $intent['last_payment_error']['code'] ?? $intent['status'],
                ])->save();

                return ['status' => 'failed', 'message' => 'No se pudo procesar el cobro. Puedes seleccionar otra tarjeta e intentarlo de nuevo.'];
            }

            return ['status' => 'indeterminate', 'message' => 'El proveedor aún está procesando el pago. Revisa de nuevo en unos momentos.'];
        }, 3);

        if ($result['status'] === 'succeeded' && isset($result['empresa'])) {
            $this->notifyAdministrators($result['empresa'], 'paid', $result['amount'], CarbonImmutable::parse($result['new_date'])->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y'));
        }

        return $result;
    }

    private function pendingAttempt(Empresa $empresa, string $cycleDate, ?string $paymentMethodId, string $origin, string $key, ?int $execution): IntentoPago
    {
        return IntentoPago::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'empresa_id' => $empresa->id,
                'fecha_renovacion' => $cycleDate,
                'tarjeta_id' => $paymentMethodId,
                'origen' => $origin,
                'concepto' => self::CONCEPT,
                'cantidad' => (string) $empresa->precio,
                'moneda' => 'mxn',
                'resultado' => 'pendiente',
                'numero_ejecucion' => $execution,
                'intentado_en' => now(),
            ]
        );
    }

    private function manualPaymentFailureMessage(array $charge): string
    {
        return match ($charge['decline_code'] ?? $charge['code'] ?? null) {
            'insufficient_funds' => 'La tarjeta no tiene fondos suficientes. Revisa el saldo o selecciona otra tarjeta.',
            'expired_card' => 'La tarjeta está vencida. Actualiza sus datos o selecciona otra tarjeta.',
            'incorrect_cvc', 'invalid_cvc' => 'El banco rechazó la tarjeta. Revisa sus datos o selecciona otra tarjeta.',
            'processing_error' => 'El banco no pudo procesar el cobro. Inténtalo de nuevo más tarde o selecciona otra tarjeta.',
            default => 'El proveedor rechazó el cobro. Revisa los datos de la tarjeta o selecciona otra tarjeta.',
        };
    }

    private function defaultFirst(array $cards, ?string $defaultId): array
    {
        usort($cards, fn ($left, $right) => (($right['id'] ?? null) === $defaultId) <=> (($left['id'] ?? null) === $defaultId));

        return $cards;
    }

    private function notifyAdministrators(Empresa $empresa, string $type, mixed $amount, ?string $renewalDate = null): void
    {
        $administrators = $empresa->usuarios()->where('profile', 2)->get(['users.id', 'users.name', 'users.email']);
        if ($administrators->isEmpty()) {
            Log::warning('No se encontraron administradores para enviar el correo de renovación.', ['empresa_id' => $empresa->id]);
            return;
        }

        foreach ($administrators as $administrator) {
            try {
                Mail::to($administrator->email, $administrator->name)->queue(new MembershipRenewalMail(
                    type: $type,
                    recipientName: (string) $administrator->name,
                    companyName: (string) ($empresa->razon_social ?: 'tu empresa'),
                    amount: number_format((float) $amount, 2),
                    renewalDate: $renewalDate,
                ));
            } catch (Throwable $exception) {
                Log::error('No se pudo enviar una notificación de renovación.', [
                    'empresa_id' => $empresa->id,
                    'administrator_id' => $administrator->id,
                    'exception' => get_class($exception),
                ]);
            }
        }
    }
}
