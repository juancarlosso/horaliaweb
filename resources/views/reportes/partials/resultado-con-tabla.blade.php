<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <a class="btn-hr btn-outline-hr" href="{{ $backUrl }}#historial-reportes">
        <i class="{{ config('constantes.tipo_iconos') }} fa-arrow-left" aria-hidden="true"></i> Volver al listado
    </a>
    @if($reporte->estado === \App\Models\ReporteGenerado::LISTO)
        <a class="btn-hr btn-outline-hr ms-auto" href="{{ $downloadUrl }}">
            <i class="{{ config('constantes.tipo_iconos') }} fa-file-excel" aria-hidden="true"></i> Exportar a Excel
        </a>
    @endif
</div>

<section class="card-hr">
    <div class="card-hd">
        <div class="d-flex gap-3 align-items-start">
            @if($logo = $reporte->empresa->logoUrl())
                <img src="{{ $logo }}" width="56" height="56" class="object-fit-contain" alt="">
            @endif
            <div>
                <h2 class="card-title-hr">{{ \App\Models\ReporteGenerado::TITULOS[$reporte->tipo] }}</h2>
                <p class="card-subtitle-hr mb-1">
                    <strong>{{ $reporte->empresa->nombre ?: $reporte->empresa->razon_social }}</strong>
                    ({{ $reporte->desde->format('d/m/Y') }} al {{ $reporte->hasta->format('d/m/Y') }})
                </p>
                @if(! empty($reporte->filtros['tipos']))
                    <p class="card-subtitle-hr mb-0">{{ implode(', ', array_map(fn ($type) => \App\Services\ReporteIncidenciasService::LABELS[$type], $reporte->filtros['tipos'])) }}</p>
                @endif
            </div>
        </div>
    </div>

    @if($reporte->estado === \App\Models\ReporteGenerado::LISTO)
        <div data-datatable-source>
            @include($table)
        </div>
    @else
        @include('reportes.partials.generado-estado')
    @endif
</section>
