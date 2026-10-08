<?php

namespace App\Http\Controllers;

use App\Models\IntentoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless((int) $user->profile === 2, 403);

        $companies = $user->empresas()->orderBy('empresas.razon_social')->get(['empresas.id', 'empresas.razon_social']);
        $companyIds = $companies->modelKeys();
        $filters = $request->validate([
            'estado' => ['nullable', 'in:exitosos,fallidos'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'empresa_id' => ['nullable', 'integer', Rule::in($companyIds)],
        ]);
        $filters['desde'] = $filters['desde'] ?? today()->startOfMonth()->toDateString();
        $filters['hasta'] = $filters['hasta'] ?? today()->toDateString();

        $payments = IntentoPago::query()
            ->with('empresa:id,razon_social')
            ->whereIn('empresa_id', $companyIds)
            ->when($filters['empresa_id'] ?? null, fn ($query, $companyId) => $query->where('empresa_id', $companyId))
            ->when(($filters['estado'] ?? null) === 'exitosos', fn ($query) => $query->where('resultado', 'exitoso'))
            ->when(($filters['estado'] ?? null) === 'fallidos', fn ($query) => $query->where('resultado', 'fallido'))
            ->when($filters['desde'] ?? null, fn ($query, $date) => $query->whereDate('intentado_en', '>=', $date))
            ->when($filters['hasta'] ?? null, fn ($query, $date) => $query->whereDate('intentado_en', '<=', $date))
            ->orderByDesc('intentado_en')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('payments.history', [
            'payments' => $payments,
            'filters' => $filters,
            'companies' => $companies,
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'payment-history',
        ]);
    }

    public function show(Request $request, int $payment)
    {
        $user = $request->user();
        abort_unless((int) $user->profile === 2, 403);

        $companyIds = $user->empresas()->pluck('empresas.id');
        $record = IntentoPago::query()
            ->with('empresa:id,razon_social')
            ->whereIn('empresa_id', $companyIds)
            ->findOrFail($payment);

        return view('payments.show', [
            'payment' => $record,
            'renewalMovements' => IntentoPago::query()
                ->where('empresa_id', $record->empresa_id)
                ->whereDate('fecha_renovacion', $record->fecha_renovacion->toDateString())
                ->orderBy('intentado_en')
                ->orderBy('id')
                ->get(),
            'attemptNumber' => $record->numero_ejecucion ?: IntentoPago::query()
                ->where('empresa_id', $record->empresa_id)
                ->whereDate('fecha_renovacion', $record->fecha_renovacion->toDateString())
                ->where('origen', $record->origen)
                ->where('origen', 'manual')
                ->where('id', '<=', $record->id)
                ->count(),
            'safeFailureReason' => $this->safeFailureReason($record->codigo_respuesta),
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'payment-history',
        ]);
    }

    private function safeFailureReason(?string $code): string
    {
        return match ($code) {
            'insufficient_funds' => 'La tarjeta no tiene fondos suficientes.',
            'expired_card' => 'La tarjeta está vencida.',
            'incorrect_cvc', 'invalid_cvc' => 'El banco no pudo validar la tarjeta.',
            'processing_error' => 'El banco no pudo procesar el cobro.',
            'no_saved_cards' => 'No había tarjetas disponibles para procesar el cobro.',
            default => 'El proveedor no pudo completar el cobro. Revisa el método de pago o contacta a tu banco.',
        };
    }
}
