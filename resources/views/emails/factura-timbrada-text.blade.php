Hola,

Adjuntamos el XML timbrado y el PDF de tu pago de membresía de {{ $empresa?->razon_social ?: $appName }}.

Serie y folio: {{ $factura->serie }}-{{ $factura->folio }}
UUID: {{ $factura->uuid }}
Total: ${{ number_format((float) $factura->total, 2) }} MXN

Conserva ambos archivos para tus registros fiscales.
Accede a Horalia: {{ $loginUrl }}

Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.
