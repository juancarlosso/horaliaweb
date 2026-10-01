<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Controla entradas y salidas de tu equipo con Horalia. Reloj checador en línea, registro desde computadora o tablet con PIN y fotografía, reportes por periodo y exportación a Excel.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://horalia.mx/">
    <title>Horalia | Reloj checador y control de asistencia en línea</title>
    @include('layouts.favicon')
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Horalia">
    <meta property="og:locale" content="es_MX">
    <meta property="og:title" content="Horalia | Reloj checador y control de asistencia en línea">
    <meta property="og:description" content="Controla las entradas y salidas de tu equipo de forma simple. Checado desde computadora o tablet con PIN y fotografía.">
    <meta property="og:url" content="https://horalia.mx/">
    <meta property="og:image" content="https://horalia.mx/assets/images/preview.jpg">
    <meta property="og:image:secure_url" content="https://horalia.mx/assets/images/preview.jpg">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1733">
    <meta property="og:image:height" content="907">
    <meta property="og:image:alt" content="Horalia — Reloj checador y control de asistencia en línea">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Horalia | Reloj checador y control de asistencia en línea">
    <meta name="twitter:description" content="Controla las entradas y salidas de tu equipo de forma simple con Horalia.">
    <meta name="twitter:image" content="https://horalia.mx/assets/images/preview.jpg">
    <meta name="twitter:image:alt" content="Horalia — Reloj checador y control de asistencia en línea">
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Horalia',
            'url' => 'https://horalia.mx/',
            'description' => 'Reloj checador en línea para registrar y controlar las entradas y salidas de empleados.',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'inLanguage' => 'es-MX',
            'image' => 'https://horalia.mx/assets/images/preview.jpg',
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) config('constantes.precio_mensual_por_empresa_mxn'),
                'priceCurrency' => 'MXN',
                'description' => 'Suscripción mensual por empresa',
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    <script src="{{ asset('assets/js/landing.js') }}" defer></script>
    @include('layouts.fontawsome')
