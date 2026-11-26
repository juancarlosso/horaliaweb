@if($reporte->enProceso())
    <div class="card-bd" data-report-pending role="status">
        <p class="mb-2">
            <span class="spinner-border spinner-border-sm me-1 text-primary" aria-hidden="true"></span>
            {{ $reporte->estado === \App\Models\ReporteGenerado::PENDIENTE ? 'Reporte en proceso' : 'Generando reporte…' }}
        </p>
        <p class="mb-0">Puedes salir de esta página y volver a abrir el reporte desde el historial.</p>
    </div>
@elseif($reporte->estado === \App\Models\ReporteGenerado::FALLIDO)
    <div class="card-bd" role="status">
        <p class="mb-2 fw-semibold text-danger">No se pudo generar el reporte.</p>
        <form method="POST" action="{{ $storeUrl ?? route('reportes.generados.store', ['tipoReporte' => $reporte->tipo]) }}">
            @csrf
            <input type="hidden" name="empresa_id" value="{{ $reporte->empresa_id }}">
            <input type="hidden" name="desde" value="{{ $reporte->desde->toDateString() }}">
            <input type="hidden" name="hasta" value="{{ $reporte->hasta->toDateString() }}">
            @foreach($reporte->filtros['tipos'] ?? [] as $type)
                <input type="hidden" name="tipos[]" value="{{ $type }}">
            @endforeach
            <button type="submit" class="btn-hr btn-outline-hr">Intentar de nuevo</button>
        </form>
    </div>
@endif
