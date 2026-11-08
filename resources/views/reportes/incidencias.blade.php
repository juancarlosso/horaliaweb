@extends('layouts.dashboard', ['pageTitle' => 'Incidencias', 'activeSection' => 'reportes-incidencias'])

@section('content')
<style>
    .page-inner{max-width:none}.ir-heading{margin-bottom:1.2rem}.ir-heading h1{margin:0 0 .35rem;color:var(--body-text);font-size:1.5rem;font-weight:800}.ir-heading p{margin:0;color:var(--body-text-muted);font-size:.875rem}.ir-filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:1rem;align-items:end}.ir-filter label{display:block;margin-bottom:.4rem;font-size:.82rem;font-weight:650}.ir-filter .hr-input{width:100%}.ir-types{grid-column:1/-1}.ir-type-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:.65rem}.ir-type-head label{margin:0}.ir-type-actions{display:flex;gap:.6rem}.ir-type-actions button{border:0;background:none;color:var(--color-primary);font-size:.82rem}.ir-type-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem}.ir-type{display:flex;align-items:center;gap:.6rem;padding:.7rem .875rem;border:1px solid var(--card-border);border-left:3px solid var(--incidence-color);border-radius:.625rem;background:color-mix(in srgb,var(--incidence-color) 7%,var(--card-bg));cursor:pointer;transition:filter .13s}.ir-type:hover{filter:brightness(1.04)}.ir-type input{appearance:none;-webkit-appearance:none;display:grid;place-content:center;flex:none;width:18px;height:18px;margin:0;border:1px solid color-mix(in srgb,var(--incidence-color) 45%,transparent);border-radius:4px;background:color-mix(in srgb,var(--incidence-color) 30%,transparent);cursor:pointer}.ir-type input::before{content:"";width:9px;height:5px;border:solid #fff;border-width:0 0 2px 2px;transform:rotate(-45deg) scale(0);transform-origin:center;transition:transform .1s ease-in-out}.ir-type input:checked{border-color:var(--incidence-color);background:var(--incidence-color)}.ir-type input:checked::before{transform:rotate(-45deg) scale(1)}.ir-type input:focus-visible{outline:2px solid var(--incidence-color);outline-offset:2px}.ir-type span{flex:1;color:var(--body-text);font-size:.875rem;font-weight:600}.ir-info{border:0;background:none;padding:0;color:var(--incidence-color)}.ir-actions{display:flex;gap:.5rem;white-space:nowrap}.ir-table{min-width:850px}.ir-table td{vertical-align:middle}.ir-empty{padding:2.5rem 1rem;text-align:center;color:var(--body-text-muted)}.ir-empty h2{font-size:1rem;color:var(--body-text)}.ir-head{display:flex;align-items:center;justify-content:space-between;gap:1rem}.ir-head p{margin:.25rem 0 0;color:var(--body-text-muted);font-size:.8rem}.ir-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-top:1px solid var(--body-border);color:var(--body-text-muted);font-size:.8rem}.ir-pagination{display:flex;align-items:center;gap:.55rem}.ir-pagination [aria-disabled="true"]{pointer-events:none;opacity:.55}.ir-loader{position:fixed;inset:0;z-index:25000;display:grid;place-items:center;padding:1rem;background:rgba(17,24,39,.42)}.ir-loader[hidden]{display:none}.ir-loader-card{display:flex;align-items:center;gap:.8rem;padding:1rem 1.25rem;border:1px solid var(--card-border);border-radius:.85rem;background:var(--card-bg);color:var(--body-text);box-shadow:0 12px 40px rgba(17,24,39,.2);font-weight:650}.ir-loader-spinner{width:1.35rem;height:1.35rem;border:3px solid var(--body-border);border-top-color:var(--color-primary);border-radius:50%;animation:ir-spin .7s linear infinite}@keyframes ir-spin{to{transform:rotate(360deg)}}@media(max-width:900px){.ir-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.ir-actions{grid-column:1/-1}.ir-type-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.ir-filters,.ir-type-grid{grid-template-columns:1fr}.ir-types,.ir-actions{grid-column:auto}.ir-head,.ir-type-head,.ir-footer{align-items:flex-start;flex-direction:column}}
</style>
<section>
    <header class="ir-heading"><h1>Reporte de Incidencias</h1><p>Consulta incidencias detectadas en las jornadas de tu personal.</p></header>
    <div class="card-hr"><div class="card-hd"><div><h2 class="card-title-hr">Filtros del reporte</h2><p class="card-subtitle-hr">Selecciona la empresa, el periodo y los tipos de incidencia.</p></div></div><div class="card-bd">
        <form id="incidents-form" class="ir-filters" method="GET" action="{{ route('reportes.incidencias') }}">
            <div class="ir-filter"><label for="empresa_id">Empresa <span class="text-danger">*</span></label><select id="empresa_id" name="empresa_id" class="hr-input select2" required data-placeholder="Selecciona una empresa">@if($companies->count() !== 1)<option value="">Selecciona una empresa</option>@endif @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)$selectedCompanyId === (string)$company->id)>{{ $company->nombre }}</option>@endforeach</select></div>
            <div class="ir-filter"><label for="desde">Desde <span class="text-danger">*</span></label><input class="hr-input" id="desde" name="desde" type="date" value="{{ $startDate }}" required></div>
            <div class="ir-filter"><label for="hasta">Hasta <span class="text-danger">*</span></label><input class="hr-input" id="hasta" name="hasta" type="date" value="{{ $endDate }}" required></div>
            <div class="ir-types"><div class="ir-type-head"><label>Tipos de incidencia <span class="text-danger">*</span></label><div class="ir-type-actions"><button type="button" id="select-all-types">Seleccionar todas</button><button type="button" id="clear-types">Deseleccionar todas</button></div></div>
                <div class="ir-type-grid">@foreach($types as $type)<label class="ir-type" style="--incidence-color: {{ $typeColors[$type] }}"><input type="checkbox" name="tipos[]" value="{{ $type }}" @checked(in_array($type,$selectedTypes,true))><span>{{ $labels[$type] }}</span><button class="ir-info" type="button" tabindex="0" aria-label="Información sobre {{ $labels[$type] }}" data-bs-toggle="popover" data-bs-placement="top" data-bs-content="{{ $descriptions[$type] }}"><i class="{{ config('constantes.tipo_iconos') }} fa-circle-info" aria-hidden="true"></i></button></label>@endforeach</div>
            </div>
            <div class="ir-actions"><button type="submit" class="btn-hr btn-primary-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-magnifying-glass" aria-hidden="true"></i> Generar reporte</button><a href="{{ route('reportes.incidencias') }}" class="btn-hr btn-outline-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
        </form>
    </div></div>
    @if($reportGenerated)
        <div class="card-hr" style="margin-top:1.25rem"><div class="card-hd ir-head"><div><h2 class="card-title-hr">Incidencias encontradas ({{ $rows->total() }})</h2><p>{{ $companies->firstWhere('id',$selectedCompanyId)?->nombre }} · Periodo del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p></div><button type="submit" form="incidents-form" formaction="{{ route('reportes.incidencias.exportar') }}" data-loading-message="Preparando el archivo de Excel…" class="btn-hr btn-outline-hr" @disabled($rows->total() === 0)><i class="{{ config('constantes.tipo_iconos') }} fa-file-excel" aria-hidden="true"></i> Exportar a Excel</button></div>
            @if($rows->total() === 0)<div class="ir-empty"><h2>No se encontraron incidencias para los filtros seleccionados.</h2></div>@else<div class="table-wrap"><table class="bs-table hover ir-table"><thead><tr><th>Fecha</th><th>Empleado</th><th>Código</th><th>Tipo de incidencia</th><th>Detalle</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') }}</td><td>{{ $row['empleado'] }}</td><td>{{ $row['codigo'] ?: '-' }}</td><td>{{ $labels[$row['tipo']] }}</td><td>{{ $row['detalle'] }}</td></tr>@endforeach</tbody></table></div>
                @if($rows->hasPages())<div class="ir-footer"><span>Mostrando {{ $rows->firstItem() }}–{{ $rows->lastItem() }} de {{ $rows->total() }}</span><nav class="ir-pagination" aria-label="Paginación del reporte"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $rows->previousPageUrl() ?? '#' }}" @if($rows->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $rows->currentPage() }} de {{ $rows->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $rows->nextPageUrl() ?? '#' }}" @unless($rows->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></nav></div>@endif
            @endif
        </div>
    @endif
</section>
<div id="incidents-loader" class="ir-loader" role="status" aria-live="assertive" aria-atomic="true" aria-busy="false" hidden><div class="ir-loader-card"><span class="ir-loader-spinner" aria-hidden="true"></span><span id="incidents-loader-message">Generando reporte…</span></div></div>
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
        showLoader(submitter?.dataset.loadingMessage || 'Generando reporte…');
    });
    document.querySelectorAll('.ir-pagination a:not([aria-disabled="true"])').forEach((link) => link.addEventListener('click', () => showLoader('Cargando página…')));
});
</script>
@endsection
