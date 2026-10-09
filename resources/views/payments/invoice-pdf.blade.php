<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 34px 34px; }
        body { color:#202541; font-family:DejaVu Sans,Arial,sans-serif; font-size:9px; line-height:1.45; }
        .top { border-bottom:2px solid #536df5; padding-bottom:13px; margin-bottom:17px; }
        .top-table { width:100%; border-collapse:collapse; }
        .logo { width:190px; height:auto; }
        .title { text-align:right; color:#202541; font-size:18px; font-weight:bold; }
        .subtitle { text-align:right; color:#69718d; font-size:9px; }
        .badge { display:inline-block; margin-top:6px; padding:4px 8px; border-radius:10px; color:#285c4b; background:#e5f5ef; font-size:8px; font-weight:bold; }
        .cols { width:100%; border-collapse:separate; border-spacing:8px 0; margin:0 -8px 13px; }
        .panel { width:50%; vertical-align:top; padding:11px 12px; border:1px solid #e2e6f0; border-radius:7px; }
        .panel-title { margin:0 0 8px; color:#536df5; font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:.5px; }
        .name { margin:0 0 5px; font-size:11px; font-weight:bold; }
        .label { color:#69718d; font-size:7px; font-weight:bold; text-transform:uppercase; }
        .value { margin:0 0 6px; font-size:8px; overflow-wrap:anywhere; }
        .meta { width:100%; border-collapse:collapse; margin:0 0 12px; }
        .meta td { width:25%; padding:7px 8px; border:1px solid #e2e6f0; }
        .meta td:first-child { border-radius:6px 0 0 6px; }
        .meta td:last-child { border-radius:0 6px 6px 0; }
        .meta strong { display:block; margin-top:3px; font-size:8px; }
        .items { width:100%; border-collapse:collapse; margin-bottom:9px; }
        .items th { padding:7px 8px; color:#69718d; background:#f4f6fb; font-size:7px; text-align:left; text-transform:uppercase; }
        .items td { padding:9px 8px; border-bottom:1px solid #e8ebf2; vertical-align:top; }
        .right { text-align:right; white-space:nowrap; }
        .concept-name { font-weight:bold; }
        .concept-meta { color:#69718d; font-size:7px; }
        .totals-wrap { width:100%; margin-bottom:13px; }
        .totals { width:230px; margin-left:auto; border-collapse:collapse; }
        .totals td { padding:4px 7px; }
        .totals .total td { padding-top:7px; border-top:1px solid #cfd5e4; font-size:11px; font-weight:bold; }
        .tax-note { margin:3px 0 13px; color:#69718d; font-size:7px; }
        .cert { margin-top:10px; padding:9px 10px; border:1px solid #e2e6f0; border-radius:7px; }
        .cert-title { margin:0 0 4px; color:#536df5; font-size:8px; font-weight:bold; }
        .seal-label { color:#69718d; font-size:7px; font-weight:bold; }
        .seal { margin:2px 0 6px; color:#3d4561; font-size:6px; overflow-wrap:anywhere; word-wrap:break-word; }
        .verify { width:100%; border-collapse:collapse; margin-top:13px; }
        .verify td { vertical-align:middle; }
        .qr { width:110px; height:110px; }
        .footer { margin-top:12px; padding-top:7px; border-top:1px solid #e2e6f0; color:#69718d; font-size:7px; text-align:center; }
    </style>
</head>
<body>
    <div class="top">
        <table class="top-table"><tr>
            <td><img class="logo" src="{{ $logoDataUri }}" alt="Horalia"></td>
            <td>
                <div class="title">Factura electrónica</div>
                <div class="subtitle">CFDI versión 4.0 - Comprobante de ingreso</div>
                <div class="subtitle">Serie y folio: {{ $invoice->serie }}-{{ $invoice->folio }}</div>
                <div class="badge">TIMBRADO</div>
            </td>
        </tr></table>
    </div>

    <table class="cols"><tr>
        <td class="panel">
            <h2 class="panel-title">Emisor</h2>
            <p class="name">{{ $issuer->getAttribute('Nombre') }}</p>
            <div class="label">RFC</div><p class="value">{{ $issuer->getAttribute('Rfc') }}</p>
            <div class="label">Régimen fiscal</div><p class="value">{{ $issuer->getAttribute('RegimenFiscal') }}{{ $issuerRegimenName ? ' - ' . $issuerRegimenName : '' }}</p>
            <div class="label">Lugar de expedición</div><p class="value">{{ $root->getAttribute('LugarExpedicion') }}</p>
        </td>
        <td class="panel">
            <h2 class="panel-title">Receptor</h2>
            <p class="name">{{ $receiver->getAttribute('Nombre') }}</p>
            <div class="label">RFC</div><p class="value">{{ $receiver->getAttribute('Rfc') }}</p>
            <div class="label">Domicilio fiscal</div><p class="value">{{ $receiver->getAttribute('DomicilioFiscalReceptor') }}</p>
            <div class="label">Régimen fiscal</div><p class="value">{{ $receiver->getAttribute('RegimenFiscalReceptor') }}{{ $receiverRegimenName ? ' - ' . $receiverRegimenName : '' }}</p>
            <div class="label">Uso CFDI</div><p class="value">{{ $receiver->getAttribute('UsoCFDI') }}{{ $usoCfdiName ? ' - ' . $usoCfdiName : '' }}</p>
        </td>
    </tr></table>

    <table class="meta"><tr>
        <td><span class="label">Fecha de emisión</span><strong>{{ \Carbon\Carbon::parse($root->getAttribute('Fecha'))->format('d/m/Y H:i') }}</strong></td>
        <td><span class="label">Método de pago</span><strong>{{ $root->getAttribute('MetodoPago') }}</strong></td>
        <td><span class="label">Forma de pago</span><strong>{{ $root->getAttribute('FormaPago') }}</strong></td>
        <td><span class="label">Moneda</span><strong>{{ $root->getAttribute('Moneda') }}</strong></td>
    </tr></table>

    <table class="items">
        <thead><tr><th>Cant.</th><th>Clave y descripción</th><th>Unidad</th><th class="right">Valor unitario</th><th class="right">Importe</th></tr></thead>
        <tbody><tr>
            <td>{{ $concept->getAttribute('Cantidad') }}</td>
            <td><span class="concept-name">{{ $concept->getAttribute('Descripcion') }}</span><br><span class="concept-meta">Clave SAT: {{ $concept->getAttribute('ClaveProdServ') }} · Objeto de impuesto: {{ $concept->getAttribute('ObjetoImp') }}</span></td>
            <td>{{ $concept->getAttribute('ClaveUnidad') }} - {{ $concept->getAttribute('Unidad') }}</td>
            <td class="right">${{ number_format((float) $concept->getAttribute('ValorUnitario'), 2) }}</td>
            <td class="right">${{ number_format((float) $concept->getAttribute('Importe'), 2) }}</td>
        </tr></tbody>
    </table>

    <table class="totals-wrap"><tr><td>
        <table class="totals">
            <tr><td>Subtotal</td><td class="right">${{ number_format((float) $root->getAttribute('SubTotal'), 2) }}</td></tr>
            <tr><td>IVA trasladado ({{ $tasaIva }}%)</td><td class="right">${{ number_format((float) $invoice->iva, 2) }}</td></tr>
            <tr class="total"><td>Total</td><td class="right">${{ number_format((float) $root->getAttribute('Total'), 2) }} {{ $root->getAttribute('Moneda') }}</td></tr>
        </table>
    </td></tr></table>

    <div class="cert">
        <p class="cert-title">Timbre Fiscal Digital</p>
        <table class="meta"><tr>
            <td><span class="label">UUID</span><strong>{{ $stamp->getAttribute('UUID') }}</strong></td>
            <td><span class="label">Fecha de timbrado</span><strong>{{ \Carbon\Carbon::parse($stamp->getAttribute('FechaTimbrado'))->format('d/m/Y H:i:s') }}</strong></td>
            <td><span class="label">Certificado SAT</span><strong>{{ $stamp->getAttribute('NoCertificadoSAT') }}</strong></td>
            <td><span class="label">Certificado emisor</span><strong>{{ $root->getAttribute('NoCertificado') }}</strong></td>
        </tr></table>
        <div class="seal-label">Sello digital del CFDI</div><p class="seal">{{ $root->getAttribute('Sello') }}</p>
        <div class="seal-label">Sello digital del SAT</div><p class="seal">{{ $stamp->getAttribute('SelloSAT') }}</p>
    </div>

    <table class="verify"><tr>
        <td width="125"><img class="qr" src="{{ $qrDataUri }}" alt="Código QR para verificar el CFDI"></td>
        <td><strong>Verifica este comprobante</strong><br>Escanea el código QR o consulta el UUID en el portal del SAT.<br><span class="concept-meta">Folio de pago Horalia: {{ $payment->folio }}</span></td>
    </tr></table>

    <div class="footer">Este documento es una representación impresa de un CFDI. · {{ config('app.name', 'Horalia') }} · {{ $invoice->serie }}-{{ $invoice->folio }}</div>
</body>
</html>
