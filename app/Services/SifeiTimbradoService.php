<?php

namespace App\Services;

use DHF\Sifei\Ws\Soap\SifeiTimbradoService as SifeiSoapClient;
use DHF\Sifei\Ws\Soap\Timbrado\getCFDI;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SoapFault;
use Throwable;
use ZipArchive;

class SifeiTimbradoService
{
    public function stamp(string $xml, string $serie, ?int $facturaId = null, ?int $folio = null): string
    {
        $user = (string) config('services.sifei.usuario', '');
        $password = (string) config('services.sifei.password', '');
        $equipmentId = (string) config('services.sifei.id_equipo', '');
        $wsdl = (string) config('services.sifei.url_timbrar', '');

        if ($user === '' || $password === '' || $equipmentId === '' || $wsdl === '') {
            throw new RuntimeException('Falta configurar el acceso de SIFEI en el entorno de la aplicación.');
        }
        $mode = strtolower(trim((string) config('services.sifei.modo', 'pruebas')));
        $testing = in_array($mode, ['prueba', 'pruebas', 'test', 'testing'], true);
        $production = in_array($mode, ['produccion', 'producción', 'production', 'prod'], true);
        if (!$testing && !$production) {
            throw new RuntimeException('SIFEI_MODO debe identificar pruebas o producción.');
        }

        $url = filter_var($wsdl, FILTER_VALIDATE_URL) ? parse_url($wsdl) : false;
        $host = strtolower((string) ($url['host'] ?? ''));
        $secure = ($url['scheme'] ?? '') === 'https';
        $officialTestEndpoint = $host === 'devcfdi.sifei.com.mx' && (int) ($url['port'] ?? 0) === 8080;
        if ((!$secure && !($testing && $officialTestEndpoint))
            || ($testing && $host !== 'devcfdi.sifei.com.mx')
            || ($production && ($host !== 'sat.sifei.com.mx' || !$secure))) {
            throw new RuntimeException('La URL de timbrado de SIFEI no corresponde al modo configurado.');
        }

        try {
            $client = new SifeiSoapClient($wsdl, [
                'soap_version' => SOAP_1_1,
                'connection_timeout' => 30,
                'exceptions' => true,
                'trace' => false,
                'cache_wsdl' => WSDL_CACHE_MEMORY,
            ]);
            $request = (new getCFDI())
                ->setUsuario($user)
                ->setPassword($password)
                ->setIdEquipo($equipmentId)
                // El XML ya contiene la serie; el flujo SIFEI probado envía este parámetro vacío.
                ->setSerie('')
                ->setArchivoXMLZip($xml);

            $response = $client->getCFDI($request);
            $zipBytes = $response?->getReturn();
        } catch (SoapFault $exception) {
            $providerError = $exception->detail?->SifeiException?->error;
            $diagnostic = $this->safeDiagnostic(
                is_scalar($providerError) ? (string) $providerError : $exception->getMessage(),
                [$user, $password, $equipmentId],
            );
            Log::warning('SIFEI devolvió un fallo SOAP al timbrar.', [
                'factura_id' => $facturaId,
                'folio' => $folio,
                'fault_code' => $this->safeDiagnostic((string) $exception->faultcode, [$user, $password, $equipmentId]),
                'detalle' => $diagnostic,
            ]);
            throw new SifeiStampException('SIFEI no confirmó el resultado del timbrado. El intento quedó bloqueado para evitar emitir un duplicado.', true, $exception);
        }

        if (!is_string($zipBytes) || $zipBytes === '') {
            Log::warning('SIFEI devolvió una respuesta vacía al timbrar.', ['factura_id' => $facturaId, 'folio' => $folio]);
            throw new SifeiStampException('SIFEI devolvió una respuesta vacía; revisa el estado antes de volver a intentar.', true);
        }

        return $this->extractStampedXml($zipBytes, $facturaId, $folio, [$user, $password, $equipmentId]);
    }

    private function extractStampedXml(string $zipBytes, ?int $facturaId, ?int $folio, array $secrets): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'horalia-sifei-');
        if ($temporaryFile === false) {
            throw new SifeiStampException('No fue posible procesar la respuesta de SIFEI.', true);
        }

        try {
            file_put_contents($temporaryFile, $zipBytes);
            $zip = new ZipArchive();
            $opened = $zip->open($temporaryFile);
            if ($opened !== true) {
                $decoded = base64_decode($zipBytes, true);
                if ($decoded === false) {
                    Log::warning('La respuesta de SIFEI no pudo abrirse como ZIP.', [
                        'factura_id' => $facturaId,
                        'folio' => $folio,
                        'respuesta' => $this->safeDiagnostic($zipBytes, $secrets),
                    ]);
                    throw new SifeiStampException('La respuesta de SIFEI no contiene un ZIP reconocible.', true);
                }
                file_put_contents($temporaryFile, $decoded);
                if ($zip->open($temporaryFile) !== true) {
                    Log::warning('La respuesta de SIFEI no pudo abrirse como ZIP.', [
                        'factura_id' => $facturaId,
                        'folio' => $folio,
                        'respuesta' => $this->safeDiagnostic($decoded, $secrets),
                    ]);
                    throw new SifeiStampException('La respuesta de SIFEI no contiene un ZIP reconocible.', true);
                }
            }

            try {
                $providerError = null;
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = $zip->getNameIndex($index);
                    if (!is_string($name)) {
                        continue;
                    }
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if ($extension !== 'xml') {
                        if ($providerError === null && in_array($extension, ['txt', 'err', 'log'], true)) {
                            $stat = $zip->statIndex($index);
                            if (($stat['size'] ?? PHP_INT_MAX) <= 10000) {
                                $entry = $zip->getFromIndex($index);
                                if (is_string($entry)) {
                                    $providerError = $this->safeDiagnostic($entry, $secrets);
                                }
                            }
                        }
                        continue;
                    }
                    $xml = $zip->getFromIndex($index);
                    if (is_string($xml) && $this->hasTimbre($xml)) {
                        return $xml;
                    }
                }
            } finally {
                $zip->close();
            }

            Log::warning('La respuesta ZIP de SIFEI no incluyó un CFDI timbrado reconocible.', [
                'factura_id' => $facturaId,
                'folio' => $folio,
                'respuesta' => $providerError,
            ]);
            throw new SifeiStampException('La respuesta de SIFEI no incluyó un CFDI timbrado reconocible.', true);
        } finally {
            @unlink($temporaryFile);
        }
    }

    private function safeDiagnostic(string $message, array $secrets = []): string
    {
        $message = mb_convert_encoding(substr($message, 0, 10000), 'UTF-8', 'UTF-8');
        $message = trim(preg_replace('/\s+/', ' ', strip_tags($message)));
        foreach ($secrets as $secret) {
            if (is_string($secret) && $secret !== '') {
                $message = str_replace($secret, '[REDACTADO]', $message);
            }
        }
        $message = preg_replace('/\b[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}\b/i', '[RFC]', $message);
        $message = preg_replace('/\b[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\b/i', '[UUID]', $message);
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[CORREO]', $message);
        return mb_substr($message, 0, 1200);
    }

    private function hasTimbre(string $xml): bool
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                return false;
            }
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            $node = $xpath->query('//tfd:TimbreFiscalDigital')->item(0);
            return $node instanceof \DOMElement
                && preg_match('/^[0-9A-Fa-f-]{36}$/', $node->getAttribute('UUID')) === 1;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
