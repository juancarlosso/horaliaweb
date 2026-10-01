@extends('layouts.dashboard', ['pageTitle' => 'Intentos de pago', 'activeSection' => 'empresas'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .payment-attempts-page { width:100%; }
    .payment-attempts-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .payment-attempts-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .payment-attempts-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .payment-attempts-heading .btn-hr svg { width:16px; height:16px; stroke:currentColor; stroke-width:1.8; }
    .attempt-result { display:inline-flex; align-items:center; padding:.25rem .6rem; border-radius:999px; font-size:.73rem; font-weight:750; white-space:nowrap; }
    .attempt-result.is-success { color:#087f5b; background:rgba(16,185,129,.1); }
    .attempt-result.is-failed { color:#b42318; background:rgba(239,68,68,.09); }
    .attempt-result.is-pending { color:#9a6700; background:rgba(234,179,8,.13); }
    .attempt-detail { display:block; max-width:420px; color:var(--body-text-muted); font-size:.78rem; white-space:normal; }
    .attempt-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .attempt-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .attempt-pagination { display:flex; align-items:center; gap:.5rem; }
    .attempt-footer [aria-disabled="true"] { cursor:default; opacity:.55; pointer-events:none; }
    @media(max-width:700px) { .payment-attempts-heading { align-items:stretch; flex-direction:column; } .payment-attempts-heading .btn-hr { align-self:flex-start; } .attempt-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="payment-attempts-page">
    <header class="payment-attempts-heading">
        <div>
            <h1>Intentos de pago</h1>
            <p>{{ $empresa->razon_social ?: 'Empresa #' . $empresa->id }} · {{ $attempts->total() }} {{ $attempts->total() === 1 ? 'intento registrado' : 'intentos registrados' }}</p>
        </div>
        <a class="btn-hr btn-outline-hr" href="{{ route('empresas.index') }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
            Regresar a empresas
        </a>
    </header>

    <div class="card-hr">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Historial de {{ $empresa->razon_social ?: 'la empresa' }}</h2>
            </div>
        </div>
        @if($attempts->count())
            <div class="table-wrap">
                <table class="bs-table hover">
                    <thead>
                        <tr>
                            <th scope="col">Fecha y hora</th>
                            <th scope="col">Concepto</th>
                            <th scope="col">Importe</th>
                            <th scope="col">Origen</th>
                            <th scope="col">Ejecución</th>
                            <th scope="col">Resultado</th>
                            <th scope="col">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            @php
                                $resultClass = match ($attempt->resultado) {
                                    'exitoso' => 'is-success',
                                    'fallido' => 'is-failed',
                                    default => 'is-pending',
                                };
                                $resultLabel = match ($attempt->resultado) {
                                    'exitoso' => 'Exitoso',
                                    'fallido' => 'Fallido',
                                    default => 'Pendiente',
                                };
                            @endphp
                            <tr>
                                <td>{{ $attempt->intentado_en?->format('d/m/Y H:i') ?? $attempt->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $attempt->concepto }}</td>
                                <td>${{ number_format((float) $attempt->cantidad, 2) }} {{ strtoupper($attempt->moneda) }}</td>
                                <td>{{ $attempt->origen === 'automatico' ? 'Automático' : 'Manual' }}</td>
                                <td>{{ $attempt->numero_ejecucion ? '#' . $attempt->numero_ejecucion : '—' }}</td>
                                <td><span class="attempt-result {{ $resultClass }}">{{ $resultLabel }}</span></td>
                                <td>
                                    <span class="attempt-detail">{{ $attempt->descripcion ?: '—' }}{{ $attempt->codigo_respuesta ? ' (' . $attempt->codigo_respuesta . ')' : '' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($attempts->hasPages())
                <nav class="attempt-footer" aria-label="Paginación de intentos de pago">
                    <span>Mostrando {{ $attempts->firstItem() }}–{{ $attempts->lastItem() }} de {{ $attempts->total() }}</span>
                    <div class="attempt-pagination">
                        @if($attempts->onFirstPage())
                            <span class="btn-hr btn-outline-hr btn-sm-hr" aria-disabled="true">Anterior</span>
                        @else
                            <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attempts->previousPageUrl() }}">Anterior</a>
                        @endif
                        <span>Página {{ $attempts->currentPage() }} de {{ $attempts->lastPage() }}</span>
                        @if($attempts->hasMorePages())
                            <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attempts->nextPageUrl() }}">Siguiente</a>
                        @else
                            <span class="btn-hr btn-outline-hr btn-sm-hr" aria-disabled="true">Siguiente</span>
                        @endif
                    </div>
                </nav>
            @endif
        @else
            <div class="attempt-empty">Esta empresa aún no tiene intentos de pago registrados.</div>
        @endif
    </div>
</section>
@endsection
