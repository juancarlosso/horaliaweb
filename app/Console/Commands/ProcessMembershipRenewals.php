<?php

namespace App\Console\Commands;

use App\Services\MembershipRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMembershipRenewals extends Command
{
    protected $signature = 'horalia:renovar-membresias';

    protected $description = 'Procesa las renovaciones pendientes de membresía de Horalia';

    public function handle(MembershipRenewalService $renewals): int
    {
        $lock = Cache::lock('horalia:membership-renewals', 14400);
        if (!$lock->get()) {
            $this->info('Ya hay un proceso de renovación en ejecución.');
            return self::SUCCESS;
        }

        try {
            $companyIds = $renewals->pendingCompanies();
            $this->info('Empresas pendientes: ' . count($companyIds));

            foreach ($companyIds as $companyId) {
                Log::info('Inicia la ejecución de renovación automática.', ['empresa_id' => $companyId]);
                try {
                    $result = $renewals->processAutomatic((int) $companyId);
                    $this->line("Empresa {$companyId}: {$result['status']}");
                } catch (Throwable $exception) {
                    Log::error('Error inesperado durante una renovación automática.', [
                        'empresa_id' => $companyId,
                        'exception' => get_class($exception),
                        'message' => $exception->getMessage(),
                    ]);
                    $this->error("Empresa {$companyId}: error; se conserva la renovación pendiente.");
                }
            }
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}
