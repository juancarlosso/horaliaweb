<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Convierte tus tablets en relojes checadores con Horalia. Registra asistencia con PIN y fotografía, sin licencias adicionales por dispositivo. Primer mes gratis.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://horalia.mx/reloj-checador-tablet">
    <title>Reloj checador para tablet con PIN | Horalia</title>
    @include('layouts.favicon')
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Horalia">
    <meta property="og:locale" content="es_MX">
    <meta property="og:title" content="Reloj checador para tablet con PIN | Horalia">
    <meta property="og:description" content="Convierte tus tablets en relojes checadores con Horalia. Registra asistencia con PIN y fotografía, sin licencias adicionales por dispositivo. Primer mes gratis.">
    <meta property="og:url" content="https://horalia.mx/reloj-checador-tablet">
    <meta property="og:image" content="https://horalia.mx/assets/images/checador-web-tablet.png">
    <meta property="og:image:secure_url" content="https://horalia.mx/assets/images/checador-web-tablet.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="580">
    <meta property="og:image:height" content="768">
    <meta property="og:image:alt" content="Pantalla real del checador web de Horalia con empresa de demostración y teclado para PIN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Reloj checador para tablet con PIN | Horalia">
    <meta name="twitter:description" content="Convierte tus tablets en relojes checadores con Horalia. Registra asistencia con PIN y fotografía, sin licencias adicionales por dispositivo. Primer mes gratis.">
    <meta name="twitter:image" content="https://horalia.mx/assets/images/checador-web-tablet.png">
    <meta name="twitter:image:alt" content="Pantalla real del checador web de Horalia con empresa de demostración y teclado para PIN">
    @include('layouts.partials.meta-pixel')
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Reloj checador para tablet con PIN | Horalia',
            'url' => 'https://horalia.mx/reloj-checador-tablet',
            'description' => 'Convierte tus tablets en relojes checadores con Horalia. Registra asistencia con PIN y fotografía, sin licencias adicionales por dispositivo. Primer mes gratis.',
            'inLanguage' => 'es-MX',
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'Horalia', 'url' => 'https://horalia.mx/'],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/reloj-checador-tablet.css') }}">
    <script src="{{ asset('assets/js/landing.js') }}" defer></script>
    @include('layouts.fontawsome')
