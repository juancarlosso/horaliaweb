<?php

namespace App\Jobs;

use App\Mail\FacturaTimbradaMail;
use App\Models\Factura;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PrepareInvoiceFilesAndEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [30, 120, 300, 900];

    public int $timeout = 120;

    public function __construct(public int $facturaId) {}

    public function handle(InvoicePdfService $pdfService): void
    {
        $invoice = Factura::query()->with('intentoPago.empresa')->findOrFail($this->facturaId);
        if (!$invoice->uuid || in_array($invoice->estado, ['correo_encolado', 'enviada'], true)) {
            return;
        }

        $local = Storage::disk('local');
        $wasabi = Storage::disk('wasabi');
        $xml = $invoice->xml_staging_path && $local->exists($invoice->xml_staging_path)
            ? $local->get($invoice->xml_staging_path)
            : ($invoice->xml_path && $wasabi->exists($invoice->xml_path) ? $wasabi->get($invoice->xml_path) : $invoice->xml_pendiente);
        if (!is_string($xml) || $xml === '') {
            throw new RuntimeException('No se encontró el XML timbrado para completar la factura.');
        }

        $fileStem = $invoice->serie . '-' . $invoice->folio;
        $remoteDirectory = 'facturas/' . $invoice->serie;
        $xmlPath = $invoice->xml_path ?: $remoteDirectory . '/' . $fileStem . '.xml';
        if (!$invoice->xml_path && !$wasabi->put($xmlPath, $xml, ['visibility' => 'private'])) {
            throw new RuntimeException('Wasabi no confirmó la carga del XML timbrado.');
        }

        $pdf = $invoice->pdf_staging_path && $local->exists($invoice->pdf_staging_path)
            ? $local->get($invoice->pdf_staging_path)
            : null;
        if (!is_string($pdf) || $pdf === '') {
            $pdf = $pdfService->render($invoice, $xml);
            $pdfStagePath = 'facturas/staging/' . $invoice->id . '/' . $fileStem . '.pdf';
            if (!$local->put($pdfStagePath, $pdf)) {
                throw new RuntimeException('No fue posible guardar temporalmente la representación PDF.');
            }
            $invoice->forceFill(['pdf_staging_path' => $pdfStagePath])->save();
        }

        $pdfPath = $invoice->pdf_path ?: $remoteDirectory . '/' . $fileStem . '.pdf';
        if (!$invoice->pdf_path && !$wasabi->put($pdfPath, $pdf, ['visibility' => 'private'])) {
            throw new RuntimeException('Wasabi no confirmó la carga del PDF.');
        }

        DB::transaction(function () use ($invoice, $xmlPath, $pdfPath) {
            $locked = Factura::query()->lockForUpdate()->findOrFail($invoice->id);
            $locked->forceFill([
                'xml_path' => $xmlPath,
                'pdf_path' => $pdfPath,
                'archivos_subidos_en' => $locked->archivos_subidos_en ?: now(),
                'estado' => 'archivos_subidos',
                'mensaje_error' => null,
                'codigo_error' => null,
                'xml_pendiente' => null,
            ])->save();
        });

        $invoice->refresh();
        Mail::to($invoice->correo_facturacion)->queue(new FacturaTimbradaMail($invoice));

        DB::transaction(function () use ($invoice, $local) {
            $locked = Factura::query()->lockForUpdate()->findOrFail($invoice->id);
            $locked->forceFill([
                'estado' => 'correo_encolado',
                'correo_encolado_en' => now(),
                'xml_staging_path' => null,
                'pdf_staging_path' => null,
                'mensaje_error' => null,
                'codigo_error' => null,
            ])->save();
            if ($invoice->xml_staging_path) {
                $local->delete($invoice->xml_staging_path);
            }
            if ($invoice->pdf_staging_path) {
                $local->delete($invoice->pdf_staging_path);
            }
        });
    }

    public function failed(Throwable $exception): void
    {
        Factura::query()->whereKey($this->facturaId)->whereIn('estado', ['timbrada', 'archivos_subidos', 'archivos_pendientes'])
            ->update([
                'estado' => 'archivos_pendientes',
                'codigo_error' => 'ARCHIVOS_O_CORREO_PENDIENTE',
                'mensaje_error' => 'El CFDI ya fue timbrado; falta completar la carga o el envío del correo.',
                'updated_at' => now(),
            ]);
    }
}
