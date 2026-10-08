@extends('layouts.dashboard', ['pageTitle' => 'Horarios', 'activeSection' => 'horarios'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .schedule-page { width:100%; }
    .schedule-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .schedule-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .schedule-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .schedule-toolbar { display:flex; align-items:end; gap:1rem; margin-bottom:1.25rem; }
    .schedule-filter { width:min(100%, 420px); }
    .schedule-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .schedule-filter select { width:100%; }
    .schedule-toolbar-action { margin-left:auto; white-space:nowrap; }
    .schedule-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .schedule-actions i { display:inline-block; width:15px; min-width:15px; font-size:15px; line-height:1; text-align:center; }
    .schedule-actions form { margin:0; }
    .schedule-actions .btn-hr { text-decoration:none; }
    .schedule-status { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(16,185,129,.22); border-radius:.625rem; color:#087f5b; background:rgba(16,185,129,.08); font-size:.845rem; }
    .schedule-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .schedule-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .schedule-empty p { margin:0; font-size:.845rem; }
    .schedule-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .schedule-pagination { display:flex; align-items:center; gap:.55rem; }
    .schedule-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    @media(max-width:700px) { .schedule-heading { align-items:stretch; flex-direction:column; } .schedule-toolbar { align-items:stretch; flex-direction:column; } .schedule-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="schedule-page">
    <header class="schedule-heading"><div><h1>Horarios</h1><p>Administra los horarios de trabajo de tus empresas.</p></div></header>
    <div class="schedule-toolbar">
        <div class="schedule-filter">
            <label for="empresa_id">Empresa</label>
            <select class="hr-input select2" id="empresa_id" name="empresa_id">
                @if($companies->count() !== 1)<option value="0" @selected(!$selectedCompanyId)>Todas las empresas</option>@endif
                @foreach($companies as $company)<option value="{{ $company->id }}" @selected($selectedCompanyId === $company->id)>{{ $company->razon_social }}</option>@endforeach
            </select>
        </div>
        @if($canCreate)<a class="btn-hr btn-primary-hr schedule-toolbar-action" href="{{ route('horarios.create', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}"><i class="fa-light fa-plus" aria-hidden="true"></i> Agregar horario</a>@endif
    </div>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Listado de horarios</h2><p class="card-subtitle-hr">{{ $horarios->total() }} {{ $horarios->total() === 1 ? 'horario registrado' : 'horarios registrados' }}</p></div></div>
        @if($horarios->count())
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Empresa</th><th>Nombre del horario</th><th>Hora de entrada</th><th>Hora de salida</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($horarios as $horario)
                    <tr>
                        <td>{{ $horario->empresa?->razon_social ?? '—' }}</td>
                        <td class="fw-bold">{{ $horario->nombre_horario }}</td>
                        <td>{{ substr($horario->hora_entrada, 0, 5) }}</td>
                        <td>{{ substr($horario->hora_salida, 0, 5) }}@if(substr((string) $horario->hora_salida, 0, 8) <= substr((string) $horario->hora_entrada, 0, 8)) <small class="d-block text-muted">Día siguiente</small>@endif</td>
                        <td><div class="schedule-actions">
                            @if(in_array($horario->empresa_id, $manageableIds, true))
                                <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('horarios.edit', $horario) }}" title="Editar" aria-label="Editar {{ $horario->nombre_horario }}"><i class="fa-light fa-pen-to-square" aria-hidden="true"></i> Editar</a>
                                <form method="POST" action="{{ route('horarios.destroy', $horario) }}" data-confirm-delete data-record-name="{{ $horario->nombre_horario }}">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" title="Eliminar" aria-label="Eliminar {{ $horario->nombre_horario }}"><i class="fa-light fa-trash-can" aria-hidden="true"></i> Eliminar</button></form>
                            @else<span class="company-rfc">Solo lectura</span>@endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($horarios->hasPages())<div class="schedule-footer"><span>Mostrando {{ $horarios->firstItem() }}–{{ $horarios->lastItem() }} de {{ $horarios->total() }}</span><div class="schedule-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $horarios->previousPageUrl() ?? '#' }}" @if($horarios->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $horarios->currentPage() }} de {{ $horarios->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $horarios->nextPageUrl() ?? '#' }}" @unless($horarios->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>@endif
        @else
            <div class="schedule-empty"><h2>Aún no hay horarios</h2><p>Registra los horarios laborales de tu empresa.</p></div>
        @endif
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.jQuery('#empresa_id').on('change', function () {
            const url = new URL(@json(route('horarios.index')), window.location.origin);
            if (this.value !== '0') url.searchParams.set('empresa_id', this.value);
            window.location.assign(url);
        });
    });
    document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning', title: '¿Eliminar horario?',
                text: 'Se eliminará «' + form.dataset.recordName + '». Esta acción no se puede deshacer.',
                showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                buttonsStyling: false, reverseButtons: true,
                customClass: { confirmButton: 'btn-hr btn-danger-hr', cancelButton: 'btn-hr btn-outline-hr' }
            }).then(function (result) { if (result.isConfirmed) form.submit(); });
        });
    });
</script>
@endsection
