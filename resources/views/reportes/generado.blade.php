@extends('layouts.dashboard', ['pageTitle' => \App\Models\ReporteGenerado::TITULOS[$reporte->tipo], 'activeSection' => 'reportes-'.$reporte->tipo])

@push('scripts')
    @if($reporte->enProceso())
        <script defer src="{{ asset('assets/horalia/js/report-hours-status.js') }}?v=2"></script>
    @endif
@endpush

@section('content')
    @if(isset($table))
        @include('components.datatable-assets')
    @endif
    @include('reportes.partials.'.($reporte->tipo === 'general' ? 'asistencia' : $reporte->tipo).'-estilos')

    @if($reporte->tipo === \App\Models\ReporteGenerado::INCIDENCIAS)
        <div id="hours-report-result" data-reload-ready data-status-url="{{ route('reportes.generados.show', $reporte) }}">
            @include('reportes.partials.resultado-con-tabla', [
                'backUrl' => route('reportes.incidencias'),
                'downloadUrl' => route('reportes.generados.download', $reporte),
                'table' => 'reportes.partials.incidencias-table',
            ])
        </div>
    @else
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <a class="btn-hr btn-outline-hr" href="{{ route('reportes.'.$reporte->tipo) }}#historial-reportes">
                <i class="{{ config('constantes.tipo_iconos') }} fa-arrow-left" aria-hidden="true"></i> Volver al listado
            </a>
            @if($reporte->estado === \App\Models\ReporteGenerado::LISTO)
                <a class="btn-hr btn-outline-hr ms-auto" href="{{ route('reportes.generados.download', $reporte) }}">
                    <i class="{{ config('constantes.tipo_iconos') }} fa-file-excel" aria-hidden="true"></i> Exportar a Excel
                </a>
            @endif
        </div>

        <section class="card-hr mb-3">
            <div class="card-hd">
                <div>
                    <h2 class="card-title-hr">{{ \App\Models\ReporteGenerado::TITULOS[$reporte->tipo] }}</h2>
                    <p class="card-subtitle-hr mb-1">
                        <strong>{{ $reporte->empresa->nombre ?: $reporte->empresa->razon_social }}</strong>
                        ({{ $reporte->desde->format('d/m/Y') }} al {{ $reporte->hasta->format('d/m/Y') }})
                    </p>
                </div>
            </div>
            @if($reporte->estado !== \App\Models\ReporteGenerado::LISTO)
                <div id="hours-report-result" data-reload-ready data-status-url="{{ route('reportes.generados.show', $reporte) }}">
                    @include('reportes.partials.generado-estado')
                </div>
            @endif
        </section>

        @if(isset($table))
            @if($reporte->tipo === \App\Models\ReporteGenerado::GENERAL)
                @include('reportes.partials.asistencia-resumen', ['summary' => $summary + [
                    'outOfRange' => $summary['out_of_range'], 'withoutExit' => $summary['without_exit'],
                ]])
                @include('reportes.partials.asistencia-modales')
            @endif
            <div class="card-hr" data-datatable-source>
                <div class="card-hd">
                    <h2 class="card-title-hr">Detalle de asistencias ({{ $unfilteredTotal }})</h2>
                </div>
                @include($table)
            </div>
        @elseif($reporte->tipo === \App\Models\ReporteGenerado::DEPARTAMENTO && $reporte->estado === \App\Models\ReporteGenerado::LISTO)
            @include('reportes.partials.departamento-resumen', [
                'departments' => collect($reporte->resumen['departamentos'])->map(fn ($department) => (object) $department),
                'startDate' => $reporte->desde->toDateString(),
                'endDate' => $reporte->hasta->toDateString(),
                'detailUrl' => fn (array $filters) => route('reportes.generados.departamento', $filters + ['reporte' => $reporte]),
            ])
        @endif
    @endif
@endsection
