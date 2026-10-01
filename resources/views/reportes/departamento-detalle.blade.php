@extends('layouts.dashboard', ['pageTitle' => $typeInfo['title'], 'activeSection' => 'reportes-departamento'])

@section('content')
<style>
    .department-detail-page { width:100%; }
    .department-detail-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .department-detail-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .department-detail-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .department-detail-back { flex:none; text-decoration:none; }
    .department-detail-context { display:flex; flex-wrap:wrap; gap:.5rem 1rem; margin-top:.65rem; color:var(--body-text-muted); font-size:.8rem; }
    .department-detail-context strong { color:var(--body-text); }
    .department-detail-table { min-width:1050px; }
    .department-detail-table th,.department-detail-table td { vertical-align:middle; }
    .department-detail-person strong { display:block; color:var(--body-text); font-weight:700; }
    .department-detail-person small { display:block; margin-top:.15rem; color:var(--body-text-muted); }
    .department-detail-status { display:inline-flex; padding:.25rem .55rem; border-radius:999px; color:#687087; background:rgba(120,130,150,.1); font-size:.73rem; font-weight:700; white-space:nowrap; }
    .department-detail-status.is-good { color:#087f5b; background:rgba(16,185,129,.1); }
    .department-detail-status.is-alert { color:#b42318; background:rgba(239,68,68,.08); }
    .department-detail-status.is-pending { color:#9a6700; background:rgba(234,179,8,.13); }
    .department-detail-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .department-detail-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .department-detail-empty p { margin:0; font-size:.845rem; }
    .department-detail-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .department-detail-pagination { display:flex; align-items:center; gap:.55rem; }
    .department-detail-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    @media(max-width:700px) { .department-detail-heading { align-items:flex-start; flex-direction:column; } .department-detail-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="department-detail-page">
    <header class="department-detail-heading">
        <div>
            <h1>{{ $typeInfo['title'] }}</h1>
            <p>{{ $typeInfo['description'] }}</p>
            <div class="department-detail-context">
                <span>Empresa: <strong>{{ $company->razon_social }}</strong></span>
                <span>Periodo: <strong>{{ \Carbon\Carbon::parse($filters['desde'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($filters['hasta'])->format('d/m/Y') }}</strong></span>
                @if($department)<span>Departamento: <strong>{{ $department->nombre }}</strong></span>@elseif($withoutDepartment)<span><strong>Sin departamento</strong></span>@endif
            </div>
        </div>
        <a class="btn-hr btn-outline-hr department-detail-back" href="{{ route('reportes.departamento', ['empresa_id' => $filters['empresa_id'], 'desde' => $filters['desde'], 'hasta' => $filters['hasta']]) }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Volver al reporte</a>
    </header>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Registros de asistencia</h2><p class="card-subtitle-hr">{{ $records->total() }} {{ $records->total() === 1 ? 'registro encontrado' : 'registros encontrados' }}</p></div></div>
        @if($records->count())
            <div class="table-wrap"><table class="bs-table hover department-detail-table">
                <thead><tr><th>Personal</th><th>Departamento</th><th>Fecha</th><th>Entrada</th><th>Retardo</th><th>Rango entrada</th><th>Salida</th><th>Salida temprana</th><th>Rango salida</th><th>Estado</th></tr></thead>
                <tbody>
                @foreach($records as $record)
                    @php
                        $employee = $record->personal;
                        $outOfRange = $record->rango_entrada === 'Fuera de rango' || $record->rango_salida === 'Fuera de rango';
                    @endphp
                    <tr>
                        <td class="department-detail-person"><strong>{{ $employee?->nombre ?? '—' }}</strong><small>{{ $company->razon_social }}</small></td>
                        <td>{{ $employee?->departamento?->nombre ?? 'Sin departamento' }}</td>
                        <td>{{ $record->fecha?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $record->llegada?->format('H:i') ?? '—' }}</td>
                        <td>{{ $record->minutos_tarde ? $record->minutos_tarde . ' min' : '0 min' }}</td>
                        <td>{{ $record->rango_entrada ?? '—' }}</td>
                        <td>{{ $record->salida?->format('H:i') ?? '—' }}</td>
                        <td>{{ $record->minutos_salida_temprano ? $record->minutos_salida_temprano . ' min' : '0 min' }}</td>
                        <td>{{ $record->rango_salida ?? '—' }}</td>
                        <td>
                            @if($outOfRange)<span class="department-detail-status is-alert">Fuera de rango</span>
                            @elseif($record->minutos_tarde > 0)<span class="department-detail-status is-pending">Llegada tarde</span>
                            @elseif($record->minutos_salida_temprano > 0)<span class="department-detail-status is-pending">Salida anticipada</span>
                            @elseif(!$record->salida)<span class="department-detail-status is-pending">Sin salida</span>
                            @else<span class="department-detail-status is-good">Completa</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($records->hasPages())
                <div class="department-detail-footer">
                    <span>Mostrando {{ $records->firstItem() }}–{{ $records->lastItem() }} de {{ $records->total() }}</span>
                    <div class="department-detail-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $records->previousPageUrl() ?? '#' }}" @if($records->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $records->currentPage() }} de {{ $records->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $records->nextPageUrl() ?? '#' }}" @unless($records->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div>
                </div>
            @endif
        @else
            <div class="department-detail-empty"><h2>No se encontraron registros</h2><p>No hay asistencias para este indicador con los filtros seleccionados.</p></div>
        @endif
    </div>
</section>
@endsection
