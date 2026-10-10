<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar sesión | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logos/logoLB.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-pages.css') }}">
    <style>
        .login-panel {
            min-height: 100vh;
            background-image: linear-gradient(rgba(19, 20, 43, .16), rgba(19, 20, 43, .16)), url("{{ asset('assets/images/fondoreloj.jpg') }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .login-panel > .abs-t-100px-r-100px-sq400px-bg-primary-8,
        .login-panel > .abs-b-50px-l-50px-sq250px-bg-violet-6 { display: none; }
        .login-mobile-brand { display: none; }
        .login-form-scroll { box-sizing: border-box; justify-content: flex-start; max-height: 100vh; min-height: 100vh; overflow-y: auto; padding-top: clamp(2rem, 5vh, 3rem); padding-bottom: 2rem; }
        @media (max-width: 767.98px) {
            .login-panel { display: none; }
            .login-mobile-brand { display: inline-flex; margin-bottom: 1.5rem; }
            .login-form-scroll { box-sizing: border-box; flex: 1 1 100%; width: 100%; min-width: 0; max-height: none; min-height: 100vh; overflow: visible; padding: clamp(1.5rem, 6vw, 3rem); }
        }
    </style>
</head>
<body class="bg-body-var">
<div class="flex-3">
    <aside class="flex-col-jfc-rel-bg-grad-sidebar-navy-sidebar-navy2-p-12 login-panel">
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
    <main class="flex-col-jfc-w480px-bg-bg-p-12-ov-y-auto-shrink0 login-form-scroll">
        <div class="w100pct-mw380px-m-0-auto">
            <a class="login-mobile-brand" href="{{ route('inicio') }}" aria-label="{{ config('app.name') }}, inicio">
                <img src="{{ asset('assets/logos/logoLN.png') }}" alt="{{ config('app.name') }}" height="48">
            </a>
            <h1 class="fs1625-fw8-c-body-m-0-0-2">Bienvenido a {{ config('app.name') }}</h1>
            <p class="fs9-c-muted-m-0-0-8">Inicia sesión para continuar con tu equipo.</p>
            @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert" aria-live="polite">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}" class="flex-col-g44">
                @csrf
                <div>
                    <label class="form-label-hr" for="email">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input" type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <div>
                    <label class="form-label-hr" for="password">Contraseña <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input" type="password" name="password" id="password" autocomplete="current-password" required>
                    <div class="text-end mt-2"><a class="fw6-c-primary-tdn" href="{{ route('password.request') }}">Olvidé mi contraseña</a></div>
                </div>
                <div class="flex-ac-g25">
                    <input class="accent-primary" type="checkbox" name="remember" id="remember">
                    <label class="fs845-c-muted" for="remember">Mantener mi sesión</label>
                </div>
                <button class="btn-hr btn-primary-hr w100pct-tdn" type="submit">Iniciar sesión</button>
            </form>
            <p class="fs845-c-muted-mt6-tc">¿Aún no tienes cuenta? <a class="fw6-c-primary-tdn" href="{{ route('registro.create') }}">Regístrate</a></p>
            <p class="text-center text-muted mt-3"><a class="fw6-c-primary-tdn" href="{{ route('inicio') }}">Volver al inicio</a></p>
        </div>
    </main>
</div>
<script defer src="{{ asset('assets/hrnexus/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/hrnexus/js/hrnexus.min.js') }}"></script>
</body>
</html>
