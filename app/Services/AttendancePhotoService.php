<?php

namespace App\Services;

use App\Helper\Wasabi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttendancePhotoService
{
    public function uploadBase64Jpeg(string $photoData, int $companyId, string $type, string $field = 'foto'): string
    {
        if (!preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=]+)$/', $photoData, $matches)) {
            throw ValidationException::withMessages([$field => 'No se pudo procesar la fotografía. Tómala nuevamente.']);
        }

        $imageContents = base64_decode($matches[1], true);
        $imageInfo = $imageContents === false ? false : @getimagesizefromstring($imageContents);
        if ($imageContents === false || strlen($imageContents) > 3_000_000 || !$imageInfo || $imageInfo['mime'] !== 'image/jpeg') {
            throw ValidationException::withMessages([$field => 'La fotografía no es válida. Tómala nuevamente.']);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'horalia-asistencia-');
        if ($temporaryPath === false) {
            throw ValidationException::withMessages([$field => 'No se pudo procesar la fotografía. Inténtalo de nuevo.']);
        }

        try {
            if (file_put_contents($temporaryPath, $imageContents) === false) {
                throw new \RuntimeException('Unable to prepare attendance photo upload.');
            }

            $file = new UploadedFile($temporaryPath, 'asistencia-' . $type . '.jpg', 'image/jpeg', null, true);
            $path = Wasabi::upload('asistencias/empresa-' . $companyId, $file);
            if (!is_string($path) || $path === '') {
                throw new \RuntimeException('Attendance photo upload returned an empty path.');
            }

            return $path;
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([$field => 'No se pudo guardar la foto. Inténtalo de nuevo.']);
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function delete(string $path): void
    {
        try {
            Storage::disk('wasabi')->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
