@extends('layouts.dashboard', ['pageTitle' => 'Personal', 'activeSection' => 'personal'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .personal-page { width:100%; }
    .personal-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .personal-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .personal-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .personal-toolbar { display:flex; align-items:end; gap:1rem; margin-bottom:1.25rem; }
    .personal-filter { width:min(100%, 420px); }
    .personal-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .personal-filter select { width:100%; }
    .personal-toolbar-action { margin-left:auto; white-space:nowrap; }
    .personal-error { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(239,68,68,.2); border-radius:.625rem; color:#b42318; background:rgba(239,68,68,.07); font-size:.845rem; }
    .personal-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .personal-actions i { display:inline-block; width:15px; min-width:15px; font-size:15px; line-height:1; text-align:center; }
    .personal-actions form { margin:0; }
    .personal-actions .btn-hr { text-decoration:none; }
    .personal-person-cell { display:flex; align-items:center; gap:.65rem; min-width:200px; }
    .personal-avatar { position:relative; display:grid; place-items:center; width:40px; height:40px; flex:none; overflow:hidden; border-radius:.7rem; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); font-size:.95rem; font-weight:800; }
    .personal-avatar img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .personal-name { color:var(--body-text); font-weight:700; }
    .personal-code { margin-top:.15rem; color:var(--body-text-muted); font-size:.75rem; }
    .personal-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .personal-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .personal-empty p { margin:0; font-size:.845rem; }
    .personal-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .personal-pagination { display:flex; align-items:center; gap:.55rem; }
    .personal-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    @media(max-width:700px) { .personal-heading { align-items:stretch; flex-direction:column; } .personal-toolbar { align-items:stretch; flex-direction:column; } .personal-footer { align-items:flex-start; flex-direction:column; } }
</style>

<section class="personal-page">
    <header class="personal-heading"><div><h1>Personal</h1><p>Administra los colaboradores y sus horarios laborales.</p></div></header>
    @if($errors->has('personal'))<div class="personal-error" role="alert">{{ $errors->first('personal') }}</div>@endif
    @if($errors->has('password') && !old('personal_id'))<div class="personal-error" role="alert">{{ $errors->first('password') }}</div>@endif
    <div class="personal-toolbar">
        <div class="personal-filter">
            <label for="empresa_id">Empresa</label>
            <select class="hr-input select2" id="empresa_id" name="empresa_id">
                @if($companies->count() !== 1)<option value="0" @selected(!$selectedCompanyId)>Todas las empresas</option>@endif
                @foreach($companies as $company)<option value="{{ $company->id }}" @selected($selectedCompanyId === $company->id)>{{ $company->nombre }}</option>@endforeach
            </select>
        </div>
        @if($canCreate)<a class="btn-hr btn-primary-hr personal-toolbar-action" href="{{ route('personal.create', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}"><i class="fa-light fa-plus" aria-hidden="true"></i> Agregar personal</a>@endif
    </div>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Listado de personal</h2><p class="card-subtitle-hr">{{ $personal->total() }} {{ $personal->total() === 1 ? 'colaborador registrado' : 'colaboradores registrados' }}</p></div></div>
        @if($personal->count())
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Colaborador</th><th>Correo electrónico</th><th>Empresa</th><th>Centro de trabajo</th><th>Sexo</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($personal as $empleado)
                    @php
                        $initial = mb_strtoupper(mb_substr(trim($empleado->nombre ?? ''), 0, 1)) ?: 'P';
                        $photoUrl = $empleado->fotoUrl();
                    @endphp
                    <tr>
                        <td><div class="personal-person-cell"><div class="personal-avatar"><span>{{ $initial }}</span>@if($photoUrl)<img src="{{ $photoUrl }}" alt="" loading="lazy" onerror="this.remove()">@endif</div><div><div class="personal-name">{{ $empleado->nombre }} @unless($empleado->activo)<span class="company-rfc"> · Dado de baja</span>@endunless</div><div class="personal-code">{{ $empleado->codigo ?: 'Sin código' }}@if($empleado->fecha_baja) · Baja {{ $empleado->fecha_baja->format('d/m/Y H:i') }}@endif</div></div></div></td>
                        <td>{{ $empleado->email ?: '—' }}</td>
                        <td>{{ $empleado->empresa?->nombre ?? '—' }}</td>
                        <td>{{ $empleado->centro?->nombre ?? '—' }}</td>
                        <td>{{ $empleado->sexo ? ucfirst($empleado->sexo) : '—' }}</td>
                        <td><div class="personal-actions">
                            @if(in_array($empleado->empresa_id, $manageableIds, true))
                                <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('personal.edit', $empleado) }}" title="Editar" aria-label="Editar {{ $empleado->nombre }}"><i class="fa-light fa-pen-to-square" aria-hidden="true"></i></a>
                                @if($empleado->activo)
                                    <button class="btn-hr btn-outline-hr btn-sm-hr" type="button" data-bs-toggle="modal" data-bs-target="#assign-password-modal" data-password-url="{{ route('personal.password.assign', $empleado) }}" data-employee-id="{{ $empleado->id }}" data-employee-name="{{ $empleado->nombre }}" title="Asignar nueva contraseña" aria-label="Asignar nueva contraseña a {{ $empleado->nombre }}"><i class="fa-light fa-lock" aria-hidden="true"></i></button>
                                    <form method="POST" action="{{ route('personal.destroy', $empleado) }}" data-confirm-termination data-record-name="{{ $empleado->nombre }}">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" title="Dar de baja" aria-label="Dar de baja a {{ $empleado->nombre }}"><i class="fa-light fa-user-minus" aria-hidden="true"></i> Dar de baja</button></form>
                                @endif
                            @else<span class="company-rfc">Solo lectura</span>@endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($personal->hasPages())<div class="personal-footer"><span>Mostrando {{ $personal->firstItem() }}–{{ $personal->lastItem() }} de {{ $personal->total() }}</span><div class="personal-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $personal->previousPageUrl() ?? '#' }}" @if($personal->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $personal->currentPage() }} de {{ $personal->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $personal->nextPageUrl() ?? '#' }}" @unless($personal->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>@endif
        @else
            <div class="personal-empty"><h2>Aún no hay colaboradores</h2><p>Agrega personal para comenzar a asignar horarios laborales.</p></div>
        @endif
    </div>
