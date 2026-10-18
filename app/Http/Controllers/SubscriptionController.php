<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\EmployeeSubscriptionPricing;
use App\Services\PaymentInvoiceAmounts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request, EmployeeSubscriptionPricing $pricing, PaymentInvoiceAmounts $invoiceAmounts)
    {
        $user = $request->user();
        abort_unless((int) $user->profile === 2, 403);

        $companies = $user->empresas()->orderBy('empresas.nombre')->get();
        $data = $request->validate([
            'empresa_id' => ['nullable', 'integer', Rule::in($companies->modelKeys())],
        ]);
        $company = $companies->firstWhere('id', (int) ($data['empresa_id'] ?? 0)) ?? $companies->first();
        $employeeCount = $company ? $pricing->employeeCount($company) : 0;
        $blocks = $pricing->blocksFor($employeeCount);
        $estimatedPrice = $pricing->priceForCount($employeeCount);
        $estimatedAmounts = $invoiceAmounts->fromTaxInclusiveTotal($estimatedPrice);

        return view('subscription.index', [
            'companies' => $companies,
            'company' => $company,
            'employeeCount' => $employeeCount,
            'billableBlocks' => $blocks,
            'blockUnitPrice' => (float) config('constantes.precio_mensual_por_empresa_mxn', 150),
            'estimatedPrice' => $estimatedPrice,
            'estimatedAmounts' => $estimatedAmounts,
            'navigation' => DashboardController::navigation(),
            'activeSection' => null,
        ]);
    }

    public function cancel(Request $request, int $empresa)
    {
        $company = $this->authorizedCompany($request, $empresa);
        abort_unless($company->activa && $company->fecha_renovacion && $company->fecha_renovacion->toDateString() > today()->toDateString(), 422, 'Esta suscripción no tiene un periodo activo para cancelar.');

        $company->forceFill(['cancelar_al_renovar' => true])->save();

        return redirect()->route('subscription.index', ['empresa_id' => $company->id])
            ->with('status', 'La suscripción se cancelará al terminar el periodo actual. Conservarás el acceso hasta el ' . $company->fecha_renovacion->format('d/m/Y') . '.');
    }

    public function resume(Request $request, int $empresa)
    {
        $company = $this->authorizedCompany($request, $empresa);
        abort_unless($company->activa && $company->cancelar_al_renovar && $company->fecha_renovacion && $company->fecha_renovacion->toDateString() > today()->toDateString(), 404);

        $company->forceFill(['cancelar_al_renovar' => false])->save();

        return redirect()->route('subscription.index', ['empresa_id' => $company->id])
            ->with('status', 'La renovación automática continuará activa.');
    }

    private function authorizedCompany(Request $request, int $empresa): Empresa
    {
        abort_unless((int) $request->user()->profile === 2, 403);

        return $request->user()->empresas()->whereKey($empresa)->firstOrFail();
    }
}
