<?php

namespace App\Services;

use App\Helper\Wasabi;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PersonalDocumentosService
{
    public static function reglasArchivo(): array
    {
        return ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.(int) config('filesystems.personal_document_max_kb', 10240)];
    }

    public function descargar(PersonalDocumento $documento)
    {
        $contenido = Storage::disk('wasabi')->get($documento->ruta_privada);
        abort_if($contenido === null, 404);
        $nombre = preg_replace('/[\r\n\x00-\x1F\x7F]/u', '', $documento->nombre_original);

        return response($contenido, 200, [
            'Content-Type' => $documento->mime,
            'Content-Length' => (string) $documento->tamano,
            'Content-Disposition' => 'attachment; filename="'.addcslashes($nombre, '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function guardar(Personal $empleado, UploadedFile $archivo, int $usuario, array $datos): PersonalDocumento
    {
        $nombre = basename(str_replace('\\', '/', $archivo->getClientOriginalName()));
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre);
        if (! $nombre || $nombre === '.' || $nombre === '..') {
            throw ValidationException::withMessages(['archivo' => 'El nombre del archivo no es válido.']);
        }

        $mime = (string) $archivo->getMimeType();
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages(['archivo' => 'El contenido del archivo no es un formato permitido.']);
        }

        $ruta = null;
        try {
            $ruta = Wasabi::uploadWithOriginalName('personal/expedientes/empresa-'.$empleado->empresa_id.'/personal-'.$empleado->id, $archivo, $nombre);
            if (! $ruta || ! Storage::disk('wasabi')->exists($ruta)) {
                throw new RuntimeException('Wasabi no confirmó la carga.');
            }

            return PersonalDocumento::create([
                'empresa_id' => $empleado->empresa_id, 'personal_id' => $empleado->id,
                'ruta_privada' => $ruta, 'nombre_original' => Str::limit($nombre, 255, ''),
                'mime' => $mime, 'tamano' => $archivo->getSize(), 'cargado_por' => $usuario,
            ] + $datos);
        } catch (Throwable $error) {
            if ($ruta) {
                try {
                    Storage::disk('wasabi')->delete($ruta);
                } catch (Throwable $cleanupError) {
                    report($cleanupError);
                }
            }

            throw $error;
        }
    }
}
