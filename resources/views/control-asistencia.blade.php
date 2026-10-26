<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Organiza empleados, horarios y centros de trabajo. Consulta entradas, salidas, pausas y retardos en Horalia. Primer mes gratis; desde $150 MXN al mes por empresa.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://horalia.mx/control-de-asistencia-empleados">
    <title>Control de asistencia de empleados para empresas | Horalia</title>
    @include('layouts.favicon')
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Horalia">
    <meta property="og:locale" content="es_MX">
    <meta property="og:title" content="Control de asistencia de empleados para empresas | Horalia">
    <meta property="og:description" content="Organiza empleados, horarios y centros de trabajo. Consulta la asistencia por periodo y exporta reportes a Excel.">
    <meta property="og:url" content="https://horalia.mx/control-de-asistencia-empleados">
    <meta property="og:image" content="https://horalia.mx/assets/images/preview.jpg">
    <meta property="og:image:secure_url" content="https://horalia.mx/assets/images/preview.jpg">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1733">
    <meta property="og:image:height" content="907">
    <meta property="og:image:alt" content="Horalia, plataforma de control de asistencia para empresas">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Control de asistencia de empleados para empresas | Horalia">
    <meta name="twitter:description" content="Organiza empleados, horarios y centros de trabajo. Consulta la asistencia por periodo y exporta reportes a Excel.">
    <meta name="twitter:image" content="https://horalia.mx/assets/images/preview.jpg">
    <meta name="twitter:image:alt" content="Horalia, plataforma de control de asistencia para empresas">
    @include('layouts.partials.meta-pixel')
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Control de asistencia de empleados para empresas | Horalia',
            'url' => 'https://horalia.mx/control-de-asistencia-empleados',
            'description' => 'Organiza empleados, horarios y centros de trabajo. Consulta entradas, salidas, pausas y retardos en Horalia.',
            'inLanguage' => 'es-MX',
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'Horalia', 'url' => 'https://horalia.mx/'],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/control-asistencia.css') }}">
    <script src="{{ asset('assets/js/landing.js') }}" defer></script>
    @include('layouts.fontawsome')
