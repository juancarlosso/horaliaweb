<?php

namespace App\Services;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InvoicePdfService
{
    public function render(Factura $invoice, string $xml): string
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                throw new RuntimeException('No fue posible leer el CFDI timbrado para crear su representación impresa.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        $root = $document->documentElement;
        $issuer = $xpath->query('/cfdi:Comprobante/cfdi:Emisor')->item(0);
        $receiver = $xpath->query('/cfdi:Comprobante/cfdi:Receptor')->item(0);
        $concept = $xpath->query('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto')->item(0);
        $stamp = $xpath->query('//tfd:TimbreFiscalDigital')->item(0);

        if (!$root instanceof \DOMElement || !$issuer instanceof \DOMElement || !$receiver instanceof \DOMElement || !$concept instanceof \DOMElement || !$stamp instanceof \DOMElement) {
            throw new RuntimeException('El CFDI timbrado está incompleto y no se puede representar.');
        }

        $company = $invoice->intentoPago?->empresa;
        $logoPath = public_path((string) config('constantes.facturacion.logo_pdf', 'assets/logos/logoLN.png'));
        if (!is_file($logoPath)) {
            throw new RuntimeException('No se encontró el logotipo configurado para la factura.');
        }

        $qrUrl = $this->satVerificationUrl($root, $issuer, $receiver);
        $qr = Http::timeout(20)->accept('image/png')->get(
            (string) config('constantes.facturacion.qr_graphoria_url'),
            ['text' => $qrUrl, 'color' => '202541', 'background' => 'ffffff', 'size' => 600, 'margin' => 8]
        );
        if (!$qr->successful() || !str_starts_with((string) $qr->header('Content-Type'), 'image/')) {
            throw new RuntimeException('Graphoria no devolvió la imagen QR para el CFDI.');
        }
        $qrImage = $qr->body();
        $image = @getimagesizefromstring($qrImage);
        if ($image === false || $image[0] < 100 || $image[1] < 100) {
            throw new RuntimeException('Graphoria devolvió una imagen QR inválida.');
        }

        $regimenes = config('constantes.regimenes_fiscales', []);
        $usoCfdi = config('constantes.uso_cfdi', []);
        $taxRate = (int) config('constantes.tasa_iva', 16);

        return Pdf::loadView('payments.invoice-pdf', [
            'invoice' => $invoice,
            'payment' => $invoice->intentoPago,
            'company' => $company,
            'issuer' => $issuer,
            'receiver' => $receiver,
            'concept' => $concept,
            'stamp' => $stamp,
            'root' => $root,
            'qrDataUri' => 'data:' . ($image['mime'] ?? 'image/png') . ';base64,' . base64_encode($qrImage),
            'logoDataUri' => 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)),
            'issuerRegimenName' => $regimenes[$issuer->getAttribute('RegimenFiscal')]['descripcion'] ?? '',
            'receiverRegimenName' => $regimenes[$receiver->getAttribute('RegimenFiscalReceptor')]['descripcion'] ?? '',
            'usoCfdiName' => $usoCfdi[$receiver->getAttribute('UsoCFDI')] ?? '',
            'tasaIva' => $taxRate,
            'issuedAt' => now()->format('d/m/Y H:i'),
            'verificationUrl' => $qrUrl,
        ])->setPaper('a4', 'portrait')->output();
    }

    private function satVerificationUrl(\DOMElement $root, \DOMElement $issuer, \DOMElement $receiver): string
    {
        $seal = $root->getAttribute('Sello');
        $total = number_format((float) $root->getAttribute('Total'), 6, '.', '');
        [$integer, $fraction] = array_pad(explode('.', $total, 2), 2, '000000');
        $tt = str_pad($integer, 10, '0', STR_PAD_LEFT) . '.' . str_pad($fraction, 6, '0', STR_PAD_RIGHT);

        return (string) config('constantes.facturacion.qr_sat_url') . '?' . http_build_query([
            'id' => $root->getAttribute('UUID') ?: $this->uuidFromTimbre($root),
            're' => $issuer->getAttribute('Rfc'),
            'rr' => $receiver->getAttribute('Rfc'),
            'tt' => $tt,
            'fe' => mb_substr($seal, -8),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function uuidFromTimbre(\DOMElement $root): string
    {
        $xpath = new \DOMXPath($root->ownerDocument);
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        return (string) $xpath->evaluate('string(//tfd:TimbreFiscalDigital/@UUID)');
    }
}
