<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Constancia de autorización de horas extraordinarias</title>
    <style>
        @page { margin: 48pt 54pt 48pt; }
        body { margin: 0; color: #17172f; font: 8pt/1.24 'DejaVu Sans', sans-serif; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .encabezado { border-bottom: 1.8pt solid #4568ff; }
        .encabezado td { padding: 0 6pt 9pt; vertical-align: top; }
        .logo { display: inline-block; line-height: 0; }
        .logo-horalia { background: #111327; padding: 0 4pt; }
        h1 { margin: 7pt 0 0; font-size: 16pt; line-height: 1.25; }
        .folio { width: 30%; padding-top: 8pt !important; text-align: right; color: #737b9c; }
        .emision { margin-top: 28pt; }
        h2 { margin: 12pt 6pt 5pt; color: #4568ff; font-size: 9pt; page-break-after: avoid; }
        .campos td { border-bottom: .55pt solid #d6ddf5; padding: 5pt 6pt; vertical-align: top; overflow-wrap: break-word; }
        .campos tr:last-child td { border-bottom: 0; }
        .etiqueta { color: #737b9c; font-size: 7pt; }
        .campos .etiqueta { width: 17%; }
        .valor { font-weight: bold; }
        .resumen { page-break-inside: avoid; }
        .resumen td { width: 33.33%; background: #f4f6ff; border: .55pt solid #d6ddf5; padding: 6pt 4pt 7pt; text-align: center; }
        .resumen strong { display: block; margin-bottom: 6pt; font-size: 15pt; }
        .texto-registro { margin: 0; white-space: pre-wrap; overflow-wrap: break-word; }
        .observaciones-titulo, .nota { margin: 8pt 6pt 0; color: #737b9c; font-size: 7pt; }
        .observaciones { margin-top: 2pt; padding: 7pt 6pt; background: #f4f6ff; border: .55pt solid #d6ddf5; font-size: 7.5pt; }
        .firmas { page-break-inside: avoid; }
        .firmas td { width: 50%; padding: 22pt 6pt 0; vertical-align: top; overflow-wrap: break-word; }
        .firma-linea { width: 116pt; margin-bottom: 6pt; border-top: .6pt solid #17172f; }
        .firma-cargo { display: block; margin-top: 4pt; }
        .nota { line-height: 1.4; }
    </style>
</head>
<body>
    <table class="encabezado">
        <tr>
            <td>
                @isset($logo)
                    <div @class(['logo', 'logo-horalia' => $logoHoralia ?? false])>
                        <img src="{{ $logo['src'] }}" style="width: {{ $logo['ancho'] }}pt; height: {{ $logo['alto'] }}pt;" alt="{{ ($logoHoralia ?? false) ? 'Horalia' : $registro->jornada['empresa'] }}">
                    </div>
                @endisset
                <h1>CONSTANCIA DE HORAS<br>EXTRAORDINARIAS</h1>
            </td>
            <td class="folio">
                AUTORIZACIÓN {{ $registro->folio }}
                <div class="emision">Emitida el {{ now($registro->jornada['zona'])->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    @include('horas-extras.datos', ['estiloPdf' => true])

    <div class="firmas">
        <h2>FIRMAS DE CONFORMIDAD</h2>
        <table>
            <tr>
                <td>
                    <div class="firma-linea"></div>
                    <strong>{{ $registro->jornada['empleado'] }}</strong>
                    <span class="firma-cargo etiqueta">Firma del empleado</span>
                </td>
                <td>
                    <div class="firma-linea"></div>
                    <strong>{{ $registro->jornada['autorizador'] }}</strong>
                    <span class="firma-cargo etiqueta">Firma de quien autoriza</span>
                </td>
            </tr>
        </table>
    </div>
    <p class="nota">Documento de soporte administrativo. Los tiempos registrados y los autorizados se presentan por separado. Las jornadas en días festivos o de descanso pueden requerir tratamiento específico en nómina.</p>
</body>
</html>
