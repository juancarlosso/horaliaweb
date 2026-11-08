@extends('layouts.dashboard', ['pageTitle' => 'Festivos', 'activeSection' => 'festivos'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .holidays-heading { margin-bottom:1.25rem; }
    .holidays-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .holidays-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .holidays-toolbar { display:flex; align-items:end; gap:1rem; margin-bottom:1.25rem; }
    .holidays-filter { width:min(100%, 340px); }
    .holidays-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .holidays-filter select { width:100%; }
    .holidays-toolbar-action { margin-left:auto; white-space:nowrap; }
    .holidays-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .holidays-actions form { margin:0; }
    .holidays-type { display:inline-flex; padding:.25rem .55rem; border-radius:99px; font-size:.75rem; font-weight:700; }
    .holidays-type-official { color:#3855bd; background:rgba(79,110,247,.1); }
    .holidays-type-company { color:#087f5b; background:rgba(16,185,129,.1); }
    .holidays-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .holidays-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    @media(max-width:700px) { .holidays-toolbar { align-items:stretch; flex-direction:column; } .holidays-toolbar-action { margin-left:0; align-self:flex-start; } }
</style>
<section>
    <header class="holidays-heading"><h1>Festivos</h1><p>Consulta los festivos oficiales y administra los días propios de cada empresa.</p></header>
    <div class="holidays-toolbar">
        <div class="holidays-filter"><label for="empresa_id">Empresa</label><select class="hr-input select2" id="empresa_id" name="empresa_id"><option value="0" @selected(!$selectedCompanyId)>Todas las empresas</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected($selectedCompanyId === (int) $company->id)>{{ $company->nombre }}</option>@endforeach</select></div>
        <div class="holidays-filter" style="max-width:190px"><label for="anio">Año</label><select class="hr-input select2" id="anio" name="anio">@foreach($years as $year)<option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>@endforeach</select></div>
        <a class="btn-hr btn-primary-hr holidays-toolbar-action" href="{{ route('festivos.create', array_filter(['empresa_id' => $selectedCompanyId, 'anio' => $selectedYear])) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-plus" aria-hidden="true"></i> Agregar festivo</a>
    </div>
    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Listado de {{ $selectedYear }}</h2><p class="card-subtitle-hr">{{ $festivos->count() }} {{ $festivos->count() === 1 ? 'festivo' : 'festivos' }}{{ $selectedCompanyId ? ' para la empresa seleccionada' : '' }}</p></div></div>
        @if($festivos->isNotEmpty())
            <div class="table-wrap"><table class="bs-table hover"><thead><tr><th>Fecha</th><th>Conmemoración</th><th>Tipo</th>@unless($selectedCompanyId)<th>Empresa</th>@endunless<th class="text-end">Acciones</th></tr></thead><tbody>
                @foreach($festivos as $festivo)<tr><td>{{ $festivo->fecha->format('d/m/Y') }}</td><td class="fw-bold">{{ $festivo->conmemoracion }}</td><td><span class="holidays-type {{ $festivo->tipo === 'Oficial' ? 'holidays-type-official' : 'holidays-type-company' }}">{{ $festivo->tipo }}</span></td>@unless($selectedCompanyId)<td>{{ $festivo->empresa ?? 'Todas' }}</td>@endunless<td><div class="holidays-actions">@if($festivo->tipo === 'Empresarial')<a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('festivos.edit', $festivo->id) }}" aria-label="Editar {{ $festivo->conmemoracion }}"><i class="{{ config('constantes.tipo_iconos') }} fa-pen-to-square" aria-hidden="true"></i> Editar</a><form method="POST" action="{{ route('festivos.destroy', $festivo->id) }}" data-confirm-delete data-record-name="{{ $festivo->conmemoracion }}">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" aria-label="Eliminar {{ $festivo->conmemoracion }}"><i class="{{ config('constantes.tipo_iconos') }} fa-trash-can" aria-hidden="true"></i> Eliminar</button></form>@else<span class="company-rfc">Solo lectura</span>@endif</div></td></tr>@endforeach
            </tbody></table></div>
        @else
            <div class="holidays-empty"><h2>No hay festivos para {{ $selectedYear }}</h2><p>Agrega un festivo empresarial para esta empresa.</p></div>
        @endif
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function applyFilters() {
        const url = new URL(@json(route('festivos.index')), window.location.origin);
        if (document.getElementById('empresa_id').value !== '0') url.searchParams.set('empresa_id', document.getElementById('empresa_id').value);
        url.searchParams.set('anio', document.getElementById('anio').value);
        window.location.assign(url);
    }
    window.jQuery('#empresa_id, #anio').on('change', applyFilters);
});
document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        Swal.fire({ icon: 'warning', title: '¿Eliminar festivo empresarial?', text: 'Se eliminará «' + form.dataset.recordName + '». Esta acción no se puede deshacer.', showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', buttonsStyling: false, reverseButtons: true, customClass: { confirmButton: 'btn-hr btn-danger-hr', cancelButton: 'btn-hr btn-outline-hr' } }).then(function (result) { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
