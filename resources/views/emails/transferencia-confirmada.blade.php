<h1>✅ ¡Tu pago ha sido confirmado!</h1>
<p>Hemos validado tu transferencia para {{ $empresa->nombre }}, correspondiente al periodo que vencía el {{ $pago->fecha_renovacion->format('d/m/Y') }}. La suscripción quedó renovada hasta el {{ \Carbon\Carbon::parse($nuevaFecha)->format('d/m/Y') }}.</p>
<p>Importe confirmado: ${{ number_format((float) $pago->cantidad, 2) }} MXN.</p>
<p><a href="{{ $historyUrl }}">Consultar historial de pagos</a></p>
