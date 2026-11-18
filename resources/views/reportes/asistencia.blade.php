@extends('layouts.dashboard', ['pageTitle' => 'General', 'activeSection' => 'reportes-general'])

@push('scripts')
    <script defer src="{{ asset('assets/horalia/js/report-date-range.js') }}"></script>
@endpush

@section('content')
@include('components.datatable-assets')
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
    .report-table { min-width:1450px; }
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
    .attendance-loader { position:fixed; inset:0; z-index:25000; display:grid; place-items:center; padding:1rem; background:rgba(17,24,39,.42); }
    .attendance-loader[hidden] { display:none; }
    .attendance-loader-card { display:flex; align-items:center; gap:.8rem; padding:1rem 1.25rem; border:1px solid var(--card-border); border-radius:.85rem; color:var(--body-text); background:var(--card-bg); box-shadow:0 12px 40px rgba(17,24,39,.2); font-weight:650; }
    .attendance-loader-spinner { width:1.35rem; height:1.35rem; border:3px solid var(--body-border); border-top-color:var(--color-primary); border-radius:50%; animation:attendance-loader-spin .7s linear infinite; }
    @keyframes attendance-loader-spin { to { transform:rotate(360deg); } }
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
        <div class="card-hd"><div><h2 class="card-title-hr">Filtros del reporte</h2><p class="card-subtitle-hr">Selecciona una empresa y un periodo de hasta {{ \App\Rules\ReportDateRange::MAX_DAYS }} días.</p></div></div>
        <div class="card-bd">
            <form data-report-max-days="{{ \App\Rules\ReportDateRange::MAX_DAYS }}" id="attendance-report-form" class="report-filters" method="GET" action="{{ route('reportes.general') }}">
                <div class="report-filter"><label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                    <select id="empresa_id" name="empresa_id" class="hr-input select2" required data-placeholder="Selecciona una empresa">
                        @if($companies->count() !== 1)<option value="">Selecciona una empresa</option>@endif
                        @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) $selectedCompanyId === (string) $company->id)>{{ $company->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="report-filter"><label for="desde">Desde <span class="text-danger">*</span></label><input class="hr-input" id="desde" name="desde" type="date" value="{{ $startDate }}" required></div>
                <div class="report-filter"><label for="hasta">Hasta <span class="text-danger">*</span></label><input class="hr-input" id="hasta" name="hasta" type="date" value="{{ $endDate }}" required></div>
                <div class="report-form-actions"><button type="submit" data-loading-message="Generando reporte…" class="btn-hr btn-primary-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-magnifying-glass" aria-hidden="true"></i> Generar reporte</button><a href="{{ route('reportes.general') }}" class="btn-hr btn-outline-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
            </form>
        </div>
    </div>

    @if($reportGenerated)
        <div class="report-summary">
            <article class="report-summary-card"><div class="report-summary-icon"><i class="{{ config('constantes.tipo_iconos') }} fa-clipboard-user" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Registros</small><strong>{{ $summary['total'] }}</strong></div></article>
            <article class="report-summary-card"><div class="report-summary-icon is-late"><i class="{{ config('constantes.tipo_iconos') }} fa-clock" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Llegadas tarde</small><strong>{{ $summary['late'] }}</strong></div></article>
            <article class="report-summary-card"><div class="report-summary-icon is-range"><i class="{{ config('constantes.tipo_iconos') }} fa-location-dot" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Fuera de rango</small><strong>{{ $summary['outOfRange'] }}</strong></div></article>
            <article class="report-summary-card"><div class="report-summary-icon is-exit"><i class="{{ config('constantes.tipo_iconos') }} fa-person-walking-arrow-right" aria-hidden="true"></i></div><div class="report-summary-copy"><small>Sin salida</small><strong>{{ $summary['withoutExit'] }}</strong></div></article>
        </div>

        <div class="card-hr" data-datatable-source>
            <div class="card-hd report-card-heading"><div><h2 class="card-title-hr">Detalle de asistencias</h2><p>Periodo del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p></div><button type="submit" form="attendance-report-form" formaction="{{ route('reportes.asistencia.exportar') }}" data-loading-message="Preparando el archivo de Excel…" class="btn-hr btn-outline-hr report-export-btn" @disabled($unfilteredTotal === 0)><i class="{{ config('constantes.tipo_iconos') }} fa-file-excel" aria-hidden="true"></i> Exportar todo a Excel</button></div>
            @include('reportes.partials.asistencia-table')
        </div>
    @endif
</section>

<div id="attendance-report-loader" class="attendance-loader" role="status" aria-live="assertive" aria-atomic="true" aria-busy="false" hidden>
    <div class="attendance-loader-card"><span class="attendance-loader-spinner" aria-hidden="true"></span><span id="attendance-report-loader-message">Generando reporte…</span></div>
</div>

<div class="modal fade report-photo-modal" id="attendance-report-photo-modal" tabindex="-1" aria-labelledby="attendance-report-photo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="attendance-report-photo-title">Foto de asistencia</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><img id="attendance-report-photo" src="" alt="Foto de asistencia"></div><div class="modal-footer"><button type="button" class="btn-hr btn-outline-hr" data-bs-dismiss="modal">Cerrar</button></div></div></div>
</div>

<div class="modal fade report-photo-modal" id="attendance-report-pauses-modal" tabindex="-1" aria-labelledby="attendance-report-pauses-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="attendance-report-pauses-title">Pausas de la jornada</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div class="table-wrap"><table class="bs-table"><thead><tr><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Duración</th><th>Estado</th></tr></thead><tbody id="attendance-report-pauses-body"></tbody></table></div><p id="attendance-report-pauses-empty" hidden>No hay pausas registradas en esta asistencia.</p></div><div class="modal-footer"><button type="button" class="btn-hr btn-outline-hr" data-bs-dismiss="modal">Cerrar</button></div></div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const reportForm = document.getElementById('attendance-report-form');
    const loader = document.getElementById('attendance-report-loader');
    const loaderMessage = document.getElementById('attendance-report-loader-message');
    const showLoader = (message = 'Generando reporte…') => {
        loaderMessage.textContent = message;
        loader.hidden = false;
        loader.setAttribute('aria-busy', 'true');
    };
    const hideLoader = () => {
        loader.hidden = true;
        loader.setAttribute('aria-busy', 'false');
    };

    reportForm?.addEventListener('submit', (event) => {
        const submitter = event.submitter;
        const exportUrl = submitter?.getAttribute('formaction');
        if (!exportUrl) {
            showLoader(submitter?.dataset.loadingMessage || 'Generando reporte…');
            return;
        }

        event.preventDefault();
        showLoader(submitter.dataset.loadingMessage || 'Preparando el archivo de Excel…');
        const url = new URL(exportUrl, window.location.href);
        url.search = new URLSearchParams(new FormData(reportForm)).toString();
        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(async (response) => {
                if (!response.ok) throw new Error('No se pudo generar el archivo de Excel.');
                const contentType = response.headers.get('Content-Type') || '';
                if (!contentType.includes('spreadsheetml')) throw new Error('El servidor no devolvió un archivo de Excel.');
                const filenameHeader = response.headers.get('Content-Disposition') || '';
                const filename = filenameHeader.match(/filename="?([^";]+)"?/i)?.[1]
                    || `reporte_asistencia_${reportForm.elements.desde.value}_${reportForm.elements.hasta.value}.xlsx`;
                return { file: await response.blob(), filename };
            })
            .then(({ file, filename }) => {
                const downloadUrl = URL.createObjectURL(file);
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 60000);
            })
            .catch((error) => Swal.fire({
                icon: 'error',
                title: 'No se pudo exportar',
                text: error.message || 'Ocurrió un error al generar el archivo de Excel.',
                confirmButtonText: 'Entendido',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn-hr btn-primary-hr' }
            }))
            .finally(hideLoader);
    });
    document.querySelectorAll('.report-pagination a:not([aria-disabled="true"])').forEach((link) => {
        link.addEventListener('click', () => showLoader('Cargando página…'));
    });

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

    const pausesElement = document.getElementById('attendance-report-pauses-modal');
    const pausesModal = window.bootstrap.Modal.getOrCreateInstance(pausesElement);
    const pausesBody = document.getElementById('attendance-report-pauses-body');
    const pausesEmpty = document.getElementById('attendance-report-pauses-empty');
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-pause-detail-url]');
        if (!button) return;
        pausesBody.replaceChildren();
        pausesEmpty.hidden = true;
        pausesModal.show();
        try {
            const response = await fetch(button.dataset.pauseDetailUrl, {headers:{'Accept':'application/json'}});
            if (!response.ok) throw new Error('No se pudo cargar el detalle.');
            const data = await response.json();
            for (const pause of data.pausas) {
                const row = document.createElement('tr');
                for (const value of [pause.tipo, pause.inicio, pause.fin, pause.duracion, pause.estado]) {
                    const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
                }
                pausesBody.appendChild(row);
            }
            pausesEmpty.hidden = data.pausas.length > 0;
        } catch (error) {
            const row = document.createElement('tr'); const cell = document.createElement('td');
            cell.colSpan = 5; cell.textContent = error.message; row.appendChild(cell); pausesBody.appendChild(row);
        }
    });
});
</script>
@endsection
