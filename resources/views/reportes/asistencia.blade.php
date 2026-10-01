@extends('layouts.dashboard', ['pageTitle' => 'General', 'activeSection' => 'reportes-general'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .attendance-report-page { width:100%; }
    .report-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .report-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .report-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .report-filters { display:grid; grid-template-columns:minmax(240px,1fr) minmax(0,1fr) minmax(0,1fr) auto; align-items:end; gap:1rem; width:100%; }
    .report-filter { min-width:0; }
    .report-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .report-filter .hr-input { width:100%; }
    .report-form-actions { display:flex; gap:.5rem; white-space:nowrap; }
    .report-form-actions .btn-hr,.report-export-btn { text-decoration:none; }
    .report-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin:1.25rem 0; }
    .report-summary-card { display:flex; align-items:center; gap:.9rem; min-height:102px; padding:1rem 1.1rem; border:1px solid var(--card-border); border-radius:.875rem; background:var(--card-bg); box-shadow:0 4px 14px rgba(27,35,72,.035); }
    .report-summary-icon { display:grid; place-items:center; width:42px; height:42px; flex:none; border-radius:.75rem; color:#536df5; background:rgba(83,109,245,.1); font-size:1rem; }
    .report-summary-icon.is-late { color:#c78300; background:rgba(245,158,11,.13); }
    .report-summary-icon.is-range { color:#dc3545; background:rgba(239,68,68,.1); }
    .report-summary-icon.is-exit { color:#0891b2; background:rgba(6,182,212,.1); }
    .report-summary-copy small { display:block; margin-bottom:.2rem; color:var(--body-text-muted); font-size:.78rem; }
    .report-summary-copy strong { color:var(--body-text); font-size:1.25rem; font-weight:800; }
    .report-card-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .report-card-heading p { margin:.25rem 0 0; color:var(--body-text-muted); font-size:.8rem; }
    .report-table { min-width:1120px; }
    .report-table th,.report-table td { vertical-align:middle; }
    .report-employee { min-width:180px; }
    .report-employee strong { display:block; color:var(--body-text); font-weight:700; }
    .report-employee small { display:block; margin-top:.2rem; color:var(--body-text-muted); }
    .report-status { display:inline-flex; padding:.25rem .55rem; border-radius:999px; color:#687087; background:rgba(120,130,150,.1); font-size:.73rem; font-weight:700; white-space:nowrap; }
    .report-status.is-ok { color:#087f5b; background:rgba(16,185,129,.1); }
    .report-status.is-bad { color:#b42318; background:rgba(239,68,68,.08); }
    .report-minute { white-space:nowrap; }
    .report-evidence { display:flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .report-icon-action { display:inline-grid; place-items:center; width:28px; height:28px; padding:0; border:1px solid var(--body-border); border-radius:.45rem; color:var(--body-text-muted); background:var(--card-bg); text-decoration:none !important; }
    .report-icon-action:hover { border-color:var(--color-primary); color:var(--color-primary); }
    .report-icon-action i { font-size:.8rem; }
    .report-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .report-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .report-empty p { margin:0; font-size:.845rem; }
    .report-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .report-pagination { display:flex; align-items:center; gap:.55rem; }
    .report-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    .report-photo-modal .modal-content { border:1px solid var(--card-border); border-radius:1rem; color:var(--body-text); background:var(--card-bg); }
    .report-photo-modal .modal-header,.report-photo-modal .modal-footer { border-color:var(--body-border); }
    .report-photo-modal .modal-body { background:var(--body-bg); text-align:center; }
    .report-photo-modal img { max-width:100%; max-height:70vh; border-radius:.65rem; }
    @media(max-width:1050px) { .report-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .report-form-actions { grid-column:1 / -1; } .report-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:650px) { .report-filters,.report-summary { grid-template-columns:1fr; } .report-form-actions { grid-column:auto; } .report-card-heading,.report-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="attendance-report-page">
    <header class="report-heading"><div><h1>Reporte general</h1><p>Consulta las asistencias, horarios, ubicaciones y evidencias de tu personal.</p></div></header>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Filtros del reporte</h2><p class="card-subtitle-hr">Selecciona una empresa y el periodo que deseas consultar.</p></div></div>
        <div class="card-bd">
            <form id="attendance-report-form" class="report-filters" method="GET" action="{{ route('reportes.general') }}">
                <div class="report-filter"><label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                    <select id="empresa_id" name="empresa_id" class="hr-input select2" required data-placeholder="Selecciona una empresa">
                        @if($companies->count() !== 1)<option value="">Selecciona una empresa</option>@endif
                        @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) $selectedCompanyId === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach
                    </select>
                </div>
                <div class="report-filter"><label for="desde">Desde <span class="text-danger">*</span></label><input class="hr-input" id="desde" name="desde" type="date" value="{{ $startDate }}" required></div>
                <div class="report-filter"><label for="hasta">Hasta <span class="text-danger">*</span></label><input class="hr-input" id="hasta" name="hasta" type="date" value="{{ $endDate }}" required></div>
                <div class="report-form-actions"><button type="submit" class="btn-hr btn-primary-hr"><i class="fa-light fa-magnifying-glass" aria-hidden="true"></i> Generar reporte</button><a href="{{ route('reportes.general') }}" class="btn-hr btn-outline-hr"><i class="fa-light fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
            </form>
        </div>
    </div>

    @if($reportGenerated)
        <div class="report-summary">
            <article class="report-summary-card"><div class="report-summary-icon"><i class="fa-light fa-clipboard-user" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Registros</small><strong>{{ $summary['total'] }}</strong></div></article>
            <article class="report-summary-card"><div class="report-summary-icon is-late"><i class="fa-light fa-clock" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Llegadas tarde</small><strong>{{ $summary['late'] }}</strong></div></article>
            <article class="report-summary-card"><div class="report-summary-icon is-range"><i class="fa-light fa-location-dot" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Fuera de rango</small><strong>{{ $summary['outOfRange'] }}</strong></div></article>
            <article class="report-summary-card"><div class="report-summary-icon is-exit"><i class="fa-light fa-person-walking-arrow-right" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Sin salida</small><strong>{{ $summary['withoutExit'] }}</strong></div></article>
        </div>

        <div class="card-hr">
            <div class="card-hd report-card-heading"><div><h2 class="card-title-hr">Detalle de asistencias</h2><p>Periodo del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p></div><button type="submit" form="attendance-report-form" formaction="{{ route('reportes.asistencia.exportar') }}" class="btn-hr btn-outline-hr report-export-btn" @disabled($attendance->total() === 0)><i class="fa-light fa-file-excel" aria-hidden="true"></i> Exportar a Excel</button></div>
            @if($attendance->count())
                <div class="table-wrap"><table class="bs-table hover report-table">
                    <thead><tr><th>Personal</th><th>Fecha</th><th>Entrada</th><th>Rango entrada</th><th>Tarde</th><th>Salida</th><th>Rango salida</th><th>Salida temprana</th><th>Evidencia entrada</th><th>Evidencia salida</th></tr></thead>
                    <tbody>
                    @foreach($attendance as $record)
                        @php
                            $employee = $record->personal;
                            $entryPhotoUrl = $record->fotoEntradaUrl();
                            $exitPhotoUrl = $record->fotoSalidaUrl();
                        @endphp
                        <tr>
                            <td><div class="report-employee"><strong>{{ $employee?->nombre ?? '—' }}</strong><small>{{ $employee?->empresa?->razon_social ?? '—' }}</small></div></td>
                            <td>{{ $record->fecha?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $record->llegada?->format('H:i') ?? '—' }}</td>
                            <td><span class="report-status {{ $record->rango_entrada === 'En rango' ? 'is-ok' : ($record->rango_entrada === 'Fuera de rango' ? 'is-bad' : '') }}">{{ $record->rango_entrada ?? '—' }}</span></td>
                            <td class="report-minute">{{ $record->minutos_tarde ? $record->minutos_tarde . ' min' : '0 min' }}</td>
                            <td>{{ $record->salida?->format('H:i') ?? '—' }}</td>
                            <td><span class="report-status {{ $record->rango_salida === 'En rango' ? 'is-ok' : ($record->rango_salida === 'Fuera de rango' ? 'is-bad' : '') }}">{{ $record->rango_salida ?? '—' }}</span></td>
                            <td class="report-minute">{{ $record->minutos_salida_temprano ? $record->minutos_salida_temprano . ' min' : '0 min' }}</td>
                            <td><div class="report-evidence">
                                @if($entryPhotoUrl)<button type="button" class="report-icon-action" data-photo-url="{{ $entryPhotoUrl }}" data-photo-title="Foto de entrada" aria-label="Ver foto de entrada de {{ $employee?->nombre }}" title="Ver foto de entrada"><i class="fa-light fa-camera" aria-hidden="true"></i></button>@endif
                                @if($record->latitud !== null && $record->longitud !== null)<a class="report-icon-action" href="https://www.google.com/maps?q={{ $record->latitud }},{{ $record->longitud }}" target="_blank" rel="noopener" aria-label="Ver ubicación de entrada de {{ $employee?->nombre }}" title="Ver ubicación de entrada"><i class="fa-light fa-location-dot" aria-hidden="true"></i></a>@endif
                                @if(!$entryPhotoUrl && ($record->latitud === null || $record->longitud === null))—@endif
                            </div></td>
                            <td><div class="report-evidence">
                                @if($exitPhotoUrl)<button type="button" class="report-icon-action" data-photo-url="{{ $exitPhotoUrl }}" data-photo-title="Foto de salida" aria-label="Ver foto de salida de {{ $employee?->nombre }}" title="Ver foto de salida"><i class="fa-light fa-camera" aria-hidden="true"></i></button>@endif
                                @if($record->latitud_salida !== null && $record->longitud_salida !== null)<a class="report-icon-action" href="https://www.google.com/maps?q={{ $record->latitud_salida }},{{ $record->longitud_salida }}" target="_blank" rel="noopener" aria-label="Ver ubicación de salida de {{ $employee?->nombre }}" title="Ver ubicación de salida"><i class="fa-light fa-location-dot" aria-hidden="true"></i></a>@endif
                                @if(!$exitPhotoUrl && ($record->latitud_salida === null || $record->longitud_salida === null))—@endif
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                @if($attendance->hasPages())<div class="report-footer"><span>Mostrando {{ $attendance->firstItem() }}–{{ $attendance->lastItem() }} de {{ $attendance->total() }}</span><div class="report-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->previousPageUrl() ?? '#' }}" @if($attendance->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $attendance->currentPage() }} de {{ $attendance->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->nextPageUrl() ?? '#' }}" @unless($attendance->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>@endif
            @else
                <div class="report-empty"><h2>No se encontraron asistencias</h2><p>No existen registros para la empresa y el periodo seleccionados.</p></div>
            @endif
        </div>
    @endif
</section>

<div class="modal fade report-photo-modal" id="attendance-report-photo-modal" tabindex="-1" aria-labelledby="attendance-report-photo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="attendance-report-photo-title">Foto de asistencia</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><img id="attendance-report-photo" src="" alt="Foto de asistencia"></div><div class="modal-footer"><button type="button" class="btn-hr btn-outline-hr" data-bs-dismiss="modal">Cerrar</button></div></div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('attendance-report-photo-modal');
    const photoModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    const photo = document.getElementById('attendance-report-photo');
    const title = document.getElementById('attendance-report-photo-title');

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-photo-url]');
        if (!button) return;
        photo.src = button.dataset.photoUrl;
        photo.alt = button.dataset.photoTitle || 'Foto de asistencia';
        title.textContent = button.dataset.photoTitle || 'Foto de asistencia';
        photoModal.show();
    });

    modalElement.addEventListener('hidden.bs.modal', function () { photo.src = ''; });
});
</script>
@endsection
