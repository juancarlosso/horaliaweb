<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\IntentoPago;
use CfdiUtils\Certificado\Certificado;
use CfdiUtils\CfdiCreator40;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Cfdi40XmlBuilder
{
    public function build(
        IntentoPago $payment,
        Empresa $company,
        array $amounts,
        int $folio,
        string $usoCfdi,
        string $formaPago,
    ): string {
        $issuer = config('constantes.emisor', []);
        $invoice = config('constantes.facturacion', []);
        $concept = config('constantes.concepto_factura', []);
        $diskName = (string) ($invoice['disco_csd'] ?? 'local');
        $directory = trim((string) ($invoice['directorio_csd'] ?? 'csd'), '/');
        $disk = Storage::disk($diskName);
        $files = $disk->files($directory);
        $certificates = array_values(array_filter($files, fn (string $path) => str_ends_with(strtolower($path), '.cer')));
        $privateKeys = array_values(array_filter($files, fn (string $path) => str_ends_with(strtolower($path), '.key.pem')));

        if (count($certificates) !== 1 || count($privateKeys) !== 1) {
            throw new RuntimeException('Configura un solo certificado .cer y una sola llave .key.pem en el directorio privado del CSD.');
        }

        $certificatePath = $disk->path($certificates[0]);
        $privateKeyPath = $disk->path($privateKeys[0]);
        $certificate = new Certificado($certificatePath);
        $expectedRfc = strtoupper(trim((string) ($issuer['rfc'] ?? '')));

        if (strtoupper($certificate->getRfc()) !== $expectedRfc) {
            throw new RuntimeException('El RFC del CSD no coincide con el RFC del emisor configurado.');
        }
        if ($certificate->getValidFrom() > now()->timestamp || $certificate->getValidTo() < now()->timestamp) {
            throw new RuntimeException('El CSD del emisor no está vigente a la fecha.');
        }

        $password = (string) config('services.sifei.password_csd_emisor', '');
        if ($password === '') {
            throw new RuntimeException('Falta configurar la contraseña privada del CSD del emisor.');
        }

        $rate = (int) config('constantes.tasa_iva', 16);
        $creator = new CfdiCreator40([
            'Version' => '4.0',
            'Serie' => (string) ($invoice['serie'] ?? 'H'),
            'Folio' => (string) $folio,
            'Fecha' => now()->format('Y-m-d\TH:i:s'),
            'SubTotal' => $amounts['subtotal'],
            'Moneda' => strtoupper((string) $payment->moneda),
            'Total' => $amounts['total'],
            'TipoDeComprobante' => (string) ($invoice['tipo_comprobante'] ?? 'I'),
            'Exportacion' => (string) ($invoice['exportacion'] ?? '01'),
            'MetodoPago' => (string) ($invoice['metodo_pago'] ?? 'PUE'),
            'FormaPago' => $formaPago,
            'LugarExpedicion' => (string) ($issuer['codigo_postal'] ?? ''),
        ], $certificate);

        $comprobante = $creator->comprobante();
        $comprobante->addEmisor([
            'RegimenFiscal' => (string) ($issuer['regimen_clave'] ?? ''),
        ]);
        $comprobante->addReceptor([
            'Rfc' => strtoupper(trim((string) $company->rfc)),
            'Nombre' => trim((string) $company->razon_social),
            'DomicilioFiscalReceptor' => trim((string) $company->domicilio_codigo_postal),
            'RegimenFiscalReceptor' => (string) $company->regimen_fiscal,
            'UsoCFDI' => $usoCfdi,
        ]);

        $comprobante->addConcepto([
            'ClaveProdServ' => (string) ($concept['clave_prod_serv'] ?? ''),
            'Cantidad' => '1',
            'ClaveUnidad' => (string) ($concept['clave_unidad'] ?? ''),
            'Unidad' => (string) ($concept['unidad'] ?? ''),
            'Descripcion' => (string) ($concept['descripcion'] ?? ''),
            'ValorUnitario' => $amounts['subtotal'],
            'Importe' => $amounts['subtotal'],
            'ObjetoImp' => (string) ($concept['objeto_impuesto'] ?? ''),
        ])->addTraslado([
            'Base' => $amounts['subtotal'],
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => number_format($rate / 100, 6, '.', ''),
            'Importe' => $amounts['iva'],
        ]);

        $creator->addSumasConceptos(null, 2);
        $creator->addSello('file://' . $privateKeyPath, $password);
        $creator->moveSatDefinitionsToComprobante();

        if ($creator->validate()->hasErrors()) {
            throw new RuntimeException('El CFDI no superó las validaciones estructurales de CFDI 4.0.');
        }

        return $creator->asXml();
    }
}
