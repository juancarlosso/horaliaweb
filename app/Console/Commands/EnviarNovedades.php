<?php

namespace App\Console\Commands;

use App\Mail\NovedadesSistemaMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarNovedades extends Command
{
    protected $signature = 'novedades:enviar
                            {archivo? : Identificador de la novedad; si se omite, procesa las novedades pendientes}
                            {--dry-run : Muestra archivos y destinatarios sin enviar correos}';

    protected $description = 'Envía por correo las novedades pendientes de documentacion/novedades';

    public function handle(): int
    {
        $archivos = $this->obtenerArchivos();
        $solicitado = $this->argument('archivo');

        if ($archivos === [] && $solicitado === null) {
            $this->info('No hay archivos de novedades en documentacion/novedades.');

            return self::SUCCESS;
        }

        if ($solicitado !== null) {
            $archivo = $this->normalizarNombre($solicitado);
            if ($archivo === null) {
                $this->error('El archivo debe ser un identificador seguro, por ejemplo 261012 o 261012.md.');

                return self::FAILURE;
            }
            if (! in_array($archivo, $archivos, true)) {
                $this->error("No se encontró la novedad: documentacion/novedades/{$archivo}");

                return self::FAILURE;
            }
            $archivos = [$archivo];
        }

        $destinatarios = $this->obtenerDestinatarios();
        if ($this->option('dry-run')) {
            $this->line('Destinatarios: '.($destinatarios === [] ? '(ninguno)' : implode(', ', $destinatarios)));
            foreach ($archivos as $archivo) {
                $identificador = pathinfo($archivo, PATHINFO_FILENAME);
                $pendientes = array_values(array_filter($destinatarios, fn (string $email) =>
                    ! DB::table('novedades_enviadas')
                        ->where('identificador', $identificador)
                        ->where('email_hash', hash('sha256', mb_strtolower($email)))
                        ->exists()
                ));

                $this->line("{$archivo}: ".count($pendientes).' destinatario(s) pendiente(s).');
                if ($pendientes !== []) {
                    $this->line((string) file_get_contents(base_path("documentacion/novedades/{$archivo}")));
                }
            }

            return self::SUCCESS;
        }

        if ($archivos !== [] && $destinatarios === []) {
            $this->error('No hay administradores de empresa con correo electrónico válido.');

            return self::FAILURE;
        }

        $fallos = 0;
        foreach ($archivos as $archivo) {
            $identificador = pathinfo($archivo, PATHINFO_FILENAME);
            $ruta = base_path("documentacion/novedades/{$archivo}");
            $contenido = is_readable($ruta) ? file_get_contents($ruta) : false;

            if ($contenido === false || trim($contenido) === '') {
                $this->error("El archivo documentacion/novedades/{$archivo} está vacío o no se pudo leer.");
                $fallos++;
                continue;
            }

            $enviados = 0;
            $pendientes = 0;
            foreach ($destinatarios as $email) {
                $emailHash = hash('sha256', mb_strtolower($email));
                $yaEnviado = DB::table('novedades_enviadas')
                    ->where('identificador', $identificador)
                    ->where('email_hash', $emailHash)
                    ->exists();

                if ($yaEnviado) {
                    continue;
                }
                $pendientes++;

                try {
                    Mail::to($email)->send(new NovedadesSistemaMail($contenido, $identificador));
                    DB::table('novedades_enviadas')->insert([
                        'identificador' => $identificador,
                        'email' => $email,
                        'email_hash' => $emailHash,
                        'enviado_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $enviados++;
                } catch (Throwable $e) {
                    $fallos++;
                    $this->error("Falló el envío de {$archivo} a {$email}: {$e->getMessage()}");
                }
            }

            if ($pendientes === 0) {
                $this->line("Novedad {$archivo}: no hay destinatarios pendientes.");
            } else {
                $this->info("Novedad {$archivo}: enviada a {$enviados} de {$pendientes} destinatario(s) pendiente(s).");
            }
        }

        return $fallos === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<int, string> */
    private function obtenerArchivos(): array
    {
        $archivos = glob(base_path('documentacion/novedades/*.md')) ?: [];
        $nombres = array_map('basename', $archivos);
        sort($nombres, SORT_STRING);

        return $nombres;
    }

    private function normalizarNombre(string $entrada): ?string
    {
        $entrada = trim($entrada);
        $nombre = basename($entrada);
        if ($nombre !== $entrada) {
            return null;
        }

        $nombre = preg_replace('/\.md$/i', '', $nombre);
        if ($nombre === null || ! preg_match('/^[A-Za-z0-9_-]+$/', $nombre)) {
            return null;
        }

        return $nombre.'.md';
    }

    /** @return array<int, string> */
    private function obtenerDestinatarios(): array
    {
        $correos = User::query()
            ->where('profile', 2)
            ->whereHas('empresas')
            ->whereNotNull('email')
            ->pluck('email');

        return $correos
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn (string $email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique(fn (string $email) => mb_strtolower($email))
            ->values()
            ->all();
    }
}
