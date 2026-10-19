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
    .profile-readonly-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.9rem 1.5rem; }
    .profile-readonly-item { min-width:0; }
    .profile-readonly-label { display:block; margin-bottom:.2rem; color:var(--body-text-muted); font-size:.75rem; font-weight:650; }
    .profile-readonly-value { display:block; color:var(--body-text); font-size:.9rem; overflow-wrap:anywhere; }
    .profile-employee-photo { position:relative; display:grid; place-items:center; width:76px; height:76px; flex:none; overflow:hidden; border-radius:1rem; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); font-size:1.6rem; font-weight:800; }
    .profile-employee-photo img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .profile-employee-heading { display:flex; align-items:center; gap:1rem; padding-bottom:1.1rem; margin-bottom:1.1rem; border-bottom:1px solid var(--body-border); }
    .profile-readonly-section-title { margin:1.35rem 0 .8rem; color:var(--body-text); font-size:.95rem; font-weight:750; }
    .profile-workday-list { display:grid; gap:.55rem; }
    .profile-workday-row { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:.75rem .9rem; border:1px solid var(--body-border); border-radius:.65rem; }
    .profile-workday-name { color:var(--body-text); font-weight:650; }
    .profile-workday-detail { color:var(--body-text-muted); font-size:.84rem; text-align:right; }
    @media(max-width:575px) { .profile-heading { align-items:stretch; flex-direction:column; } .profile-photo-row { align-items:flex-start; } .profile-photo { width:64px; height:64px; border-radius:.875rem; font-size:1.4rem; } }
    @media(max-width:575px) { .profile-readonly-grid { grid-template-columns:1fr; } .profile-workday-row { align-items:flex-start; flex-direction:column; gap:.15rem; } .profile-workday-detail { text-align:left; } }
</style>

<section class="profile-page">
    <header class="profile-heading">
        <div><h1>Mi Perfil</h1><p>{{ $isEmployeeProfile ? 'Consulta los datos de tu ficha de personal y tu horario laboral.' : 'Actualiza tu nombre y la foto que aparece en tu cuenta.' }}</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('home') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    @if($isEmployeeProfile)
        @if($personal)
            @php
                $workDayNumbers = array_map('intval', array_filter(explode('@', (string) $personal->laborados), fn ($day) => $day !== ''));
                $personalSchedules = $personal->horarios->keyBy('dia');
                $personalPhotoUrl = $personal->fotoUrl();
                $sexLabels = ['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro'];
                $accessProfile = collect(config('constantes.perfilesPersonal', []))->first(fn ($profile) => (int) $profile['id'] === (int) $user->profile);
                $timezoneName = $personal->centro?->timezone;
            @endphp
            <div class="card-hr mb-1rem">
                <div class="card-hd"><div><h2 class="card-title-hr">Datos personales y laborales</h2><p class="card-subtitle-hr">Información de solo lectura administrada por tu empresa.</p></div></div>
                <div class="card-bd">
                    <div class="profile-employee-heading">
                        <div class="profile-employee-photo" aria-hidden="true">
                            <span>{{ mb_strtoupper(mb_substr(trim((string) $personal->nombre), 0, 1)) ?: 'P' }}</span>
                            @if($personalPhotoUrl)<img src="{{ $personalPhotoUrl }}" alt="" width="76" height="76" onerror="this.remove()">@endif
                        </div>
                        <div>
                            <h3 class="card-title-hr mb-1">{{ $personal->nombre ?: 'Sin nombre registrado' }}</h3>
                            <span class="badge {{ $personal->activo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $personal->activo ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                    </div>

                    <div class="profile-readonly-grid">
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Código</span><span class="profile-readonly-value">{{ $personal->codigo ?: 'No capturado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Perfil de acceso</span><span class="profile-readonly-value">{{ $accessProfile['nombre'] ?? 'No asignado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Nombre completo</span><span class="profile-readonly-value">{{ $personal->nombre ?: 'No capturado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Sexo</span><span class="profile-readonly-value">{{ $sexLabels[$personal->sexo] ?? 'No capturado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Correo de personal</span><span class="profile-readonly-value">{{ $personal->email ?: 'No capturado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Teléfono</span><span class="profile-readonly-value">{{ $personal->telefono ?: 'No capturado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">PIN del checador</span><span class="profile-readonly-value">{{ $personal->pin ? 'Configurado (protegido)' : 'No configurado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Empresa</span><span class="profile-readonly-value">{{ $personal->empresa?->nombre ?: 'No asignada' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Centro de trabajo</span><span class="profile-readonly-value">{{ $personal->centro?->nombre ?: 'No asignado' }}</span></div>
                        @if($timezoneName)
                            <div class="profile-readonly-item"><span class="profile-readonly-label">Zona horaria del centro</span><span class="profile-readonly-value">{{ $timezoneLabels[$timezoneName] ?? $timezoneName }}</span></div>
                        @endif
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Departamento</span><span class="profile-readonly-value">{{ $personal->departamento?->nombre ?: 'No asignado' }}</span></div>
                        <div class="profile-readonly-item"><span class="profile-readonly-label">Puesto</span><span class="profile-readonly-value">{{ $personal->puesto?->nombre ?: 'No asignado' }}</span></div>
                        @if($personal->fecha_baja)
                            <div class="profile-readonly-item"><span class="profile-readonly-label">Fecha de baja</span><span class="profile-readonly-value">{{ $personal->fecha_baja->format('d/m/Y') }}</span></div>
                        @endif
                    </div>

                    <h3 class="profile-readonly-section-title">Días y horarios laborales</h3>
                    <div class="profile-workday-list">
                        @foreach($workDays as $dayNumber => $dayName)
                            @php
                                $schedule = $personalSchedules->get($dayNumber);
                                $isWorkingDay = in_array($dayNumber, $workDayNumbers, true);
                                $scheduleEntry = $schedule?->entrada ? substr((string) $schedule->entrada, 0, 5) : null;
                                $scheduleExit = $schedule?->salida ? substr((string) $schedule->salida, 0, 5) : null;
                                $endsNextDay = $scheduleEntry && $scheduleExit && substr((string) $schedule->salida, 0, 8) <= substr((string) $schedule->entrada, 0, 8);
                            @endphp
                            <div class="profile-workday-row">
                                <span class="profile-workday-name">{{ $dayName }}</span>
                                <span class="profile-workday-detail">
                                    @if($isWorkingDay)
                                        {{ $schedule?->horarioEmpresa?->nombre_horario ?: 'Horario asignado' }}
                                        @if($scheduleEntry && $scheduleExit) · {{ $scheduleEntry }}–{{ $scheduleExit }}@if($endsNextDay) (día siguiente)@endif
                                        @else · Horario no disponible
                                        @endif
                                    @else
                                        {{ $schedule ? 'Horario registrado: ' . ($scheduleEntry ?: '—') . '–' . ($scheduleExit ?: '—') : 'Descanso' }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="card-hr"><div class="card-bd">Tu cuenta todavía no tiene una ficha de personal vinculada. Contacta al administrador de tu empresa.</div></div>
        @endif
    @else
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
    @endif
</section>

@unless($isEmployeeProfile)
<script>
    document.getElementById('foto')?.addEventListener('change', function () {
        document.getElementById('foto-seleccionada').textContent = this.files?.[0]?.name ?? '';
    });
</script>
@endunless
@endsection