</head>
<body class="landing-page attendance-page">
    <header class="site-header">
        <nav class="nav-wrap" aria-label="Navegación principal">
            <a class="brand" href="{{ route('inicio') }}" aria-label="{{ config('app.name') }}, inicio"><img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}, tu equipo a tiempo"></a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Abrir menú"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
            <div class="nav-content" id="main-nav">
                <div class="nav-links"><a href="{{ route('inicio') }}">Inicio</a><a href="#organizacion">Organización</a><a href="#seguimiento">Seguimiento</a><a href="#reportes">Reportes</a><a href="#precios">Precios</a><a href="#preguntas">Preguntas</a></div>
                <div class="nav-actions"><a class="button button-outline" href="{{ route('login') }}">Iniciar sesión</a><a class="button button-primary" href="{{ route('registro.create') }}">Crear mi cuenta</a></div>
            </div>
        </nav>
    </header>

    <main>
        <section class="attendance-hero" id="inicio" aria-labelledby="attendance-title">
            <div class="attendance-hero-inner section-shell">
                <div class="attendance-hero-copy">
                    <span class="eyebrow"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Asistencia organizada</span>
                    <h1 id="attendance-title">Control de asistencia de empleados <span>para tu empresa</span></h1>
                    <p>Organiza a tu personal, administra horarios y centros de trabajo, y consulta entradas, salidas, retardos y pausas desde un solo lugar.</p>
                    <a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <p class="hero-trial-note"><i class="fa-solid fa-gift" aria-hidden="true"></i><span>¡Tu primer mes es gratis!</span></p>
                </div>
                <div class="attendance-hero-panel" aria-label="Empleados, horarios y centros de trabajo vinculados con la asistencia">
                    <div class="attendance-panel-heading"><span class="attendance-panel-logo"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span><div><strong>Tu operación, conectada</strong><small>De la organización al seguimiento</small></div></div>
                    <div class="attendance-panel-grid">
                        <div><span class="attendance-panel-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span><strong>Empleados</strong><small>Personal y asignaciones</small></div>
                        <div><span class="attendance-panel-icon"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span><strong>Horarios</strong><small>Jornadas configuradas</small></div>
                        <div><span class="attendance-panel-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span><strong>Centros de trabajo</strong><small>Ubicación del equipo</small></div>
                    </div>
                    <div class="attendance-panel-result"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i><span><strong>Asistencia por jornada</strong><small>Entradas · salidas · pausas</small></span></div>
                </div>
            </div>
        </section>

        <section class="attendance-problem section-shell" aria-labelledby="problem-title">
            <div><span class="eyebrow">El reto diario</span><h2 id="problem-title">Controlar la asistencia no debería ser complicado</h2></div>
            <div><p>Administrar horarios, revisar retardos y consultar los registros de diferentes empleados puede convertirse en una tarea complicada cuando la información está dispersa.</p><p>Con Horalia puedes centralizar el control de asistencia y consultar la información de tu personal desde una misma plataforma.</p></div>
        </section>

        <section class="attendance-organization" id="organizacion" aria-labelledby="organization-title">
            <div class="section-shell">
                <div class="attendance-section-heading"><span class="eyebrow">Primero, organiza</span><h2 id="organization-title">Organiza empleados, horarios y centros de trabajo</h2><p>Cada empresa trabaja de manera diferente. Horalia te permite organizar a tu personal según sus horarios y centros de trabajo para llevar un mejor control de la asistencia.</p></div>
                <div class="attendance-card-grid">
                    <article class="attendance-info-card"><span class="attendance-card-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span><span class="attendance-card-number">01</span><h3>Empleados</h3><p>Administra la información de tu personal y sus asignaciones.</p></article>
                    <article class="attendance-info-card"><span class="attendance-card-icon"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span><span class="attendance-card-number">02</span><h3>Horarios</h3><p>Configura los horarios de trabajo para dar seguimiento a entradas, salidas y retardos.</p></article>
                    <article class="attendance-info-card"><span class="attendance-card-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span><span class="attendance-card-number">03</span><h3>Centros de trabajo</h3><p>Organiza a tus empleados por sucursal o centro de trabajo.</p></article>
                </div>
            </div>
        </section>

        <section class="attendance-tracking section-shell" id="seguimiento" aria-labelledby="tracking-title">
            <div class="attendance-tracking-copy"><span class="eyebrow">Después, da seguimiento</span><h2 id="tracking-title">Conoce lo que sucede durante cada jornada</h2><p>Consulta los registros de entrada y salida de tu personal, identifica retardos y salidas anticipadas, y lleva un seguimiento de las pausas registradas durante la jornada.</p><p class="attendance-text-link"><a href="{{ route('inicio') }}#checador">Conoce nuestro reloj checador en línea <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p><p class="attendance-text-link"><a href="{{ route('reloj-checador.tablet') }}">Conoce cómo usar una tablet como reloj checador <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p></div>
            <div class="attendance-tracking-list" aria-label="Datos disponibles para dar seguimiento a la asistencia">
                <div><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span>Entradas y salidas</span></div>
                <div><i class="fa-regular fa-clock" aria-hidden="true"></i><span>Retardos</span></div>
                <div><i class="fa-solid fa-person-walking-arrow-right" aria-hidden="true"></i><span>Salidas anticipadas</span></div>
                <div><i class="fa-solid fa-mug-hot" aria-hidden="true"></i><span>Pausas</span></div>
                <div><i class="fa-solid fa-clipboard-question" aria-hidden="true"></i><span>Registros sin salida</span></div>
            </div>
        </section>

        <section class="attendance-reports" id="reportes" aria-labelledby="reports-title">
            <div class="section-shell">
                <div class="attendance-section-heading"><span class="eyebrow">Información para consultar</span><h2 id="reports-title">Toda la información de asistencia, cuando la necesitas</h2><p>Consulta la asistencia de tu personal por periodo, revisa retardos, salidas anticipadas y pausas, y exporta tus reportes a Excel para trabajar con la información de tu empresa.</p></div>
                <div class="attendance-report-layout">
                    <div class="attendance-report-image"><img src="{{ asset('assets/images/reportes2.jpg') }}" alt="Vista del reporte de asistencia de Horalia con filtros por periodo, resumen de registros y exportación a Excel" width="1713" height="918" loading="lazy"></div>
                    <div class="attendance-report-benefits">
                        <article><span><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span><div><h3>Consulta por periodo</h3><p>Selecciona las fechas que necesitas revisar.</p></div></article>
                        <article><span><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span><div><h3>Incidencias</h3><p>Identifica retardos, salidas anticipadas y registros sin salida.</p></div></article>
                        <article><span><i class="fa-solid fa-mug-hot" aria-hidden="true"></i></span><div><h3>Pausas</h3><p>Consulta los periodos de pausa registrados durante las jornadas.</p></div></article>
                        <article><span><i class="fa-solid fa-file-excel" aria-hidden="true"></i></span><div><h3>Exportación a Excel</h3><p>Descarga la información de asistencia para utilizarla en tus propios análisis.</p></div></article>
                    </div>
                </div>
            </div>
        </section>

        <section class="attendance-pricing" id="precios" aria-labelledby="pricing-title">
            <div class="attendance-pricing-inner section-shell">
                <div><span class="eyebrow">Precio transparente</span><h2 id="pricing-title">Control de asistencia a un precio accesible</h2><p>Organiza la asistencia de tu equipo con una mensualidad por empresa.</p></div>
                <div class="attendance-price-card"><p class="attendance-price">{{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} <small>MXN / mes</small></p><p class="attendance-price-unit">Por empresa · IVA incluido</p><ul><li>Incluye hasta 200 empleados.</li><li>Cada bloque adicional de hasta 200 empleados cuesta {{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales, IVA incluido.</li></ul><p class="attendance-price-trial"><i class="fa-solid fa-gift" aria-hidden="true"></i> ¡Tu primer mes es gratis!</p><a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            </div>
        </section>

        <section class="faq-section section-shell attendance-faq" id="preguntas" aria-labelledby="faq-title">
            <h2 class="faq-title" id="faq-title">Preguntas frecuentes</h2>
            <div class="faq-list">
                <details><summary>¿Puedo administrar empleados de diferentes sucursales?</summary><p>Sí. Horalia permite organizar empleados por centros de trabajo y asignarles los horarios correspondientes.</p></details>
                <details><summary>¿Cómo puedo identificar retardos y salidas anticipadas?</summary><p>Horalia compara los registros de asistencia con los horarios configurados y permite consultar los minutos de retardo o salida anticipada cuando corresponde.</p></details>
                <details><summary>¿Qué información incluyen los reportes?</summary><p>Puedes consultar entradas, salidas, horarios, retardos, salidas anticipadas y pausas, según la información disponible en cada registro.</p></details>
                <details><summary>¿Puedo exportar la asistencia a Excel?</summary><p>Sí. Horalia permite exportar reportes de asistencia y pausas a Excel para consultar y trabajar con los datos.</p></details>
                <details><summary>¿Cuánto cuesta Horalia?</summary><p>Horalia cuesta {{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales por empresa, IVA incluido, e incluye hasta 200 empleados. Cada bloque adicional de hasta 200 empleados cuesta {{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales, IVA incluido. Tu primer mes es gratis.</p></details>
            </div>
        </section>

        <section class="attendance-final-cta" aria-labelledby="final-title"><div class="section-shell"><div><span class="eyebrow">Comienza con tu equipo</span><h2 id="final-title">Simplifica el control de asistencia de tu empresa</h2><p>Organiza a tu personal, consulta sus registros y lleva el control de asistencia desde una sola plataforma.</p></div><div class="attendance-final-actions"><a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><p><i class="fa-solid fa-gift" aria-hidden="true"></i> ¡Tu primer mes es gratis!</p></div></div></section>
    </main>

    <footer class="site-footer" id="footer">
        <div class="footer-inner section-shell">
            <a class="brand" href="{{ route('inicio') }}" aria-label="{{ config('app.name') }}, inicio"><img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}, tu equipo a tiempo"></a>
            <nav class="footer-legal-links" aria-label="Documentos legales"><a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer">Términos y Condiciones</a><a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer">Política de Privacidad</a><a href="{{ route('legal.cookies') }}" target="_blank" rel="noopener noreferrer">Política de Cookies</a></nav>
            <div class="footer-support" aria-label="Contacto y soporte"><a href="https://wa.me/525513447932?text={{ rawurlencode('Hola, requiero soporte para ' . config('app.name') . '.') }}" target="_blank" rel="noopener noreferrer" aria-label="Solicitar soporte para {{ config('app.name') }} por WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span></a><a href="mailto:contacto@horalia.mx" aria-label="Enviar correo a contacto@horalia.mx"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span>Correo</span></a></div>
            <small>Horalia es un servicio de <a href="https://solventia.com.mx">Solventia Software</a>. © {{ date('Y') }} Todos los derechos reservados.</small>
        </div>
    </footer>
</body>
</html>
