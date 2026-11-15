<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\IntentoPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferenciaConfirmadaMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Empresa $empresa, public IntentoPago $pago, public string $nuevaFecha) {}
    public function envelope(): Envelope { return new Envelope(subject: EmailSubject::prefix('Tu pago por transferencia fue confirmado')); }
    public function content(): Content { return new Content(view: 'emails.transferencia-confirmada', with: ['empresa' => $this->empresa, 'pago' => $this->pago, 'nuevaFecha' => $this->nuevaFecha, 'historyUrl' => route('payment-history.index')]); }
}
