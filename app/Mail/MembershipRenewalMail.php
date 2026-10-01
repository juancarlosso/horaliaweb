<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipRenewalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public string $recipientName,
        public string $companyName,
        public string $amount,
        public ?string $renewalDate = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'paid' => 'Pago de membresía realizado correctamente',
            'retry' => 'No se pudo realizar el cobro de tu membresía',
            default => 'Membresía desactivada por falta de pago',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-renewal',
            text: 'emails.membership-renewal-text',
            with: [
                'appName' => config('app.name', 'Horalia'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
                'loginUrl' => route('login'),
            ],
        );
    }
}
