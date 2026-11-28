<p class="mt-1 mb-0 text-danger-emphasis">
    <strong>Comprobante rechazado.</strong>
    @if(filled($rejectedTransfer->motivo_rechazo))
        Motivo: {{ $rejectedTransfer->motivo_rechazo }}
    @endif
    @if($rejectedTransfer->comprobanteActual)
        @php($isPdf = $rejectedTransfer->comprobanteActual->mime === 'application/pdf')
        <a class="text-reset fw-semibold d-inline-block" href="{{ route('transferencias.rejected-proof', $rejectedTransfer->empresa_id) }}" @unless($isPdf) target="_blank" rel="noopener" @endunless>{{ $isPdf ? 'Descargar comprobante enviado' : 'Ver comprobante enviado' }}</a>
    @endif
</p>
