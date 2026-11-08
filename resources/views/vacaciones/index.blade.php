@extends('layouts.dashboard', ['pageTitle' => 'Vacaciones', 'activeSection' => 'vacaciones'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .catalog-page { width:100%; }
    .catalog-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .catalog-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .catalog-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .catalog-toolbar { display:flex; align-items:end; gap:1rem; margin-bottom:1.25rem; }
    .catalog-filter { width:min(100%, 420px); flex:1 1 280px; }
    .catalog-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .catalog-filter select { width:100%; }
    .catalog-toolbar-action { margin-left:auto; white-space:nowrap; }
    .catalog-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .catalog-actions i { display:inline-block; width:15px; min-width:15px; font-size:15px; line-height:1; text-align:center; }
    .catalog-actions form { margin:0; }
    .catalog-actions .btn-hr { text-decoration:none; }
    .vacation-info-button { display:inline-grid; place-items:center; width:2rem; height:2rem; padding:0; border:0; border-radius:.5rem; color:#2563eb; background:transparent; cursor:pointer; }
    .vacation-info-button:hover,.vacation-info-button:focus-visible { color:#1d4ed8; background:rgba(37,99,235,.1); }
    .vacation-info-button i { width:auto; min-width:0; font-size:1.1rem; }
    .vacation-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem 1.25rem; margin-bottom:1rem; text-align:left; }
    .vacation-info-item { display:flex; min-width:0; flex-direction:column; gap:.12rem; }
    .vacation-info-item strong { color:#344054; font-size:.78rem; }
    .vacation-info-item span { overflow-wrap:anywhere; color:#101828; font-size:.88rem; }
    .catalog-state { display:inline-flex; align-items:center; gap:.4rem; padding:.28rem .55rem; border-radius:99px; font-size:.75rem; font-weight:650; }
    .catalog-state::before { width:6px; height:6px; border-radius:50%; background:currentColor; content:""; }
    .catalog-state-active { color:#087f5b; background:rgba(16,185,129,.1); }
    .catalog-state-inactive { color:#b42318; background:rgba(239,68,68,.09); }
    .catalog-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .catalog-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .catalog-empty p { margin:0 0 1rem; font-size:.845rem; }
    .catalog-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .catalog-pagination { display:flex; align-items:center; gap:.55rem; }
    .catalog-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    .vacation-detail-modal { max-height:65vh; overflow:auto; padding:.75rem 1rem; border:1px solid #d7dee8; border-radius:.45rem; text-align:left; }
    .vacation-detail-heading { display:block; margin-bottom:.45rem; color:#252a32; font-size:.95rem; font-weight:700; }
    .vacation-detail-row { display:flex; justify-content:space-between; gap:1rem; padding:.35rem 0; border-top:1px solid #d7dee8; font-size:.85rem; text-align:left; }
    .vacation-detail-date { color:#303640; }
    .vacation-detail-result { text-align:right; }
    .vacation-detail-counted { color:#079455; }
    .vacation-detail-excluded { color:#667085; }
    .vacation-detail-legacy { margin-bottom:.75rem; color:#667085; font-size:.82rem; text-align:left; }
    @media(max-width:600px) { .vacation-detail-row { align-items:flex-start; flex-direction:column; gap:.15rem; } .vacation-detail-result { text-align:left; } }
    @media(max-width:600px) { .vacation-info-grid { grid-template-columns:1fr; gap:.55rem; } }
    @media(max-width:700px) { .catalog-heading { align-items:stretch; flex-direction:column; } .catalog-toolbar { align-items:stretch; flex-direction:column; } .catalog-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="catalog-page">
    <header class="catalog-heading">
        <div><h1>Vacaciones</h1><p>Periodos previamente autorizados y días descontados.</p></div>
    </header>

    <div class="catalog-toolbar">
        <div class="catalog-filter">
            <label for="empresa_id">Empresa</label>
            <select class="hr-input select2" id="empresa_id" name="empresa_id">
                @if($companies->count() !== 1)<option value="0" @selected(!$selectedCompanyId)>Todas las empresas</option>@endif
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected($selectedCompanyId === $company->id)>{{ $company->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="catalog-filter">
            <label for="personal_id">Personal</label>
            <select class="hr-input select2" id="personal_id" name="personal_id">
                <option value="0" @selected(!$selectedPersonalId)>Todo el personal</option>
                @foreach($personalOptions as $person)
                    <option value="{{ $person->id }}" @selected($selectedPersonalId === $person->id)>{{ $person->nombre }}@if($companies->count() !== 1) — {{ $person->empresa?->nombre }}@endif</option>
                @endforeach
            </select>
        </div>
        @if($canCreate)
            <a class="btn-hr btn-primary-hr catalog-toolbar-action" href="{{ route('vacaciones.create', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-plus" aria-hidden="true"></i> Registrar vacaciones</a>
        @endif
    </div>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Listado de periodos vacacionales</h2><p class="card-subtitle-hr">{{ $vacaciones->total() }} {{ $vacaciones->total() === 1 ? 'periodo registrado' : 'periodos registrados' }}</p></div></div>
        @if($vacaciones->count())
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Empresa</th><th>Colaborador</th><th>Periodo</th><th>Días</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($vacaciones as $vacacion)
                    <tr>
                        <td>{{ $vacacion->empresa?->nombre ?? '—' }}</td>
                        <td>{{ $vacacion->personal->nombre }}</td>
                        <td>{{ $vacacion->fecha_inicio->format('d/m/Y') }} – {{ $vacacion->fecha_fin->format('d/m/Y') }}
                            @php($hasDailyBreakdown = $vacacion->detalles->isNotEmpty())
                            @php($vacationDayRows = $hasDailyBreakdown
                                ? $vacacion->detalles->map(fn ($day) => ['fecha' => $day->fecha->toDateString(), 'cuenta' => $day->cuenta, 'motivo' => $day->motivo, 'festivos' => $day->festivos ?? []])->values()
                                : $vacacion->fechas->map(fn ($day) => ['fecha' => $day->fecha->toDateString(), 'cuenta' => true, 'motivo' => 'Día vacacional guardado', 'festivos' => []])->values())
                            @php($vacationInfo = [
                                'id' => '#'.$vacacion->id,
                                'empresa' => $vacacion->empresa?->nombre ?? '—',
                                'colaborador' => $vacacion->personal->nombre,
                                'fecha_inicio' => $vacacion->fecha_inicio->format('d/m/Y'),
                                'fecha_fin' => $vacacion->fecha_fin->format('d/m/Y'),
                                'total_dias' => $vacacion->total_dias,
                                'autorizado_por' => $vacacion->autorizadoPor?->nombre ?? '—',
                                'registrado_por' => $vacacion->registradoPor?->name ?? '—',
                                'estado' => $vacacion->estado === 'registrado' ? 'Registrado' : 'Cancelado',
                                'creado_en' => $vacacion->created_at?->format('d/m/Y H:i') ?? '—',
                                'actualizado_en' => $vacacion->updated_at?->format('d/m/Y H:i') ?? '—',
                                'cancelado_por' => $vacacion->canceladoPor?->name ?? '—',
                                'cancelado_en' => $vacacion->cancelado_en?->format('d/m/Y H:i') ?? '—',
                                'has_daily_breakdown' => $hasDailyBreakdown,
                                'dias' => $vacationDayRows->all(),
                            ])
                        </td>
                        <td>{{ $vacacion->total_dias }}</td>
                        <td><span class="catalog-state {{ $vacacion->estado === 'registrado' ? 'catalog-state-active' : 'catalog-state-inactive' }}">{{ $vacacion->estado === 'registrado' ? 'Registrado' : 'Cancelado' }}</span></td>
                        <td><div class="catalog-actions">
                            <button type="button" class="vacation-info-button" data-vacation-info="{{ json_encode($vacationInfo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" aria-label="Información de las vacaciones de {{ $vacacion->personal->nombre }}" title="Información"><i class="{{ config('constantes.tipo_iconos') }} fa-circle-info" aria-hidden="true"></i></button>
                            @if($vacacion->estado === 'registrado')
                                <form method="POST" action="{{ route('vacaciones.cancel', $vacacion) }}" data-confirm-vacation-cancel data-employee="{{ $vacacion->personal->nombre }}">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit"><i class="{{ config('constantes.tipo_iconos') }} fa-ban" aria-hidden="true"></i> Cancelar</button></form>
                            @else
                                <span class="company-rfc">—</span>
                            @endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($vacaciones->hasPages())
                <div class="catalog-footer"><span>Mostrando {{ $vacaciones->firstItem() }}–{{ $vacaciones->lastItem() }} de {{ $vacaciones->total() }}</span><div class="catalog-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $vacaciones->previousPageUrl() ?? '#' }}" @if($vacaciones->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $vacaciones->currentPage() }} de {{ $vacaciones->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $vacaciones->nextPageUrl() ?? '#' }}" @unless($vacaciones->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>
            @endif
        @else
            <div class="catalog-empty"><h2>Aún no hay periodos vacacionales</h2><p>Registra un periodo previamente autorizado para que aparezca en este listado.</p></div>
        @endif
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.jQuery('#empresa_id').on('change', function () {
            const url = new URL(@json(route('vacaciones.index')), window.location.origin);
            if (this.value !== '0') url.searchParams.set('empresa_id', this.value);
            window.location.assign(url);
        });
        window.jQuery('#personal_id').on('change', function () {
            const url = new URL(@json(route('vacaciones.index')), window.location.origin);
            const companyId = document.getElementById('empresa_id').value;
            if (companyId !== '0') url.searchParams.set('empresa_id', companyId);
            if (this.value !== '0') url.searchParams.set('personal_id', this.value);
            window.location.assign(url);
        });
    });
    document.querySelectorAll('[data-confirm-vacation-cancel]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning', title: '¿Cancelar las vacaciones?',
                text: 'Se cancelará el periodo de ' + form.dataset.employee + '. El historial se conservará.',
                showCancelButton: true, confirmButtonText: 'Sí, cancelar', cancelButtonText: 'Volver',
                buttonsStyling: false, reverseButtons: true,
                customClass: { confirmButton: 'btn-hr btn-danger-hr', cancelButton: 'btn-hr btn-outline-hr' }
            }).then(function (result) { if (result.isConfirmed) form.submit(); });
        });
    });
    function createVacationDayPanel(days, total, hasDailyBreakdown) {
        const content = document.createElement('div');
        if (!hasDailyBreakdown) {
                const note = document.createElement('p');
                note.className = 'vacation-detail-legacy';
                note.textContent = 'Este periodo se registró antes de guardar el desglose diario. Se muestran las fechas contabilizadas que conserva el historial.';
                content.append(note);
        }
        const panel = document.createElement('div');
        panel.className = 'vacation-detail-modal';
        const heading = document.createElement('strong');
        heading.className = 'vacation-detail-heading';
        heading.textContent = total + ' día(s) vacacional(es) a descontar';
        panel.append(heading);
        days.forEach(function (day) {
            const row = document.createElement('div');
            row.className = 'vacation-detail-row';
            const date = document.createElement('span');
            date.className = 'vacation-detail-date';
            const [year, month, dayOfMonth] = day.fecha.slice(0, 10).split('-');
            date.textContent = [dayOfMonth, month, year].join('/') + ((day.festivos || []).length ? ' · ' + day.festivos.join(', ') : '');
            const result = document.createElement('span');
            result.className = 'vacation-detail-result ' + (day.cuenta ? 'vacation-detail-counted' : 'vacation-detail-excluded');
            result.textContent = (day.cuenta ? 'Cuenta' : 'No cuenta') + ' · ' + day.motivo;
            row.append(date, result);
            panel.append(row);
        });
        content.append(panel);
        return content;
    }
    function showVacationInfo(info) {
        const content = document.createElement('div');
        const grid = document.createElement('div');
        grid.className = 'vacation-info-grid';
        const fields = [
            ['Folio', info.id], ['Empresa', info.empresa], ['Colaborador', info.colaborador],
            ['Desde', info.fecha_inicio], ['Hasta', info.fecha_fin], ['Días vacacionales', info.total_dias],
            ['Autorizó', info.autorizado_por], ['Registró', info.registrado_por], ['Estado', info.estado],
            ['Fecha de registro', info.creado_en], ['Última actualización', info.actualizado_en],
            ['Canceló', info.cancelado_por], ['Fecha de cancelación', info.cancelado_en],
        ];
        fields.forEach(function (field) {
            const item = document.createElement('div');
            item.className = 'vacation-info-item';
            const label = document.createElement('strong');
            label.textContent = field[0];
            const value = document.createElement('span');
            value.textContent = field[1] ?? '—';
            item.append(label, value);
            grid.append(item);
        });
        content.append(grid, createVacationDayPanel(info.dias, info.total_dias, info.has_daily_breakdown));
        Swal.fire({
            title: 'Información del registro', html: content,
            width: 'min(900px, calc(100vw - 2rem))', confirmButtonText: 'Cerrar', buttonsStyling: false,
            customClass: { confirmButton: 'btn-hr btn-primary-hr' }
        });
    }
    document.querySelectorAll('[data-vacation-info]').forEach(function (button) {
        button.addEventListener('click', function () {
            showVacationInfo(JSON.parse(button.dataset.vacationInfo));
        });
    });
</script>
@endsection
