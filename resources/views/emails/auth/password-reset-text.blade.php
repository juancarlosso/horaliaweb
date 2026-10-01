Hola{{ filled($name) ? ' ' . $name : '' }},

Recibimos una solicitud para cambiar la contraseña de tu cuenta de {{ $appName }}.

Restablece tu contraseña usando este enlace:
{{ $resetUrl }}

Por seguridad, el enlace vence en {{ $expiration }} minutos y solo puede utilizarse una vez.

Si no solicitaste restablecer tu contraseña, ignora este correo. Tu contraseña actual seguirá siendo la misma.

{{ $appName }}
