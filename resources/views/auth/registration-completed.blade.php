<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Registro completado | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logos/logoLB.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-pages.css') }}">
    <style>
        .completed-form-scroll { box-sizing: border-box; justify-content: flex-start; max-height: 100vh; min-height: 100vh; overflow-y: auto; padding-top: clamp(2rem, 5vh, 3rem); padding-bottom: 2rem; }
        .completed-panel {
            min-height: 100vh;
            background-image: linear-gradient(rgba(19, 20, 43, .16), rgba(19, 20, 43, .16)), url("{{ asset('assets/images/fondoreloj.jpg') }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        @media (max-width: 767.98px) { .completed-panel { min-height: auto; } .completed-form-scroll { max-height: none; overflow: visible; } }
        .completed-panel > .abs-t-100px-r-100px-sq400px-bg-primary-8,
        .completed-panel > .abs-b-50px-l-50px-sq250px-bg-violet-6 { display: none; }
    </style>
    @include('layouts.partials.meta-pixel')
</head>
<body class="bg-body-var">
<div class="flex-3">
    <aside class="flex-col-jfc-rel-bg-grad-sidebar-navy-sidebar-navy2-p-12 completed-panel">
        <div class="abs-t-100px-r-100px-sq400px-bg-primary-8"></div>
        <div class="abs-b-50px-l-50px-sq250px-bg-violet-6"></div>
        <div class="pos-rel-z1">
            <a class="iflex-mb12-tdn" href="{{ route('inicio') }}" aria-label="{{ config('app.name') }}, inicio">
                <img src="{{ asset('assets/logos/logoLB.png') }}" alt="{{ config('app.name') }}" height="48">
            </a>
            <h2 class="fs2-fw8-c-white-m-0-0-4-lh-13">Tu equipo.<br>A tiempo.</h2>
            <p class="fs1-c-white-60-m-0-0-10-lh-16">Controla la asistencia de tu equipo desde cualquier lugar.</p>
        </div>
    </aside>
    <main class="flex-col-jfc-w480px-bg-bg-p-12-ov-y-auto-shrink0 completed-form-scroll">
        <div class="w100pct-mw380px-m-0-auto">
            <h1 class="fs1625-fw8-c-body-m-0-0-2">¡Tu cuenta está lista!</h1>
            <p class="fs9-c-muted-m-0-0-8">Tu empresa fue creada correctamente en Horalia.</p>
            <p class="fs9-c-muted-m-0-0-8">Ya puedes iniciar sesión y comenzar a registrar las entradas y salidas de tu equipo.</p>
            <a class="btn-hr btn-primary-hr w100pct-mt1-tdn text-center" href="{{ route('login') }}">Iniciar sesión</a>
        </div>
    </main>
</div>
<script>
    @if($eventId && config('services.meta.pixel_id'))
        if (typeof window.fbq === 'function') {
            window.fbq('track', 'CompleteRegistration', {}, { eventID: @json($eventId) });
        }
    @endif
</script>
</body>
</html>
