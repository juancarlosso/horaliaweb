@extends('layouts.dashboard', ['pageTitle' => 'Cambiar mi contraseña', 'activeSection' => null])

@section('content')
<style>
    .page-inner { max-width:none; }
    .profile-page { width:100%; }
    .profile-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin:0 0 1.25rem; }
    .profile-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .profile-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .profile-field { margin-bottom:1rem; }
    .profile-feedback { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid transparent; border-radius:.625rem; font-size:.845rem; line-height:1.5; }
    .profile-feedback-success { color:#087f5b; background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.22); }
    .profile-feedback-error { color:#b42318; background:rgba(239,68,68,.07); border-color:rgba(239,68,68,.2); }
    .profile-input-error { border-color:#ef4444; }
    .profile-error-text { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    @media(max-width:575px) { .profile-heading { align-items:stretch; flex-direction:column; } }
</style>

<section class="profile-page">
    <header class="profile-heading">
        <div><h1>Cambiar mi contraseña</h1><p>Actualiza tu contraseña para mantener segura tu cuenta.</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('home') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    <div class="card-hr mb-1rem">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Seguridad de la cuenta</h2>
                <p class="card-subtitle-hr">Elige una contraseña nueva de al menos 8 caracteres.</p>
            </div>
        </div>
        <div class="card-bd">
            <form method="POST" action="{{ route('profile.password.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 profile-field">
                    <label class="form-label-hr" for="actual">Contraseña actual <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input @error('actual') profile-input-error @enderror" id="actual" name="actual" type="password" required autocomplete="current-password">
                    @error('actual') <span class="profile-error-text">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6 profile-field">
                    <label class="form-label-hr" for="nuevo">Nueva contraseña <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input @error('nuevo') profile-input-error @enderror" id="nuevo" name="nuevo" type="password" required minlength="8" autocomplete="new-password">
                    @error('nuevo') <span class="profile-error-text">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6 profile-field">
                    <label class="form-label-hr" for="confirmar">Confirmar nueva contraseña <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input @error('confirmar') profile-input-error @enderror" id="confirmar" name="confirmar" type="password" required minlength="8" autocomplete="new-password">
                    @error('confirmar') <span class="profile-error-text">{{ $message }}</span> @enderror
                </div>
                <div class="col-12 flex-g2-wrap">
                    <button class="btn-hr btn-primary-hr" type="submit">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
                        Actualizar contraseña
                    </button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('home') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
