<?php

namespace App\Services;

use DHF\Sifei\Ws\Soap\SifeiTimbradoService as SifeiSoapClient;
use DHF\Sifei\Ws\Soap\Timbrado\getCFDI;
use RuntimeException;
use SoapFault;
use ZipArchive;

class SifeiTimbradoService
{
    public function stamp(string $xml, string $serie): string
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
                'connection_timeout' => 30,
                'exceptions' => true,
                'trace' => false,
                'cache_wsdl' => WSDL_CACHE_MEMORY,
            ]);
            $request = (new getCFDI())
                ->setUsuario($user)
                ->setPassword($password)
                ->setIdEquipo($equipmentId)
                ->setSerie($serie)
                ->setArchivoXMLZip($xml);

            $response = $client->getCFDI($request);
            $zipBytes = $response?->getReturn();
        } catch (SoapFault $exception) {
            throw new SifeiStampException('SIFEI no confirmó el resultado del timbrado. El intento quedó bloqueado para evitar emitir un duplicado.', true, $exception);
        }

        if (!is_string($zipBytes) || $zipBytes === '') {
            throw new SifeiStampException('SIFEI devolvió una respuesta vacía; revisa el estado antes de volver a intentar.', true);
        }

        return $this->extractStampedXml($zipBytes);
    }

    private function extractStampedXml(string $zipBytes): string
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
                    throw new SifeiStampException('La respuesta de SIFEI no contiene un ZIP reconocible.', true);
                }
                file_put_contents($temporaryFile, $decoded);
                if ($zip->open($temporaryFile) !== true) {
                    throw new SifeiStampException('La respuesta de SIFEI no contiene un ZIP reconocible.', true);
                }
            }

            try {
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = $zip->getNameIndex($index);
                    if (!is_string($name) || !str_ends_with(strtolower($name), '.xml')) {
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

            throw new SifeiStampException('La respuesta de SIFEI no incluyó un CFDI timbrado reconocible.', true);
        } finally {
            @unlink($temporaryFile);
        }
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
