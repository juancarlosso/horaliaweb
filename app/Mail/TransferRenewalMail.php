<?php

namespace App\Mail;

use App\Models\TransferenciaPago;
use App\Services\TransferMembershipRenewalService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TransferRenewalMail extends Mailable
{
    public function __construct(
        public TransferenciaPago $payment,
        public string $companyName,
        public int $remainingDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: EmailSubject::prefix($this->headline()));
    }

    public function content(): Content
    {
        $suspension = $this->payment->fecha_renovacion->copy()->addDays(TransferMembershipRenewalService::GRACE_DAYS);
        $pending = $this->payment->estado === 'pendiente_de_confirmar';

        return new Content(view: 'emails.aviso-renovacion-proxima', with: [
            'transferRenewal' => true,
            'appName' => config('app.name', 'Horalia'),
            'logoUrl' => asset('assets/logos/logoLB.png'),
            'headline' => $this->headline(),
            'renewalDate' => $this->payment->fecha_renovacion->format('d/m/Y'),
            'deadline' => $suspension->copy()->subDay()->format('d/m/Y'),
            'suspensionDate' => $suspension->format('d/m/Y'),
            'pending' => $pending,
            'bank' => $pending ? [] : config('constantes.pagos_transferencia'),
            'reviewHours' => config('constantes.pagos_transferencia.horario'),
            'paymentUrl' => $pending
                ? route('payment-history.index')
                : route('transferencias.upload-form', $this->payment->empresa_id),
        ]);
    }

    private function headline(): string
    {
        $status = match ($this->payment->estado) {
            'pendiente_de_confirmar' => 'Tu comprobante está en revisión',
            'rechazado' => 'Tu comprobante fue rechazado',
            default => 'Es momento de renovar tu servicio',
        };

        return match ($this->remainingDays) {
            5 => $status,
            1 => 'Último día para confirmar el pago · '.$status,
            default => "Quedan {$this->remainingDays} días para confirmar el pago · ".$status,
        };
    }
}
