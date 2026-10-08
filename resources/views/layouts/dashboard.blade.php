<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#13142b">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $pageTitle }} | {{ config('app.name') }}</title>
    @include('layouts.favicon')
    <link rel="icon" type="image/png" href="{{ asset('assets/logos/logoLB.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/hrnexus/css/hrnexus-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}">
    @include('layouts.fontawsome')
    <style>
        a.btn-hr,
        a.btn-hr:link,
        a.btn-hr:visited,
        a.btn-hr:hover,
        a.btn-hr:focus,
        a.btn-hr:focus-visible,
        a.btn-hr:active { text-decoration:none !important; }
        .select2-container { width:100% !important; font-family:var(--font); font-size:.845rem; }
        .select2-container--default .select2-selection--single { height:39px; border:1px solid var(--body-border); border-radius:.5625rem; background:var(--card-bg); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { padding-right:2rem; color:var(--body-text); line-height:37px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height:37px; right:.35rem; }
        .select2-dropdown { overflow:hidden; border:1px solid var(--body-border); border-radius:.5rem; background:var(--card-bg); color:var(--body-text); box-shadow:0 10px 28px rgba(27,35,72,.12); }
        .select2-container--default .select2-search--dropdown .select2-search__field { padding:.45rem .6rem; border:1px solid var(--body-border); border-radius:.375rem; background:var(--card-bg); color:var(--body-text); font:inherit; }
        .select2-container--default .select2-results__option { padding:.5rem .7rem; }
        .select2-container--default .select2-results__option[aria-selected=true] { background:var(--body-bg); color:var(--body-text); }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background:var(--color-primary); color:#fff; }
        .select2-container--default.select2-container--focus .select2-selection--single { border-color:var(--color-primary); box-shadow:0 0 0 3px var(--color-primary-glow); }
        .select2-container--default .select2-results__option--highlighted[aria-selected=true] { background:var(--color-primary); color:#fff; }
        select.catalog-input-error + .select2-container .select2-selection { border-color:#ef4444; }
        .swal2-popup { color:var(--body-text); background:var(--card-bg); }
        .swal2-title,.swal2-html-container { color:var(--body-text); }
        .horalia-brand { display:flex; align-items:center; justify-content:center; min-height:68px; }
        .horalia-brand img { display:block; width:166px; height:auto; }
        .sidebar-nav { padding-bottom:1rem; }
        .sidebar-section { margin-top:.5rem; }
        [data-sidebar-mobile-toggle] { display:none; }
        .nav-lnk .nav-icon svg { width:18px; height:18px; display:block; stroke:currentColor; }
        .main-content { min-height:100vh; }
        .topbar-user { display:flex; align-items:center; gap:.65rem; padding:0 .5rem; }
        .topbar-user-copy { display:flex; flex-direction:column; min-width:0; }
        .topbar-user-name { max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.82rem; font-weight:700; color:var(--body-text); }
        .topbar-user-email { max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.7rem; color:var(--body-text-muted); }
        .account-avatar { display:block; width:36px; height:36px; flex:none; border:2px solid rgba(83,109,245,.18); border-radius:50%; object-fit:cover; background:#4f6ef7; }
        .account-menu { width:260px; right:0; left:auto; }
        .account-menu .account-menu-content { padding:1rem; }
        .account-menu .account-menu-header { display:flex; align-items:center; gap:.875rem; min-width:0; margin-bottom:1rem; }
        .account-menu .account-menu-avatar { position:relative; display:grid; place-items:center; width:48px; height:48px; flex:none; overflow:hidden; border-radius:.875rem; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); font-size:1.125rem; font-weight:800; }
        .account-menu .account-menu-avatar img { position:absolute; inset:0; display:block; width:100%; height:100%; object-fit:cover; }
        .account-menu .account-menu-copy { min-width:0; }
        .account-menu .account-menu-name { overflow:hidden; color:var(--body-text); font-size:.9375rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .account-menu .account-menu-email { overflow:hidden; color:var(--body-text-muted); font-size:.775rem; text-overflow:ellipsis; white-space:nowrap; }
        .account-menu .account-menu-link { display:flex; align-items:center; gap:.625rem; padding:.5rem .25rem; color:var(--body-text); font-size:.845rem; font-weight:500; text-decoration:none; transition:background .13s ease,color .13s ease; }
        .account-menu .account-menu-link:hover { color:var(--primary,#4f6ef7); background:var(--body-bg); }
        .account-menu .account-menu-link svg { width:16px; height:16px; flex:none; color:#4f6ef7; stroke:currentColor; stroke-width:2; }
        .account-menu form { margin:0; padding:0; }
        .account-menu .account-menu-logout { display:flex; align-items:center; justify-content:center; gap:.5rem; }
        .account-menu .account-menu-logout svg { width:16px; height:16px; stroke:currentColor; stroke-width:2; }
        .dashboard-welcome { position:relative; overflow:hidden; padding:2rem; border:1px solid rgba(255,255,255,.1); border-radius:1rem; color:#fff; background:linear-gradient(120deg,#151832 0%,#25295a 58%,#343c79 100%); }
        .dashboard-welcome::after { content:""; position:absolute; width:290px; height:290px; top:-175px; right:-42px; border:1px solid rgba(255,255,255,.1); border-radius:50%; box-shadow:0 0 0 34px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.02); pointer-events:none; }
        .dashboard-welcome h1 { position:relative; z-index:1; margin:0 0 .6rem; color:#fff; font-size:clamp(1.45rem,2.4vw,2rem); font-weight:800; letter-spacing:-.03em; }
        .dashboard-welcome p { position:relative; z-index:1; max-width:660px; margin:0; color:rgba(255,255,255,.72); font-size:.98rem; line-height:1.7; }
        .dashboard-start { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin-top:1.25rem; }
        .start-item { display:flex; gap:.9rem; min-height:138px; padding:1.2rem; border:1px solid var(--card-border); border-radius:1rem; background:var(--card-bg); color:inherit; text-decoration:none; transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease; }
        .start-item:hover { transform:translateY(-2px); border-color:rgba(79,110,247,.45); box-shadow:0 12px 28px rgba(27,35,72,.08); }
        .start-item-icon { display:grid; place-items:center; width:42px; height:42px; flex:none; border-radius:.8rem; color:#4f6ef7; background:rgba(79,110,247,.11); }
        .start-item-icon svg { width:20px; height:20px; stroke:currentColor; }
        .start-item h2 { margin:0 0 .4rem; color:var(--body-text); font-size:.98rem; font-weight:750; }
        .start-item p { margin:0; color:var(--body-text-muted); font-size:.8rem; line-height:1.55; }
        .dashboard-note { display:flex; align-items:flex-start; gap:.75rem; margin-top:1.25rem; padding:1rem 1.1rem; border-left:3px solid #536df5; border-radius:.45rem; background:rgba(83,109,245,.07); color:var(--body-text-muted); font-size:.84rem; line-height:1.6; }
        .dashboard-note svg { width:18px; height:18px; flex:none; margin-top:.08rem; stroke:#536df5; }
        .module-placeholder { display:grid; min-height:310px; place-items:center; padding:2rem; border:1px dashed var(--card-border); border-radius:1rem; background:var(--card-bg); text-align:center; }
        .module-placeholder-inner { max-width:440px; }
        .module-placeholder-icon { display:grid; place-items:center; width:62px; height:62px; margin:0 auto 1.1rem; border-radius:1.1rem; color:#536df5; background:rgba(83,109,245,.1); }
        .module-placeholder-icon svg { width:28px; height:28px; stroke:currentColor; }
        .module-placeholder h2 { margin:0 0 .55rem; color:var(--body-text); font-size:1.35rem; font-weight:800; }
        .module-placeholder p { margin:0; color:var(--body-text-muted); font-size:.9rem; line-height:1.65; }
        @media(max-width:900px) { .dashboard-start { grid-template-columns:1fr; } .start-item { min-height:auto; } }
        @media(max-width:767px) { [data-sidebar-toggle] { display:none; } [data-sidebar-mobile-toggle] { display:flex; } }
        @media(max-width:575px) { .dashboard-welcome { padding:1.4rem; } .topbar-user-copy { display:none; } .page-inner { padding-left:1rem; padding-right:1rem; } }
    </style>
</head>
<body>
<div class="mobile-backdrop" aria-hidden="true"></div>
<div id="sb-main-popup" role="menu" aria-hidden="true"></div>
<nav class="sidebar anim-sil" aria-label="Navegación principal">
    <a class="sidebar-brand horalia-brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }}, Home">
        <img src="{{ asset('assets/logos/logoLB.png') }}" alt="{{ config('app.name') }}">
    </a>
    <div class="sidebar-nav" id="sidebarNav">
        @php($isRegularProfile = (int) auth()->user()->profile === 3)
        @php($isCompanyAdmin = (int) auth()->user()->profile === 2)
        @php($canManageChecador = (int) auth()->user()->profile === 1 || auth()->user()->empresas()->wherePivot('control_total', true)->exists())
        <div class="sidebar-section">Principal</div>
        <div class="nav-li">
            <a href="{{ route('home') }}" class="nav-lnk {{ $activeSection === 'home' ? 'active' : '' }}" @if($activeSection === 'home') aria-current="page" @endif data-sidebar-tooltip="Home">
                <span class="nav-icon">@include('dashboard.partials.icon', ['name' => 'home'])</span><span class="nav-text">Home</span>
            </a>
        </div>
        @foreach(['Organización', 'Operación', 'REPORTES'] as $group)
            @if(!$isRegularProfile || $group === 'Operación')
                <div class="sidebar-section">{{ $group }}</div>
                @foreach($navigation as $slug => $item)
                    @if($item['group'] === $group && (!$isRegularProfile || $slug === 'asistencia') && ($slug !== 'reloj-checador' || $canManageChecador))
                        <div class="nav-li">
                            <a href="{{ $slug === 'empresas' ? route('empresas.index') : ($slug === 'reloj-checador' ? route('checador.index') : ($slug === 'reportes-general' ? route('reportes.general') : ($slug === 'reportes-departamento' ? route('reportes.departamento') : route('dashboard.module', ['section' => $slug])))) }}" class="nav-lnk {{ $activeSection === $slug ? 'active' : '' }}" @if($activeSection === $slug) aria-current="page" @endif data-sidebar-tooltip="{{ $item['label'] }}">
                                <span class="nav-icon">@include('dashboard.partials.icon', ['name' => $item['icon']])</span><span class="nav-text">{{ $item['label'] }}</span>
                            </a>
                        </div>
                    @endif
                @endforeach
            @endif
        @endforeach
        <div class="sidebar-section">Mis Datos</div>
        <div class="nav-li">
            <a href="{{ route('profile.edit') }}" class="nav-lnk {{ request()->routeIs('profile.edit', 'profile.update') ? 'active' : '' }}" @if(request()->routeIs('profile.edit', 'profile.update')) aria-current="page" @endif data-sidebar-tooltip="Mi Perfil">
                <span class="nav-icon">@include('dashboard.partials.icon', ['name' => 'user'])</span><span class="nav-text">Mi Perfil</span>
            </a>
        </div>
        <div class="nav-li">
            <a href="{{ route('profile.password.edit') }}" class="nav-lnk {{ request()->routeIs('profile.password.*') ? 'active' : '' }}" @if(request()->routeIs('profile.password.*')) aria-current="page" @endif data-sidebar-tooltip="Cambiar Contraseña">
                <span class="nav-icon">@include('dashboard.partials.icon', ['name' => 'lock'])</span><span class="nav-text">Cambiar Contraseña</span>
            </a>
        </div>
        @if($isCompanyAdmin)
            <div class="nav-li">
                <a href="{{ route('payment-methods.index') }}" class="nav-lnk {{ request()->routeIs('payment-methods.*') ? 'active' : '' }}" @if(request()->routeIs('payment-methods.*')) aria-current="page" @endif data-sidebar-tooltip="Métodos de Pago">
                    <span class="nav-icon">@include('dashboard.partials.icon', ['name' => 'card'])</span><span class="nav-text">Métodos de Pago</span>
                </a>
            </div>
            <div class="nav-li">
                <a href="{{ route('payment-history.index') }}" class="nav-lnk {{ request()->routeIs('payment-history.*') ? 'active' : '' }}" @if(request()->routeIs('payment-history.*')) aria-current="page" @endif data-sidebar-tooltip="Historial de pagos">
                    <span class="nav-icon"><i class="fa-light fa-clock-rotate-left" aria-hidden="true"></i></span><span class="nav-text">Historial de pagos</span>
                </a>
            </div>
        @endif
        <div class="sidebar-section">SESIÓN</div>
        <div class="nav-li">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-lnk w100pct-tdn" data-sidebar-tooltip="Finalizar Sesión">
                    <span class="nav-icon">@include('dashboard.partials.icon', ['name' => 'logout'])</span><span class="nav-text">Finalizar Sesión</span>
                </button>
            </form>
        </div>
    </div>
</nav>
<div class="main-content" id="mainContent">
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <button type="button" class="btn-icon-top" data-sidebar-toggle aria-label="Contraer o expandir el menú"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <button type="button" class="btn-icon-top" data-sidebar-mobile-toggle aria-label="Abrir menú"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <nav class="breadcrumb-bar" aria-label="Ruta de navegación"><span>{{ config('app.name') }}</span><span class="bc-sep">/</span><span class="bc-current">{{ $pageTitle }}</span></nav>
        </div>
        <div class="topbar-right pos-rel">
            <button type="button" class="btn-icon-top" data-theme-toggle aria-label="Cambiar entre tema claro y oscuro" title="Cambiar tema">
                <svg class="theme-icon-light" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                <svg class="theme-icon-dark" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.9 13A8.5 8.5 0 0 1 11 3.1 8.5 8.5 0 1 0 20.9 13Z"/></svg>
            </button>
            <button type="button" class="topbar-user btn-hr btn-ghost-hr" data-panel-toggle="account-panel" aria-label="Cuenta de {{ auth()->user()->name }}" aria-expanded="false">
                <div class="topbar-user-copy"><span class="topbar-user-name">{{ auth()->user()->name }}</span><span class="topbar-user-email">{{ auth()->user()->email }}</span></div>
                <img class="account-avatar" src="{{ auth()->user()->avatarUrl() }}" alt="Avatar de {{ auth()->user()->name }}" width="36" height="36" decoding="async">
            </button>
            <div class="hr-panel panel-w260 account-menu" id="account-panel">
                <div class="account-menu-content">
                    <div class="account-menu-header">
                        <div class="account-menu-avatar">
                            <span aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(auth()->user()->name), 0, 1)) }}</span>
                            @if(auth()->user()->personal?->foto)
                                <img src="{{ auth()->user()->avatarUrl() }}" alt="Foto de {{ auth()->user()->name }}" width="48" height="48" decoding="async" onerror="this.remove()">
                            @endif
                        </div>
                        <div class="account-menu-copy">
                            <div class="account-menu-name">{{ auth()->user()->name }}</div>
                            <div class="account-menu-email">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <div class="divider-hr m-2-0"></div>
                    <a class="account-menu-link" href="{{ route('profile.edit') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.4-3.5 2.6-5.3 6.5-5.3s6.1 1.8 6.5 5.3"/></svg><span>Mi Perfil</span></a>
                    <a class="account-menu-link" href="{{ route('profile.password.edit') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/></svg><span>Cambiar mi contraseña</span></a>
                    @if($isCompanyAdmin)
                        <a class="account-menu-link" href="{{ route('payment-methods.index') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18m-14 5h4"/></svg><span>Métodos de Pago</span></a>
                    @endif
                    <div class="divider-hr m-2-0"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-hr btn-danger-hr w100pct-mt1 account-menu-logout" type="submit"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg><span>Finalizar Sesión</span></button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    <main class="page-content">
        <div class="page-inner">
            @include('layouts.alertas')
            @yield('content')
        </div>
    </main>
</div>
<script defer src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
<script defer src="{{ asset('assets/vendor/select2/js/select2.min.js') }}"></script>
<script defer src="{{ asset('assets/hrnexus/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/hrnexus/js/hrnexus.min.js') }}"></script>
<script defer src="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery('select.select2').each(function () {
                const $select = window.jQuery(this);
                if (!$select.hasClass('select2-hidden-accessible')) {
                    $select.select2({ width: '100%', minimumResultsForSearch: 8 });
                }
            });
        }

        const validationErrors = @json($errors->all());

        if (!window.Swal) return;

        if (validationErrors.length) {
            Swal.fire({
                icon: 'error',
                title: 'Revisa la información',
                text: validationErrors.join('\n'),
                confirmButtonText: 'Entendido',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn-hr btn-primary-hr' }
            });
        }
    });
</script>
</body>
</html>
