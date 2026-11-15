<?php

namespace App\Mail;

use App\Models\TransferenciaPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferenciaRechazadaMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public TransferenciaPago $transferencia) {}
    public function envelope(): Envelope { return new Envelope(subject: EmailSubject::prefix('Necesitamos que reemplaces tu comprobante de transferencia')); }
    public function content(): Content { return new Content(view: 'emails.transferencia-rechazada', with: ['transferencia' => $this->transferencia, 'historyUrl' => route('payment-history.index')]); }
}
