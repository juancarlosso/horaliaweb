@extends('emails.layout')

@section('title', 'Tu pago por transferencia fue confirmado')
@section('category', 'Pago por transferencia')
@section('heading', '¡Tu pago ha sido confirmado!')

@section('content')
    <p style="margin:0 0 18px;">Hemos validado tu transferencia para <strong>{{ $empresa->nombre }}</strong>, correspondiente al periodo que vencía el <strong>{{ $pago->fecha_renovacion->format('d/m/Y') }}</strong>.</p>

    <div style="margin:0 0 22px;padding:16px 20px;background:#f8f9fc;border:1px solid #e9ebf2;border-radius:8px;">
        <p style="margin:0 0 4px;">La suscripción quedó renovada hasta el <strong>{{ \Carbon\Carbon::parse($nuevaFecha)->format('d/m/Y') }}</strong>.</p>
        <p style="margin:0;"><strong>Importe confirmado</strong> · ${{ number_format((float) $pago->cantidad, 2) }} MXN</p>
    </div>

    <p style="margin:0;"><a href="{{ $historyUrl }}" style="display:inline-block;padding:11px 20px;border-radius:8px;background:#536df5;color:#ffffff;font-weight:700;text-decoration:none;">Consultar historial de pagos</a></p>
@endsection
