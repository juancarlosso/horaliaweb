<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Horalia es un reloj checador en línea para empresas. Registra entradas, salidas y pausas, consulta reportes y prueba gratis tu primer mes.">
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
    @include('layouts.partials.meta-pixel')
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
                <div class="nav-links"><a href="#inicio">Inicio</a><a href="#caracteristicas">Características</a><a href="#como-funciona">Cómo funciona</a><a href="#registro-electronico">Marco Legal</a><a href="#precios">Precios</a><a href="#preguntas">Preguntas</a></div>
                <div class="nav-actions"><a class="button button-outline" href="{{ route('login') }}">Iniciar sesión</a><a class="button button-primary" href="{{ route('registro.create') }}">Crear mi cuenta</a></div>
            </div>
        </nav>
    </header>

    <main>
        @if(session('registro_completado'))
            <div class="section-shell" role="status"><p style="margin:1.5rem auto;padding:1rem 1.25rem;background:#e9f8f2;color:#147a5c;border-radius:12px">Tu cuenta y tu empresa se registraron correctamente.</p></div>
        @endif
        <section class="hero section-shell" id="inicio">
            <div class="hero-copy">
                <a class="labor-reform-badge" href="#registro-electronico"><span aria-hidden="true">🇲🇽</span> Prepárate para el registro electrónico de la jornada laboral</a>
                <h1>Reloj checador en línea<br><span>para tu empresa</span></h1>
                <p class="hero-lede">Registra entradas, salidas y pausas de tu personal desde una tablet o computadora. Consulta la asistencia y genera reportes desde un solo lugar.</p>
                <div class="hero-points">
                    <div><i class="fa-regular fa-clock" aria-hidden="true"></i><span><b>Reloj checador</b><small>fácil de usar</small></span></div>
                    <div><i class="fa-solid fa-chart-column" aria-hidden="true"></i><span><b>Reportes</b><small>en tiempo real</small></span></div>
                    <div><i class="fa-solid fa-building" aria-hidden="true"></i><span><b>Centros de trabajo</b><small>organiza tus sucursales</small></span></div>
                </div>
                <div class="hero-actions"><a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><button class="button button-video" type="button" data-video-open><i class="fa-regular fa-circle-play" aria-hidden="true"></i> Ver cómo funciona</button></div>
                <p class="hero-trial-note"><i class="fa-solid fa-gift" aria-hidden="true"></i><span>¡Tu primer mes es gratis!</span></p>
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
                <article class="step-card"><span class="step-number">2</span><span class="icon-tile"><i class="fa-regular fa-clock"></i></span><div><h3>Controla</h3><p>Tu personal registra entradas, salidas y pausas con el reloj checador.</p></div></article><span class="step-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <article class="step-card"><span class="step-number">3</span><span class="icon-tile"><i class="fa-solid fa-chart-column"></i></span><div><h3>Consulta</h3><p>Revisa asistencias, retardos, faltas e incidencias.</p></div></article>
            </div>
        </section>

        <section class="feature-story section-shell" id="checador">
            <div class="tablet-scene"><img class="scene-placeholder" src="{{ asset('assets/images/asistencia.jpg') }}" alt="Registro de asistencia en Horalia desde una tablet" width="1536" height="1024" loading="lazy"></div>
            <div class="story-copy"><i class="fa-regular fa-clock story-clock" aria-hidden="true"></i><span class="eyebrow">Reloj checador moderno</span><h2>Marcar asistencia nunca fue tan fácil</h2><p>Tu equipo puede registrar su entrada y salida desde una computadora, tablet o celular, con su foto y la hora exacta. Sin instalaciones, sin equipos costosos y sin complicaciones.</p><a class="button button-outline tablet-story-link" href="/reloj-checador-tablet"><span>Conoce el reloj checador para tablet</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><ul class="check-list"><li>Funciona en cualquier dispositivo</li><li>Registro de foto y ubicación cuando aplique</li><li>Ideal para oficinas, sucursales y trabajo en campo</li></ul></div>
        </section>

        <section class="features-section section-shell" id="caracteristicas"><div class="feature-heading"><div><h2>Todo lo que necesitas<br>en un solo sistema</h2><p>{{ config('app.name') }} te da las herramientas para mantener el control de tu equipo y simplificar la gestión de la asistencia.</p></div><span class="eyebrow">Simple. Completo. Eficiente.</span></div>
            <div class="feature-grid">
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-users"></i></span><div><h3>Gestión de empleados</h3><p>Registra tu equipo, asigna horarios y departamentos.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-calendar-days"></i></span><div><h3>Horarios flexibles</h3><p>Configura turnos, días de descanso y horas laborales.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-clipboard-check"></i></span><div><h3>Asistencias en tiempo real</h3><p>Consulta entradas, salidas, retardos y faltas al instante.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-file-lines"></i></span><div><h3>Pausas e incidencias</h3><p>Registra los periodos de pausa del personal y gestiona sus incidencias.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-chart-column"></i></span><div><h3>Reportes</h3><p>Obtén información clara y exporta a Excel.</p></div></article>
                <article class="feature-card"><span class="icon-tile"><i class="fa-solid fa-building"></i></span><div><h3>Centros de trabajo</h3><p>Organiza la asistencia por centros de trabajo o sucursales.</p></div></article>
            </div>
            <p class="feature-deep-link"><a href="{{ route('control-asistencia.empleados') }}">Conoce cómo organizar el control de asistencia de tus empleados <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
        </section>

        <section class="implementation-section section-shell" id="implementacion-sin-resistencia" aria-labelledby="implementation-title">
            <div class="implementation-copy">
                <span class="eyebrow"><i class="fa-solid fa-users" aria-hidden="true"></i> IMPLEMENTACIÓN SIN COMPLICACIONES</span>
                <h2 id="implementation-title">Un cambio positivo para todos, no solo para tu empresa.</h2>
                <p>Implementar un reloj checador no tiene por qué generar conflictos. Cuando tu equipo comprende sus beneficios, el registro de asistencia puede convertirse en una herramienta de organización, transparencia y confianza.</p>
                <div class="implementation-benefits">
                    <article><span class="icon-tile"><i class="fa-solid fa-users" aria-hidden="true"></i></span><div><h3>Genera confianza</h3><p>El registro de asistencia ofrece información clara tanto para la empresa como para sus colaboradores.</p></div></article>
                    <article><span class="icon-tile"><i class="fa-regular fa-clock" aria-hidden="true"></i></span><div><h3>Respeta el tiempo de todos</h3><p>Documenta horarios, entradas, salidas y pausas para reducir confusiones sobre las jornadas laborales.</p></div></article>
                    <article><span class="icon-tile"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span><div><h3>Facilita la adaptación</h3><p>Comunica los beneficios, escucha las inquietudes y permite que tu equipo se familiarice gradualmente con el sistema.</p></div></article>
                </div>
                <a class="button button-primary button-large implementation-cta" href="/blog/como-implementar-reloj-checador-empleados">Descubre cómo implementar Horalia sin resistencia <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="implementation-visual"><img src="{{ asset('assets/images/todos.jpg') }}" alt="Equipo de trabajo colaborando alrededor de una computadora" width="1536" height="1024" loading="lazy"></div>
        </section>

        <section class="reports-story section-shell" id="reportes"><div class="report-window"><img class="screen-placeholder report-placeholder" src="{{ asset('assets/images/reportes.jpg') }}" alt="Reporte de asistencia por periodo en Horalia" width="1712" height="919" loading="lazy"></div>
            <div class="story-copy"><span class="eyebrow">Decisiones basadas en información</span><h2>Reportes claros y siempre disponibles</h2><p>Consulta en un mismo lugar entradas, salidas, retardos y evidencias, y exporta tus reportes a Excel cuando los necesites.</p><ul class="check-list"><li>Consulta por empresa y periodo</li><li>Detalle de entradas, salidas y retardos</li><li>Evidencias de asistencia</li><li>Exportación a Excel</li></ul></div></section>

        <section class="labor-reform-section" id="registro-electronico" aria-labelledby="labor-reform-title">
            <div class="labor-reform-inner section-shell">
                <div class="labor-reform-copy">
                    <span class="eyebrow labor-section-eyebrow"><span aria-hidden="true">🇲🇽</span> REFORMA LABORAL EN MÉXICO</span>
                    <h2 id="labor-reform-title">Tu empresa, preparada para el registro electrónico de asistencia.</h2>
                    <p>La <strong>Ley Federal del Trabajo</strong> contempla disposiciones relacionadas con el registro electrónico de la jornada laboral.</p>
                    <p>Con Horalia puedes digitalizar las entradas y salidas de tus colaboradores, mantener un historial de asistencia y generar reportes de manera sencilla, <strong>facilitando la adaptación de tu empresa a las nuevas disposiciones laborales sobre el registro electrónico de la jornada de trabajo.</strong></p>
                    <div class="labor-benefits">
                        <article><i class="fa-regular fa-clock" aria-hidden="true"></i><div><h3>Registro digital de entradas y salidas.</h3><p>Registra la fecha y hora de los movimientos de cada colaborador.</p></div></article>
                        <article><i class="fa-solid fa-calendar-check" aria-hidden="true"></i><div><h3>Historial de asistencias.</h3><p>Consulta los registros de asistencia para facilitar su seguimiento.</p></div></article>
                        <article><i class="fa-solid fa-file-excel" aria-hidden="true"></i><div><h3>Reportes exportables.</h3><p>Genera reportes en Excel para consulta y revisión de la información.</p></div></article>
                    </div>
                    <button class="button button-outline" type="button" data-legal-open>Conoce el marco legal <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                </div>
                <div class="labor-visual">
                    <img class="labor-visual-image" src="{{ asset('assets/images/ley.jpg') }}" alt="Diario Oficial de la Federación junto a iconos de registro de jornada y asistencia" width="1196" height="1315" loading="lazy">
                </div>
                <aside class="labor-legal-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><p>Horalia es una herramienta de apoyo para el registro electrónico de asistencia.<br>El cumplimiento de las obligaciones laborales depende de las disposiciones oficiales vigentes y de la implementación que realice cada empresa.</p><a href="https://www.dof.gob.mx/" target="_blank" rel="noopener noreferrer">Consultar fuente oficial <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></aside>
            </div>
        </section>

        <section class="pricing-section" id="precios"><div class="pricing-inner section-shell"><div class="pricing-copy"><span class="eyebrow">Un precio. Sin complicaciones.</span><h2>Todo esto por solo</h2><p class="price">${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} <span>MXN / mes</span></p><p class="price-unit">por empresa · IVA incluido</p><small class="pricing-limit">Incluye hasta 200 empleados</small><p class="pricing-trial">¡Tu primer mes es gratis!</p><p>Acceso completo a las funcionalidades.</p><p>¿Necesitas más capacidad? Cada bloque adicional de hasta 200 empleados cuesta ${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales, IVA incluido.</p><a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div><div class="pricing-card"><ul class="check-list"><li>Reloj checador para todos tus empleados</li><li>Gestión de horarios e incidencias</li><li>Reportes y exportación a Excel</li><li>Multiempresa: administra varias empresas, cada una con su suscripción</li><li>Soporte por correo</li><li>Actualizaciones incluidas</li></ul></div></div></section>

        <section class="business-section section-shell"><h2>Ideal para todo tipo de negocios</h2><div class="business-grid"><div><i class="fa-regular fa-building"></i><span>Oficinas</span></div><div><i class="fa-solid fa-shop"></i><span>Comercios</span></div><div><i class="fa-solid fa-utensils"></i><span>Restaurantes</span></div><div><i class="fa-solid fa-screwdriver-wrench"></i><span>Talleres</span></div><div><i class="fa-solid fa-hospital"></i><span>Clínicas</span></div><div><i class="fa-solid fa-school"></i><span>Escuelas</span></div><div><i class="fa-solid fa-building"></i><span>Sucursales</span></div><div><i class="fa-solid fa-shapes"></i><span>Más...</span></div></div></section>

        <section class="faq-section section-shell" id="preguntas"><h2 class="faq-title">Preguntas frecuentes</h2><div class="faq-list"><details><summary>¿Horalia tiene un periodo de prueba?</summary><p>Sí. Puedes utilizar Horalia gratis durante tu primer mes.</p></details><details><summary>¿Necesito registrar una tarjeta para comenzar?</summary><p>No. Puedes crear tu cuenta y comenzar a utilizar Horalia sin registrar una tarjeta.</p></details><details><summary>¿Cuánto cuesta Horalia?</summary><p>Horalia cuesta ${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales por empresa, IVA incluido, e incluye hasta 200 empleados. Cada bloque adicional de hasta 200 empleados cuesta ${{ number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales, IVA incluido.</p></details><details><summary>¿Qué sucede cuando termina mi primer mes gratis?</summary><p>Al finalizar el primer mes gratis, podrás continuar utilizando Horalia mediante la renovación de tu suscripción correspondiente.</p></details><details><summary>¿Qué incluye la mensualidad?</summary><p>Incluye el reloj checador, gestión de empleados, horarios, asistencias, incidencias, reportes, soporte y actualizaciones para una empresa.</p></details><details><summary>¿Puedo administrar varias empresas?</summary><p>Sí. Puedes administrar varias empresas desde una misma cuenta. Cada empresa mantiene su propia suscripción.</p></details><details><summary>¿Mi equipo necesita un dispositivo especial?</summary><p>No. Puede registrar su asistencia desde una computadora, tablet o celular.</p></details><details><summary>¿Es obligatorio registrar la ubicación?</summary><p>No. La ubicación puede registrarse cuando aplique; el equipo también puede usar el reloj checador sin ella.</p></details><details><summary>¿Horalia me ayuda a prepararme para las disposiciones sobre registro electrónico de asistencia?</summary><p>Sí. Horalia permite registrar electrónicamente entradas y salidas, consultar el historial de asistencias y exportar reportes. Estas herramientas ayudan a digitalizar el control de asistencia. El cumplimiento legal específico depende de la normativa aplicable y de cómo cada empresa implemente sus procesos.</p></details></div></section>

        <section class="final-cta" id="contacto"><div class="final-cta-inner section-shell"><div><h2>Empieza a controlar<br>la asistencia de tu equipo hoy.</h2><p>{{ config('app.name') }} es la forma más sencilla y accesible de mantener a tu equipo a tiempo.</p></div><a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div></section>
    </main>
    <div class="video-modal" data-video-modal data-video-url="{{ config('constantes.video_como_funciona_youtube') }}" aria-hidden="true">
        <div class="video-modal-backdrop" data-video-close></div>
        <section class="video-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="video-modal-title" tabindex="-1">
            <header class="video-modal-header">
                <h2 id="video-modal-title">Cómo funciona Horalia</h2>
                <button class="video-modal-close" type="button" data-video-close aria-label="Cerrar video">&times;</button>
            </header>
            <div class="video-modal-frame" data-video-frame></div>
        </section>
    </div>
    <div class="legal-modal" data-legal-modal aria-hidden="true" inert>
        <div class="legal-modal-backdrop" data-legal-close></div>
        <section class="legal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="labor-legal-modal-title" tabindex="-1">
            <header class="legal-modal-header"><h2 id="labor-legal-modal-title">Registro electrónico de la jornada laboral en México</h2><button class="legal-modal-close" type="button" data-legal-close aria-label="Cerrar marco legal">&times;</button></header>
            <p>La normativa laboral mexicana contempla obligaciones relacionadas con el registro de la jornada de trabajo. Horalia facilita el registro de entradas y salidas y la consulta de reportes. La aplicabilidad y los requisitos específicos deben revisarse conforme a las disposiciones oficiales vigentes.</p>
            <a class="button button-primary" href="https://www.dof.gob.mx/" target="_blank" rel="noopener noreferrer">Consultar el Diario Oficial de la Federación <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            <p class="legal-modal-note">Horalia no cuenta con aval o certificación de la STPS y no sustituye la asesoría legal laboral.</p>
        </section>
    </div>
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
            <small>Horalia es un servicio de <a href="https://solventia.com.mx">Solventia Software</a>. © {{ date('Y') }} Todos los derechos reservados.</small>
        </div>
    </footer>
</body>
</html>
