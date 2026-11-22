<?php

namespace App\Services;

use App\Helper\Wasabi;
use App\Support\HoraliaTimezones;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttendancePhotoService
{
    private const MAX_EDGE = 960;
    private const JPEG_QUALITY = 55;
    private const MAX_INPUT_PIXELS = 24_000_000;

    public function uploadBase64Jpeg(string $photoData, int $companyId, string $type, string $field = 'foto', ?string $centerTimezone = null): string
    {
        if (!preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=]+)$/', $photoData, $matches)) {
            throw ValidationException::withMessages([$field => 'No se pudo procesar la fotografía. Tómala nuevamente.']);
        }

        $imageContents = base64_decode($matches[1], true);
        $imageInfo = $imageContents === false ? false : @getimagesizefromstring($imageContents);
        if ($imageContents === false || strlen($imageContents) > 3_000_000 || !$imageInfo
            || $imageInfo['mime'] !== 'image/jpeg' || $imageInfo[0] * $imageInfo[1] > self::MAX_INPUT_PIXELS) {
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

            $this->compressPhoto($temporaryPath);
            clearstatcache(true, $temporaryPath);
            $file = new UploadedFile($temporaryPath, 'asistencia-' . $type . '.jpg', 'image/jpeg', null, true);
            $datePath = now(HoraliaTimezones::forCenter($centerTimezone))->format('Y/m/d');
            $path = Wasabi::upload('asistencias/empresa-' . $companyId . '/' . $datePath, $file);
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

    private function compressPhoto(string $path): void
    {
        $image = @imagecreatefromjpeg($path);
        if ($image === false) {
            throw new \RuntimeException('Unable to decode attendance JPEG.');
        }

        $scale = min(1, self::MAX_EDGE / max(imagesx($image), imagesy($image)));
        if ($scale < 1) {
            $image = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)), IMG_BILINEAR_FIXED);
            if ($image === false) {
                throw new \RuntimeException('Unable to resize attendance JPEG.');
            }
        }

        $exif = function_exists('exif_read_data') ? @exif_read_data($path, 'IFD0') : false;
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 8 => 90,
            6, 7 => -90,
            default => 0,
        };

        if ($angle !== 0) {
            $image = imagerotate($image, $angle, 0);
        }

        if ($image === false || !imagejpeg($image, $path, self::JPEG_QUALITY)) {
            throw new \RuntimeException('Unable to encode attendance JPEG.');
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
