@extends('emails.layout')

@section('title', 'Comprobante de transferencia por validar')
@section('category', 'Pago por transferencia')
@section('heading', 'Comprobante de transferencia por validar')

@section('content')
    <p style="margin:0 0 18px;">Se recibió un comprobante de <strong>{{ $transferencia->empresa->nombre }}</strong> (ID {{ $transferencia->empresa_id }}).</p>

    <div style="margin:0 0 22px;padding:16px 20px;background:#f8f9fc;border:1px solid #e9ebf2;border-radius:8px;">
        <p style="margin:0;"><strong>Concepto:</strong> {{ $transferencia->concepto }}<br>
            <strong>Importe esperado:</strong> ${{ number_format((float) $transferencia->cantidad, 2) }} MXN<br>
            <strong>Periodo / vencimiento:</strong> {{ $transferencia->fecha_renovacion->format('d/m/Y') }}<br>
            <strong>Recibido:</strong> {{ $transferencia->cargado_en?->format('d/m/Y H:i') }}<br>
            <strong>Usuario:</strong> {{ $transferencia->usuarioCarga?->name ?: 'Usuario' }} (ID {{ $transferencia->usuario_carga_id }})
        </p>
    </div>

    <p style="margin:0 0 18px;"><a href="{{ $adminUrl }}" style="display:inline-block;padding:11px 20px;border-radius:8px;background:#536df5;color:#ffffff;font-weight:700;text-decoration:none;">Revisar transferencias</a></p>

    @if($transferencia->comprobanteActual)
        <p style="margin:0;"><a href="{{ route('transferencias.admin.file', $transferencia->comprobanteActual->id) }}" style="color:#536df5;">Abrir comprobante</a> (requiere sesión de Superadministrador).</p>
    @endif
@endsection
