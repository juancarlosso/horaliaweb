@extends('layouts.dashboard', ['pageTitle' => 'Agregar horas extras'])

@section('content')
@include('components.datatable-assets')
@include('components.administrative-catalog-styles')
<section class="catalog-page">
    <div class="card-hr">
        <div class="card-hd d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h1 class="card-title-hr">Agregar horas extras</h1>
            <a class="btn-hr btn-outline-hr" href="{{ route('horas-extras.index', $empresa ? ['empresa_id' => $empresa] : []) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-left" aria-hidden="true"></i> Regresar</a>
        </div>
        <div class="card-bd">
            <div class="alert alert-info mb-3" role="note">
                <p class="d-flex align-items-center gap-2 mb-2">
                    <i class="{{ config('constantes.tipo_iconos') }} fa-circle-info text-primary" aria-hidden="true"></i>
                    <strong>Registro de horas extras</strong>
                </p>
                <p class="mb-0">Utiliza los filtros para localizar la jornada laboral a la que deseas registrar horas extras. Una vez identificada, selecciónala e indica el tiempo extraordinario autorizado, el motivo y la persona responsable de autorizarlo.</p>
            </div>
            <form id="buscar-jornadas" method="GET" action="{{ route('horas-extras.create') }}">
                <div class="row g-3">
                    @include('horas-extras.empresa', ['buscador' => true])
                    <div class="col-sm-6 col-lg-4">
                        <label for="personal_id" class="form-label-hr">Empleado</label>
                        <select name="personal_id" id="personal_id" class="hr-input select2" @disabled(!$empresa)>
                            <option value="">Todos los Empleados</option>
                            @foreach($empleados as $opcion)
                                <option value="{{ $opcion->id }}" @selected($empleado === (int) $opcion->id)>{{ $opcion->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="desde" class="form-label-hr">Desde <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="hr-input" id="desde" name="desde" value="{{ $desde }}" required>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="hasta" class="form-label-hr">Hasta <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="hr-input" id="hasta" name="hasta" value="{{ $hasta }}" required>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                    <button class="btn-hr btn-primary-hr" type="submit">Buscar jornadas</button>
                    <span class="text-muted" role="status">
                        <span class="spinner-border spinner-border-sm me-1" data-search-spinner aria-hidden="true" hidden></span>
                        <span data-search-status></span>
                    </span>
                </div>
            </form>
        </div>
    </div>
    @if($buscado)
        <div class="card-hr mt-3">
            <div class="card-hd">
                <div>
                    <h2 class="card-title-hr">Jornadas encontradas</h2>
                    <p class="card-subtitle-hr">{{ $jornadas->count() }} {{ $jornadas->count() === 1 ? 'jornada' : 'jornadas' }}</p>
                </div>
            </div>
            @if($jornadas->isEmpty())
                <div class="catalog-empty"><h2>No se encontraron jornadas para el periodo seleccionado</h2></div>
            @else
                <div class="table-wrap">
                    <table class="bs-table hover" data-datatable="{{ config('constantes.itemsPorPagina') }}">
                        <thead>
                            <tr><th>Fecha</th><th>Empleado</th><th>Salida</th><th class="text-center">Hr. Detectadas</th><th>Condiciones</th><th class="no-search">Acciones</th></tr>
                        </thead>
                        <tbody>
                            @foreach($jornadas as $jornada)
                                @php
                                    $jornadaVisible = array_replace($jornada, [
                                        'entrada' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['entrada']),
                                        'salida' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['salida']),
                                        'programado' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['programado']),
                                    ]);
                                @endphp
                                <tr>
                                    <td data-order="{{ $jornada['fecha'] }}">{{ \Carbon\CarbonImmutable::parse($jornada['fecha'])->format('d/m/Y') }}</td>
                                    <td>{{ $jornada['empleado'] }}</td>
                                    <td>{{ $jornadaVisible['salida'] }}</td>
                                    <td class="text-center" data-order="{{ $jornada['minutos'] }}">
                                        {{ \App\Models\Asistencia::formatoDuracion($jornada['minutos']) }}
                                        @if($jornada['minutos'] === 0)
                                            <small class="text-muted d-block">Festivo programado sin tiempo adicional</small>
                                        @endif
                                    </td>
                                    <td>{{ implode(' · ', $jornada['condiciones']) }}</td>
                                    <td>
                                        @if($jornada['minutos'] > 0)
                                            <button type="button" class="btn-hr btn-success-hr btn-sm-hr" data-bs-toggle="modal" data-bs-target="#registroHorasExtras"
                                                data-jornada="{{ json_encode($jornadaVisible, JSON_THROW_ON_ERROR) }}">Registrar horas extras</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</section>

