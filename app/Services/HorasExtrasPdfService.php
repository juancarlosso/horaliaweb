<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\HoraExtra;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Dompdf\Canvas;
use Dompdf\FontMetrics;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HorasExtrasPdfService
{
    public function generar(HoraExtra $registro): DocumentoPdf
    {
        $logo = $this->logoEmpresa(Empresa::find($registro->empresa_id)?->logo);
        $logoHoralia = $logo === null;
        $logo ??= $this->imagen(file_get_contents(public_path('assets/logos/logoLB.png')), 108, 32);

        $pdf = Pdf::loadView('horas-extras.pdf', compact('registro', 'logo', 'logoHoralia'))->setPaper('letter');
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_script(static function (
            int $pagina, int $total, Canvas $canvas, FontMetrics $fuentes
        ): void {
            $izquierda = 54;
            $derecha = $canvas->get_width() - 54;
            $y = $canvas->get_height() - 35;
            $font = $fuentes->getFont('DejaVu Sans', 'normal');
            $color = [0.45, 0.48, 0.61];
            $texto = "Página {$pagina} de {$total}";

            $canvas->line($izquierda, $y, $derecha, $y, [0.84, 0.86, 0.97], 0.7);
            $canvas->text($izquierda, $y + 5, 'Generado por Horalia  |  horalia.mx', $font, 7, $color);
            $canvas->text($derecha - $fuentes->getTextWidth($texto, $font, 7), $y + 5, $texto, $font, 7, $color);
        });

        return $pdf;
    }

    private function logoEmpresa(?string $ruta): ?array
    {
        if (! $ruta) {
            return null;
        }

        // Las cargas anteriores al almacenamiento en Wasabi se guardan en el disco público.
        $disco = str_starts_with($ruta, 'empresas/logos/') ? 'public' : 'wasabi';

        try {
            $contenido = Storage::disk($disco)->get($ruta);
        } catch (Throwable $error) {
            report($error);

            return null;
        }

        return $contenido ? $this->imagen($contenido, 128, 42) : null;
    }

    private function imagen(string $contenido, int $anchoMaximo, int $altoMaximo): ?array
    {
        $imagen = @getimagesizefromstring($contenido);
        if (! $imagen || ! in_array($imagen['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)
            || ($imagen['mime'] === 'image/webp' && ! function_exists('imagecreatefromwebp'))) {
            return null;
        }

        [$ancho, $alto] = $imagen;
        $escala = min($anchoMaximo / $ancho, $altoMaximo / $alto);

        return [
            'src' => 'data:'.$imagen['mime'].';base64,'.base64_encode($contenido),
            'ancho' => $ancho * $escala,
            'alto' => $alto * $escala,
        ];
    }
}
