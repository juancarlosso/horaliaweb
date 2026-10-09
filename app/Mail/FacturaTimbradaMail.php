<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacturaTimbradaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Factura $factura) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu factura de Horalia ' . $this->factura->serie . '-' . $this->factura->folio);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.factura-timbrada',
            text: 'emails.factura-timbrada-text',
            with: [
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
                'factura' => $this->factura,
                'empresa' => $this->factura->intentoPago?->empresa,
                'loginUrl' => route('login'),
            ],
        );
    }

    public function attachments(): array
    {
        $stem = $this->factura->serie . '-' . $this->factura->folio;

        return [
            Attachment::fromStorageDisk('wasabi', (string) $this->factura->xml_path)
                ->as('Factura-' . $stem . '.xml')
                ->withMime('application/xml'),
            Attachment::fromStorageDisk('wasabi', (string) $this->factura->pdf_path)
                ->as('Factura-' . $stem . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
