@php
    $totals = [
        'total' => $departments->sum(fn ($department) => (int) $department->total),
        'late' => $departments->sum(fn ($department) => (int) $department->late),
        'earlyDeparture' => $departments->sum(fn ($department) => (int) $department->early_departure),
        'outOfRange' => $departments->sum(fn ($department) => (int) $department->out_of_range),
        'withoutExit' => $departments->sum(fn ($department) => (int) $department->without_exit),
    ];
@endphp
<div class="department-report-summary">
    <a class="department-report-widget" href="{{ $detailUrl(['tipo' => 'total']) }}">
        <div class="department-report-widget-icon" style="background:rgba(79,110,247,.12);color:#4f6ef7"><i class="{{ config('constantes.tipo_iconos') }} fa-clipboard-user" aria-hidden="true"></i></div>
        <div><div class="department-report-widget-value">{{ $totals['total'] }}</div><div class="department-report-widget-label">Registros</div><div class="department-report-widget-note" style="color:#4f6ef7">Asistencias del periodo</div></div>
    </a>
    <a class="department-report-widget" href="{{ $detailUrl(['tipo' => 'late']) }}">
        <div class="department-report-widget-icon" style="background:rgba(245,158,11,.13);color:#d97706"><i class="{{ config('constantes.tipo_iconos') }} fa-clock" aria-hidden="true"></i></div>
        <div><div class="department-report-widget-value">{{ $totals['late'] }}</div><div class="department-report-widget-label">Llegadas tarde</div><div class="department-report-widget-note" style="color:#d97706">Con minutos de retardo</div></div>
    </a>
    <a class="department-report-widget" href="{{ $detailUrl(['tipo' => 'early_departure']) }}">
        <div class="department-report-widget-icon" style="background:rgba(139,92,246,.12);color:#7c3aed"><i class="{{ config('constantes.tipo_iconos') }} fa-person-walking-arrow-right" aria-hidden="true"></i></div>
        <div><div class="department-report-widget-value">{{ $totals['earlyDeparture'] }}</div><div class="department-report-widget-label">Salidas anticipadas</div><div class="department-report-widget-note" style="color:#7c3aed">Con minutos de salida temprana</div></div>
    </a>
    <a class="department-report-widget" href="{{ $detailUrl(['tipo' => 'out_of_range']) }}">
        <div class="department-report-widget-icon" style="background:rgba(239,68,68,.1);color:#dc3545"><i class="{{ config('constantes.tipo_iconos') }} fa-location-dot" aria-hidden="true"></i></div>
        <div><div class="department-report-widget-value">{{ $totals['outOfRange'] }}</div><div class="department-report-widget-label">Fuera de rango</div><div class="department-report-widget-note" style="color:#b42318">Entrada o salida</div></div>
    </a>
    <a class="department-report-widget" href="{{ $detailUrl(['tipo' => 'without_exit']) }}">
        <div class="department-report-widget-icon" style="background:rgba(6,182,212,.12);color:#0891b2"><i class="{{ config('constantes.tipo_iconos') }} fa-person-walking-arrow-right" aria-hidden="true"></i></div>
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
                @php($departmentDetailFilters = ($department->id ? ['departamento_id' => $department->id] : ['sin_departamento' => 1]))
                <tr>
                    <td class="department-report-name">{{ $department->nombre }}</td>
                    <td><a class="department-report-count-link" href="{{ $detailUrl($departmentDetailFilters + ['tipo' => 'total']) }}">{{ $department->total }}</a></td>
                    <td><a class="department-report-count-link" href="{{ $detailUrl($departmentDetailFilters + ['tipo' => 'late']) }}">{{ $department->late }}</a></td>
                    <td><a class="department-report-count-link" href="{{ $detailUrl($departmentDetailFilters + ['tipo' => 'early_departure']) }}">{{ $department->early_departure }}</a></td>
                    <td><a class="department-report-count-link" href="{{ $detailUrl($departmentDetailFilters + ['tipo' => 'out_of_range']) }}">{{ $department->out_of_range }}</a></td>
                    <td><a class="department-report-count-link" href="{{ $detailUrl($departmentDetailFilters + ['tipo' => 'without_exit']) }}">{{ $department->without_exit }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @else
        <div class="department-report-empty"><h2>No se encontraron departamentos</h2><p>No hay departamentos asociados a esta empresa.</p></div>
    @endif
</div>
