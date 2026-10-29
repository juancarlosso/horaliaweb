<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class NovedadesController extends Controller
{
    public function index()
    {
        $novedades = collect(File::files(base_path('documentacion/novedades')))
            ->filter(fn ($file) => $file->getExtension() === 'md')
            ->map(fn ($file) => $this->novedad($file->getFilename()))
            ->filter(fn (?array $novedad) => $novedad !== null && $novedad['publicada_at']->lte(now()))
            ->sort(fn (array $a, array $b) => [$b['publicada_at']->getTimestamp(), $b['id']] <=> [$a['publicada_at']->getTimestamp(), $a['id']])
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginadas = new LengthAwarePaginator(
            $novedades->forPage($page, 10)->values(),
            $novedades->count(),
            10,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('novedades.index', [
            'pageTitle' => 'Novedades de Horalia',
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'novedades',
            'novedades' => $paginadas,
        ]);
    }

    public function show(string $identificador): Response|View
    {
        abort_unless(preg_match('/^\d{6}(?:\d{4})?(?:[-_][A-Za-z0-9_-]+)?$/', $identificador), 404);
        $novedad = $this->novedad($identificador.'.md');
        abort_unless($novedad !== null && $novedad['publicada_at']->lte(now()), 404);

        $html = $this->renderMarkdown($novedad['contenido']);
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function (array $match): string {
            $attributes = preg_replace('/\s+(?:target|rel)="[^"]*"/i', '', $match[1]);

            return '<a'.$attributes.' target="_blank" rel="noopener noreferrer">';
        }, $html) ?? $html;

        return response()->view('novedades.show', [
            'pageTitle' => $novedad['titulo'],
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'novedades',
            'novedad' => $novedad,
            'contenidoHtml' => $html,
        ]);
    }

    private function novedad(string $archivo): ?array
    {
        if (! preg_match('/^(\d{6}|\d{10})(?:[-_].*)?\.md$/', $archivo, $matches)) {
            return null;
        }

        $formato = strlen($matches[1]) === 10 ? '!ymdHi' : '!ymd';
        $formatoSalida = strlen($matches[1]) === 10 ? 'ymdHi' : 'ymd';
        $publicada = CarbonImmutable::createFromFormat($formato, $matches[1], config('app.timezone'));
        if (! $publicada || $publicada->format($formatoSalida) !== $matches[1]) {
            return null;
        }

        $ruta = base_path('documentacion/novedades/'.$archivo);
        $contenido = is_readable($ruta) ? file_get_contents($ruta) : false;
        if (! is_string($contenido) || trim($contenido) === '') {
            return null;
        }

        preg_match('/^\s*#\s+(.+)$/mu', $contenido, $tituloMatch);
        $titulo = trim($tituloMatch[1] ?? pathinfo($archivo, PATHINFO_FILENAME));
        $textoPlano = html_entity_decode(strip_tags($this->renderMarkdown($contenido)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $textoPlano = preg_replace('/\s+/u', ' ', $textoPlano) ?? $textoPlano;
        $textoPlano = trim(preg_replace('/^'.preg_quote($titulo, '/').'\s*/u', '', $textoPlano, 1) ?? $textoPlano);

        return [
            'id' => pathinfo($archivo, PATHINFO_FILENAME),
            'titulo' => $titulo,
            'publicada_at' => $publicada,
            'resumen' => $this->limitarResumen($textoPlano),
            'contenido' => $contenido,
        ];
    }

    private function renderMarkdown(string $contenido): string
    {
        return Str::markdown($contenido, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    private function limitarResumen(string $texto): string
    {
        if (mb_strlen($texto) <= 240) {
            return $texto;
        }

        $fragmento = mb_substr($texto, 0, 240);
        $ultimoEspacio = mb_strrpos($fragmento, ' ');
        if ($ultimoEspacio !== false && $ultimoEspacio > 0) {
            $fragmento = mb_substr($fragmento, 0, $ultimoEspacio);
        }

        return rtrim($fragmento).'…';
    }
}
