<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Restablece tu contraseña | {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f4fa;font-family:Arial,Helvetica,sans-serif;color:#202541;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4fa;padding:36px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e5e8f1;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:25px 38px;background-color:#171a3b;background-image:linear-gradient(120deg,#171a3b,#25295a);">
                            <img src="{{ $logoUrl }}" width="164" alt="{{ $appName }}" style="display:block;width:164px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:42px 42px 36px;">
                            <p style="margin:0 0 12px;color:#536df5;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;">Seguridad de tu cuenta</p>
                            <h1 style="margin:0 0 18px;color:#202541;font-size:27px;line-height:1.25;font-weight:700;">Restablece tu contraseña</h1>
                            <p style="margin:0 0 14px;color:#444b65;font-size:16px;line-height:1.65;">Hola{{ filled($name) ? ' ' . $name : '' }},</p>
                            <p style="margin:0 0 26px;color:#626a83;font-size:15px;line-height:1.7;">Recibimos una solicitud para cambiar la contraseña de tu cuenta de {{ $appName }}. Usa el botón para crear una nueva:</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 27px;">
                                <tr>
                                    <td align="center" bgcolor="#536df5" style="border-radius:8px;box-shadow:0 5px 12px rgba(83,109,245,.22);">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 25px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:8px;">Restablecer contraseña</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 20px;color:#78809a;font-size:13px;line-height:1.6;">Por seguridad, este enlace vence en <strong style="color:#444b65;">{{ $expiration }} minutos</strong> y solo puede utilizarse una vez.</p>
                            <div style="height:1px;background:#e9ebf2;margin:25px 0;"></div>
                            <p style="margin:0 0 8px;color:#78809a;font-size:12px;line-height:1.6;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                            <p style="margin:0;color:#536df5;font-size:12px;line-height:1.6;word-break:break-all;"><a href="{{ $resetUrl }}" style="color:#536df5;">{{ $resetUrl }}</a></p>
                            <p style="margin:24px 0 0;color:#78809a;font-size:13px;line-height:1.6;">Si no solicitaste restablecer tu contraseña, ignora este correo. Tu contraseña actual seguirá siendo la misma.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:19px 28px;background:#f8f9fc;border-top:1px solid #e9ebf2;color:#8a91a7;font-size:12px;line-height:1.6;">
                            Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.
                        </td>
                    </tr>
                </table>
                <p style="margin:18px 0 0;color:#a0a6b8;font-size:11px;line-height:1.5;">© {{ date('Y') }} {{ $appName }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
