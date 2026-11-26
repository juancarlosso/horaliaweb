@extends('layouts.dashboard', ['pageTitle' => 'Incidencias', 'activeSection' => 'reportes-incidencias'])

@push('scripts')
    <script defer src="{{ asset('assets/horalia/js/report-date-range.js') }}?v=2"></script>
@endpush

@section('content')
@if($reportGenerated)
    @include('components.datatable-assets')
@endif
@include('reportes.partials.incidencias-estilos')
<section>
    <div class="card-hr">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Reporte de incidencias</h2>
                <p class="card-subtitle-hr">Selecciona la empresa, los tipos de incidencia y un periodo de hasta {{ \App\Rules\ReportDateRange::MAX_DAYS }} días, con fecha final hasta ayer.</p>
            </div>
        </div>
        <div class="card-bd">
            <form id="incidents-form" class="row g-3 align-items-end" data-report-max-days="{{ \App\Rules\ReportDateRange::MAX_DAYS }}" method="POST" action="{{ route('reportes.generados.store', ['tipoReporte' => 'incidencias']) }}">
                @csrf
                @include('reportes.partials.campos-periodo')

                <fieldset class="col-12">
                    <legend class="form-label">Tipos de incidencia <span class="text-danger">*</span></legend>
                    <div class="ir-type-actions mb-2">
                        <button type="button" id="select-all-types" class="btn-hr btn-outline-hr btn-sm-hr">Seleccionar todas</button>
                        <button type="button" id="clear-types" class="btn-hr btn-outline-hr btn-sm-hr">Deseleccionar todas</button>
                    </div>
                    <div class="ir-type-grid">
                        @foreach($types as $type)
                            <div class="ir-type" style="--incidence-color: {{ $typeColors[$type] }}">
                                <label>
                                    <input type="checkbox" name="tipos[]" value="{{ $type }}" @checked(in_array($type, (array) old('tipos', $selectedTypes), true))>
                                    <span>{{ $labels[$type] }}</span>
                                </label>
                                <button class="ir-info" type="button" aria-label="Información sobre {{ $labels[$type] }}" data-bs-toggle="popover" data-bs-placement="top" data-bs-content="{{ $descriptions[$type] }}">
                                    <i class="{{ config('constantes.tipo_iconos') }} fa-circle-info" aria-hidden="true"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn-hr btn-primary-hr" @disabled($companies->isEmpty())>
                        <i class="{{ config('constantes.tipo_iconos') }} fa-magnifying-glass" aria-hidden="true"></i> Generar reporte
                    </button>
                    <a href="{{ route('reportes.incidencias') }}" class="btn-hr btn-outline-hr">
                        <i class="{{ config('constantes.tipo_iconos') }} fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    @if($reportGenerated)
        <div class="card-hr" style="margin-top:1.25rem" data-datatable-source><div class="card-hd ir-head"><div><h2 class="card-title-hr">Incidencias encontradas ({{ $unfilteredTotal }})</h2><p>{{ $companies->firstWhere('id',$selectedCompanyId)?->nombre }} · Periodo del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p></div><button type="submit" form="incidents-form" formaction="{{ route('reportes.incidencias.exportar') }}" data-loading-message="Preparando el archivo de Excel…" class="btn-hr btn-outline-hr" @disabled($unfilteredTotal === 0)><i class="{{ config('constantes.tipo_iconos') }} fa-file-excel" aria-hidden="true"></i> Exportar todo a Excel</button></div>
            @include('reportes.partials.incidencias-table')
        </div>
    @endif
    @include('reportes.partials.historial')
</section>
<div id="incidents-loader" class="ir-loader" role="status" aria-live="assertive" aria-atomic="true" aria-busy="false" hidden><div class="ir-loader-card"><span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span><span id="incidents-loader-message">Generando reporte…</span></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let activePopover = null;
    let activeTrigger = null;
    const clickLocks = new WeakMap();
    const closeActivePopover = () => {
        if (!activePopover) return;
        clickLocks.set(activeTrigger, false);
        activePopover.hide();
        activeTrigger?.setAttribute('aria-expanded', 'false');
        activePopover = activeTrigger = null;
    };
    const showPopover = (popover, trigger) => {
        if (activeTrigger !== trigger) closeActivePopover();
        activePopover = popover;
        activeTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        popover.show();
    };

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
        const popover = new bootstrap.Popover(el, { trigger: 'manual', container: 'body' });
        el.setAttribute('aria-expanded', 'false');
        el.addEventListener('mouseenter', () => showPopover(popover, el));
        el.addEventListener('mouseleave', () => {
            if (!clickLocks.get(el) && !el.matches(':focus-visible')) closeActivePopover();
        });
        el.addEventListener('focus', () => { if (el.matches(':focus-visible')) showPopover(popover, el); });
        el.addEventListener('blur', () => {
            if (!clickLocks.get(el) && !el.matches(':hover')) closeActivePopover();
        });
        el.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (activeTrigger === el && clickLocks.get(el)) {
                clickLocks.set(el, false);
                closeActivePopover();
            } else {
                clickLocks.set(el, true);
                showPopover(popover, el);
            }
        });
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-bs-toggle="popover"]')) closeActivePopover();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeActivePopover();
    });
    const boxes = [...document.querySelectorAll('input[name="tipos[]"]')];
    document.getElementById('select-all-types')?.addEventListener('click', () => boxes.forEach((box) => box.checked = true));
    document.getElementById('clear-types')?.addEventListener('click', () => boxes.forEach((box) => box.checked = false));
    const form = document.getElementById('incidents-form');
    const loader = document.getElementById('incidents-loader');
    const loaderMessage = document.getElementById('incidents-loader-message');
    const showLoader = (message = 'Generando reporte…') => {
        loaderMessage.textContent = message;
        loader.hidden = false;
        loader.setAttribute('aria-busy', 'true');
    };
    const hideLoader = () => {
        loader.hidden = true;
        loader.setAttribute('aria-busy', 'false');
    };
    form?.addEventListener('submit', (event) => {
        if (!boxes.some((box) => box.checked)) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona un tipo de incidencia',
                text: 'Selecciona al menos un tipo de incidencia.',
                confirmButtonText: 'Entendido',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn-hr btn-primary-hr' }
            });
            return;
        }
        const submitter = event.submitter;
        const exportUrl = submitter?.getAttribute('formaction');
        if (exportUrl) {
            event.preventDefault();
            showLoader(submitter.dataset.loadingMessage || 'Preparando el archivo de Excel…');
            const url = new URL(exportUrl, window.location.href);
            url.search = new URLSearchParams(new FormData(form)).toString();
            url.searchParams.delete('_token');
            fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(async (response) => {
                    if (!response.ok) throw new Error('No se pudo generar el archivo de Excel.');
                    const contentType = response.headers.get('Content-Type') || '';
                    if (!contentType.includes('spreadsheetml')) throw new Error('El servidor no devolvió un archivo de Excel.');
                    return response.blob();
                })
                .then((file) => {
                    const downloadUrl = URL.createObjectURL(file);
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = `horalia_incidencias_${form.elements.desde.value.replaceAll('-', '')}_${form.elements.hasta.value.replaceAll('-', '')}.xlsx`;
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
            return;
        }
    });
    document.querySelectorAll('.ir-pagination a:not([aria-disabled="true"])').forEach((link) => link.addEventListener('click', () => showLoader('Cargando página…')));
});
</script>
@endsection
