<template data-table-total="{{ $unfilteredTotal }}" data-table-filtered="{{ $rows->total() }}"></template>
<div class="table-wrap">
    <table class="bs-table hover ir-table" data-datatable="{{ config('constantes.itemsPorPagina') }}" data-per-page="{{ $rows->perPage() }}" data-page="{{ $rows->currentPage() }}" data-search-placeholder="Buscar incidencias…" data-datatable-no-csv data-empty="No se encontraron incidencias para los filtros seleccionados.">
        <thead>
            <tr><th data-sort-key="fecha">Fecha</th><th data-sort-key="empleado">Empleado</th><th data-sort-key="codigo">Código</th><th data-sort-key="tipo">Tipo de incidencia</th><th data-sort-key="detalle">Detalle</th></tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') }}</td>
                    <td>{{ $row['empleado'] }}</td>
                    <td>{{ $row['codigo'] ?: '-' }}</td>
                    <td>{{ $labels[$row['tipo']] }}</td>
                    <td>{{ $row['detalle'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if($rows->hasPages())<div class="ir-footer" data-datatable-fallback><span>Mostrando {{ $rows->firstItem() }}–{{ $rows->lastItem() }} de {{ $rows->total() }}</span><nav class="ir-pagination" aria-label="Paginación del reporte"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $rows->previousPageUrl() ?? '#' }}" @if($rows->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $rows->currentPage() }} de {{ $rows->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $rows->nextPageUrl() ?? '#' }}" @unless($rows->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></nav></div>@endif
@if(!$rows->count())
    <div class="ir-empty" data-datatable-fallback><h2>No se encontraron incidencias para los filtros seleccionados.</h2></div>
@endif
