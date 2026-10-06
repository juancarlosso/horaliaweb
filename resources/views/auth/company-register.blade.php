<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Crea tu cuenta de {{ config('app.name') }} y registra tu empresa para comenzar a controlar la asistencia de tu equipo.">
    <title>Crear cuenta | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logos/logoLB.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-pages.css') }}">
    <style>
        .hr-input.is-invalid { border-color: #ef4444; }
        .register-panel {
            min-height: 100vh;
            background-image: linear-gradient(rgba(19, 20, 43, .16), rgba(19, 20, 43, .16)), url("{{ asset('assets/images/fondoreloj.jpg') }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .register-panel > .abs-t-100px-r-100px-sq400px-bg-primary-8,
        .register-panel > .abs-b-50px-l-50px-sq250px-bg-violet-6 { display: none; }
        .register-form-scroll { box-sizing: border-box; justify-content: flex-start; max-height: 100vh; min-height: 100vh; overflow-y: auto; padding-top: clamp(2rem, 5vh, 3rem); padding-bottom: 2rem; }
        .register-terms a { color:var(--color-primary); }
        @media (max-width: 767.98px) { .register-panel { min-height:auto; } .register-form-scroll { max-height:none; overflow:visible; } }
    </style>
    @if(config('services.cloudflare.turnstile_sitekey'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1629742415604987');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1629742415604987&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>
<body class="bg-body-var">
<div class="flex-3">
    <aside class="flex-col-jfc-rel-bg-grad-sidebar-navy-sidebar-navy2-p-12 register-panel">
        <div class="abs-t-100px-r-100px-sq400px-bg-primary-8"></div>
        <div class="abs-b-50px-l-50px-sq250px-bg-violet-6"></div>
        <div class="pos-rel-z1">
            <a class="iflex-mb12-tdn" href="{{ route('inicio') }}" aria-label="{{ config('app.name') }}, inicio">
                <img src="{{ asset('assets/logos/logoLB.png') }}" alt="{{ config('app.name') }}" height="48">
            </a>
            <h2 class="fs2-fw8-c-white-m-0-0-4-lh-13">Tu equipo.<br>A tiempo.</h2>
            <p class="fs1-c-white-60-m-0-0-10-lh-16">Controla la asistencia de tu equipo desde cualquier lugar.</p>
            <div class="flex-col-g35">
                <div class="flex-ac-g35-c-white-80"><div class="avatar-primary-25-32" aria-hidden="true">✓</div><span class="fs9">Entradas y salidas en tiempo real</span></div>
                <div class="flex-ac-g35-c-white-80"><div class="avatar-primary-25-32" aria-hidden="true">✓</div><span class="fs9">Horarios, incidencias y reportes</span></div>
                <div class="flex-ac-g35-c-white-80"><div class="avatar-primary-25-32" aria-hidden="true">✓</div><span class="fs9">Acceso desde computadora o celular</span></div>
            </div>
        </div>
    </aside>
    <main class="flex-col-jfc-w480px-bg-bg-p-12-ov-y-auto-shrink0 register-form-scroll">
        <div class="w100pct-mw380px-m-0-auto">
            <h1 class="fs1625-fw8-c-body-m-0-0-2">Crea tu cuenta</h1>
            <p class="fs9-c-muted-m-0-0-8">Registra tu empresa y comienza a organizar la asistencia de tu equipo.</p>
            @if($errors->any())
                <div class="alert alert-danger" role="alert" aria-live="polite">
                    <p class="fw-semibold mb-1">Revisa la información del formulario:</p>
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('registro.store') }}" class="flex-col-g44">
                @csrf
                <section aria-labelledby="company-heading">
                    <h2 id="company-heading" class="h6 fw-bold mb-3">Datos de la empresa</h2>
                    <div class="mb-3">
                        <label class="form-label-hr" for="razon_social">Nombre de la empresa / razón social <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('razon_social') is-invalid @enderror" id="razon_social" name="razon_social" value="{{ old('razon_social') }}" maxlength="255" autocomplete="organization" required>
                        @error('razon_social')<div class="form-hint error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label-hr" for="rfc">RFC</label>
                        <input class="hr-input @error('rfc') is-invalid @enderror" id="rfc" name="rfc" value="{{ old('rfc') }}" minlength="12" maxlength="13" autocomplete="off" autocapitalize="characters">
                        <p class="form-hint">Si lo dejas vacío, asignaremos un RFC provisional que podrás actualizar después.</p>
                        @error('rfc')<div class="form-hint error">{{ $message }}</div>@enderror
                    </div>
                </section>
                <section aria-labelledby="account-heading">
                    <h2 id="account-heading" class="h6 fw-bold mb-3">Tu cuenta de administrador</h2>
                    <div class="mb-3">
                        <label class="form-label-hr" for="name">Nombre <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
                        @error('name')<div class="form-hint error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label-hr" for="email">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                        @error('email')<div class="form-hint error">{{ $message }}</div>@enderror
                        <p class="form-hint">Usaremos este correo para acceder a {{ config('app.name') }}.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-hr" for="password">Contraseña <span class="text-danger" aria-hidden="true">*</span></label>
                            <input class="hr-input @error('password') is-invalid @enderror" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                            @error('password')<div class="form-hint error">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-hr" for="password_confirmation">Confirmar contraseña <span class="text-danger" aria-hidden="true">*</span></label>
                            <input class="hr-input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>
                </section>
                <div class="flex-afs-g25 register-terms">
                    <input class="mt08-accent-primary" id="terms" name="terms" value="1" type="checkbox" @checked(old('terms')) required>
                    <label class="fs8125-muted" for="terms">Acepto los <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Términos y Condiciones</a> y el <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Aviso de Privacidad</a>. <span class="text-danger" aria-hidden="true">*</span></label>
                </div>
                @error('terms')<div class="form-hint error">{{ $message }}</div>@enderror
                @if(config('services.cloudflare.turnstile_sitekey'))
                    <div>
                        <div class="cf-turnstile" data-sitekey="{{ config('services.cloudflare.turnstile_sitekey') }}" data-language="es"></div>
                        @error('cf-turnstile-response')<div class="form-hint error">{{ $message }}</div>@enderror
                    </div>
                @endif
                <button class="btn-hr btn-primary-hr w100pct-mt1-tdn" type="submit">Crear cuenta</button>
            </form>
            <p class="fs845-c-muted-mt6-tc">¿Ya tienes cuenta? <a class="fw6-c-primary-tdn" href="{{ route('login') }}">Inicia sesión</a></p>
            <p class="text-center text-muted mt-3"><a class="fw6-c-primary-tdn" href="{{ route('inicio') }}">Volver al inicio</a></p>
        </div>
    </main>
</div>
<script defer src="{{ asset('assets/hrnexus/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/hrnexus/js/hrnexus.min.js') }}"></script>
</body>
</html>
