@extends('layouts.dashboard', ['pageTitle' => 'Facturar pago', 'activeSection' => 'payment-history'])

@section('content')
<style>
    .invoice-page { width:100%; }
    .invoice-page h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .invoice-page .subtitle { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .invoice-page-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .invoice-company-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.1rem; padding:1.25rem; }
    .invoice-company-field { min-width:0; }
    .invoice-company-field.address { grid-column:span 1; }
    .invoice-company-field strong { display:block; margin-bottom:.25rem; color:var(--body-text-muted); font-size:.75rem; font-weight:700; }
    .invoice-company-field div { color:var(--body-text); font-size:.9rem; overflow-wrap:anywhere; }
    .invoice-fiscal-notice { display:flex; align-items:flex-start; gap:.65rem; margin:1rem 1.25rem 0; padding:.8rem 1rem; border:1px solid rgba(83,109,245,.18); border-radius:.625rem; color:var(--body-text); background:rgba(83,109,245,.055); font-size:.82rem; line-height:1.55; }
    .invoice-fiscal-notice i { margin-top:.1rem; color:#536df5; font-size:1rem; }
    .invoice-fiscal-notice strong { display:block; margin-bottom:.15rem; }
    .invoice-summary-meta { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; padding:1.25rem; }
    .invoice-summary-meta span { display:block; margin-bottom:.25rem; color:var(--body-text-muted); font-size:.75rem; font-weight:700; }
    .invoice-summary-meta strong { color:var(--body-text); font-size:.9rem; font-weight:650; overflow-wrap:anywhere; }
    .invoice-summary-table { width:100%; margin:0; border-collapse:collapse; color:var(--body-text); font-size:.85rem; }
    .invoice-summary-table th,.invoice-summary-table td { padding:.8rem 1.25rem; border-top:1px solid var(--body-border); text-align:left; }
    .invoice-summary-table th { color:var(--body-text-muted); font-size:.72rem; font-weight:700; }
    .invoice-summary-table .numeric { text-align:right; white-space:nowrap; }
    .invoice-summary-totals { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.45rem 2rem; width:min(100%,360px); padding:1rem 1.25rem 1.25rem; margin-left:auto; color:var(--body-text); font-size:.86rem; }
    .invoice-summary-totals dt,.invoice-summary-totals dd { margin:0; }
    .invoice-summary-totals dd { text-align:right; white-space:nowrap; }
    .invoice-summary-totals .grand-total { padding-top:.65rem; margin-top:.25rem; border-top:1px solid var(--body-border); font-size:1rem; font-weight:800; }
    .invoice-summary-note { margin:0; padding:0 1.25rem 1rem; color:var(--body-text-muted); font-size:.75rem; }
    .invoice-fiscal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; padding:1.25rem; }
    .invoice-fiscal-field { display:grid; min-width:0; gap:.4rem; }
    .invoice-fiscal-field label { color:var(--body-text-muted); font-size:.8rem; font-weight:650; }
    .invoice-fiscal-actions { display:flex; justify-content:flex-end; padding:0 1.25rem 1.25rem; }
    .invoice-field-error { display:block; color:#b42318; font-size:.775rem; }
    @media(max-width:800px) { .invoice-company-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .invoice-company-field.address { grid-column:span 1; } }
    @media(max-width:650px) { .invoice-summary-meta { grid-template-columns:1fr 1fr; } .invoice-summary-meta > :last-child { grid-column:1 / -1; } }
    @media(max-width:560px) { .invoice-page-header { align-items:flex-start; flex-direction:column; } .invoice-company-grid,.invoice-fiscal-grid,.invoice-summary-meta { grid-template-columns:1fr; } .invoice-company-field.address,.invoice-summary-meta > :last-child { grid-column:auto; } .invoice-summary-table th,.invoice-summary-table td { padding:.7rem .6rem; font-size:.75rem; } .invoice-summary-totals { gap:.45rem 1rem; } }
</style>
<section class="invoice-page">
    <header class="invoice-page-header">
        <div><h1>Facturar pago</h1><p class="subtitle">Revisa los datos de facturación de la empresa.</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('payment-history.index', request()->query()) }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    <div class="card-hr">
        <div class="card-hd" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <h2 class="card-title-hr">Datos de la empresa</h2>
            @if($canEditCompany)
                <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('empresas.edit', $payment->empresa_id) }}"><i class="fa-light fa-pen-to-square" aria-hidden="true"></i> Editar datos de la empresa</a>
            @endif
        </div>
        <div class="invoice-fiscal-notice" role="note">
            <i class="fa-light fa-circle-info" aria-hidden="true"></i>
            <div><strong>Verifica tus datos fiscales</strong>La razón social, RFC, régimen fiscal y código postal deben coincidir exactamente con los registrados en tu Constancia de Situación Fiscal del SAT. Los datos incorrectos pueden impedir la generación de tu factura.</div>
        </div>
        <div class="invoice-company-grid">
            <div class="invoice-company-field"><strong>Razón social</strong><div>{{ $payment->empresa?->razon_social ?: 'No capturada' }}</div></div>
            <div class="invoice-company-field"><strong>RFC</strong><div>{{ $payment->empresa?->rfc ?: 'No capturado' }}</div></div>
            <div class="invoice-company-field"><strong>Régimen fiscal</strong><div>{{ $payment->empresa?->regimen_fiscal ? $payment->empresa->regimen_fiscal . ($regimenFiscal ? ' — ' . $regimenFiscal : '') : 'No capturado' }}</div></div>
            <div class="invoice-company-field"><strong>Teléfono</strong><div>{{ $payment->empresa?->telefono ?: 'No capturado' }}</div></div>
            <div class="invoice-company-field address"><strong>Domicilio fiscal</strong><div>{{ collect([$payment->empresa?->domicilio_calle, $payment->empresa?->domicilio_numero_exterior ? 'No. ' . $payment->empresa->domicilio_numero_exterior : null, $payment->empresa?->domicilio_numero_interior ? 'Int. ' . $payment->empresa->domicilio_numero_interior : null, $payment->empresa?->domicilio_colonia, $payment->empresa?->domicilio_municipio, $payment->empresa?->domicilio_ciudad, $payment->empresa?->domicilio_estado])->filter()->implode(', ') ?: 'No capturado' }}</div></div>
            <div class="invoice-company-field"><strong>Código Postal</strong><div>{{ $payment->empresa?->domicilio_codigo_postal ?: 'No capturado' }}</div></div>
        </div>
    </div>

    <div class="card-hr" style="margin-top:1rem">
        <div class="card-hd"><h2 class="card-title-hr">Resumen de facturación</h2></div>
        <div class="invoice-summary-meta">
            <div><span>Folio del pago</span><strong>{{ $payment->folio ?: '—' }}</strong></div>
            <div><span>Fecha y hora</span><strong>{{ $payment->intentado_en?->format('d/m/Y H:i') ?? '—' }}</strong></div>
            <div><span>Empresa</span><strong>{{ $payment->empresa?->razon_social ?: 'Empresa #' . $payment->empresa_id }}</strong></div>
        </div>
        <div class="table-wrap">
            <table class="invoice-summary-table">
                <thead><tr><th>Cantidad</th><th>Concepto</th><th class="numeric">Precio unitario</th><th class="numeric">Importe</th></tr></thead>
                <tbody><tr><td>1</td><td>{{ $payment->concepto ?: 'Membresía Horalia' }}</td><td class="numeric">${{ number_format((float) $invoiceAmounts['subtotal'], 2) }}</td><td class="numeric">${{ number_format((float) $invoiceAmounts['subtotal'], 2) }}</td></tr></tbody>
            </table>
        </div>
        <dl class="invoice-summary-totals">
            <dt>Subtotal</dt><dd>${{ number_format((float) $invoiceAmounts['subtotal'], 2) }}</dd>
            <dt>IVA ({{ $invoiceAmounts['iva_rate'] }}%)</dt><dd>${{ number_format((float) $invoiceAmounts['iva'], 2) }}</dd>
            <dt class="grand-total">TOTAL</dt><dd class="grand-total">${{ number_format((float) $invoiceAmounts['total'], 2) }} {{ strtoupper($payment->moneda) }}</dd>
        </dl>
        <p class="invoice-summary-note">Desglose informativo calculado sobre el importe histórico cobrado. No representa un CFDI emitido o timbrado.</p>
    </div>

    <form id="invoice-options-form" method="POST" action="{{ route('payment-history.invoice.store', $payment->id) }}">
        @csrf
        <div class="card-hr" style="margin-top:1rem">
            <div class="card-hd"><h2 class="card-title-hr">Opciones fiscales para la facturación</h2></div>
            @if($invoiceValidationErrors)
                <div class="alert alert-danger" role="alert" style="margin:1rem 1.25rem 0">
                    <ul style="margin:0;padding-left:1.25rem">
                        @foreach($invoiceValidationErrors as $validationError)
                            <li>{{ $validationError }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="invoice-fiscal-grid">
                <div class="invoice-fiscal-field">
                    <label for="uso_cfdi">Uso de CFDI <span aria-hidden="true">*</span></label>
                    <select class="hr-input select2 @error('uso_cfdi') catalog-input-error @enderror" id="uso_cfdi" name="uso_cfdi" required aria-required="true">
                        <option value="">Selecciona el uso de CFDI</option>
                        @foreach($usosCfdi as $clave => $descripcion)
                            <option value="{{ $clave }}" @selected((string) old('uso_cfdi') === (string) $clave)>{{ $clave }} — {{ $descripcion }}</option>
                        @endforeach
                    </select>
                    @error('uso_cfdi')<span class="invoice-field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="invoice-fiscal-field">
                    <label for="forma_pago_sat">Forma de pago <span aria-hidden="true">*</span></label>
                    <select class="hr-input select2 @error('forma_pago_sat') catalog-input-error @enderror" id="forma_pago_sat" name="forma_pago_sat" aria-required="true" aria-describedby="forma-pago-error">
                        <option value="">Selecciona la forma de pago</option>
                        @foreach($formasPagoSat as $clave => $descripcion)
                            <option value="{{ $clave }}" @selected((string) old('forma_pago_sat', $payment->forma_pago_sat) === (string) $clave)>{{ $clave }} — {{ $descripcion }}</option>
                        @endforeach
                    </select>
                    <span class="invoice-field-error" id="forma-pago-error" role="alert" hidden>Selecciona una forma de pago.</span>
                    @error('forma_pago_sat')<span class="invoice-field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="invoice-fiscal-field">
                    <label for="correo_facturacion">Correo para facturación <span aria-hidden="true">*</span></label>
                    <input class="hr-input @error('correo_facturacion') catalog-input-error @enderror" id="correo_facturacion" name="correo_facturacion" type="email" value="{{ old('correo_facturacion', $payment->empresa?->correo_facturacion) }}" autocomplete="email" required>
                    @error('correo_facturacion')<span class="invoice-field-error" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            @if($canGenerateInvoice)
                <div class="invoice-fiscal-actions">
                    <button class="btn-hr btn-primary-hr" type="submit">Generar Factura</button>
                </div>
            @endif
        </div>
    </form>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('invoice-options-form');
        const select = document.getElementById('forma_pago_sat');
        const error = document.getElementById('forma-pago-error');

        if (!form || !select || !error) return;

        const setInvalid = (invalid) => {
            select.classList.toggle('catalog-input-error', invalid);
            error.hidden = !invalid;
            if (window.jQuery && window.jQuery(select).data('select2')) {
                window.jQuery(select).next('.select2-container').find('.select2-selection').attr('aria-invalid', invalid ? 'true' : 'false');
            }
        };

        form.addEventListener('submit', function (event) {
            if (!select.value) {
                event.preventDefault();
                setInvalid(true);
                if (window.jQuery && window.jQuery(select).data('select2')) window.jQuery(select).select2('open');
            }
        });

        if (window.jQuery) window.jQuery(select).on('change', function () { setInvalid(!select.value); });
    });
</script>
@endsection
