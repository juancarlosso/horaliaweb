@extends('layouts.dashboard', ['pageTitle' => 'Detalle del pago', 'activeSection' => 'payment-history'])

@section('content')
<style>
    .payment-detail-grid { padding:1.25rem; display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1.1rem; }
    .payment-method-field { grid-column:2 / -1; }
    .payment-method-field.is-successful { grid-column:3 / -1; }
    .payment-reason-field { grid-column:2 / 5; }
    .payment-attempt-number-field { grid-column:5; }
    @media(max-width:1100px) { .payment-detail-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .payment-method-field { grid-column:auto; } }
    @media(max-width:1100px) { .payment-reason-field { grid-column:2 / -1; } .payment-attempt-number-field { grid-column:3; } }
    @media(max-width:700px) { .payment-detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .payment-method-field,.payment-method-field.is-successful { grid-column:span 2; } }
    @media(max-width:700px) { .payment-reason-field { grid-column:1 / -1; } .payment-attempt-number-field { grid-column:2; } }
    @media(max-width:480px) { .payment-detail-grid { grid-template-columns:1fr; } .payment-method-field,.payment-method-field.is-successful { grid-column:auto; } }
    @media(max-width:480px) { .payment-reason-field,.payment-attempt-number-field { grid-column:auto; } }
</style>
<section style="width:100%;max-width:none">
    <header style="display:flex;justify-content:space-between;align-items:start;gap:1rem;margin-bottom:1.25rem">
        <div><h1 style="margin:0 0 .35rem;color:var(--body-text);font-size:1.5rem;font-weight:800">Detalle del movimiento</h1><p style="margin:0;color:var(--body-text-muted)">{{ $payment->empresa?->razon_social ?: 'Empresa #' . $payment->empresa_id }}</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('payment-history.index', request()->query()) }}" style="display:inline-flex;align-items:center;gap:.5rem"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>Regresar al historial</a>
    </header>
    <div class="card-hr"><div class="card-hd"><h2 class="card-title-hr">{{ $payment->resultado === 'exitoso' ? 'Pago confirmado' : ($payment->resultado === 'fallido' ? 'Intento rechazado' : 'Cobro pendiente de confirmación') }}</h2></div>
        <div class="payment-detail-grid">
            @if($payment->resultado === 'exitoso')<div><strong>Folio</strong><div>{{ $payment->folio ?: 'Pendiente de regularización' }}</div></div>@endif
            <div><strong>Empresa</strong><div>{{ $payment->empresa?->razon_social ?: 'Empresa #' . $payment->empresa_id }}</div></div>
            <div><strong>Fecha y hora</strong><div>{{ $payment->intentado_en?->format('d/m/Y H:i:s') ?? '—' }}</div></div>
            <div><strong>Concepto</strong><div>{{ $payment->concepto }}</div></div>
            <div><strong>Periodo de renovación</strong><div>{{ $payment->fecha_renovacion?->format('d/m/Y') ?? '—' }}</div></div>
            <div><strong>Importe</strong><div>${{ number_format((float) $payment->cantidad, 2) }} {{ strtoupper($payment->moneda) }}</div></div>
            <div><strong>Estado</strong><div>{{ $payment->resultado === 'exitoso' ? 'Exitoso' : ($payment->resultado === 'fallido' ? 'Rechazado' : 'Pendiente de confirmación') }}</div></div>
            <div class="payment-method-field {{ $payment->resultado === 'exitoso' ? 'is-successful' : '' }}"><strong>Método de pago</strong><div>{{ $payment->tarjeta_marca && $payment->tarjeta_ultimos4 ? ucfirst($payment->tarjeta_marca) . ' ****' . $payment->tarjeta_ultimos4 : 'No hay datos de marca y últimos cuatro dígitos disponibles.' }}</div></div>
            @if($payment->resultado !== 'exitoso')<div><strong>Referencia técnica</strong><div>{{ $payment->transaccion_id ?: '—' }}</div></div>@endif
            @if($payment->resultado !== 'exitoso')
                <div class="payment-reason-field"><strong>Motivo</strong><div>{{ $payment->resultado === 'fallido' ? $safeFailureReason : 'El proveedor aún no confirma el resultado del cobro.' }}</div></div>
                <div class="payment-attempt-number-field"><strong>Número de intento</strong><div>{{ $attemptNumber ? '#' . $attemptNumber : '—' }}</div></div>
            @endif
        </div>
    </div>
    @if($renewalMovements->count() > 1)
        <div class="card-hr" style="margin-top:1rem"><div class="card-hd"><h2 class="card-title-hr">Intentos de esta renovación</h2></div>
            <div style="padding:0 1.25rem 1.25rem;color:var(--body-text-muted);font-size:.85rem">
                @if($renewalMovements->contains(fn ($movement) => $movement->resultado === 'exitoso'))<p>Esta renovación quedó pagada; los rechazos anteriores son intentos de cobro, no mensualidades adicionales.</p>@endif
                <ul>@foreach($renewalMovements as $movement)<li>{{ $movement->intentado_en?->format('d/m/Y H:i') ?? '—' }} · {{ $movement->resultado === 'exitoso' ? 'Exitoso' : ($movement->resultado === 'fallido' ? 'Rechazado' : 'Pendiente') }} · {{ $movement->resultado === 'exitoso' ? ($movement->folio ?: 'Pago sin folio') : 'Sin folio' }}</li>@endforeach</ul>
            </div>
        </div>
    @endif
</section>
@endsection
