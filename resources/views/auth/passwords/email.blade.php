<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar contraseña | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logos/logoLB.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-pages.css') }}">
    <style>
        .auth-brand-panel { min-height: 100vh; background-image: linear-gradient(rgba(19,20,43,.16),rgba(19,20,43,.16)),url("{{ asset('assets/images/fondoreloj.jpg') }}"); background-position:center; background-repeat:no-repeat; background-size:cover; }
        .auth-brand-panel > .abs-t-100px-r-100px-sq400px-bg-primary-8, .auth-brand-panel > .abs-b-50px-l-50px-sq250px-bg-violet-6 { display:none; }
        .auth-form-scroll { box-sizing:border-box; justify-content:flex-start; max-height:100vh; min-height:100vh; overflow-y:auto; padding-top:clamp(2rem,5vh,3rem); padding-bottom:2rem; }
        @media (max-width:767.98px) { .auth-brand-panel { min-height:auto; } .auth-form-scroll { max-height:none; min-height:100vh; overflow:visible; } }
    </style>
</head>
<body class="bg-body-var">
<div class="flex-3">
    @include('auth.partials.brand-panel')
    <main class="flex-col-jfc-w480px-bg-bg-p-12-ov-y-auto-shrink0 auth-form-scroll">
        <div class="w100pct-mw380px-m-0-auto">
            <h1 class="fs1625-fw8-c-body-m-0-0-2">Recupera tu contraseña</h1>
            <p class="fs9-c-muted-m-0-0-8">Escribe el correo de tu cuenta y te enviaremos un enlace para restablecerla.</p>
            @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert" aria-live="polite">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('password.email') }}" class="flex-col-g44">
                @csrf
                <div>
                    <label class="form-label-hr" for="email">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input" type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <button class="btn-hr btn-primary-hr w100pct-tdn" type="submit">Enviar enlace</button>
            </form>
            <p class="fs845-c-muted-mt6-tc"><a class="fw6-c-primary-tdn" href="{{ route('login') }}">Volver a iniciar sesión</a></p>
        </div>
    </main>
</div>
<script defer src="{{ asset('assets/hrnexus/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/hrnexus/js/hrnexus.min.js') }}"></script>
</body>
</html>
