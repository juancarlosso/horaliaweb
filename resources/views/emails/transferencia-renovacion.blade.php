<p style="margin:0 0 18px;color:#444b65;font-size:16px;">¡Hola!</p>
<p style="margin:0 0 18px;">La fecha de renovación de <strong>{{ $companyName }}</strong> es el <strong>{{ $renewalDate }}</strong>.</p>

@if($pending)
    <p style="margin:0 0 18px;">Recibimos tu comprobante y está en proceso de validación. Puedes consultar el estado desde Horalia.</p>
@else
    @if($payment->estado === 'rechazado')
        <p style="margin:0 0 18px;">El comprobante de este periodo fue rechazado.
            @if(filled($payment->motivo_rechazo))
                <strong>Motivo:</strong> {{ $payment->motivo_rechazo }}.
            @endif
            Si ya realizaste la transferencia, revisa el motivo del rechazo antes de hacer otro pago.
        </p>
    @endif
    <h2 style="margin:0 0 8px;color:#202541;font-size:19px;">Datos para tu transferencia</h2>
    <ul style="margin:0 0 18px;padding-left:22px;">
        <li>Beneficiario: {{ $bank['beneficiario'] }}</li>
        <li>Banco: {{ $bank['banco'] }}</li>
        <li>CLABE interbancaria: {{ $bank['clabe'] }}</li>
        @if(filled($bank['cuenta'] ?? null))
            <li>Número de cuenta: {{ $bank['cuenta'] }}</li>
        @endif
        <li>Importe exacto: <strong>${{ number_format((float) $payment->cantidad, 2) }} MXN</strong></li>
        <li>Concepto obligatorio: <strong>{{ $payment->concepto }}</strong></li>
    </ul>
    <p style="margin:0 0 18px;">Usa el importe y el concepto indicados. Tras realizar la transferencia, ingresa a Horalia y carga el comprobante desde Historial de pagos.</p>
@endif

<p style="margin:0 0 18px;">El periodo de gracia es de cinco días naturales e incluye la fecha de renovación.
    @if($remainingDays === 1)
        <strong>Hoy es el último día para que el pago quede confirmado.</strong>
    @else
        <strong>Quedan {{ $remainingDays }} días naturales, incluyendo hoy.</strong>
    @endif
    La fecha límite de confirmación es el <strong>{{ $deadline }}</strong>. Si el pago sigue sin confirmarse, el servicio se suspenderá el <strong>{{ $suspensionDate }}</strong>.
</p>
<p style="margin:0 0 18px;">La validación puede tomar hasta <strong>48 horas hábiles</strong> desde que recibimos el comprobante.
    Cargarlo no confirma el pago ni amplía el periodo de gracia.
    @if(!$pending)
        <br><strong>Horario de revisión:</strong> {{ $reviewHours['dias'] }}, de {{ $reviewHours['abre'] }} a {{ $reviewHours['cierra'] }} ({{ $reviewHours['timezone'] }}). Días inhábiles: {{ $reviewHours['inhabiles'] }}.
    @endif
</p>
<p style="margin:0 0 22px;"><a href="{{ $paymentUrl }}" style="display:inline-block;padding:11px 20px;border-radius:8px;background:#536df5;color:#ffffff;font-weight:700;text-decoration:none;">Ingresar a Horalia</a></p>