<div class="modal fade" id="registroHorasExtras" tabindex="-1" aria-labelledby="tituloRegistroHorasExtras" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('horas-extras.store') }}">
            @csrf
            <input type="hidden" name="empresa_id" value="{{ $empresa }}">
            <input type="hidden" name="asistencia_id">
            <input type="hidden" name="revision">
            <div class="modal-header card-hd">
                <h2 class="modal-title card-title-hr" id="tituloRegistroHorasExtras">Registrar horas extras</h2>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body card-bd">
                <div class="alert alert-warning"><strong>Importante: registro definitivo.</strong> Una vez guardada la autorización de horas extras, el registro no podrá modificarse ni eliminarse. Verifica cuidadosamente las horas autorizadas, el responsable y la información capturada antes de continuar.</div>
                <dl class="vacation-info-grid">
                    @foreach(['empresa' => 'Empresa', 'centro' => 'Centro de trabajo', 'empleado' => 'Empleado', 'fecha' => 'Jornada', 'condiciones' => 'Condiciones', 'festivos' => 'Festivos', 'programado' => 'Horario programado', 'entrada' => 'Entrada', 'salida' => 'Salida', 'detectado' => 'Tiempo detectado', 'criterio' => 'Criterio de cálculo'] as $campo => $etiqueta)
                        <div class="vacation-info-item">
                            <dt><strong>{{ $etiqueta }}</strong></dt>
                            <dd class="mb-0"><span data-campo="{{ $campo }}"></span></dd>
                        </div>
                    @endforeach
                </dl>
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label-hr" for="horas">Horas autorizadas <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="number" class="hr-input" id="horas" name="horas" min="0" max="71582787" step="1" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label-hr" for="minutos">Minutos autorizados <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="number" class="hr-input" id="minutos" name="minutos" min="0" max="59" step="1" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label-hr" for="autorizador_personal_id">Quién autoriza <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input" id="autorizador_personal_id" name="autorizador_personal_id" required>
                            <option value="">Selecciona un responsable</option>
                            @foreach($empleados as $autorizador)
                                <option value="{{ $autorizador->id }}">{{ $autorizador->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label-hr" for="motivo">Motivo <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea class="hr-input" id="motivo" name="motivo" rows="2" maxlength="5000" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label-hr" for="observaciones">Observaciones <span class="text-danger" aria-hidden="true">*</span></label>
                        <p class="form-text mt-0" id="diferenciaHorasExtras" aria-live="polite"></p>
                        <textarea class="hr-input" id="observaciones" name="observaciones" rows="3" maxlength="5000" aria-describedby="diferenciaHorasExtras" required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-hr btn-outline-hr" type="button" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn-hr btn-primary-hr" type="submit">Guardar autorización</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const desde = document.getElementById('desde');
    const hasta = document.getElementById('hasta');
    const maxDias = @json($maxDias);

    function actualizarLimites() {
        const ultimoDia = desde.valueAsDate;
        hasta.min = desde.value;
        if (ultimoDia) {
            ultimoDia.setUTCDate(ultimoDia.getUTCDate() + maxDias - 1);
        }
        hasta.max = ultimoDia?.toISOString().slice(0, 10) ?? '';
    }

    desde.addEventListener('input', actualizarLimites);
    actualizarLimites();

    const buscador = document.getElementById('buscar-jornadas');
    const botonBuscar = buscador.querySelector('button[type="submit"]');
    const spinner = buscador.querySelector('[data-search-spinner]');
    const estadoBusqueda = buscador.querySelector('[data-search-status]');
    const selectorEmpresa = buscador.querySelector('#empresa_id');
    const selectorEmpleado = buscador.querySelector('#personal_id');
    const rutaEmpleados = @json(route('horas-extras.empleados', ['empresa' => '__empresa__']));
    let solicitudEmpleados;

    $(selectorEmpresa).on('change', async () => {
        solicitudEmpleados?.abort();
        const empresa = selectorEmpresa.value;
        selectorEmpleado.length = 1;
        selectorEmpleado.value = '';
        selectorEmpleado.disabled = true;
        $(selectorEmpleado).trigger('change.select2');
        if (!empresa) return;

        const solicitud = new AbortController();
        solicitudEmpleados = solicitud;
        try {
            const respuesta = await fetch(rutaEmpleados.replace('__empresa__', encodeURIComponent(empresa)), {
                signal: solicitud.signal, headers: { Accept: 'application/json' },
            });
            if (!respuesta.ok) throw new Error();
            const empleados = await respuesta.json();
            if (solicitud.signal.aborted) return;
            empleados.forEach(empleado => selectorEmpleado.add(new Option(empleado.nombre, empleado.id)));
            selectorEmpleado.disabled = false;
            $(selectorEmpleado).trigger('change.select2');
        } catch (error) {
            if (!solicitud.signal.aborted) {
                Swal.fire({
                    icon: 'error', title: 'No se pudieron cargar los empleados',
                    text: 'Puedes buscar a todos los empleados activos o volver a seleccionar la empresa.',
                    confirmButtonText: 'Entendido', buttonsStyling: false,
                    customClass: { confirmButton: 'btn-hr btn-primary-hr' },
                });
            }
        }
    });

    function mostrarBusqueda(enCurso) {
        botonBuscar.disabled = enCurso;
        spinner.hidden = !enCurso;
        estadoBusqueda.textContent = enCurso ? 'Buscando jornadas…' : '';
    }

    buscador.addEventListener('submit', event => {
        if (botonBuscar.disabled) {
            event.preventDefault();
            return;
        }
        mostrarBusqueda(true);
    });
    window.addEventListener('pageshow', () => mostrarBusqueda(false));

    const modal = document.getElementById('registroHorasExtras');
    const form = modal.querySelector('form');
    const field = name => form.elements.namedItem(name);
    const hours = field('horas');
    const minutes = field('minutos');
    const autorizador = field('autorizador_personal_id');
    const opcionesAutorizador = Array.from(autorizador.options);
    const $autorizador = $(autorizador).select2({ width: '100%', dropdownParent: $(modal) });
    const message = document.getElementById('diferenciaHorasExtras');
    const old = @json($errors->has('jornada') ? [] : session()->getOldInput());
    const fields = ['horas', 'minutos', 'autorizador_personal_id', 'motivo', 'observaciones'];
    let detected = 0;

    function updateDifference() {
        const total = Number(hours.value) * 60 + Number(minutes.value);
        const difference = total - detected;
        hours.setCustomValidity(total > 0 ? '' : 'El tiempo autorizado debe ser mayor a cero.');
        message.textContent = difference === 0 ? ''
            : `El tiempo autorizado es ${difference > 0 ? 'mayor' : 'menor'} al detectado. Explica la diferencia.`;
    }

    modal.addEventListener('show.bs.modal', event => {
        const jornada = JSON.parse(event.relatedTarget.dataset.jornada);
        form.reset();
        autorizador.replaceChildren(...opcionesAutorizador.filter(option => option.value !== String(jornada.personal_id)));
        autorizador.value = '';
        detected = jornada.minutos;
        field('asistencia_id').value = jornada.id;
        field('revision').value = jornada.revision;
        hours.value = Math.floor(detected / 60);
        minutes.value = detected % 60;
        const data = {
            ...jornada,
            fecha: jornada.fecha.split('-').reverse().join('/'),
            detectado: `${hours.value} h ${minutes.value} min`,
        };
        modal.querySelectorAll('[data-campo]').forEach(element => {
            const value = data[element.dataset.campo];
            element.textContent = (Array.isArray(value) ? value.join(' · ') : value) || '—';
        });
        if (String(old.asistencia_id) === String(jornada.id) && old.revision === jornada.revision) {
            fields.forEach(name => field(name).value = old[name] ?? '');
            delete old.asistencia_id;
        }
        $autorizador.trigger('change.select2');
        updateDifference();
    });

    hours.addEventListener('input', updateDifference);
    minutes.addEventListener('input', updateDifference);
});
</script>
@endpush
