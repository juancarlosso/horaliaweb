@extends('emails.layout')

@section('title', 'Reemplaza tu comprobante de transferencia')
@section('category', 'Pago por transferencia')
@section('heading', 'Reemplaza tu comprobante de transferencia')

@section('content')
    <p style="margin:0 0 18px;">El comprobante de <strong>{{ $transferencia->empresa->nombre }}</strong> para el periodo <strong>{{ $transferencia->fecha_renovacion->format('d/m/Y') }}</strong> requiere corrección.</p>

    <div style="margin:0 0 22px;padding:16px 20px;background:#f8f9fc;border:1px solid #e9ebf2;border-radius:8px;">
        <p style="margin:0;"><strong>Motivo:</strong> {{ $transferencia->motivo_rechazo }}</p>
    </div>

    <p style="margin:0 0 18px;">Puedes cargar un comprobante corregido desde Historial de Pagos.</p>
    <p style="margin:0;"><a href="{{ $historyUrl }}" style="display:inline-block;padding:11px 20px;border-radius:8px;background:#536df5;color:#ffffff;font-weight:700;text-decoration:none;">Ir al Historial de Pagos</a></p>
@endsection
