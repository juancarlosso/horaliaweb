<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoRenovacionProximaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $renewalDate) {}

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
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
            ],
        );
    }
}