</section>
<div class="modal fade" id="assign-password-modal" tabindex="-1" aria-labelledby="assign-password-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" id="assign-password-form" action="" novalidate>
            @csrf
            <input type="hidden" name="personal_id" id="assign-password-personal-id" value="{{ old('personal_id') }}">
            <div class="modal-header"><h2 class="modal-title fs-5" id="assign-password-title">Asignar nueva contraseña</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">Estás por cambiar la contraseña de <strong id="assign-password-employee-name"></strong>. Ingresa y confirma la nueva contraseña para guardar el cambio.<br>Una vez actualizada, asegúrate de compartirla con el empleado a través de un medio seguro.</div>
                @if($errors->has('password'))<div class="personal-error" role="alert">{{ $errors->first('password') }}</div>@endif
                <div class="alert alert-danger" id="assign-password-client-error" role="alert" aria-live="assertive" hidden></div>
                <div class="mb-3"><label class="form-label" for="assign-password-input">Nueva contraseña</label><input class="form-control" id="assign-password-input" name="password" type="password" minlength="6" required autocomplete="new-password"><div class="form-text">Mínimo 6 caracteres.</div></div>
                <div><label class="form-label" for="assign-password-confirmation">Confirmar contraseña</label><input class="form-control" id="assign-password-confirmation" name="password_confirmation" type="password" minlength="6" required autocomplete="new-password"></div>
            </div>
            <div class="modal-footer"><button class="btn-hr btn-outline-hr" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn-hr btn-primary-hr" type="submit">Guardar contraseña</button></div>
        </form>
    </div></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.jQuery('#empresa_id').on('change', function () {
            const url = new URL(@json(route('personal.index')), window.location.origin);
            if (this.value !== '0') url.searchParams.set('empresa_id', this.value);
            window.location.assign(url);
        });
    });
    const assignPasswordModal = document.getElementById('assign-password-modal');
    const assignPasswordForm = document.getElementById('assign-password-form');
    const assignPasswordName = document.getElementById('assign-password-employee-name');
    const assignPasswordId = document.getElementById('assign-password-personal-id');
    const assignPasswordInput = document.getElementById('assign-password-input');
    const assignPasswordConfirmation = document.getElementById('assign-password-confirmation');
    const assignPasswordClientError = document.getElementById('assign-password-client-error');
    const clearAssignPasswordError = function () {
        assignPasswordClientError.textContent = '';
        assignPasswordClientError.hidden = true;
    };
    assignPasswordModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        assignPasswordForm.action = trigger.dataset.passwordUrl;
        assignPasswordId.value = trigger.dataset.employeeId;
        assignPasswordName.textContent = trigger.dataset.employeeName;
        assignPasswordInput.value = '';
        assignPasswordConfirmation.value = '';
        clearAssignPasswordError();
    });
    [assignPasswordInput, assignPasswordConfirmation].forEach(function (input) {
        input.addEventListener('input', clearAssignPasswordError);
    });
    assignPasswordForm.addEventListener('submit', function (event) {
        clearAssignPasswordError();
        if (assignPasswordInput.value.length < 6) {
            event.preventDefault();
            assignPasswordClientError.textContent = 'La contraseña debe tener al menos 6 caracteres.';
            assignPasswordClientError.hidden = false;
            assignPasswordInput.focus();
            return;
        }
        if (assignPasswordInput.value !== assignPasswordConfirmation.value) {
            event.preventDefault();
            assignPasswordClientError.textContent = 'La confirmación no coincide con la contraseña.';
            assignPasswordClientError.hidden = false;
            assignPasswordConfirmation.focus();
        }
    });
    @if($errors->has('password') && old('personal_id'))
        const invalidPasswordTrigger = Array.from(document.querySelectorAll('[data-employee-id]')).find(function (trigger) {
            return trigger.dataset.employeeId === @json((string) old('personal_id'));
        });
        if (invalidPasswordTrigger) {
            assignPasswordForm.action = invalidPasswordTrigger.dataset.passwordUrl;
            assignPasswordName.textContent = invalidPasswordTrigger.dataset.employeeName;
            new bootstrap.Modal(assignPasswordModal).show();
        }
    @endif
    document.querySelectorAll('[data-confirm-termination]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning', title: '¿Dar de baja a este colaborador?',
                text: form.dataset.recordName + ' dejará de acceder a Horalia y de registrar asistencias. Sus datos e historial se conservarán y seguirá contando para la suscripción.',
                showCancelButton: true, confirmButtonText: 'Sí, dar de baja', cancelButtonText: 'Cancelar',
                buttonsStyling: false, reverseButtons: true,
                customClass: { confirmButton: 'btn-hr btn-danger-hr', cancelButton: 'btn-hr btn-outline-hr' }
            }).then(function (result) { if (result.isConfirmed) form.submit(); });
        });
    });
</script>
@endsection
