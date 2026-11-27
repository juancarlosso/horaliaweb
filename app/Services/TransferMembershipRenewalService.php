<?php

namespace App\Services;

use App\Mail\TransferRenewalMail;
use App\Models\Empresa;
use App\Models\TransferenciaPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TransferMembershipRenewalService
{
    public const GRACE_DAYS = 5;

    public function __construct(private readonly EmployeeSubscriptionPricing $pricing) {}

    public function paymentForCycle(Empresa $empresa, string $cycleDate): TransferenciaPago
    {
        $payment = $empresa->transferencias()->whereDate('fecha_renovacion', $cycleDate)->first();
        if ($payment) {
            return $payment;
        }

        return $empresa->transferencias()->create([
            'fecha_renovacion' => $cycleDate,
            'cantidad' => $this->pricing->prepareRenewal($empresa, $cycleDate),
            'concepto' => 'HORALIA '.$empresa->referenciaTransferencia(),
            'estado' => 'pendiente_de_pago',
        ]);
    }

    // Called with the company locked, as in the transfer confirmation.
    public function process(Empresa $empresa, string $cycleDate): array
    {
        $result = ['empresa_id' => $empresa->id, 'renewal_date' => $cycleDate];

        if ($this->isPaid($empresa, $cycleDate)) {
            return $result + ['status' => 'already_paid'];
        }

        if (today()->gte($empresa->fecha_renovacion->copy()->addDays(self::GRACE_DAYS))) {
            $empresa->forceFill([
                'activa' => false,
                'periodo_suspendido_por_transferencia' => $cycleDate,
            ])->save();

            return $result + ['status' => 'transfer_suspended'];
        }

        return $result + ['status' => 'transfer_pending'];
    }

    public function notifyAdministrators(int $empresaId, string $cycleDate): void
    {
        DB::transaction(function () use ($empresaId, $cycleDate) {
            $empresa = Empresa::query()->whereKey($empresaId)->lockForUpdate()->firstOrFail();
            $date = today();
            $renewal = $empresa->fecha_renovacion;
            if (!$empresa->activa || $empresa->cancelar_al_renovar || $empresa->metodo_pago !== 'TRANSFERENCIA'
                || $renewal?->toDateString() !== $cycleDate || $date->lt($renewal)
                || $date->gte($renewal->copy()->addDays(self::GRACE_DAYS)) || $this->isPaid($empresa, $cycleDate)) {
                return;
            }

            $administrators = $empresa->usuarios()->where('profile', 2)->whereNotNull('email')
                ->get(['users.id', 'users.name', 'users.email'])
                ->filter(fn ($administrator) => filter_var(trim($administrator->email), FILTER_VALIDATE_EMAIL));
            if ($administrators->isEmpty()) {
                return;
            }

            $payment = $this->paymentForCycle($empresa, $cycleDate);
            foreach ($administrators as $administrator) {
                $email = trim($administrator->email);
                $notice = [
                    'transferencia_pago_id' => $payment->id,
                    'fecha' => $date->toDateString(),
                    'destinatario' => mb_strtolower($email),
                ];
                if (!DB::table('transferencia_avisos_renovacion')->insertOrIgnore($notice)) {
                    continue;
                }

                try {
                    Mail::to($email, $administrator->name)->send(new TransferRenewalMail(
                        payment: $payment,
                        companyName: $empresa->nombre ?: $empresa->razon_social,
                        remainingDays: self::GRACE_DAYS - (int) $renewal->diffInDays($date),
                    ));
                } catch (Throwable $exception) {
                    DB::table('transferencia_avisos_renovacion')->where($notice)->delete();
                    Log::error('No se pudo enviar el aviso de renovación por transferencia.', [
                        'empresa_id' => $empresa->id,
                        'administrator_id' => $administrator->id,
                        'exception' => get_class($exception),
                    ]);
                }
            }
        });
    }

    private function isPaid(Empresa $empresa, string $cycleDate): bool
    {
        return $empresa->intentosPago()->whereDate('fecha_renovacion', $cycleDate)->where('resultado', 'exitoso')->exists()
            || $empresa->transferencias()->whereDate('fecha_renovacion', $cycleDate)->where('estado', 'confirmado')->exists();
    }
}
