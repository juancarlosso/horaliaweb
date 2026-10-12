<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NovedadesSistemaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contenido,
        public string $identificador,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Novedades de Horalia '.$this->identificador);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.novedades-sistema',
            text: 'emails.novedades-sistema-text',
            with: [
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
            ],
        );
    }
}