</head>
<body class="landing-page tablet-page">
    <header class="site-header">
        <nav class="nav-wrap" aria-label="Navegación principal">
            <a class="brand" href="{{ route('inicio') }}" aria-label="{{ config('app.name') }}, inicio"><img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}, tu equipo a tiempo"></a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Abrir menú"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
            <div class="nav-content" id="main-nav">
                <div class="nav-links"><a href="{{ route('inicio') }}">Inicio</a><a href="#como-funciona">Cómo funciona</a><a href="#requisitos">Requisitos</a><a href="#precios">Precios</a><a href="#preguntas">Preguntas</a></div>
                <div class="nav-actions"><a class="button button-outline" href="{{ route('login') }}">Iniciar sesión</a><a class="button button-primary" href="{{ route('registro.create') }}">Crear mi cuenta</a></div>
            </div>
        </nav>
    </header>

    <main>
        <section class="tablet-hero" aria-labelledby="tablet-title">
            <div class="tablet-hero-inner section-shell">
                <div class="tablet-hero-copy">
                    <span class="eyebrow"><i class="fa-solid fa-tablet-screen-button" aria-hidden="true"></i> Checador web para tu empresa</span>
                    <h1 id="tablet-title">Convierte una tablet en tu <span>reloj checador</span></h1>
                    <p class="tablet-lede">Registra entradas, salidas y pausas de tus empleados desde una tablet compatible. Cada persona se identifica con su PIN y, en entradas y salidas, el checador web captura fotografía y ubicación.</p>
                    <p class="tablet-device-benefit">Utiliza las tablets que necesites, sin pagar licencias adicionales por dispositivo.</p>
                    <a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <p class="hero-trial-note"><i class="fa-solid fa-gift" aria-hidden="true"></i><span>¡Tu primer mes es gratis!</span></p>
                    <p class="tablet-unlimited"><i class="fa-solid fa-check" aria-hidden="true"></i><span>Tablets ilimitadas · Sin costo adicional por dispositivo</span></p>
                </div>
                <figure class="tablet-product">
                    <div class="tablet-product-frame"><img src="{{ asset('assets/images/checador-web-tablet.png') }}" alt="Checador web de Horalia con teclado para PIN y acceso a pausas, con empresa de demostración" width="580" height="768" fetchpriority="high"></div>
                    <figcaption>Captura real del checador web.<br>Empresa de demostración.</figcaption>
                </figure>
            </div>
        </section>

        <section class="tablet-alternative section-shell" aria-labelledby="alternative-title">
            <div><span class="eyebrow">Tus dispositivos, un punto de registro</span><h2 id="alternative-title">Un reloj checador en las tablets de tu empresa</h2></div>
            <div><p>Utiliza tus propias tablets compatibles como puntos de registro de asistencia. Con Horalia, cada empleado marca con su PIN y la información queda disponible para su consulta desde la plataforma. Puedes habilitar varios dispositivos sin pagar una licencia adicional por cada tablet.</p><p class="tablet-note">Las tablets no están incluidas; se requiere un dispositivo compatible y conexión a internet.</p><p class="tablet-text-link"><a href="{{ route('inicio') }}">Conoce Horalia, nuestro reloj checador en línea <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p></div>
        </section>

        <section class="tablet-process" id="como-funciona" aria-labelledby="process-title">
            <div class="section-shell">
                <div class="tablet-section-heading"><span class="eyebrow">De la activación a la consulta</span><h2 id="process-title">Así funciona el checador desde una tablet</h2></div>
                <ol class="tablet-process-list">
                    <li><span class="tablet-step-number" aria-hidden="true">01</span><h3>Activa el checador</h3><p>Desde Horalia, un administrador genera un enlace temporal para la empresa y lo abre en la tablet dentro del plazo de activación.</p></li>
                    <li><span class="tablet-step-number" aria-hidden="true">02</span><h3>Ingresa el PIN</h3><p>Cada empleado utiliza su PIN personal de cinco dígitos para identificarse.</p></li>
                    <li><span class="tablet-step-number" aria-hidden="true">03</span><h3>Registra la asistencia</h3><p>En entradas y salidas, el checador web solicita ubicación y fotografía; muestra una confirmación cuando el servidor acepta el registro.</p></li>
                    <li><span class="tablet-step-number" aria-hidden="true">04</span><h3>Consulta los registros</h3><p>La empresa revisa la asistencia y los reportes desde el área de administración de Horalia.</p></li>
                </ol>
                <p class="tablet-text-link"><a href="{{ route('control-asistencia.empleados') }}">Conoce cómo organizar y consultar la asistencia de tu personal <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
            </div>
        </section>

        <section class="tablet-marks section-shell" aria-labelledby="marks-title">
            <div class="tablet-marks-copy"><span class="eyebrow">Un dispositivo compartido</span><h2 id="marks-title">Entradas, salidas y pausas desde el mismo dispositivo</h2><p>El personal registra el inicio y fin de su jornada con su PIN. También puede iniciar y finalizar pausas de comida, sanitario, descanso o permiso desde el checador, de acuerdo con las reglas de Horalia.</p><p class="tablet-note">La fotografía se solicita para entradas y salidas en el checador web, no para las pausas. Para iniciar una pausa debe existir una entrada abierta; solo puede haber una pausa activa.</p></div>
            <div class="tablet-marks-panel">
                <div class="tablet-mark-row"><span class="tablet-mark-icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span><div><h3>Entradas y salidas</h3><p>PIN · Fotografía · Ubicación</p></div></div>
                <div class="tablet-mark-row"><span class="tablet-mark-icon"><i class="fa-solid fa-mug-hot" aria-hidden="true"></i></span><div><h3>Pausas durante la jornada</h3><p>Inicio y fin con PIN</p></div></div>
                <ul class="tablet-pause-types" aria-label="Tipos de pausa"><li>Comida</li><li>Sanitario</li><li>Descanso</li><li>Permiso</li></ul>
            </div>
        </section>

        <section class="tablet-requirements" id="requisitos" aria-labelledby="requirements-title">
            <div class="section-shell">
                <div class="tablet-section-heading"><span class="eyebrow">Antes de comenzar</span><h2 id="requirements-title">¿Qué necesita tu tablet para funcionar?</h2></div>
                <ul class="tablet-requirement-list">
                    <li><i class="fa-solid fa-tablet-screen-button" aria-hidden="true"></i><span>Tablet con navegador compatible y cámara funcional.</span></li>
                    <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>Acceso a ubicación y permisos de cámara y ubicación habilitados.</span></li>
                    <li><i class="fa-solid fa-wifi" aria-hidden="true"></i><span>Conexión a internet durante las marcaciones y acceso seguro HTTPS.</span></li>
                    <li><i class="fa-solid fa-users" aria-hidden="true"></i><span>Empresa y empleados activos, PIN asignado y ubicación del centro de trabajo configurada.</span></li>
                    <li><i class="fa-solid fa-display" aria-hidden="true"></i><span>Configurar el bloqueo de pantalla del dispositivo según las necesidades de operación.</span></li>
                </ul>
                <p class="tablet-note">El checador web requiere conexión a internet. Comprueba el funcionamiento con la tablet y el navegador que usarás en tu empresa; la compatibilidad depende del dispositivo y de los permisos disponibles.</p>
            </div>
        </section>

        <section class="tablet-pricing section-shell" id="precios" aria-labelledby="pricing-title">
            <div class="tablet-pricing-copy"><span class="eyebrow">Crece en dispositivos</span><h2 id="pricing-title">Tablets ilimitadas, una suscripción sencilla</h2><p>Tablets ilimitadas, sin licencias adicionales por dispositivo.</p><p class="tablet-note">Los dispositivos los proporciona el cliente.</p></div>
            <div class="tablet-price-card">
                <p class="tablet-price">{{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} <span>MXN / mes</span></p>
                <p class="tablet-price-detail">Por empresa · IVA incluido · Hasta 200 empleados</p>
                <ul class="check-list"><li>Tablets ilimitadas, sin licencias adicionales por dispositivo.</li><li>Primer mes gratis.</li><li>Cada bloque adicional de hasta 200 empleados cuesta {{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales, IVA incluido.</li></ul>
                <a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>

        <section class="faq-section section-shell tablet-faq" id="preguntas" aria-labelledby="faq-title">
            <h2 class="faq-title" id="faq-title">Preguntas frecuentes sobre el checador en tablet</h2>
            <div class="faq-list">
                <details><summary>¿Cuántas tablets puedo utilizar?</summary><p>Puedes utilizar las tablets que necesites dentro de tu empresa sin pagar licencias adicionales por dispositivo. Deben ser compatibles y contar con los permisos y la conexión requeridos.</p></details>
                <details><summary>¿Cada empleado necesita una cuenta en la tablet?</summary><p>No. En el checador web, el administrador activa el punto de registro y cada empleado se identifica con su PIN de cinco dígitos.</p></details>
                <details><summary>¿Se necesita cámara e internet?</summary><p>Sí. Para entradas y salidas mediante el checador web se requieren cámara, ubicación y conexión a internet, además de los permisos correspondientes.</p></details>
                <details><summary>¿Puedo registrar pausas desde la tablet?</summary><p>Sí. El empleado puede iniciar y finalizar las pausas disponibles con su PIN, siempre que tenga una jornada iniciada y cumpla las reglas del sistema.</p></details>
                <details><summary>¿Cuánto cuesta Horalia?</summary><p>Desde {{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales por empresa, IVA incluido, hasta 200 empleados. Cada bloque adicional de hasta 200 empleados cuesta {{ '$' . number_format(config('constantes.precio_mensual_por_empresa_mxn')) }} MXN mensuales, IVA incluido. El primer mes es gratis y no hay cargos adicionales por el número de tablets.</p></details>
            </div>
        </section>

        <section class="tablet-final" aria-labelledby="final-title"><div class="section-shell"><span class="eyebrow">Comienza con tus dispositivos</span><h2 id="final-title">Convierte tus tablets en puntos de registro de asistencia</h2><p>Crea tu cuenta, configura a tus empleados y comienza a registrar asistencia con Horalia. Utiliza las tablets compatibles que necesites sin pagar por dispositivo.</p><a class="button button-primary button-large" href="{{ route('registro.create') }}">Crear mi cuenta <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><p class="hero-trial-note"><i class="fa-solid fa-gift" aria-hidden="true"></i><span>¡Tu primer mes es gratis!</span></p></div></section>
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
