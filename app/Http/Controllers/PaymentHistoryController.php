<?php

namespace App\Http\Controllers;

use App\Models\IntentoPago;
use App\Models\Empresa;
use App\Services\PaymentInvoiceAmounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->with(['empresa:id,razon_social', 'facturaEmitida:id,intento_pago_id,serie,folio,uuid,estado,xml_path,pdf_path'])
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

    public function invoiceDocument(Request $request, int $payment, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xml'], true), 404);

        $user = $request->user();
        abort_unless((int) $user->profile === 2, 403);

        $companyIds = $user->empresas()->pluck('empresas.id');
        $record = IntentoPago::query()
            ->with('facturaEmitida')
            ->whereIn('empresa_id', $companyIds)
            ->findOrFail($payment);
        $invoice = $record->facturaEmitida;

        abort_unless(
            $record->resultado === 'exitoso'
                && (int) $record->factura === 1
                && $invoice?->uuid,
            404
        );

        $path = $format === 'pdf' ? $invoice->pdf_path : $invoice->xml_path;
        if (!$path) {
            return response('El archivo de la factura todavía se está preparando.', 503)
                ->header('Retry-After', '10')
                ->header('Cache-Control', 'no-store');
        }

        $disk = Storage::disk('wasabi');
        abort_unless($disk->exists($path), 404);
        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        $extension = $format;
        $contentType = $format === 'pdf' ? 'application/pdf' : 'application/xml; charset=UTF-8';
        $safeSeries = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $invoice->serie);
        $filename = sprintf('Factura-%s-%d.%s', $safeSeries, $invoice->folio, $extension);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function invoice(Request $request, int $payment, PaymentInvoiceAmounts $amounts)
    {
        $user = $request->user();
        $record = $this->eligibleInvoicePayment($request, $payment);

        $regimenesFiscales = config('constantes.regimenes_fiscales', []);
        $regimenFiscal = $regimenesFiscales[$record->empresa?->regimen_fiscal]['descripcion'] ?? null;
        $empresa = $record->empresa;
        $invoiceValidationErrors = $this->invoiceCompanyValidationErrors($empresa);

        $canEditCompany = $user->empresas()
            ->wherePivot('control_total', true)
            ->where('empresas.id', $record->empresa_id)
            ->exists();

        return view('payments.invoice', [
            'payment' => $record,
            'regimenFiscal' => $regimenFiscal,
            'invoiceAmounts' => $amounts->fromTaxInclusiveTotal((string) $record->cantidad),
            'usosCfdi' => config('constantes.uso_cfdi', []),
            'formasPagoSat' => config('constantes.formas_pago_sat', []),
            'invoiceValidationErrors' => $invoiceValidationErrors,
            'canGenerateInvoice' => $invoiceValidationErrors === [],
            'canEditCompany' => $canEditCompany,
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'payment-history',
        ]);
    }

    public function storeInvoiceOptions(Request $request, int $payment, \App\Services\PaymentInvoiceIssuingService $issuing)
    {
        $record = $this->eligibleInvoicePayment($request, $payment);
        $invoiceValidationErrors = $this->invoiceCompanyValidationErrors($record->empresa);

        if ($invoiceValidationErrors !== []) {
            return redirect()->route('payment-history.invoice', $record->id);
        }

        $formasPagoSat = config('constantes.formas_pago_sat', []);
        $usosCfdi = config('constantes.uso_cfdi', []);
        $validated = $request->validate([
            'uso_cfdi' => ['required', 'string', Rule::in(array_keys($usosCfdi))],
            'forma_pago_sat' => ['required', 'string', Rule::in(array_keys($formasPagoSat))],
            'correo_facturacion' => ['required', 'email', 'max:255'],
        ], [
            'uso_cfdi.required' => 'Selecciona el uso de CFDI.',
            'uso_cfdi.in' => 'Selecciona un uso de CFDI válido.',
            'forma_pago_sat.required' => 'Selecciona la forma de pago con la que se realizó el cobro.',
            'forma_pago_sat.in' => 'Selecciona una forma de pago SAT válida.',
            'correo_facturacion.required' => 'Captura el correo al que enviaremos la factura.',
            'correo_facturacion.email' => 'Captura un correo válido para recibir la factura.',
        ]);

        try {
            $invoice = $issuing->issue($record, $validated, (int) $request->user()->id);
        } catch (\RuntimeException $exception) {
            $fresh = \App\Models\Factura::query()->where('intento_pago_id', $record->id)->first();
            if ($fresh?->estado === 'requiere_revision') {
                return redirect()->route('payment-history.index')->with('error', 'SIFEI no confirmó el resultado. El intento se bloqueó para evitar un CFDI duplicado; soporte revisará el estado antes de volver a timbrar.');
            }

            if ($fresh?->uuid) {
                return redirect()->route('payment-history.index')->with('status', 'La factura fue timbrada, puedes consultar en tu correo o en los enlaces correspondientes.');
            }

            return redirect()->route('payment-history.invoice', $record->id)
                ->withInput($request->only(['uso_cfdi', 'forma_pago_sat', 'correo_facturacion']))
                ->with('error', $exception->getMessage());
        }

        $message = $invoice->estado === 'requiere_revision'
            ? 'SIFEI no confirmó el resultado. El intento se bloqueó para evitar un CFDI duplicado; soporte revisará el estado antes de volver a timbrar.'
            : 'La factura fue timbrada, puedes consultar en tu correo o en los enlaces correspondientes.';

        return redirect()->route('payment-history.index')->with('status', $message);
    }

    private function eligibleInvoicePayment(Request $request, int $payment): IntentoPago
    {
        $user = $request->user();
        abort_unless((int) $user->profile === 2, 403);

        $companyIds = $user->empresas()->pluck('empresas.id');
        $record = IntentoPago::query()
            ->with('empresa')
            ->whereIn('empresa_id', $companyIds)
            ->findOrFail($payment);

        abort_unless(
            $record->resultado === 'exitoso'
                && (int) $record->factura === 0
                && $record->intentado_en?->isCurrentMonth(),
            404
        );

        return $record;
    }

    private function invoiceCompanyValidationErrors(Empresa $empresa): array
    {
        $errors = [];
        $rfc = mb_strtoupper(trim((string) $empresa->rfc), 'UTF-8');

        if (preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc) !== 1) {
            $errors[] = 'No es posible generar la factura porque el RFC de la empresa no está capturado o tiene un formato inválido (12 o 13 caracteres).';
        }

        $regimenesFiscales = config('constantes.regimenes_fiscales', []);
        if (!isset($regimenesFiscales[(string) $empresa->regimen_fiscal])) {
            $errors[] = 'No es posible generar la factura porque la empresa no tiene un régimen fiscal válido seleccionado.';
        }

        if (preg_match('/^[0-9]{5}$/', trim((string) $empresa->domicilio_codigo_postal)) !== 1) {
            $errors[] = 'No es posible generar la factura porque el código postal fiscal no está capturado o no contiene cinco dígitos.';
        }

        return $errors;
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
