@extends('layouts.dashboard', ['pageTitle' => 'Festivos Oficiales', 'activeSection' => 'festivos-oficiales'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .holiday-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .holiday-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .holiday-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .holiday-toolbar { display:flex; align-items:end; gap:1rem; margin-bottom:1.25rem; }
    .holiday-filter { width:min(100%, 260px); }
    .holiday-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .holiday-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .holiday-actions form { margin:0; }
    .holiday-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .holiday-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    @media(max-width:700px) { .holiday-heading { align-items:stretch; flex-direction:column; } .holiday-toolbar { align-items:stretch; flex-direction:column; } }
</style>

<section>
    <header class="holiday-heading"><div><h1>Festivos Oficiales</h1><p>Administra las fechas y conmemoraciones oficiales.</p></div></header>
    <div class="holiday-toolbar">
        <div class="holiday-filter"><label for="anio">Año</label><select class="hr-input select2" id="anio" name="anio">@foreach($years as $year)<option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>@endforeach</select></div>
        <a class="btn-hr btn-primary-hr" href="{{ route('festivos-oficiales.create') }}"><i class="{{ config('constantes.tipo_iconos') }} fa-plus" aria-hidden="true"></i> Agregar festivo</a>
    </div>
    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Listado de {{ $selectedYear }}</h2><p class="card-subtitle-hr">{{ $festivos->count() }} {{ $festivos->count() === 1 ? 'festivo registrado' : 'festivos registrados' }}</p></div></div>
        @if($festivos->isNotEmpty())
            <div class="table-wrap"><table class="bs-table hover"><thead><tr><th>Fecha</th><th>Conmemoración</th><th class="text-end">Acciones</th></tr></thead><tbody>
                @foreach($festivos as $festivo)<tr><td>{{ $festivo->fecha->format('d/m/Y') }}</td><td class="fw-bold">{{ $festivo->conmemoracion }}</td><td><div class="holiday-actions"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('festivos-oficiales.edit', $festivo) }}" aria-label="Editar {{ $festivo->conmemoracion }}"><i class="{{ config('constantes.tipo_iconos') }} fa-pen-to-square" aria-hidden="true"></i> Editar</a><form method="POST" action="{{ route('festivos-oficiales.destroy', $festivo) }}" data-confirm-delete data-record-name="{{ $festivo->conmemoracion }}">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" aria-label="Eliminar {{ $festivo->conmemoracion }}"><i class="{{ config('constantes.tipo_iconos') }} fa-trash-can" aria-hidden="true"></i> Eliminar</button></form></div></td></tr>@endforeach
            </tbody></table></div>
        @else
            <div class="holiday-empty"><h2>No hay festivos para {{ $selectedYear }}</h2><p>Agrega el primer festivo de este año.</p></div>
        @endif
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.jQuery('#anio').on('change', function () {
            const url = new URL(@json(route('festivos-oficiales.index')), window.location.origin);
            url.searchParams.set('anio', this.value);
            window.location.assign(url);
        });
    });
    document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({ icon: 'warning', title: '¿Eliminar festivo?', text: 'Se eliminará «' + form.dataset.recordName + '». Esta acción no se puede deshacer.', showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', buttonsStyling: false, reverseButtons: true, customClass: { confirmButton: 'btn-hr btn-danger-hr', cancelButton: 'btn-hr btn-outline-hr' } }).then(function (result) { if (result.isConfirmed) form.submit(); });
        });
    });
</script>
@endsection
