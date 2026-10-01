<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeCardService
{
    public function ready(): bool
    {
        return (bool) config('services.stripe.key') && (bool) config('services.stripe.publishable_key');
    }

    public function publishableKey(): ?string
    {
        return config('services.stripe.publishable_key');
    }

    public function cards(Empresa $empresa): array
    {
        if (!$empresa->stripe_customer_id) {
            return [];
        }

        $cards = [];
        $startingAfter = null;

        do {
            $parameters = [
                'customer' => $empresa->stripe_customer_id,
                'type' => 'card',
                'limit' => 100,
            ];
            if ($startingAfter) {
                $parameters['starting_after'] = $startingAfter;
            }

            $page = $this->request()->get('/payment_methods', $parameters)->throw()->json();
            $pageCards = $page['data'] ?? [];
            $cards = array_merge($cards, $pageCards);
            $startingAfter = ($page['has_more'] ?? false) && $pageCards
                ? ($pageCards[array_key_last($pageCards)]['id'] ?? null)
                : null;
        } while ($startingAfter);

        return $cards;
    }

    public function createSetupIntent(Empresa $empresa): array
    {
        $customerId = $empresa->stripe_customer_id;
        if (!$customerId) {
            $customer = $this->request()->asForm()->post('/customers', [
                'name' => $empresa->razon_social,
                'metadata[empresa_id]' => (string) $empresa->id,
            ])->throw()->json();

            $customerId = $customer['id'];
            $empresa->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $intent = $this->request()->asForm()->post('/setup_intents', [
            'customer' => $customerId,
            'usage' => 'off_session',
            'automatic_payment_methods[enabled]' => 'true',
            'automatic_payment_methods[allow_redirects]' => 'never',
            'metadata[empresa_id]' => (string) $empresa->id,
        ])->throw()->json();

        // This form is specifically for adding another card. Keep Stripe's
        // saved-method selector out of it so an existing card can't appear to
        // be the one being edited or replaced.
        $customerSession = $this->request()->asForm()->post('/customer_sessions', [
            'customer' => $customerId,
            'components[payment_element][enabled]' => 'true',
            'components[payment_element][features][payment_method_redisplay]' => 'disabled',
            'components[payment_element][features][payment_method_save]' => 'disabled',
            'components[payment_element][features][payment_method_remove]' => 'disabled',
        ])->throw()->json();

        return [
            'client_secret' => $intent['client_secret'],
            'customer_session_client_secret' => $customerSession['client_secret'],
        ];
    }

    public function detachCard(Empresa $empresa, string $paymentMethodId): void
    {
        $method = $this->request()->get('/payment_methods/' . rawurlencode($paymentMethodId))->throw()->json();
        abort_unless(($method['customer'] ?? null) === $empresa->stripe_customer_id, 404);

        $this->request()->post('/payment_methods/' . rawurlencode($paymentMethodId) . '/detach')->throw();
    }

    public function setDefaultCard(Empresa $empresa, ?string $paymentMethodId): void
    {
        if ($paymentMethodId !== null) {
            $method = $this->request()->get('/payment_methods/' . rawurlencode($paymentMethodId))->throw()->json();
            abort_unless(($method['customer'] ?? null) === $empresa->stripe_customer_id, 404);
        }

        $this->request()->asForm()->post('/customers/' . rawurlencode($empresa->stripe_customer_id), [
            'invoice_settings[default_payment_method]' => $paymentMethodId ?? '',
        ])->throw();
    }

    public function chargeMembership(
        Empresa $empresa,
        string $paymentMethodId,
        string $amount,
        string $idempotencyKey,
        bool $offSession,
    ): array {
        try {
            $method = $this->request()->get('/payment_methods/' . rawurlencode($paymentMethodId))->throw()->json();
        } catch (ConnectionException) {
            return ['outcome' => 'indeterminate', 'message' => 'No fue posible validar la tarjeta con el proveedor.', 'code' => 'connection_error'];
        } catch (RequestException $exception) {
            if ($exception->response->status() !== 404) {
                return ['outcome' => 'indeterminate', 'message' => 'No fue posible validar la tarjeta con el proveedor.', 'code' => 'provider_error'];
            }

            return ['outcome' => 'failed', 'message' => 'La tarjeta ya no está disponible para esta empresa.', 'code' => 'invalid_payment_method'];
        }

        if (($method['customer'] ?? null) !== $empresa->stripe_customer_id) {
            return ['outcome' => 'failed', 'message' => 'La tarjeta ya no está disponible para esta empresa.', 'code' => 'invalid_payment_method'];
        }

        $amountMinor = (int) round(((float) $amount) * 100);
        if ($amountMinor < 1) {
            return ['outcome' => 'failed', 'message' => 'El importe de la membresía no es válido.', 'code' => 'invalid_amount'];
        }

        try {
            $response = $this->request()->withHeaders(['Idempotency-Key' => $idempotencyKey])->post('/payment_intents', [
                'amount' => $amountMinor,
                'currency' => 'mxn',
                'customer' => $empresa->stripe_customer_id,
                'payment_method' => $paymentMethodId,
                'confirm' => 'true',
                'off_session' => $offSession ? 'true' : 'false',
                'automatic_payment_methods[enabled]' => 'true',
                'automatic_payment_methods[allow_redirects]' => 'never',
                'description' => 'MEMBRESIA HORALIA',
                'metadata[empresa_id]' => (string) $empresa->id,
                'metadata[concepto]' => 'MEMBRESIA HORALIA',
            ]);
        } catch (ConnectionException $exception) {
            return ['outcome' => 'indeterminate', 'message' => 'No fue posible confirmar la respuesta del proveedor.', 'code' => 'connection_error'];
        }

        $error = $response->json('error', []);
        $intent = data_get($error, 'payment_intent');
        $intent = is_array($intent) ? $intent : $response->json();
        $lastPaymentError = $intent['last_payment_error'] ?? [];
        $paymentIntentReference = $error['payment_intent'] ?? null;
        $intentId = $intent['id'] ?? (is_string($paymentIntentReference) ? $paymentIntentReference : null);
        $status = $intent['status'] ?? null;
        $errorCode = $lastPaymentError['code'] ?? $error['code'] ?? null;
        $declineCode = $lastPaymentError['decline_code'] ?? $error['decline_code'] ?? null;
        $errorMessage = $lastPaymentError['message'] ?? $error['message'] ?? null;

        if ($response->successful() && $status === 'succeeded') {
            return [
                'outcome' => 'succeeded',
                'message' => 'Pago procesado correctamente.',
                'code' => null,
                'transaction_id' => $intentId,
            ];
        }

        if ($response->successful() && $status === 'requires_action' && !$offSession) {
            return [
                'outcome' => 'requires_action',
                'message' => 'El banco requiere confirmar este pago.',
                'code' => 'authentication_required',
                'transaction_id' => $intentId,
                'client_secret' => $intent['client_secret'] ?? null,
            ];
        }

        if ($response->successful() && in_array($status, ['processing', 'requires_confirmation', 'requires_capture'], true)) {
            return [
                'outcome' => 'indeterminate',
                'message' => 'El pago continúa pendiente de confirmación por el proveedor.',
                'code' => $status,
                'transaction_id' => $intentId,
            ];
        }

        if ($response->serverError()) {
            return [
                'outcome' => 'indeterminate',
                'message' => 'El proveedor no pudo confirmar el resultado del cobro.',
                'code' => $errorCode ?? 'provider_error',
                'decline_code' => $declineCode,
                'error_type' => $error['type'] ?? null,
                'http_status' => $response->status(),
                'transaction_id' => $intentId,
            ];
        }

        $description = $errorMessage
            ? mb_substr((string) $errorMessage, 0, 1000)
            : ($status
                ? 'El proveedor no pudo completar el pago (estado: ' . mb_substr((string) $status, 0, 80) . ').'
                : 'El proveedor rechazó el pago.');

        return [
            'outcome' => 'failed',
            'message' => $description,
            'code' => $errorCode ?? $declineCode,
            'decline_code' => $declineCode,
            'error_type' => $error['type'] ?? null,
            'http_status' => $response->status(),
            'transaction_id' => $intentId,
        ];
    }

    public function paymentIntent(string $paymentIntentId): array
    {
        return $this->request()->get('/payment_intents/' . rawurlencode($paymentIntentId))->throw()->json();
    }

    public function setupIntent(string $setupIntentId): array
    {
        return $this->request()->get('/setup_intents/' . rawurlencode($setupIntentId))->throw()->json();
    }

    private function request(): PendingRequest
    {
        $secret = config('services.stripe.key');
        if (!$secret) {
            throw new RuntimeException('Stripe no está configurado.');
        }

        return Http::baseUrl('https://api.stripe.com/v1')
            ->withBasicAuth($secret, '')
            ->acceptJson()
            ->asForm()
            ->timeout(15);
    }
}
