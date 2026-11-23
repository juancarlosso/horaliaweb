@extends('layouts.dashboard', ['pageTitle' => 'Horas Extras'])

@section('content')
@include('components.datatable-assets')
@include('components.administrative-catalog-styles')
<style>
    .horas-extras-toolbar .catalog-filter-period { flex: 0 1 160px; }
    @media (max-width: 700px) {
        .horas-extras-toolbar .catalog-filter,
        .horas-extras-toolbar .catalog-filter-period { flex: auto; width: 100%; }
        .horas-extras-toolbar .catalog-toolbar-action { margin-left: 0; }
    }
</style>
<section class="catalog-page">
    <form method="GET" action="{{ route('horas-extras.index') }}" class="catalog-toolbar horas-extras-toolbar">
        @include('horas-extras.empresa', ['buscador' => false])
        <div class="catalog-filter catalog-filter-period">
            <label for="mes" class="form-label-hr">Mes</label>
            <select id="mes" name="mes" class="hr-input select2">
                <option value="">Todos los meses</option>
                @foreach(range(1, 12) as $opcion)
                    <option value="{{ $opcion }}" @selected($mes == $opcion)>{{ ucfirst(\Carbon\CarbonImmutable::create(2000, $opcion, 1)->locale('es')->monthName) }}</option>
                @endforeach
            </select>
        </div>
        <div class="catalog-filter catalog-filter-period">
            <label for="anio" class="form-label-hr">Año</label>
            <select id="anio" name="anio" class="hr-input select2">
                <option value="">Todos los años</option>
                @foreach($anios->when($anio, fn ($lista) => $lista->push((string) $anio))->unique()->sortDesc() as $opcion)
                    <option value="{{ $opcion }}" @selected($anio == $opcion)>{{ $opcion }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-hr btn-outline-hr" type="submit">Filtrar</button>
        @if($puedeRegistrar)
            <a class="btn-hr btn-primary-hr catalog-toolbar-action" href="{{ route('horas-extras.create', $empresa ? ['empresa_id' => $empresa] : []) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-plus" aria-hidden="true"></i> Agregar horas extras</a>
        @endif
    </form>
    <div class="card-hr">
        <div class="card-hd">
            <div>
                <h1 class="card-title-hr">Listado de horas extras</h1>
                <p class="card-subtitle-hr">{{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }}</p>
            </div>
        </div>
        @if($registros->isEmpty())
            <div class="catalog-empty">
                <h2>No hay autorizaciones registradas para estos filtros</h2>
                @if($puedeRegistrar)
                    <p>Registra una autorización para que aparezca en este listado.</p>
                    <a class="btn-hr btn-primary-hr" href="{{ route('horas-extras.create', $empresa ? ['empresa_id' => $empresa] : []) }}">Agregar horas extras</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table class="bs-table hover" data-datatable="{{ config('constantes.itemsPorPagina') }}">
                    <thead><tr><th>Jornada</th><th>Empresa</th><th>Empleado</th><th class="text-center">Hr. Autorizadas</th><th>Autorizó</th><th class="no-search text-end">Acciones</th></tr></thead>
                    <tbody>
                        @foreach($registros as $registro)
                            @php($jornada = $registro->jornada)
                            <tr>
                                <td data-order="{{ $registro->fecha->toDateString() }}">{{ $registro->fecha->format('d/m/Y') }}</td>
                                <td>{{ $jornada['empresa'] ?? '—' }}</td>
                                <td>{{ $jornada['empleado'] }}</td>
                                <td class="text-center" data-order="{{ $registro->autorizados }}">
                                    {{ intdiv($registro->autorizados, 60) }} Hr
                                    @if($registro->autorizados % 60 !== 0)
                                        {{ $registro->autorizados % 60 }} Min
                                    @endif
                                </td>
                                <td>{{ $jornada['autorizador'] }}</td>
                                <td>
                                    <div class="catalog-actions">
                                        <a class="vacation-info-button text-decoration-none" href="{{ route('horas-extras.show', $registro) }}" aria-label="Ver detalle" title="Ver detalle"><i class="{{ config('constantes.tipo_iconos') }} fa-circle-info" aria-hidden="true"></i></a>
                                        @if($registro->tiene_constancia_firmada)
                                            <a class="btn-hr btn-success-hr btn-sm-hr" href="{{ route('horas-extras.constancia.download', $registro) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-download" aria-hidden="true"></i> Descargar constancia firmada</a>
                                        @else
                                            <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('horas-extras.pdf', $registro) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-download" aria-hidden="true"></i> Descargar PDF</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection

@if(session('horas_extras_success'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({ icon: 'success', text: @json(session('horas_extras_success')), confirmButtonText: 'Entendido', buttonsStyling: false, customClass: { confirmButton: 'btn-hr btn-primary-hr' } });
            });
        </script>
    @endpush
@endif
