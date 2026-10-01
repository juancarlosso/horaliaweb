@extends('layouts.dashboard', ['pageTitle' => 'Mi Perfil', 'activeSection' => null])

@section('content')
<style>
    .page-inner { max-width:none; }
    .profile-page { width:100%; }
    .profile-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin:0 0 1.25rem; }
    .profile-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .profile-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .profile-photo-row { display:flex; align-items:center; gap:1.125rem; padding-bottom:1.25rem; margin-bottom:1.25rem; border-bottom:1px solid var(--body-border); }
    .profile-photo { position:relative; display:grid; place-items:center; width:80px; height:80px; flex:none; overflow:hidden; border-radius:1rem; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); font-size:1.75rem; font-weight:800; }
    .profile-photo img { position:absolute; inset:0; display:block; width:100%; height:100%; object-fit:cover; }
    .profile-photo-actions { min-width:0; }
    .profile-photo-actions p { margin:.35rem 0 0; color:var(--body-text-muted); font-size:.775rem; }
    .profile-file-input { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    .profile-file-input:focus-visible + .profile-file-label { outline:0; box-shadow:0 0 0 3px rgba(79,110,247,.22); }
    .profile-file-name { display:block; max-width:300px; overflow:hidden; color:var(--body-text-muted); font-size:.775rem; text-overflow:ellipsis; white-space:nowrap; }
    .profile-field { margin-bottom:1rem; }
    .profile-feedback { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid transparent; border-radius:.625rem; font-size:.845rem; line-height:1.5; }
    .profile-feedback-success { color:#087f5b; background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.22); }
    .profile-feedback-error { color:#b42318; background:rgba(239,68,68,.07); border-color:rgba(239,68,68,.2); }
    .profile-input-error { border-color:#ef4444; }
    .profile-error-text { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    @media(max-width:575px) { .profile-heading { align-items:stretch; flex-direction:column; } .profile-photo-row { align-items:flex-start; } .profile-photo { width:64px; height:64px; border-radius:.875rem; font-size:1.4rem; } }
</style>

<section class="profile-page">
    <header class="profile-heading">
        <div><h1>Mi Perfil</h1><p>Actualiza tu nombre y la foto que aparece en tu cuenta.</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('home') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    <div class="card-hr mb-1rem">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Información del perfil</h2>
                <p class="card-subtitle-hr">Estos datos identifican tu cuenta en Horalia.</p>
            </div>
        </div>
        <div class="card-bd">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="profile-photo-row">
                    <div class="profile-photo" aria-hidden="true">
                        <span>{{ mb_strtoupper(mb_substr(trim($user->name), 0, 1)) }}</span>
                        @if($user->personal?->foto)
                            <img src="{{ $avatarUrl }}" alt="" width="80" height="80" onerror="this.remove()">
                        @endif
                    </div>
                    <div class="profile-photo-actions">
                        <input class="profile-file-input" id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="foto-ayuda foto-seleccionada">
                        <label class="btn-hr btn-outline-hr btn-sm-hr profile-file-label" for="foto">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
                            Cambiar foto
                        </label>
                        <p id="foto-ayuda">JPG, PNG o WebP. Tamaño máximo: 5 MB.</p>
                        <span class="profile-file-name" id="foto-seleccionada" aria-live="polite"></span>
                        @error('foto') <span class="profile-error-text">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="profile-field">
                    <label class="form-label-hr" for="name">Nombre <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="hr-input @error('name') profile-input-error @enderror" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" required autocomplete="name">
                    @error('name') <span class="profile-error-text">{{ $message }}</span> @enderror
                </div>

                <div class="flex-g2-wrap mt-1rem">
                    <button class="btn-hr btn-primary-hr" type="submit">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h9"/></svg>
                        Guardar cambios
                    </button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('home') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    document.getElementById('foto')?.addEventListener('change', function () {
        document.getElementById('foto-seleccionada').textContent = this.files?.[0]?.name ?? '';
    });
</script>
@endsection
