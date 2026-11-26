<template data-table-total="{{ $unfilteredTotal }}" data-table-filtered="{{ $rows->total() }}"></template>
<div class="table-wrap">
    <table class="bs-table hover hours-report-fields" data-datatable="{{ config('constantes.itemsPorPagina') }}"
        data-per-page="{{ $rows->perPage() }}" data-page="{{ $rows->currentPage() }}" data-datatable-no-csv
        data-search-placeholder="Buscar personal o semana…" data-empty="No hay registros de asistencia para el periodo seleccionado.">
        <thead>
            <tr>
                <th scope="col" data-sort-key="personal">Personal</th>
                <th scope="col" data-sort-key="semana">Semana (lunes a domingo)</th>
                <th scope="col" class="text-end" data-sort-key="dias_registrados">Días registrados</th>
                <th scope="col" class="text-end" data-sort-key="dias_indeterminados">Días con tiempo indeterminado</th>
                @foreach(\App\Models\ReporteHorasTrabajadasFila::COLUMNAS_DURACION as $column => $label)
                    <th scope="col" class="text-end" data-sort-key="{{ $column }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->personal }}</td>
                    <td>{{ $row->semana->format('d/m/Y') }}</td>
                    <td class="text-end">{{ $row->dias_registrados }}</td>
                    <td class="text-end">{{ $row->dias_indeterminados }}</td>
                    @foreach(\App\Models\ReporteHorasTrabajadasFila::COLUMNAS_DURACION as $column => $label)
                        <td class="text-end">{{ \App\Models\Asistencia::formatoDuracion((int) $row->{$column}) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div data-datatable-fallback>
    @if($rows->isEmpty())
        <p class="card-bd mb-0">No hay resultados para los filtros seleccionados.</p>
    @endif
    @include('reportes.partials.horas-trabajadas-paginacion', ['paginator' => $rows])
</div>
