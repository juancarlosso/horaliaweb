@extends('layouts.dashboard', ['pageTitle' => 'Centros de Trabajo', 'activeSection' => 'centros-de-trabajo'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .catalog-page { width:100%; }
    .catalog-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .catalog-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .catalog-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .catalog-toolbar { display:flex; align-items:end; gap:1rem; margin-bottom:1.25rem; }
    .catalog-filter { width:min(100%, 420px); }
    .catalog-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .catalog-filter select { width:100%; }
    .catalog-toolbar-action { margin-left:auto; white-space:nowrap; }
    .catalog-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .catalog-actions i { display:inline-block; width:15px; min-width:15px; font-size:15px; line-height:1; text-align:center; }
    .catalog-actions form { margin:0; }
    .catalog-actions .btn-hr { text-decoration:none; }
    .catalog-status { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(16,185,129,.22); border-radius:.625rem; color:#087f5b; background:rgba(16,185,129,.08); font-size:.845rem; }
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
    @media(max-width:700px) { .catalog-heading { align-items:stretch; flex-direction:column; } .catalog-toolbar { align-items:stretch; flex-direction:column; } .catalog-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="catalog-page">
    <header class="catalog-heading">
        <div><h1>Centros de Trabajo</h1><p>Administra las ubicaciones y puntos de operación de tus empresas.</p></div>
    </header>


    <div class="catalog-toolbar">
        <div class="catalog-filter">
            <label for="empresa_id">Empresa</label>
            <select class="hr-input select2" id="empresa_id" name="empresa_id">
                @if($companies->count() !== 1)<option value="0" @selected(!$selectedCompanyId)>Todas las empresas</option>@endif
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected($selectedCompanyId === $company->id)>{{ $company->razon_social }}</option>
                @endforeach
            </select>
        </div>
        @if($canCreate)
            <a class="btn-hr btn-primary-hr catalog-toolbar-action" href="{{ route('centros.create', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}"><i class="fa-light fa-plus" aria-hidden="true"></i> Agregar centro</a>
        @endif
    </div>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Listado de centros de trabajo</h2><p class="card-subtitle-hr">{{ $centros->total() }} {{ $centros->total() === 1 ? 'centro registrado' : 'centros registrados' }}</p></div></div>
        @if($centros->count())
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Empresa</th><th>Centro de trabajo</th><th>Geolocalización</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($centros as $centro)
                    <tr>
                        <td>{{ $centro->empresa?->razon_social ?? '—' }}</td>
                        <td class="fw-bold">{{ $centro->nombre }}</td>
                        <td>{{ $centro->geolocalizacion ?: '—' }}</td>
                        <td><span class="catalog-state {{ $centro->activo ? 'catalog-state-active' : 'catalog-state-inactive' }}">{{ $centro->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td><div class="catalog-actions">
                            @if(in_array($centro->empresa_id, $manageableIds, true))
                                <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('centros.edit', $centro) }}" title="Editar" aria-label="Editar {{ $centro->nombre }}"><i class="fa-light fa-pen-to-square" aria-hidden="true"></i> Editar</a>
                                <form method="POST" action="{{ route('centros.destroy', $centro) }}" data-confirm-delete data-record-name="{{ $centro->nombre }}" data-record-type="centro de trabajo">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" title="Eliminar" aria-label="Eliminar {{ $centro->nombre }}"><i class="fa-light fa-trash-can" aria-hidden="true"></i> Eliminar</button></form>
                            @else
                                <span class="company-rfc">Solo lectura</span>
                            @endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($centros->hasPages())
                <div class="catalog-footer"><span>Mostrando {{ $centros->firstItem() }}–{{ $centros->lastItem() }} de {{ $centros->total() }}</span><div class="catalog-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $centros->previousPageUrl() ?? '#' }}" @if($centros->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $centros->currentPage() }} de {{ $centros->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $centros->nextPageUrl() ?? '#' }}" @unless($centros->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>
            @endif
        @else
            <div class="catalog-empty"><h2>Aún no hay centros de trabajo</h2><p>Registra un centro para organizar las ubicaciones de tu equipo.</p></div>
        @endif
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.jQuery('#empresa_id').on('change', function () {
            const url = new URL(@json(route('centros.index')), window.location.origin);
            if (this.value !== '0') url.searchParams.set('empresa_id', this.value);
            window.location.assign(url);
        });
    });
    document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning', title: '¿Eliminar ' + form.dataset.recordType + '?',
                text: 'Se eliminará «' + form.dataset.recordName + '». Esta acción no se puede deshacer.',
                showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                buttonsStyling: false, reverseButtons: true,
                customClass: { confirmButton: 'btn-hr btn-danger-hr', cancelButton: 'btn-hr btn-outline-hr' }
            }).then(function (result) { if (result.isConfirmed) form.submit(); });
        });
    });
</script>
@endsection
