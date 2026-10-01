<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    @include('layouts.favicon')
    <link rel="canonical" href="https://horalia.mx/{{ request()->path() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    @include('layouts.fontawsome')
</head>
<body class="landing-page legal-page">
    <header class="legal-header">
        <div class="section-shell legal-header-inner">
            <a class="brand" href="{{ url('/') }}" aria-label="{{ config('app.name') }}, volver al inicio"><img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}, tu equipo a tiempo"></a>
            <a class="legal-return" href="{{ url('/') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Regresar a {{ config('app.name') }}</a>
        </div>
    </header>

    <main class="section-shell legal-main">
        <div class="legal-kicker"><span class="eyebrow">{{ config('app.name') }} · Información legal</span></div>
        <article class="legal-document">{!! $content !!}</article>
        <div class="legal-bottom"><a class="button button-primary" href="{{ url('/') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver a {{ config('app.name') }}</a></div>
    </main>

    <footer class="legal-footer"><div class="section-shell">© {{ date('Y') }} <a href="https://solventia.com.mx">Solventia Software</a>. Todos los derechos reservados.</div></footer>
</body>
</html>
