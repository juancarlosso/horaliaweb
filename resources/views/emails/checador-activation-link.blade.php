<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Enlace del reloj checador | {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f4fa;font-family:Arial,Helvetica,sans-serif;color:#202541;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4fa;padding:36px 12px;">
        <tr><td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e5e8f1;border-radius:14px;overflow:hidden;">
                <tr><td style="padding:25px 38px;background-color:#171a3b;background-image:linear-gradient(120deg,#171a3b,#25295a);">
                    <img src="{{ $logoUrl }}" width="164" alt="{{ $appName }}" style="display:block;width:164px;max-width:100%;height:auto;border:0;">
                </td></tr>
                <tr><td style="padding:42px 42px 36px;">
                    <p style="margin:0 0 12px;color:#536df5;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;">Asistencia · {{ $companyName }}</p>
                    <h1 style="margin:0 0 18px;color:#202541;font-size:27px;line-height:1.25;font-weight:700;">Enlace del reloj checador</h1>
                    <p style="margin:0 0 14px;color:#444b65;font-size:16px;line-height:1.65;">Hola,</p>
                    <p style="margin:0 0 22px;color:#626a83;font-size:15px;line-height:1.7;">Usa este enlace en la tablet para abrir el reloj checador de <strong>{{ $companyName }}</strong>.</p>
                    @if($expiresAt)
                        <p style="margin:0 0 22px;padding:13px 16px;border:1px solid #e5e8f1;border-radius:9px;background:#f8f9fc;color:#626a83;font-size:14px;line-height:1.6;">La sesión estará activa hasta <strong>{{ $expiresAt }}</strong>.</p>
                    @else
                        <p style="margin:0 0 22px;padding:13px 16px;border:1px solid #e5e8f1;border-radius:9px;background:#f8f9fc;color:#626a83;font-size:14px;line-height:1.6;">El enlace debe abrirse durante los próximos 15 minutos para activar el checador. Una vez activado, la sesión durará 24 horas.</p>
                    @endif
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 25px;">
                        <tr><td align="center" bgcolor="#536df5" style="border-radius:8px;box-shadow:0 5px 12px rgba(83,109,245,.22);">
                            <a href="{{ $activationUrl }}" style="display:inline-block;padding:14px 25px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:8px;">Abrir reloj checador</a>
                        </td></tr>
                    </table>
                    <p style="margin:0;color:#78809a;font-size:12px;line-height:1.6;word-break:break-all;">Si el botón no funciona, copia este enlace en el navegador de la tablet:<br>{{ $activationUrl }}</p>
                </td></tr>
                <tr><td align="center" style="padding:19px 28px;background:#f8f9fc;border-top:1px solid #e9ebf2;color:#8a91a7;font-size:12px;line-height:1.6;">Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.</td></tr>
            </table>
            <p style="margin:18px 0 0;color:#a0a6b8;font-size:11px;line-height:1.5;">© {{ date('Y') }} {{ $appName }}</p>
        </td></tr>
    </table>
</body>
</html>
