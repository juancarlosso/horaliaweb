@php
    $jornada = $registro->jornada;
    $diferencia = $registro->autorizados - $registro->detectados;
    $duracion = fn (int $minutos) => intdiv($minutos, 60).' h '.($minutos % 60).' min';
    $datos = [
        'Empresa' => $jornada['empresa'],
        'Centro de trabajo' => $jornada['centro'],
        'Empleado' => $jornada['empleado'],
        'Código del empleado' => $jornada['codigo'],
        'Fecha de la jornada' => $registro->fecha->format('d/m/Y'),
        'Condiciones' => implode(' · ', $jornada['condiciones']),
        'Festivos' => implode(' · ', $jornada['festivos']),
        'Horario programado' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['programado']),
        'Zona horaria de la jornada' => $jornada['zona'],
        'Entrada real' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['entrada']),
        'Salida real' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['salida']),
        'Tiempo detectado' => $duracion($registro->detectados),
        'Criterio de cálculo' => $jornada['criterio'],
        'Tiempo autorizado' => $duracion($registro->autorizados),
        'Diferencia respecto al detectado' => ($diferencia > 0 ? '+' : ($diferencia < 0 ? '−' : '')).$duracion(abs($diferencia)),
        'Autorizó' => $jornada['autorizador'],
        'Registró' => $jornada['capturista'],
        'Fecha y hora de registro' => \App\Support\HoraliaTimezones::sinAbreviatura($jornada['registro_local']),
    ];
@endphp

@if($estiloPdf ?? false)
    @include('horas-extras.datos-pdf')
@else
    <dl class="vacation-info-grid">
        @foreach($datos as $etiqueta => $valor)
            <div class="vacation-info-item">
                <dt><strong>{{ $etiqueta }}</strong></dt>
                <dd class="mb-0"><span>{{ $valor === null || $valor === '' ? '—' : $valor }}</span></dd>
            </div>
        @endforeach
        @foreach(['Motivo' => $registro->motivo, 'Observaciones' => $registro->observaciones ?? '—'] as $etiqueta => $valor)
            <div class="vacation-info-item detalle-texto">
                <dt><strong>{{ $etiqueta }}</strong></dt>
                <dd class="mb-0"><span>{{ $valor }}</span></dd>
            </div>
        @endforeach
    </dl>
@endif
