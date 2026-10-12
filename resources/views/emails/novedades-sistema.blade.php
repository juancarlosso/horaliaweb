<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Novedades | {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f4fa;font-family:Arial,Helvetica,sans-serif;color:#202541;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4fa;padding:36px 12px;">
        <tr><td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e5e8f1;border-radius:14px;overflow:hidden;">
                <tr><td style="padding:25px 38px;background-color:#171a3b;background-image:linear-gradient(120deg,#171a3b,#25295a);">
                    <img src="{{ $logoUrl }}" width="164" alt="{{ $appName }}" style="display:block;width:164px;max-width:100%;height:auto;border:0;">
                </td></tr>
                <tr><td style="padding:42px 42px 36px;color:#626a83;font-size:15px;line-height:1.7;">
                    <div style="color:#626a83;font-size:15px;line-height:1.7;">
                        {!! \Illuminate\Support\Str::markdown($contenido) !!}
                    </div>
                </td></tr>
                <tr><td align="center" style="padding:19px 28px;background:#f8f9fc;border-top:1px solid #e9ebf2;color:#8a91a7;font-size:12px;line-height:1.6;">Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.</td></tr>
            </table>
            <p style="margin:18px 0 0;color:#a0a6b8;font-size:11px;line-height:1.5;">© {{ date('Y') }} {{ $appName }}</p>
        </td></tr>
    </table>
</body>
</html>
