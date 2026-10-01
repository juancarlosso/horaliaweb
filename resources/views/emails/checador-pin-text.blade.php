Hola{{ filled($recipientName) ? ' ' . $recipientName : '' }},

Este es tu PIN para registrar tus entradas y salidas en el checador de {{ $companyName }}:

{{ $pin }}

Usa este código únicamente en el checador de tu centro de trabajo. No lo compartas con otras personas.

Accede a tu cuenta: {{ $loginUrl }}

Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.
