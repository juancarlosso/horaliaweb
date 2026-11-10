<?php

namespace App\Console\Commands;

use App\Mail\AvisoRenovacionProximaMail;
use App\Models\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarAvisosRenovacion extends Command
{
    protected $signature = 'renovaciones:avisar-proximas
                            {--dry-run : Muestra las empresas y destinatarios sin enviar correos}';

    protected $description = 'Envía un aviso a administradores cuyas empresas activas renuevan en tres días';

    public function handle(): int
    {
        $fecha = today()->addDays(3);
        $empresas = Empresa::query()
            ->where('activa', true)
            ->whereDate('fecha_renovacion', $fecha->toDateString())
            ->with(['usuarios' => fn ($query) => $query
                ->where('profile', 2)
                ->whereNotNull('email')
                ->select('users.id', 'users.name', 'users.email')])
            ->orderBy('id')
            ->get();

        if ($empresas->isEmpty()) {
            $this->info('No hay empresas activas que renueven en tres días ('.$fecha->format('d/m/Y').').');

            return self::SUCCESS;
        }

        $errores = 0;
        $enviados = 0;
        foreach ($empresas as $empresa) {
            $administradores = $empresa->usuarios
                ->filter(fn ($usuario) => filter_var(trim((string) $usuario->email), FILTER_VALIDATE_EMAIL))
                ->unique(fn ($usuario) => mb_strtolower(trim((string) $usuario->email)));

            if ($administradores->isEmpty()) {
                $this->warn("Empresa {$empresa->id} ({$empresa->nombre}): no tiene administradores con correo válido.");
                continue;
            }

            foreach ($administradores as $administrador) {
                $email = trim((string) $administrador->email);
                $this->line("Empresa {$empresa->id} ({$empresa->nombre}) → {$email}");

                if ($this->option('dry-run')) {
                    continue;
                }

                try {
                    Mail::to($email, (string) $administrador->name)
                        ->send(new AvisoRenovacionProximaMail($fecha->format('d/m/Y')));
                    $enviados++;
                } catch (Throwable $exception) {
                    $errores++;
                    $this->error("No se pudo enviar el aviso a {$email} (empresa {$empresa->id}): {$exception->getMessage()}");
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: no se enviaron correos.');
        } else {
            $this->info("Avisos enviados: {$enviados}.");
        }

        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }
}
