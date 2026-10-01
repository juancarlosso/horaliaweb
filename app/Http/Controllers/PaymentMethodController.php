<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Services\StripeCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentMethodController extends Controller
{
    public function index(Request $request, StripeCardService $stripe)
    {
        $this->authorizeProfile($request);
        $empresas = $request->user()->empresas()->orderBy('razon_social')->get();
        $empresaId = $request->integer('empresa_id');
        $empresa = $empresas->firstWhere('id', $empresaId) ?? ($empresas->count() === 1 ? $empresas->first() : null);
        $cards = [];
        $stripeError = null;

        if ($empresa && $stripe->ready()) {
            try {
                $cards = $stripe->cards($empresa);
                $cardIds = array_column($cards, 'id');
                if ($cards && !in_array($empresa->stripe_default_payment_method_id, $cardIds, true)) {
                    $defaultId = $cards[0]['id'];
                    $stripe->setDefaultCard($empresa, $defaultId);
                    $empresa->forceFill(['stripe_default_payment_method_id' => $defaultId])->save();
                } elseif (!$cards && $empresa->stripe_default_payment_method_id) {
                    $empresa->forceFill(['stripe_default_payment_method_id' => null])->save();
                }
            } catch (Throwable $exception) {
                $this->logStripeFailure('No se pudieron consultar las tarjetas de Stripe.', $empresa, $exception);
                $stripeError = 'No se pudieron cargar las tarjetas. Inténtalo de nuevo más tarde.';
            }
        }

        return view('payment-methods.index', compact('empresas', 'empresa', 'cards', 'stripeError') + [
            'navigation' => DashboardController::navigation(),
            'activeSection' => null,
            'stripeReady' => $stripe->ready(),
            'publishableKey' => $stripe->publishableKey(),
        ]);
    }

    public function setupIntent(Request $request, StripeCardService $stripe)
    {
        $empresa = $this->authorizedCompany($request);
        abort_unless($stripe->ready(), 503, 'Stripe no está configurado.');

        try {
            $intent = $stripe->createSetupIntent($empresa);
        } catch (Throwable $exception) {
            Log::error('No se pudo iniciar el registro de tarjeta en Stripe.', ['empresa_id' => $empresa->id]);
            return response()->json(['message' => 'No se pudo iniciar el registro de la tarjeta. Inténtalo más tarde.'], 502);
        }

        return response()->json([
            'client_secret' => $intent['client_secret'],
            'customer_session_client_secret' => $intent['customer_session_client_secret'],
        ]);
    }

    public function destroy(Request $request, StripeCardService $stripe, string $paymentMethod)
    {
        $empresa = $this->authorizedCompany($request);
        abort_unless(preg_match('/^pm_[A-Za-z0-9]+$/', $paymentMethod) === 1, 404);

        if (IntentoPago::query()
            ->where('empresa_id', $empresa->id)
            ->where('tarjeta_id', $paymentMethod)
            ->where('resultado', 'pendiente')
            ->exists()) {
            return back()->withErrors(['stripe' => 'No se puede eliminar esta tarjeta mientras un cobro está pendiente de confirmación.']);
        }

        try {
            $stripe->detachCard($empresa, $paymentMethod);
            DB::table('payment_method_consents')->where('stripe_payment_method_id', $paymentMethod)->delete();
            if ($empresa->stripe_default_payment_method_id === $paymentMethod) {
                $remainingCards = $stripe->cards($empresa);
                $nextDefault = $remainingCards[0]['id'] ?? null;
                $stripe->setDefaultCard($empresa, $nextDefault);
                $empresa->forceFill(['stripe_default_payment_method_id' => $nextDefault])->save();
            }
        } catch (Throwable $exception) {
            $this->logStripeFailure('No se pudo eliminar una tarjeta de Stripe.', $empresa, $exception);
            return back()->withErrors(['stripe' => 'No se pudo eliminar la tarjeta. Inténtalo más tarde.']);
        }

        return redirect()->route('payment-methods.index', ['empresa_id' => $empresa->id])->with('status', 'La tarjeta se eliminó correctamente.');
    }

    public function makeDefault(Request $request, StripeCardService $stripe, string $paymentMethod)
    {
        $empresa = $this->authorizedCompany($request);
        abort_unless(preg_match('/^pm_[A-Za-z0-9]+$/', $paymentMethod) === 1, 404);

        try {
            $cards = $stripe->cards($empresa);
            abort_unless(in_array($paymentMethod, array_column($cards, 'id'), true), 404);
            $stripe->setDefaultCard($empresa, $paymentMethod);
            $empresa->forceFill(['stripe_default_payment_method_id' => $paymentMethod])->save();
        } catch (Throwable $exception) {
            $this->logStripeFailure('No se pudo establecer la tarjeta predeterminada.', $empresa, $exception);
            return back()->withErrors(['stripe' => 'No se pudo cambiar la tarjeta predeterminada. Inténtalo más tarde.']);
        }

        return redirect()->route('payment-methods.index', ['empresa_id' => $empresa->id])->with('status', 'La tarjeta predeterminada se actualizó.');
    }

    public function completeSetup(Request $request, StripeCardService $stripe)
    {
        $empresa = $this->authorizedCompany($request);
        $data = $request->validate([
            'setup_intent_id' => ['required', 'string', 'regex:/^seti_[A-Za-z0-9]+$/'],
            'consent' => ['accepted'],
        ]);

        try {
            $intent = $stripe->setupIntent($data['setup_intent_id']);
            abort_unless(($intent['status'] ?? null) === 'succeeded', 422, 'Stripe no confirmó el registro de la tarjeta.');
            abort_unless(($intent['customer'] ?? null) === $empresa->stripe_customer_id, 403);
            abort_unless(is_string($intent['payment_method'] ?? null), 422, 'Stripe no devolvió la tarjeta registrada.');

            if (!$empresa->stripe_default_payment_method_id) {
                $stripe->setDefaultCard($empresa, $intent['payment_method']);
                $empresa->forceFill(['stripe_default_payment_method_id' => $intent['payment_method']])->save();
            }

            $consentText = 'Autorizo guardar esta tarjeta para que la empresa pueda realizar futuros cobros relacionados con sus servicios de Horalia, conforme a los importes, periodicidad y condiciones comunicados para cada servicio.';
            DB::table('payment_method_consents')->insertOrIgnore([
                    'stripe_payment_method_id' => $intent['payment_method'],
                    'empresa_id' => $empresa->id,
                    'user_id' => $request->user()->id,
                    'terms_version' => 'future-charges-v1',
                    'consent_text' => $consentText,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 5000),
                    'accepted_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
        } catch (Throwable $exception) {
            Log::error('No se pudo confirmar el registro de tarjeta en Stripe.', ['empresa_id' => $empresa->id]);
            return response()->json(['message' => 'No se pudo confirmar la tarjeta. Actualiza la página e inténtalo de nuevo.'], 502);
        }

        return response()->json(['message' => 'La tarjeta se agregó correctamente.']);
    }

    private function authorizedCompany(Request $request): Empresa
    {
        $this->authorizeProfile($request);
        $empresaId = $request->integer('empresa_id');
        abort_unless($empresaId > 0, 422, 'Selecciona una empresa.');

        return $request->user()->empresas()->whereKey($empresaId)->firstOrFail();
    }

    private function authorizeProfile(Request $request): void
    {
        abort_unless((int) $request->user()->profile === 2, 403);
    }

    private function logStripeFailure(string $message, Empresa $empresa, Throwable $exception): void
    {
        $context = [
            'empresa_id' => $empresa->id,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ];

        if (method_exists($exception, 'response') && $response = $exception->response()) {
            $body = $response->json();
            $context['stripe_http_status'] = $response->status();
            $context['stripe_error'] = data_get($body, 'error.message');
            $context['stripe_error_code'] = data_get($body, 'error.code');
            $context['stripe_request_id'] = $response->header('Request-Id');
        }

        Log::error($message, $context);
    }
}
