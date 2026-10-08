Hola,

Usa este enlace en la tablet para abrir el reloj checador de {{ $companyName }}:

{{ $activationUrl }}

@if($expiresAt)
La sesión estará activa hasta {{ $expiresAt }}.
@else
El enlace debe abrirse durante los próximos 15 minutos para activar el checador. Una vez activado, la sesión durará {{ $durationDays }} {{ $durationDays === 1 ? 'día' : 'días' }}.
@endif

Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.
