@extends('layouts.dashboard', ['pageTitle' => 'Reporte por departamento', 'activeSection' => 'reportes-departamento'])

@section('content')
<style>
    .department-report-page { width:100%; }
    .department-report-heading { margin-bottom:1.25rem; }
    .department-report-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .department-report-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .department-report-filters { display:grid; grid-template-columns:minmax(240px,1fr) minmax(0,1fr) minmax(0,1fr) auto; align-items:end; gap:1rem; width:100%; }
    .department-report-filter { min-width:0; }
    .department-report-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .department-report-filter .hr-input { width:100%; }
    .department-report-actions { display:flex; gap:.5rem; white-space:nowrap; }
    .department-report-actions a { text-decoration:none; }
    .department-report-summary { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.875rem; margin:1.25rem 0; }
    .department-report-widget { display:flex; align-items:flex-start; gap:.8rem; min-width:0; padding:1rem 1.05rem; border:1px solid var(--card-border); border-radius:.95rem; background:var(--card-bg); transition:transform .18s ease,box-shadow .18s ease; }
    a.department-report-widget { color:inherit; text-decoration:none; }
    .department-report-widget:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(27,35,72,.08); }
    .department-report-widget-icon { display:flex; align-items:center; justify-content:center; width:42px; height:42px; flex:none; border-radius:.8rem; font-size:1rem; }
    .department-report-widget-value { color:var(--body-text); font-size:1.25rem; font-weight:900; line-height:1.15; }
    .department-report-widget-label { margin-top:.25rem; color:var(--body-text-muted); font-size:.68rem; font-weight:700; letter-spacing:.055em; text-transform:uppercase; }
    .department-report-widget-note { margin-top:.35rem; font-size:.72rem; font-weight:700; }
    .department-report-table { min-width:700px; }
    .department-report-table th,.department-report-table td { vertical-align:middle; }
    .department-report-table td:not(:first-child),.department-report-table th:not(:first-child) { text-align:right; }
    .department-report-name { color:var(--body-text); font-weight:700; }
    .department-report-count-link { color:var(--color-primary); font-weight:700; text-decoration:none; }
    .department-report-count-link:hover { text-decoration:underline; }
    .department-report-period { margin:.25rem 0 0; color:var(--body-text-muted); font-size:.8rem; }
    .department-report-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .department-report-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .department-report-empty p { margin:0; font-size:.845rem; }
    @media(max-width:1200px) { .department-report-summary { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:1050px) { .department-report-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .department-report-actions { grid-column:1 / -1; } .department-report-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:650px) { .department-report-filters,.department-report-summary { grid-template-columns:1fr; } .department-report-actions { grid-column:auto; } .department-report-widget { padding:.85rem .95rem; } }
</style>

<section class="department-report-page">
    <header class="department-report-heading"><h1>Reporte por departamento</h1><p>Compara las asistencias registradas en cada departamento de la empresa.</p></header>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Filtros del reporte</h2><p class="card-subtitle-hr">Selecciona una empresa y el periodo que deseas consultar.</p></div></div>
        <div class="card-bd">
            <form class="department-report-filters" method="GET" action="{{ route('reportes.departamento') }}">
                <div class="department-report-filter"><label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                    <select id="empresa_id" name="empresa_id" class="hr-input select2" required data-placeholder="Selecciona una empresa">
                        @if($companies->count() !== 1)<option value="">Selecciona una empresa</option>@endif
                        @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) $selectedCompanyId === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="department-report-filter"><label for="desde">Desde <span class="text-danger">*</span></label><input class="hr-input" id="desde" name="desde" type="date" value="{{ $startDate }}" required></div>
                <div class="department-report-filter"><label for="hasta">Hasta <span class="text-danger">*</span></label><input class="hr-input" id="hasta" name="hasta" type="date" value="{{ $endDate }}" required></div>
                <div class="department-report-actions"><button type="submit" class="btn-hr btn-primary-hr"><i class="fa-light fa-magnifying-glass" aria-hidden="true"></i> Generar reporte</button><a href="{{ route('reportes.departamento') }}" class="btn-hr btn-outline-hr"><i class="fa-light fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
            </form>
        </div>
    </div>

    @if($reportGenerated)
        @php
            $totals = [
                'total' => $departments->sum(fn ($department) => (int) $department->total),
                'late' => $departments->sum(fn ($department) => (int) $department->late),
                'earlyDeparture' => $departments->sum(fn ($department) => (int) $department->early_departure),
                'outOfRange' => $departments->sum(fn ($department) => (int) $department->out_of_range),
                'withoutExit' => $departments->sum(fn ($department) => (int) $department->without_exit),
            ];
        @endphp
        @php($detailFilters = ['empresa_id' => $selectedCompanyId, 'desde' => $startDate, 'hasta' => $endDate])
        <div class="department-report-summary">
            <a class="department-report-widget" href="{{ route('reportes.departamento.detalle', $detailFilters + ['tipo' => 'total']) }}">
                <div class="department-report-widget-icon" style="background:rgba(79,110,247,.12);color:#4f6ef7"><i class="fa-light fa-clipboard-user" aria-hidden="true"></i></div>
                <div><div class="department-report-widget-value">{{ $totals['total'] }}</div><div class="department-report-widget-label">Registros</div><div class="department-report-widget-note" style="color:#4f6ef7">Asistencias del periodo</div></div>
            </a>
            <a class="department-report-widget" href="{{ route('reportes.departamento.detalle', $detailFilters + ['tipo' => 'late']) }}">
                <div class="department-report-widget-icon" style="background:rgba(245,158,11,.13);color:#d97706"><i class="fa-light fa-clock" aria-hidden="true"></i></div>
                <div><div class="department-report-widget-value">{{ $totals['late'] }}</div><div class="department-report-widget-label">Llegadas tarde</div><div class="department-report-widget-note" style="color:#d97706">Con minutos de retardo</div></div>
            </a>
            <a class="department-report-widget" href="{{ route('reportes.departamento.detalle', $detailFilters + ['tipo' => 'early_departure']) }}">
                <div class="department-report-widget-icon" style="background:rgba(139,92,246,.12);color:#7c3aed"><i class="fa-light fa-person-walking-arrow-right" aria-hidden="true"></i></div>
                <div><div class="department-report-widget-value">{{ $totals['earlyDeparture'] }}</div><div class="department-report-widget-label">Salidas anticipadas</div><div class="department-report-widget-note" style="color:#7c3aed">Con minutos de salida temprana</div></div>
            </a>
            <a class="department-report-widget" href="{{ route('reportes.departamento.detalle', $detailFilters + ['tipo' => 'out_of_range']) }}">
                <div class="department-report-widget-icon" style="background:rgba(239,68,68,.1);color:#dc3545"><i class="fa-light fa-location-dot" aria-hidden="true"></i></div>
                <div><div class="department-report-widget-value">{{ $totals['outOfRange'] }}</div><div class="department-report-widget-label">Fuera de rango</div><div class="department-report-widget-note" style="color:#b42318">Entrada o salida</div></div>
            </a>
            <a class="department-report-widget" href="{{ route('reportes.departamento.detalle', $detailFilters + ['tipo' => 'without_exit']) }}">
                <div class="department-report-widget-icon" style="background:rgba(6,182,212,.12);color:#0891b2"><i class="fa-light fa-person-walking-arrow-right" aria-hidden="true"></i></div>
                <div><div class="department-report-widget-value">{{ $totals['withoutExit'] }}</div><div class="department-report-widget-label">Sin salida</div><div class="department-report-widget-note" style="color:#0891b2">Entrada sin salida registrada</div></div>
            </a>
        </div>

        <div class="card-hr">
            <div class="card-hd"><div><h2 class="card-title-hr">Resumen por departamento</h2><p class="department-report-period">Periodo del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p></div></div>
            @if($departments->isNotEmpty())
                <div class="table-wrap"><table class="bs-table hover department-report-table">
                    <thead><tr><th>Departamento</th><th>Registros</th><th>Llegadas tarde</th><th>Salidas anticipadas</th><th>Fuera de rango</th><th>Sin salida</th></tr></thead>
                    <tbody>
                    @foreach($departments as $department)
                        @php($departmentDetailFilters = $detailFilters + ($department->id ? ['departamento_id' => $department->id] : ['sin_departamento' => 1]))
                        <tr>
                            <td class="department-report-name">{{ $department->nombre }}</td>
                            <td><a class="department-report-count-link" href="{{ route('reportes.departamento.detalle', $departmentDetailFilters + ['tipo' => 'total']) }}">{{ $department->total }}</a></td>
                            <td><a class="department-report-count-link" href="{{ route('reportes.departamento.detalle', $departmentDetailFilters + ['tipo' => 'late']) }}">{{ $department->late }}</a></td>
                            <td><a class="department-report-count-link" href="{{ route('reportes.departamento.detalle', $departmentDetailFilters + ['tipo' => 'early_departure']) }}">{{ $department->early_departure }}</a></td>
                            <td><a class="department-report-count-link" href="{{ route('reportes.departamento.detalle', $departmentDetailFilters + ['tipo' => 'out_of_range']) }}">{{ $department->out_of_range }}</a></td>
                            <td><a class="department-report-count-link" href="{{ route('reportes.departamento.detalle', $departmentDetailFilters + ['tipo' => 'without_exit']) }}">{{ $department->without_exit }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @else
                <div class="department-report-empty"><h2>No se encontraron departamentos</h2><p>No hay departamentos asociados a esta empresa.</p></div>
            @endif
        </div>
    @endif
</section>
@endsection
