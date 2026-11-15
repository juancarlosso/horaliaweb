<h1>Reemplaza tu comprobante de transferencia</h1>
<p>El comprobante de {{ $transferencia->empresa->nombre }} para el periodo {{ $transferencia->fecha_renovacion->format('d/m/Y') }} requiere corrección.</p>
<p>Motivo: {{ $transferencia->motivo_rechazo }}</p>
<p>Puedes cargar un comprobante corregido desde <a href="{{ $historyUrl }}">Historial de Pagos</a>.</p>
