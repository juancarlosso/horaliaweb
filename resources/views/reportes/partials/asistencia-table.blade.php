<template data-table-total="{{ $unfilteredTotal }}" data-table-filtered="{{ $attendance->total() }}"></template>
<div class="table-wrap"><table class="bs-table hover report-table" data-datatable="{{ config('constantes.itemsPorPagina') }}" data-per-page="{{ $attendance->perPage() }}" data-page="{{ $attendance->currentPage() }}" data-search-placeholder="Buscar..." data-datatable-no-csv data-empty="No se encontraron asistencias para este periodo.">
    <thead><tr><th data-sort-key="nombre">Personal</th><th data-sort-key="fecha">Fecha</th><th data-sort-key="modalidad">Modalidad</th><th data-sort-key="llegada">Entrada</th><th data-sort-key="rango_entrada">Rango entrada</th><th data-sort-key="distancia_entrada">Distancia entrada</th><th data-sort-key="retardo">Tarde</th><th data-sort-key="salida">Salida</th><th data-sort-key="rango_salida">Rango salida</th><th data-sort-key="distancia_salida">Distancia salida</th><th data-sort-key="salida_temprana">Salida temprana</th><th>Tiempo de pausas</th><th>Tiempo productivo</th><th class="no-search">Detalle de pausas</th><th class="no-search">Evidencia entrada</th><th class="no-search">Evidencia salida</th></tr></thead>
    <tbody>
    @foreach($attendance as $record)
        @php
            $employee = $record->personal;
            $entryPhotoUrl = $record->fotoEntradaUrl();
            $exitPhotoUrl = $record->fotoSalidaUrl();
        @endphp
        <tr>
            <td><div class="report-employee"><strong>{{ $employee?->nombre ?? '—' }}</strong><small>{{ $employee?->empresa?->nombre ?? '—' }}</small></div></td>
            <td>{{ $record->fecha?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ ($record->modalidad ?? 'PRESENCIAL') === 'HOME_OFFICE' ? 'Home Office' : 'Presencial' }}</td>
            <td>{{ $record->llegada?->format('H:i') ?? '—' }}</td>
            <td><span class="report-status {{ $record->rango_entrada === 'En rango' ? 'is-ok' : ($record->rango_entrada === 'Fuera de rango' ? 'is-bad' : '') }}">{{ $record->rango_entrada ?? '—' }}</span></td>
            <td>{{ $record->distancia_entrada_metros !== null ? number_format((float) $record->distancia_entrada_metros, 0) . ' m' : '—' }}</td>
            <td class="report-minute">{{ $record->minutos_tarde ? $record->minutos_tarde . ' min' : '0 min' }}</td>
            <td>{{ $record->salida?->format('H:i') ?? '—' }}</td>
            <td><span class="report-status {{ $record->rango_salida === 'En rango' ? 'is-ok' : ($record->rango_salida === 'Fuera de rango' ? 'is-bad' : '') }}">{{ $record->rango_salida ?? '—' }}</span></td>
            <td>{{ $record->distancia_salida_metros !== null ? number_format((float) $record->distancia_salida_metros, 0) . ' m' : '—' }}</td>
            <td class="report-minute">{{ $record->minutos_salida_temprano ? $record->minutos_salida_temprano . ' min' : '0 min' }}</td>
            <td>{{ \App\Models\Asistencia::formatoDuracion($record->tiempoPausasMinutos()) }}</td>
            <td>{{ \App\Models\Asistencia::formatoDuracion($record->tiempoProductivoMinutos()) }}</td>
            <td><button type="button" class="btn-hr btn-outline-hr btn-sm-hr" data-pause-detail-url="{{ isset($snapshotReport) ? route('reportes.generados.pausas', ['reporte' => $snapshotReport, 'asistencia' => $record->id]) : route('reportes.asistencia.pausas', ['asistencia' => $record->id, 'empresa_id' => $selectedCompanyId, 'desde' => $startDate, 'hasta' => $endDate]) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-mug-hot" aria-hidden="true"></i> Ver pausas</button></td>
            <td><div class="report-evidence">
                @if($entryPhotoUrl)<button type="button" class="report-icon-action" data-photo-url="{{ $entryPhotoUrl }}" data-photo-title="Foto de entrada" aria-label="Ver foto de entrada de {{ $employee?->nombre }}" title="Ver foto de entrada"><i class="{{ config('constantes.tipo_iconos') }} fa-camera" aria-hidden="true"></i></button>@endif
                @if($record->latitud !== null && $record->longitud !== null)<a class="report-icon-action" href="https://www.google.com/maps?q={{ $record->latitud }},{{ $record->longitud }}" target="_blank" rel="noopener" aria-label="Ver ubicación de entrada de {{ $employee?->nombre }}" title="Ver ubicación de entrada"><i class="{{ config('constantes.tipo_iconos') }} fa-location-dot" aria-hidden="true"></i></a>@endif
                @if(!$entryPhotoUrl && ($record->latitud === null || $record->longitud === null))—@endif
            </div></td>
            <td><div class="report-evidence">
                @if($exitPhotoUrl)<button type="button" class="report-icon-action" data-photo-url="{{ $exitPhotoUrl }}" data-photo-title="Foto de salida" aria-label="Ver foto de salida de {{ $employee?->nombre }}" title="Ver foto de salida"><i class="{{ config('constantes.tipo_iconos') }} fa-camera" aria-hidden="true"></i></button>@endif
                @if($record->latitud_salida !== null && $record->longitud_salida !== null)<a class="report-icon-action" href="https://www.google.com/maps?q={{ $record->latitud_salida }},{{ $record->longitud_salida }}" target="_blank" rel="noopener" aria-label="Ver ubicación de salida de {{ $employee?->nombre }}" title="Ver ubicación de salida"><i class="{{ config('constantes.tipo_iconos') }} fa-location-dot" aria-hidden="true"></i></a>@endif
                @if(!$exitPhotoUrl && ($record->latitud_salida === null || $record->longitud_salida === null))—@endif
            </div></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@if($attendance->hasPages())<div class="report-footer" data-datatable-fallback><span>Mostrando {{ $attendance->firstItem() }}–{{ $attendance->lastItem() }} de {{ $attendance->total() }}</span><div class="report-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->previousPageUrl() ?? '#' }}" @if($attendance->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $attendance->currentPage() }} de {{ $attendance->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->nextPageUrl() ?? '#' }}" @unless($attendance->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>@endif
@if(!$attendance->count())
    <div class="report-empty" data-datatable-fallback><h2>No se encontraron asistencias</h2><p>No hay resultados para los filtros seleccionados.</p></div>
@endif
