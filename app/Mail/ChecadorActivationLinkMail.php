<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChecadorActivationLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $activationUrl,
        public ?string $expiresAt,
        public int $durationDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Enlace para el reloj checador de Horalia');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checador-activation-link',
            text: 'emails.checador-activation-link-text',
            with: [
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
            ],
        );
    }
}
