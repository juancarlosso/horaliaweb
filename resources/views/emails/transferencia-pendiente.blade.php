<h1>Comprobante de transferencia por validar</h1>
<p>Empresa: {{ $transferencia->empresa->nombre }} (ID {{ $transferencia->empresa_id }})</p>
<p>Concepto: {{ $transferencia->concepto }}<br>Importe esperado: ${{ number_format((float) $transferencia->cantidad, 2) }} MXN<br>Periodo / vencimiento: {{ $transferencia->fecha_renovacion->format('d/m/Y') }}<br>Recibido: {{ $transferencia->cargado_en?->format('d/m/Y H:i') }}<br>Usuario: {{ $transferencia->usuarioCarga?->name ?: 'Usuario' }} (ID {{ $transferencia->usuario_carga_id }})</p>
@if($transferencia->comprobanteActual)<p><a href="{{ route('transferencias.admin.file', $transferencia->comprobanteActual->id) }}">Abrir comprobante (requiere sesión de Superadministrador)</a></p>@endif
<p><a href="{{ $adminUrl }}">Revisar transferencias</a></p>
