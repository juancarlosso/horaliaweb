<?php

namespace App\Services;

use App\Jobs\PrepareInvoiceFilesAndEmail;
use App\Models\Factura;
use App\Models\FolioFactura;
use App\Models\IntentoPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PaymentInvoiceIssuingService
{
    public function __construct(
        private readonly PaymentInvoiceAmounts $amounts,
        private readonly Cfdi40XmlBuilder $xmlBuilder,
        private readonly SifeiTimbradoService $sifei,
    ) {}

    public function issue(IntentoPago $payment, array $options, int $userId): Factura
    {
        $invoice = DB::transaction(function () use ($payment, $options, $userId) {
            $lockedPayment = IntentoPago::query()
                ->with('empresa')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->resultado !== 'exitoso' || (int) $lockedPayment->factura !== 0 || !$lockedPayment->intentado_en?->isCurrentMonth()) {
                throw new RuntimeException('Este pago ya no se puede facturar.');
            }

            $current = Factura::query()
                ->where('intento_pago_id', $lockedPayment->id)
                ->lockForUpdate()
                ->first();

            if ($current && in_array($current->estado, ['procesando', 'requiere_revision', 'timbrada', 'archivos_pendientes', 'archivos_subidos', 'correo_encolado'], true)) {
                throw new RuntimeException('La solicitud de factura ya está en proceso o requiere revisión. No se enviará otro timbrado.');
            }

            $serie = (string) config('constantes.facturacion.serie', 'H');
            $sequence = FolioFactura::query()->where('serie', $serie)->lockForUpdate()->first();
            if (!$sequence) {
                throw new RuntimeException('No existe el consecutivo global de facturas para la serie configurada.');
            }

            $nextFolio = $sequence->ultimo_folio + 1;
            $sequence->forceFill(['ultimo_folio' => $nextFolio])->save();

            $amounts = $this->amounts->fromTaxInclusiveTotal((string) $lockedPayment->cantidad);

            return Factura::query()->updateOrCreate(
                ['intento_pago_id' => $lockedPayment->id],
                [
                    'serie' => $serie,
                    'folio' => $nextFolio,
                    'usuario_id' => $userId,
                    'uuid' => null,
                    'estado' => 'procesando',
                    'modo_sifei' => (string) config('services.sifei.modo', 'pruebas'),
                    'uso_cfdi' => $options['uso_cfdi'],
                    'forma_pago_sat' => $options['forma_pago_sat'],
                    'correo_facturacion' => $options['correo_facturacion'],
                    'subtotal' => $amounts['subtotal'],
                    'iva' => $amounts['iva'],
                    'total' => $amounts['total'],
                    'xml_path' => null,
                    'pdf_path' => null,
                    'xml_staging_path' => null,
                    'pdf_staging_path' => null,
                    'timbrado_en' => null,
                    'archivos_subidos_en' => null,
                    'correo_encolado_en' => null,
                    'codigo_error' => null,
                    'mensaje_error' => null,
                    'xml_pendiente' => null,
                ]
            )->load('intentoPago.empresa');
        });

        try {
            $payment->loadMissing('empresa');
            $amounts = $this->amounts->fromTaxInclusiveTotal((string) $payment->cantidad);
            $unsignedAndSignedXml = $this->xmlBuilder->build(
                $payment,
                $payment->empresa,
                $amounts,
                $invoice->folio,
                $invoice->uso_cfdi,
                $invoice->forma_pago_sat,
            );
            $stampedXml = $this->sifei->stamp($unsignedAndSignedXml, $invoice->serie);
            $timbre = $this->readTimbre($stampedXml);

            $stagingPath = sprintf('facturas/staging/%d/%s-%d.xml', $invoice->id, $invoice->serie, $invoice->folio);
            if (!Storage::disk('local')->put($stagingPath, $stampedXml)) {
                $remotePath = sprintf('facturas/%s/%s-%d.xml', $invoice->serie, $invoice->serie, $invoice->folio);
                $savedToWasabi = Storage::disk('wasabi')->put($remotePath, $stampedXml, ['visibility' => 'private']);
                if ($savedToWasabi) {
                    $this->markStamped($invoice->id, $timbre, null, $remotePath);
                } else {
                    Log::critical('CFDI timbrado; XML conservado en base de datos para reintentar el almacenamiento.', ['factura_id' => $invoice->id]);
                    $this->markStamped($invoice->id, $timbre, null, null, $stampedXml);
                }
            } else {
                $this->markStamped($invoice->id, $timbre, $stagingPath);
            }

            try {
                PrepareInvoiceFilesAndEmail::dispatch($invoice->id)->afterCommit();
            } catch (Throwable $dispatchException) {
                Log::error('CFDI timbrado; no se pudo encolar la preparación de archivos.', ['factura_id' => $invoice->id, 'exception' => $dispatchException::class]);
                Factura::query()->whereKey($invoice->id)->update([
                    'estado' => 'archivos_pendientes',
                    'codigo_error' => 'COLA_NO_DISPONIBLE',
                    'mensaje_error' => 'El CFDI quedó timbrado; falta encolar la preparación de archivos y correo.',
                    'updated_at' => now(),
                ]);
            }

            return $invoice->fresh();
        } catch (Throwable $exception) {
            $requiresReview = $exception instanceof SifeiStampException && $exception->requiresReview;
            $this->markFailure($invoice->id, $exception, $requiresReview);

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('No se pudo completar el timbrado. Revisa el estado antes de volver a intentar.', 0, $exception);
        }
    }

    private function markStamped(int $invoiceId, array $timbre, ?string $stagingPath, ?string $xmlWasabiPath = null, ?string $xmlPending = null): void
    {
        DB::transaction(function () use ($invoiceId, $timbre, $stagingPath, $xmlWasabiPath, $xmlPending) {
            $invoice = Factura::query()->lockForUpdate()->findOrFail($invoiceId);
            $invoice->forceFill([
                'uuid' => $timbre['uuid'],
                'estado' => $stagingPath || $xmlWasabiPath ? 'timbrada' : 'archivos_pendientes',
                'xml_path' => $xmlWasabiPath,
                'xml_staging_path' => $stagingPath,
                'xml_pendiente' => $xmlPending,
                'timbrado_en' => $timbre['fecha'],
                'codigo_error' => null,
                'mensaje_error' => null,
            ])->save();

            IntentoPago::query()->whereKey($invoice->intento_pago_id)->update(['factura' => 1]);
        });
    }

    private function markFailure(int $invoiceId, Throwable $exception, bool $requiresReview): void
    {
        $invoice = Factura::query()->find($invoiceId);
        if (!$invoice || $invoice->uuid) {
            return;
        }

        $invoice->forceFill([
            'estado' => $requiresReview ? 'requiere_revision' : 'rechazada',
            'codigo_error' => $exception instanceof SifeiStampException ? 'SIFEI_RESULTADO_NO_CONFIRMADO' : 'FACTURA_NO_GENERADA',
            'mensaje_error' => mb_substr($exception->getMessage(), 0, 2000),
        ])->save();
    }

    private function readTimbre(string $xml): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                throw new SifeiStampException('SIFEI devolvió un XML que no se pudo leer.', true);
            }
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            $node = $xpath->query('//tfd:TimbreFiscalDigital')->item(0);
            if (!$node instanceof \DOMElement || !preg_match('/^[0-9A-Fa-f-]{36}$/', $node->getAttribute('UUID'))) {
                throw new SifeiStampException('SIFEI devolvió un XML sin UUID de timbrado válido.', true);
            }

            return [
                'uuid' => strtoupper($node->getAttribute('UUID')),
                'fecha' => $node->getAttribute('FechaTimbrado') ?: now()->toDateTimeString(),
            ];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
