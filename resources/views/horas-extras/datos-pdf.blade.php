@include('horas-extras.filas-pdf', ['titulo' => 'IDENTIFICACIÓN', 'campos' => [
    'Empresa' => 'Empresa', 'Centro de trabajo' => 'Centro de trabajo',
    'Empleado' => 'Empleado', 'Código del empleado' => 'Código de empleado',
]])

@include('horas-extras.filas-pdf', ['titulo' => 'JORNADA REGISTRADA', 'campos' => [
    'Fecha de la jornada' => 'Fecha de jornada', 'Condiciones' => 'Condiciones',
    'Horario programado' => 'Horario programado', 'Zona horaria de la jornada' => 'Zona horaria',
    'Entrada real' => 'Entrada registrada', 'Salida real' => 'Salida registrada',
    'Festivos' => 'Festivos', 'Criterio de cálculo' => 'Criterio de cálculo',
]])

<h2>RESUMEN DE TIEMPO</h2>
<table class="resumen">
    <tr>
        @foreach(['Tiempo detectado' => 'Tiempo detectado', 'Tiempo autorizado' => 'Tiempo autorizado', 'Diferencia respecto al detectado' => 'Diferencia'] as $campo => $etiqueta)
            <td><strong>{{ $datos[$campo] }}</strong><span class="etiqueta">{{ $etiqueta }}</span></td>
        @endforeach
    </tr>
</table>

@php
    $motivoEnTabla = mb_strlen($registro->motivo) <= 140 && substr_count($registro->motivo, "\n") <= 2;
    $datos['Motivo'] = $motivoEnTabla ? $registro->motivo : 'Ver motivo debajo';
@endphp

@include('horas-extras.filas-pdf', ['titulo' => 'JUSTIFICACIÓN Y RESPONSABLES', 'campos' => [
    'Motivo' => 'Motivo', 'Autorizó' => 'Autorizó',
    'Registró' => 'Registró', 'Fecha y hora de registro' => 'Registro',
]])

@if(!$motivoEnTabla)
    <p class="observaciones-titulo">Motivo</p>
    <p class="texto-registro observaciones">{{ $registro->motivo }}</p>
@endif
<p class="observaciones-titulo">Observaciones</p>
<p class="texto-registro observaciones">{{ $registro->observaciones ?? '—' }}</p>
