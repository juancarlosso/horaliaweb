@extends('layouts.dashboard', ['pageTitle' => 'Historial de pagos', 'activeSection' => 'payment-history'])

@section('content')
<style>
    .payment-history { width:100%; }
    .payment-history h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .payment-history .subtitle { margin:0 0 1.25rem; color:var(--body-text-muted); font-size:.875rem; }
    .payment-filters { box-sizing:border-box; display:grid; grid-template-columns:minmax(280px,2fr) minmax(170px,1.2fr) repeat(2,minmax(150px,1fr)) auto auto; align-items:end; gap:.75rem; width:100%; padding:1rem 1.25rem; }
    .payment-filters label { display:grid; min-width:0; gap:.35rem; color:var(--body-text-muted); font-size:.78rem; font-weight:650; }
    .payment-filters input { width:100%; min-width:0; min-height:38px; padding:.4rem .65rem; border:1px solid var(--body-border); border-radius:.5rem; background:var(--card-bg); color:var(--body-text); }
    @media(max-width:1100px) { .payment-filters { grid-template-columns:minmax(220px,2fr) repeat(3,minmax(130px,1fr)); } }
    @media(max-width:700px) { .payment-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .payment-filters label:first-child { grid-column:1 / -1; } .payment-filters button,.payment-filters > a { width:100%; justify-content:center; } }
    @media(max-width:450px) { .payment-filters { grid-template-columns:1fr; } .payment-filters label:first-child { grid-column:auto; } }
    .payment-status { display:inline-flex; padding:.25rem .6rem; border-radius:999px; font-size:.73rem; font-weight:750; white-space:nowrap; }
    .payment-status.success { color:#087f5b; background:rgba(16,185,129,.1); }
    .payment-status.failed { color:#b42318; background:rgba(239,68,68,.09); }
    .payment-status.pending { color:#9a6700; background:rgba(234,179,8,.13); }
    .payment-actions { display:inline-flex; align-items:center; gap:.35rem; }
    .payment-actions .btn-hr { box-sizing:border-box; height:34px; min-height:34px; }
    .payment-actions .payment-detail { width:34px; padding:0 !important; justify-content:center; color:#536df5; font-size:1rem; }
    .payment-actions .payment-invoice { padding:0 .75rem !important; }
    .payment-actions .payment-document { min-width:46px; padding:0 .55rem !important; justify-content:center; }
    .payment-actions .payment-document[aria-disabled="true"] { opacity:.55; cursor:wait; }
    .payment-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .payment-footer { display:flex; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    @media(max-width:700px) { .payment-footer { flex-direction:column; } }
</style>
<section class="payment-history">
    <h1>Historial de pagos</h1>
    <p class="subtitle">Pagos confirmados e intentos de cobro de tus empresas.</p>
    <div class="card-hr">
        <form class="payment-filters" method="GET" action="{{ route('payment-history.index') }}">
            <label>Empresa
                <select class="hr-input select2" id="empresa_id" name="empresa_id" aria-label="Empresa"><option value="">Todas las empresas</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) ($filters['empresa_id'] ?? '') === (string) $company->id)>{{ $company->razon_social ?: 'Empresa #' . $company->id }}</option>@endforeach</select>
            </label>
            <label>Estado
                <select class="hr-input select2" id="estado" name="estado" aria-label="Estado"><option value="">Todos</option><option value="exitosos" @selected(($filters['estado'] ?? '') === 'exitosos')>Exitosos</option><option value="fallidos" @selected(($filters['estado'] ?? '') === 'fallidos')>Fallidos</option></select>
            </label>
            <label>Desde<input type="date" name="desde" value="{{ $filters['desde'] ?? '' }}"></label>
            <label>Hasta<input type="date" name="hasta" value="{{ $filters['hasta'] ?? '' }}"></label>
            <button class="btn-hr btn-primary-hr" type="submit">Filtrar</button>
            @if($filters)<a class="btn-hr btn-outline-hr" href="{{ route('payment-history.index') }}">Limpiar</a>@endif
        </form>
        @if($payments->count())
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Folio</th><th>Fecha y hora</th><th>Empresa</th><th>Concepto</th><th>Importe</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>@foreach($payments as $payment)
                    @php($statusClass = $payment->resultado === 'exitoso' ? 'success' : ($payment->resultado === 'fallido' ? 'failed' : 'pending'))
                    @php($statusLabel = $payment->resultado === 'exitoso' ? 'Exitoso' : ($payment->resultado === 'fallido' ? 'Rechazado' : 'Pendiente'))
                    <tr>
                        <td>{{ $payment->resultado === 'exitoso' ? ($payment->folio ?: '—') : '—' }}</td>
                        <td>{{ $payment->intentado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $payment->empresa?->razon_social ?: 'Empresa #' . $payment->empresa_id }}</td>
                        <td>{{ $payment->concepto }}</td>
                        <td>${{ number_format((float) $payment->cantidad, 2) }} {{ strtoupper($payment->moneda) }}</td>
                        <td><span class="payment-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td style="white-space:nowrap">
                            <div class="payment-actions">
                            <a class="btn-hr btn-outline-hr btn-sm-hr payment-detail" href="{{ route('payment-history.show', array_merge(['payment' => $payment->id], request()->query())) }}" aria-label="Ver detalle" title="Ver detalle"><i class="fa-light fa-circle-info" aria-hidden="true"></i></a>
                            @if((int) $payment->factura === 1 && $payment->facturaEmitida?->uuid)
                                @foreach(['pdf' => 'PDF', 'xml' => 'XML'] as $format => $label)
                                    @php($documentPath = $format === 'pdf' ? $payment->facturaEmitida->pdf_path : $payment->facturaEmitida->xml_path)
                                    @if($documentPath)
                                        <a class="btn-hr btn-outline-hr btn-sm-hr payment-document" href="{{ route('payment-history.invoice.document', ['payment' => $payment->id, 'format' => $format]) }}" target="_blank" rel="noopener" aria-label="Abrir {{ $label }} de la factura en una pestaña nueva" title="Abrir {{ $label }} en una pestaña nueva">{{ $label }}</a>
                                    @else
                                        <span class="btn-hr btn-outline-hr btn-sm-hr payment-document" aria-disabled="true" title="El archivo se está preparando">{{ $label }}</span>
                                    @endif
                                @endforeach
                            @endif
                            @if($payment->resultado === 'exitoso' && (int) $payment->factura === 0 && $payment->intentado_en?->isCurrentMonth())
                                <a class="btn-hr btn-outline-hr btn-sm-hr payment-invoice" href="{{ route('payment-history.invoice', array_merge(['payment' => $payment->id], request()->query())) }}">Facturar</a>
                            @endif
                            </div>
                        </td>
                    </tr>
                @endforeach</tbody>
            </table></div>
            @if($payments->hasPages())<nav class="payment-footer" aria-label="Paginación del historial"><span>Mostrando {{ $payments->firstItem() }}–{{ $payments->lastItem() }} de {{ $payments->total() }}</span>{{ $payments->links() }}</nav>@endif
        @else
            <div class="payment-empty">No hay movimientos de cobro para los filtros seleccionados.</div>
        @endif
    </div>
</section>
@endsection
