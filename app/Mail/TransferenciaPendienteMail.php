<?php

namespace App\Mail;

use App\Models\TransferenciaPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferenciaPendienteMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public TransferenciaPago $transferencia) {}
    public function envelope(): Envelope { return new Envelope(subject: EmailSubject::prefix('Comprobante de transferencia por validar')); }
    public function content(): Content
    {
        return new Content(view: 'emails.transferencia-pendiente', with: [
            'transferencia' => $this->transferencia,
            'adminUrl' => route('transferencias.admin.index'),
        ]);
    }

    public function attachments(): array
    {
        $proof = $this->transferencia->comprobanteActual;
        if (!$proof || $proof->tamano > ((int) config('constantes.pagos_transferencia.mail_attach_max_kb', 4096) * 1024)) return [];
        return [Attachment::fromStorageDisk('wasabi', $proof->ruta_privada)->as($proof->nombre_original)->withMime($proof->mime)];
    }
}
