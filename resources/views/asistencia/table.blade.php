<template data-table-total="{{ $unfilteredTotal }}" data-table-filtered="{{ $attendance->total() }}"></template>
<div class="table-wrap"><table class="bs-table hover" data-datatable="{{ config('constantes.itemsPorPagina') }}" data-per-page="{{ $attendance->perPage() }}" data-page="{{ $attendance->currentPage() }}" data-empty="No hay asistencias para este periodo.">
    <thead><tr><th data-sort-key="colaborador">Colaborador</th><th data-sort-key="fecha">Fecha</th><th data-sort-key="modalidad">Modalidad</th><th data-sort-key="llegada">Entrada</th><th data-sort-key="salida">Salida</th><th data-sort-key="minutos_tarde">Retardo</th><th data-sort-key="minutos_salida_temprano">Salida temprana</th><th>Rango</th></tr></thead>
    <tbody>
    @foreach($attendance as $record)
        @php
            $employee = $record->personal;
            $entryEvidenceUrl = $record->fotoEntradaUrl();
            $exitEvidenceUrl = $record->fotoSalidaUrl();
            $initial = mb_strtoupper(mb_substr(trim($employee?->nombre ?? ''), 0, 1)) ?: 'P';
        @endphp
        <tr>
            <td><div class="attendance-user"><div class="attendance-avatar"><span>{{ $initial }}</span>@if($employee)<img src="{{ $employee->avatarUrl() }}" alt="" loading="lazy" onerror="this.remove()">@endif</div><div><div class="attendance-user-name">{{ $employee?->nombre ?? 'No disponible' }}</div><div class="attendance-user-company">{{ $employee?->empresa?->nombre ?? '—' }}</div></div></div></td>
            <td>{{ $record->fecha?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ ($record->modalidad ?? 'PRESENCIAL') === 'HOME_OFFICE' ? 'Home Office' : 'Presencial' }}</td>
            <td><div class="attendance-time-cell"><span>{{ $record->llegada?->format('H:i') ?? '—' }}</span>
                @if($entryEvidenceUrl)<a class="attendance-icon-action" href="{{ $entryEvidenceUrl }}" target="_blank" rel="noopener" title="Ver foto de entrada" aria-label="Ver foto de entrada de {{ $employee?->nombre }}"><i class="{{ config('constantes.tipo_iconos') }} fa-camera" aria-hidden="true"></i></a>@endif
                @if($record->latitud !== null && $record->longitud !== null)<a class="attendance-icon-action" href="https://www.google.com/maps?q={{ $record->latitud }},{{ $record->longitud }}" target="_blank" rel="noopener" title="Ver ubicación de entrada" aria-label="Ver ubicación de entrada de {{ $employee?->nombre }}"><i class="{{ config('constantes.tipo_iconos') }} fa-location-dot" aria-hidden="true"></i></a>@endif
            </div></td>
            <td><div class="attendance-time-cell"><span>{{ $record->salida?->format('H:i') ?? '—' }}</span>
                @if($exitEvidenceUrl)<a class="attendance-icon-action" href="{{ $exitEvidenceUrl }}" target="_blank" rel="noopener" title="Ver foto de salida" aria-label="Ver foto de salida de {{ $employee?->nombre }}"><i class="{{ config('constantes.tipo_iconos') }} fa-camera" aria-hidden="true"></i></a>@endif
                @if($record->latitud_salida !== null && $record->longitud_salida !== null)<a class="attendance-icon-action" href="https://www.google.com/maps?q={{ $record->latitud_salida }},{{ $record->longitud_salida }}" target="_blank" rel="noopener" title="Ver ubicación de salida" aria-label="Ver ubicación de salida de {{ $employee?->nombre }}"><i class="{{ config('constantes.tipo_iconos') }} fa-location-dot" aria-hidden="true"></i></a>@endif
            </div></td>
            <td>{{ $record->minutos_tarde ? $record->minutos_tarde . ' min' : '—' }}</td>
            <td>{{ $record->minutos_salida_temprano ? $record->minutos_salida_temprano . ' min' : '—' }}</td>
            <td><div class="d-flex flex-column gap-1"><span class="attendance-pill {{ $record->rango_entrada === 'En rango' ? 'is-in-range' : ($record->rango_entrada === 'Fuera de rango' ? 'is-out-range' : '') }}">E: {{ $record->rango_entrada ?? '—' }}</span><span class="attendance-pill {{ $record->rango_salida === 'En rango' ? 'is-in-range' : ($record->rango_salida === 'Fuera de rango' ? 'is-out-range' : '') }}">S: {{ $record->rango_salida ?? '—' }}</span></div></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@if($attendance->hasPages())<div class="attendance-footer" data-datatable-fallback><span>Mostrando {{ $attendance->firstItem() }}–{{ $attendance->lastItem() }} de {{ $attendance->total() }}</span><div class="attendance-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->previousPageUrl() ?? '#' }}" @if($attendance->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $attendance->currentPage() }} de {{ $attendance->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->nextPageUrl() ?? '#' }}" @unless($attendance->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>@endif
@if(!$attendance->count())
    <div class="attendance-empty" data-datatable-fallback><h2>No hay asistencias para este periodo</h2><p>No se encontraron registros para {{ $periodLabel }}.</p></div>
@endif