</head>
<body class="landing-page">
    <header class="site-header">
        <nav class="nav-wrap" aria-label="Navegación principal">
            <a class="brand" href="#inicio" aria-label="{{ config('app.name') }}, inicio"><img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}, tu equipo a tiempo"></a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Abrir menú"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
            <div class="nav-content" id="main-nav">
                <div class="nav-links"><a href="#inicio">Inicio</a><a href="#caracteristicas">Características</a><a href="#como-funciona">Cómo funciona</a><a href="#precios">Precios</a><a href="#preguntas">Preguntas</a></div>
                <div class="nav-actions"><a class="button button-outline" href="{{ route('login') }}">Iniciar sesión</a><a class="button button-primary" href="{{ route('registro.create') }}">Comenzar ahora</a></div>
            </div>
        </nav>
    </header>

    <main>
        @if(session('registro_completado'))
            <div class="section-shell" role="status"><p style="margin:1.5rem auto;padding:1rem 1.25rem;background:#e9f8f2;color:#147a5c;border-radius:12px">Tu cuenta y tu empresa se registraron correctamente.</p></div>
        @endif
        <section class="hero section-shell" id="inicio">
            <div class="hero-copy">
                <span class="eyebrow">Reloj checador en la nube</span>
                <h1>Reloj checador.<br><span>En línea.</span></h1>
                <p class="hero-lede">Controla entradas, salidas y asistencia de tu equipo de forma sencilla, desde cualquier lugar.</p>
                <div class="hero-points">
                    <div><i class="fa-regular fa-clock" aria-hidden="true"></i><span><b>Reloj checador</b><small>fácil de usar</small></span></div>
                    <div><i class="fa-solid fa-chart-column" aria-hidden="true"></i><span><b>Reportes</b><small>en tiempo real</small></span></div>
                    <div><i class="fa-solid fa-users" aria-hidden="true"></i><span><b>Multiempresa</b><small>varias empresas, una cuenta</small></span></div>
                </div>
                <div class="hero-actions"><a class="button button-primary button-large" href="{{ route('registro.create') }}">Comenzar ahora <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><a class="button button-video" href="#como-funciona"><i class="fa-regular fa-circle-play" aria-hidden="true"></i> Ver cómo funciona</a></div>
            </div>
            <div class="hero-visual" aria-label="Vista previa del panel de asistencia {{ config('app.name') }}">
                <div class="hero-orb"></div>
                <div class="laptop-mockup">
                    <div class="laptop-screen"><img class="screen-placeholder dashboard-placeholder" src="{{ asset('assets/images/laptop.jpg') }}" alt="Registro de entradas y salidas en Horalia" width="1615" height="839" fetchpriority="high"></div><div class="laptop-base"></div>
                </div>
            <div class="phone-mockup"><div class="phone-speaker"></div><div class="phone-content"><img class="screen-placeholder phone-placeholder" src="{{ asset('assets/images/celular.jpg') }}" alt="Vista de Horalia en un celular" width="574" height="828"></div></div>
            </div>
        </section>

        <section class="steps-section" id="como-funciona">
            <div class="section-heading"><h2>¿Cómo funciona?</h2><p>En tres simples pasos tu equipo estará registrando su asistencia.</p></div>
            <div class="steps-grid section-shell">
                <article class="step-card"><span class="step-number">1</span><span class="icon-tile"><i class="fa-solid fa-user-plus"></i></span><div><h3>Registra</h3><p>Crea tu empresa, agrega tus empleados y define sus horarios.</p></div></article><span class="step-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <article class="step-card"><span class="step-number">2</span><span class="icon-tile"><i class="fa-regular fa-clock"></i></span><div><h3>Controla</h3><p>Tu equipo marca entrada y salida desde su dispositivo.</p></div></article><span class="step-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <article class="step-card"><span class="step-number">3</span><span class="icon-tile"><i class="fa-solid fa-chart-column"></i></span><div><h3>Consulta</h3><p>Revisa asistencias, retardos, faltas e incidencias.</p></div></article>
            </div>
        </section>

        <section class="feature-story section-shell" id="checador">
            <div class="tablet-scene"><img class="scene-placeholder" src="{{ asset('assets/images/asistencia.jpg') }}" alt="Registro de asistencia en Horalia desde una tablet" width="1536" height="1024" loading="lazy"></div>
            <div class="story-copy"><i class="fa-regular fa-clock story-clock" aria-hidden="true"></i><span class="eyebrow">Reloj checador moderno</span><h2>Marcar asistencia nunca fue tan fácil</h2><p>Tu equipo puede registrar su entrada y salida desde una computadora, tablet o celular, con su foto y la hora exacta. Sin instalaciones, sin equipos costosos y sin complicaciones.</p><ul class="check-list"><li>Funciona en cualquier dispositivo</li><li>Registro de foto y ubicación cuando aplique</li><li>Ideal para oficinas, sucursales y trabajo en campo</li></ul></div>
        </section>

        <section class="features-section section-shell" id="caracteristicas"><div class="feature-heading"><div><h2>Todo lo que necesitas<br>en un solo sistema</h2><p>{{ config('app.name') }} te da las herramientas para mantener el control de tu equipo y simplificar la gestión de la asistencia.</p></div><span class="eyebrow">Simple. Completo. Eficiente.</span></div>
            <div class="feature-grid">
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-users"></i></span><div><h3>Gestión de empleados</h3><p>Registra tu equipo, asigna horarios y departamentos.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-calendar-days"></i></span><div><h3>Horarios flexibles</h3><p>Configura turnos, días de descanso y horas laborales.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-clipboard-check"></i></span><div><h3>Asistencias en tiempo real</h3><p>Consulta entradas, salidas, retardos y faltas al instante.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-file-lines"></i></span><div><h3>Incidencias y justificaciones</h3><p>Registra permisos, vacaciones y faltas de forma sencilla.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-chart-column"></i></span><div><h3>Reportes</h3><p>Obtén información clara y exporta a Excel.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-building"></i></span><div><h3>Multiempresa</h3><p>Administra varias empresas desde una sola cuenta. Cada empresa mantiene su propia suscripción.</p></div></article>
            </div>
        </section>

        <section class="reports-story section-shell" id="reportes"><div class="report-window"><img class="screen-placeholder report-placeholder" src="{{ asset('assets/images/reportes.jpg') }}" alt="Reporte de asistencia por periodo en Horalia" width="1712" height="919" loading="lazy"></div>
            <div class="story-copy"><span class="eyebrow">Decisiones basadas en información</span><h2>Reportes claros y siempre disponibles</h2><p>Consulta la asistencia de tu equipo por periodo, revisa entradas, salidas, retardos y evidencias, y exporta la información a Excel cuando la necesites.</p><ul class="check-list"><li>Consulta por empresa y periodo</li><li>Detalle de entradas, salidas y retardos</li><li>Evidencias de asistencia</li><li>Exportación a Excel</li></ul></div></section>

        <section class="pricing-section" id="precios"><div class="pricing-inner section-shell"><div class="pricing-copy"><span class="eyebrow">Un precio. Sin complicaciones.</span><h2>Todo esto por solo</h2><p class="price">${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} <span>MXN / mes</span></p><p class="price-unit">por empresa</p><p>Acceso completo a las funcionalidades.<br>Sin costos ocultos ni planes complicados.</p><a class="button button-primary button-large" href="{{ route('registro.create') }}">Comenzar ahora <i class="fa-solid fa-arrow-right"></i></a></div><div class="pricing-card"><ul class="check-list"><li>Reloj checador para todos tus empleados</li><li>Gestión de horarios e incidencias</li><li>Reportes y exportación a Excel</li><li>Multiempresa: administra varias empresas, cada una con su suscripción</li><li>Soporte por correo</li><li>Actualizaciones incluidas</li></ul></div></div></section>

        <section class="business-section section-shell"><h2>Ideal para todo tipo de negocios</h2><div class="business-grid"><div><i class="fa-regular fa-building"></i><span>Oficinas</span></div><div><i class="fa-solid fa-shop"></i><span>Comercios</span></div><div><i class="fa-solid fa-utensils"></i><span>Restaurantes</span></div><div><i class="fa-solid fa-screwdriver-wrench"></i><span>Talleres</span></div><div><i class="fa-solid fa-hospital"></i><span>Clínicas</span></div><div><i class="fa-solid fa-school"></i><span>Escuelas</span></div><div><i class="fa-solid fa-building"></i><span>Sucursales</span></div><div><i class="fa-solid fa-shapes"></i><span>Más...</span></div></div></section>

        <section class="faq-section section-shell" id="preguntas"><h2 class="faq-title">Preguntas frecuentes</h2><div class="faq-list"><details><summary>¿Qué incluye la mensualidad de ${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }}?</summary><p>Incluye el reloj checador, gestión de empleados, horarios, asistencias, incidencias, reportes, soporte y actualizaciones para una empresa.</p></details><details><summary>¿Puedo administrar varias empresas?</summary><p>Sí. Puedes administrar varias empresas desde una misma cuenta. La suscripción es de ${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN al mes por cada empresa.</p></details><details><summary>¿Mi equipo necesita un dispositivo especial?</summary><p>No. Puede registrar su asistencia desde una computadora, tablet o celular.</p></details><details><summary>¿Es obligatorio registrar la ubicación?</summary><p>No. La ubicación puede registrarse cuando aplique; el equipo también puede usar el reloj checador sin ella.</p></details></div></section>

        <section class="final-cta" id="contacto"><div class="final-cta-inner section-shell"><div><h2>Empieza a controlar<br>la asistencia de tu equipo hoy.</h2><p>{{ config('app.name') }} es la forma más sencilla y accesible de mantener a tu equipo a tiempo.</p></div><a class="button button-primary button-large" href="{{ route('registro.create') }}">Comenzar ahora <i class="fa-solid fa-arrow-right"></i></a></div></section>
    </main>
    <footer class="site-footer" id="footer">
        <div class="footer-inner section-shell">
            <a class="brand" href="#inicio"><img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}, tu equipo a tiempo"></a>
            <nav class="footer-legal-links" aria-label="Documentos legales">
                <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer">Términos y Condiciones</a>
                <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer">Política de Privacidad</a>
                <a href="{{ route('legal.cookies') }}" target="_blank" rel="noopener noreferrer">Política de Cookies</a>
            </nav>
            <div class="footer-support" aria-label="Contacto y soporte">
                <a href="https://wa.me/525513447932?text={{ rawurlencode('Hola, requiero soporte para ' . config('app.name') . '.') }}" target="_blank" rel="noopener noreferrer" aria-label="Solicitar soporte para {{ config('app.name') }} por WhatsApp">
                    <i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span>
                </a>
                <a href="mailto:contacto@horalia.mx" aria-label="Enviar correo a contacto@horalia.mx">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i><span>Correo</span>
                </a>
            </div>
            <small>© {{ date('Y') }} <a href="https://solventia.com.mx">Solventia Software</a>. Todos los derechos reservados.</small>
        </div>
    </footer>
</body>
</html>
