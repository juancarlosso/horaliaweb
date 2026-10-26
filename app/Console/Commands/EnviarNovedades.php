<?php

namespace App\Console\Commands;

use App\Mail\NovedadesSistemaMail;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarNovedades extends Command
{
    protected $signature = 'novedades:enviar
                            {archivo? : Identificador fechado de la novedad (AAMMDD o AAMMDDHHMM); si se omite, procesa las novedades pendientes}
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
            foreach ($archivos as $archivo) {
                $identificador = pathinfo($archivo, PATHINFO_FILENAME);
                $elegibles = $this->destinatariosParaNovedad($archivo, $destinatarios);
                if ($elegibles === null) {
                    return self::FAILURE;
                }
                $pendientes = array_values(array_filter($elegibles, fn (array $destinatario) =>
                    ! DB::table('novedades_enviadas')
                        ->where('identificador', $identificador)
                        ->where('email_hash', hash('sha256', mb_strtolower($destinatario['email'])))
                        ->exists()
                ));

                $this->line("{$archivo}: ".count($pendientes).' destinatario(s) pendiente(s).');
                $this->line('Destinatarios: '.($pendientes === [] ? '(ninguno)' : implode(', ', array_column($pendientes, 'email'))));
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
            $elegibles = $this->destinatariosParaNovedad($archivo, $destinatarios);
            if ($elegibles === null) {
                $fallos++;
                continue;
            }
            $ruta = base_path("documentacion/novedades/{$archivo}");
            $contenido = is_readable($ruta) ? file_get_contents($ruta) : false;

            if ($contenido === false || trim($contenido) === '') {
                $this->error("El archivo documentacion/novedades/{$archivo} está vacío o no se pudo leer.");
                $fallos++;
                continue;
            }

            $enviados = 0;
            $pendientes = 0;
            foreach ($elegibles as $destinatario) {
                $email = $destinatario['email'];
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

    /** @return array<int, array{email: string, created_at: string}> */
    private function obtenerDestinatarios(): array
    {
        return User::query()
            ->where('profile', 2)
            ->whereHas('empresas')
            ->whereNotNull('email')
            ->whereNotNull('created_at')
            ->get(['email', 'created_at'])
            ->map(fn (User $user) => ['email' => trim((string) $user->email), 'created_at' => $user->created_at->format('Y-m-d H:i:s')])
            ->filter(fn (array $destinatario) => $destinatario['email'] !== '' && filter_var($destinatario['email'], FILTER_VALIDATE_EMAIL))
            ->unique(fn (array $destinatario) => mb_strtolower($destinatario['email']))
            ->values()
            ->all();
    }

    /**
     * AAMMDD representa el inicio del día; AAMMDDHHMM permite distinguir altas del mismo día.
     *
     * @param array<int, array{email: string, created_at: string}> $destinatarios
     * @return array<int, array{email: string, created_at: string}>|null
     */
    private function destinatariosParaNovedad(string $archivo, array $destinatarios): ?array
    {
        $identificador = pathinfo($archivo, PATHINFO_FILENAME);
        $valido = preg_match('/^(\d{10}|\d{6})(?:[-_].*)?$/', $identificador, $matches);
        $formato = $valido && strlen($matches[1]) === 10 ? '!ymdHi' : '!ymd';
        $fecha = $valido ? DateTimeImmutable::createFromFormat($formato, $matches[1]) : false;

        if ($fecha === false || $fecha->format(strlen($matches[1] ?? '') === 10 ? 'ymdHi' : 'ymd') !== ($matches[1] ?? null)) {
            $this->error("La novedad {$archivo} necesita un identificador que comience con una fecha válida AAMMDD o AAMMDDHHMM.");

            return null;
        }

        $publicadaEl = $fecha->format('Y-m-d H:i:s');

        return array_values(array_filter($destinatarios, fn (array $destinatario) => $destinatario['created_at'] < $publicadaEl));
    }
}
