@extends('layouts.dashboard', ['pageTitle' => 'Reloj Checador', 'activeSection' => 'reloj-checador'])

@section('content')
<style>
    .kiosk-admin { width:100%; max-width:none; margin:0; }
    .kiosk-admin-heading { display:flex; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .kiosk-admin-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .kiosk-admin-heading p,.kiosk-note { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .kiosk-link-result { margin-top:1rem; padding:1rem; border:1px solid rgba(16,185,129,.25); border-radius:.75rem; background:rgba(16,185,129,.07); }
    .kiosk-link-status { margin:.15rem 0 .7rem; color:var(--body-text-muted); font-size:.8rem; }
    .personal-error { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(239,68,68,.2); border-radius:.625rem; color:#b42318; background:rgba(239,68,68,.07); font-size:.845rem; }
    .kiosk-link-result label { display:block; margin-bottom:.4rem; color:var(--body-text); font-weight:700; }
    .kiosk-link-result input { width:100%; }
    .kiosk-email-modal { position:fixed; inset:0; z-index:11000; display:grid; place-items:center; padding:1rem; background:rgba(15,19,38,.62); }
    .kiosk-email-modal[hidden] { display:none; }
    .kiosk-email-modal-card { width:min(100%,520px); max-height:min(90vh,720px); overflow:auto; padding:1.35rem; border:1px solid var(--body-border); border-radius:1rem; background:var(--card-bg); box-shadow:0 24px 70px rgba(10,15,35,.28); }
    .kiosk-email-modal-head { display:flex; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
    .kiosk-email-modal-head h2 { margin:0; color:var(--body-text); font-size:1.15rem; font-weight:800; }
    .kiosk-email-modal-head p { margin:.3rem 0 0; color:var(--body-text-muted); font-size:.84rem; }
    .kiosk-email-modal-actions { display:flex; justify-content:flex-end; gap:.65rem; margin-top:1.1rem; }
    @media(max-width:575px) { .kiosk-email-modal-card { padding:1rem; } }
    @media(max-width:600px) { .kiosk-admin-heading { align-items:stretch; flex-direction:column; } }
</style>
<section class="kiosk-admin">
    <header class="kiosk-admin-heading"><div><h1>Reloj Checador</h1><p>Selecciona la empresa y la vigencia de la sesión para generar un enlace que permita registrar la asistencia de su personal, sin importar el centro de trabajo.</p></div><a class="btn-hr btn-outline-hr" href="{{ route('asistencia.index') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a></header>
    @if($errors->any())<div class="personal-error" role="alert">{{ $errors->first() }}</div>@endif
    <article class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Nueva sesión de checador</h2><p class="card-subtitle-hr">La tablet quedará vinculada a una empresa.</p></div></div>
        <div class="card-bd">
            <form method="POST" action="{{ route('checador.store') }}" class="d-grid gap-3">
                @csrf
                <div><label class="form-label-hr" for="empresa_id">Empresa</label><select class="hr-input select2" id="empresa_id" name="empresa_id" required><option value="">Selecciona una empresa</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $selectedCompanyId) === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach</select></div>
                <div><label class="form-label-hr" for="duracion_dias">Vigencia de la sesión</label><select class="hr-input select2" id="duracion_dias" name="duracion_dias" required>@for($days = 1; $days <= 7; $days++)<option value="{{ $days }}" @selected((int) old('duracion_dias', 1) === $days)>{{ $days }} {{ $days === 1 ? 'día' : 'días' }}</option>@endfor</select></div>
                <p class="kiosk-note">Abre el enlace generado en la tablet de la empresa. El enlace puede activarse una sola vez durante los siguientes 15 minutos; después, la sesión durará el periodo seleccionado.</p>
                <div><button class="btn-hr btn-primary-hr" type="submit"><i class="fa-light fa-link" aria-hidden="true"></i> Generar enlace del checador</button></div>
            </form>
            <div class="kiosk-link-list">
                @forelse($activationSessions as $activationSession)
                    @php($activationUrl = $activationSession->activation_url)
                    @php($activationInputId = 'activation-url-' . $activationSession->id)
                    <div class="kiosk-link-result">
                        <label for="{{ $activationInputId }}">{{ $activationSession->empresa->razon_social }}</label>
                        <p class="kiosk-link-status">{{ $activationSession->link_status }}</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <input class="hr-input flex-grow-1" id="{{ $activationInputId }}" type="url" readonly value="{{ $activationUrl }}">
                            <button class="btn-hr btn-outline-hr" type="button" data-copy-activation-url data-target="{{ $activationInputId }}">Copiar enlace</button>
                            <a class="btn-hr btn-primary-hr" href="{{ $activationUrl }}" target="_blank" rel="noopener">Abrir</a>
                            <button class="btn-hr btn-outline-hr" type="button" data-open-kiosk-email data-email-url="{{ route('checador.email', $activationSession->id) }}" data-company="{{ $activationSession->empresa->razon_social }}"><i class="fa-light fa-envelope" aria-hidden="true"></i> Enviar por correo</button>
                        </div>
                    </div>
                @empty
                    <p class="kiosk-note mt-3">Todavía no hay enlaces de checador vigentes.</p>
                @endforelse
            </div>
        </div>
    </article>
</section>
<div class="kiosk-email-modal" id="kiosk-email-modal" role="dialog" aria-modal="true" aria-labelledby="kiosk-email-modal-title" @if(!$errors->has('email') && !$errors->has('email_secondary')) hidden @endif>
    <div class="kiosk-email-modal-card">
        <div class="kiosk-email-modal-head">
            <div><h2 id="kiosk-email-modal-title">Enviar enlace del checador</h2><p id="kiosk-email-company"></p></div>
            <button class="btn-hr btn-outline-hr" type="button" data-close-kiosk-email aria-label="Cerrar">Cerrar</button>
        </div>
        <form id="kiosk-email-form" method="POST" action="{{ old('_email_action', '') }}">
            @csrf
            <input type="hidden" name="_email_action" id="kiosk-email-action" value="{{ old('_email_action') }}">
            <div class="mb-3"><label class="form-label-hr" for="kiosk-email-primary">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label><input class="hr-input" id="kiosk-email-primary" name="email" type="email" maxlength="255" autocomplete="email" value="{{ old('email') }}" required></div>
            <div><label class="form-label-hr" for="kiosk-email-secondary">Segundo correo electrónico</label><input class="hr-input" id="kiosk-email-secondary" name="email_secondary" type="email" maxlength="255" autocomplete="email" value="{{ old('email_secondary') }}"></div>
            <div class="kiosk-email-modal-actions"><button class="btn-hr btn-outline-hr" type="button" data-close-kiosk-email>Cancelar</button><button class="btn-hr btn-primary-hr" type="submit">Enviar</button></div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-copy-activation-url]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const input = document.getElementById(button.dataset.target);
            await navigator.clipboard.writeText(input.value);
            button.textContent = 'Enlace copiado';
        });
    });
    const modal = document.getElementById('kiosk-email-modal');
    const form = document.getElementById('kiosk-email-form');
    const company = document.getElementById('kiosk-email-company');
    const actionInput = document.getElementById('kiosk-email-action');
    const closeModal = () => { modal.hidden = true; document.body.style.overflow = ''; };
    document.querySelectorAll('[data-open-kiosk-email]').forEach(function (button) {
        button.addEventListener('click', function () {
            form.action = button.dataset.emailUrl;
            actionInput.value = button.dataset.emailUrl;
            company.textContent = button.dataset.company;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            document.getElementById('kiosk-email-primary').focus();
        });
    });
    document.querySelectorAll('[data-close-kiosk-email]').forEach(button => button.addEventListener('click', closeModal));
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
    @if($errors->has('email') || $errors->has('email_secondary'))
        const emailAction = @json(old('_email_action', ''));
        const emailButtons = [...document.querySelectorAll('[data-open-kiosk-email]')];
        const firstEmailButton = emailButtons.find(button => button.dataset.emailUrl === emailAction) || emailButtons[0];
        if (firstEmailButton) {
            form.action = firstEmailButton.dataset.emailUrl;
            actionInput.value = firstEmailButton.dataset.emailUrl;
            company.textContent = firstEmailButton.dataset.company;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        }
    @endif
});
</script>
@endsection
