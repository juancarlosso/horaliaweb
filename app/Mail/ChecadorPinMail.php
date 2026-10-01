<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChecadorPinMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $pin,
        public string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu PIN del checador de Horalia');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checador-pin',
            text: 'emails.checador-pin-text',
            with: [
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
                'loginUrl' => route('login'),
            ],
        );
    }
}
