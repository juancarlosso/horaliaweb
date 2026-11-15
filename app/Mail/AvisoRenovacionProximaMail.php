<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Empresa;

class AvisoRenovacionProximaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $renewalDate, public ?Empresa $empresa = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: EmailSubject::prefix('Tu renovación de Horalia está cerca ⏰'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.aviso-renovacion-proxima',
            text: 'emails.aviso-renovacion-proxima-text',
            with: [
                'renewalDate' => $this->renewalDate,
                'empresa' => $this->empresa,
                'historyUrl' => route('payment-history.index'),
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
            ],
        );
    }
}
