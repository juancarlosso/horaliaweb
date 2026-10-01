<?php

namespace App\Http\Controllers;

use App\Services\MembershipRenewalService;
use App\Services\StripeCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MembershipPaymentController extends Controller
{
    public function pay(Request $request, int $empresa, StripeCardService $stripe, MembershipRenewalService $renewals)
    {
        abort_unless((int) $request->user()->profile === 2, 403);
        $company = $request->user()->empresas()->whereKey($empresa)->firstOrFail();
        abort_unless($stripe->ready(), 503, 'El proveedor de pagos no está disponible.');

        $data = $request->validate([
            'payment_method_id' => ['required', 'string', 'regex:/^pm_[A-Za-z0-9]+$/'],
        ]);

        try {
            $result = $renewals->payManually($company, $data['payment_method_id']);
        } catch (Throwable $exception) {
            Log::error('Falló el pago manual de una membresía.', [
                'empresa_id' => $company->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo confirmar el pago. Si tu banco muestra un cargo, no lo vuelvas a intentar y contacta a soporte.'], 502);
        }

        $status = match ($result['status']) {
            'succeeded' => 200,
            'requires_action' => 200,
            'failed' => 422,
            'indeterminate' => 503,
            'conflict' => 409,
            'not_due', 'already_paid' => 409,
            default => 400,
        };

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? 'No se pudo procesar el pago.',
            'new_date' => $result['new_date'] ?? null,
            'amount' => $result['amount'] ?? null,
            'required_payment_method_id' => $result['required_payment_method_id'] ?? null,
            'pending_source' => $result['pending_source'] ?? null,
            'payment_intent_id' => $result['payment_intent_id'] ?? null,
            'client_secret' => $result['client_secret'] ?? null,
        ], $status);
    }

    public function confirm(Request $request, int $empresa, MembershipRenewalService $renewals)
    {
        abort_unless((int) $request->user()->profile === 2, 403);
        $company = $request->user()->empresas()->whereKey($empresa)->firstOrFail();
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string', 'regex:/^pi_[A-Za-z0-9]+$/'],
        ]);

        try {
            $result = $renewals->completeManualPayment($company, $data['payment_intent_id']);
        } catch (Throwable $exception) {
            Log::error('No se pudo confirmar el resultado del pago manual.', [
                'empresa_id' => $company->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['status' => 'indeterminate', 'message' => 'No se pudo confirmar la respuesta. Revisa de nuevo en unos momentos.'], 503);
        }

        $status = match ($result['status']) {
            'succeeded' => 200,
            'failed' => 422,
            'indeterminate' => 503,
            default => 409,
        };

        return response()->json($result, $status);
    }
}
