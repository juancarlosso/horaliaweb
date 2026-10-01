@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar personal' : 'Agregar personal', 'activeSection' => 'personal'])

@section('content')
@php
    $dayNumbers = array_keys($days);
    $savedDays = $isEdit
        ? $horariosSeleccionados->keys()->map(fn ($number) => $dayNumbers[(int) $number - 1] ?? null)->filter()->values()->all()
        : [];
    $selectedDays = (array) old('laborados', $savedDays);
    $selectedCompany = old('empresa_id', $selectedCompanyId ?? $empleado->empresa_id);
    $selectedCenter = old('centro_id', $empleado->centro_id);
    $selectedDepartment = old('departamento_id', $empleado->departamento_id);
    $selectedPosition = old('puesto_id', $empleado->puesto_id);
    $personalProfiles = config('constantes.perfilesPersonal', []);
    $selectedProfile = old('profile', $empleado->usuario?->profile ?? data_get($personalProfiles, 'empleado_regular.id'));
    $fotoUrl = $empleado->fotoUrl();
    $centerOptionsJson = $centros->map(fn ($center) => ['id' => $center->id, 'empresa_id' => $center->empresa_id, 'nombre' => $center->nombre])->values()->toJson();
    $departmentOptionsJson = $departamentos->map(fn ($department) => ['id' => $department->id, 'empresa_id' => $department->empresa_id, 'nombre' => $department->nombre])->values()->toJson();
    $positionOptionsJson = $puestos->map(fn ($position) => ['id' => $position->id, 'empresa_id' => $position->empresa_id, 'nombre' => $position->nombre])->values()->toJson();
    $scheduleOptionsJson = $horarios->map(fn ($schedule) => ['id' => $schedule->id, 'empresa_id' => $schedule->empresa_id, 'nombre' => $schedule->nombre_horario, 'entrada' => substr($schedule->hora_entrada, 0, 5), 'salida' => substr($schedule->hora_salida, 0, 5)])->values()->toJson();
@endphp
<style>
    .page-inner { max-width:none; }
    .personal-page { width:100%; }
    .personal-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .personal-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .personal-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .personal-error { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(239,68,68,.2); border-radius:.625rem; color:#b42318; background:rgba(239,68,68,.07); font-size:.845rem; }
    .personal-field-error { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    .personal-input-error { border-color:#ef4444; }
    .personal-pin-input { position:relative; }
    .personal-pin-input .hr-input { padding-right:2.8rem; }
    .personal-pin-toggle { position:absolute; top:50%; right:.35rem; display:grid; place-items:center; width:2.1rem; height:2.1rem; padding:0; transform:translateY(-50%); border:0; border-radius:.45rem; color:var(--body-text-muted); background:transparent; cursor:pointer; }
    .personal-pin-toggle:hover,.personal-pin-toggle:focus-visible { color:var(--color-primary); background:var(--color-primary-light); }
    .personal-pin-toggle:focus-visible { outline:2px solid var(--color-primary); outline-offset:1px; }
    .personal-photo-row { display:flex; align-items:center; gap:1rem; padding-bottom:1.25rem; margin-bottom:1.25rem; border-bottom:1px solid var(--body-border); }
    .personal-photo-preview { position:relative; display:grid; place-items:center; width:88px; height:88px; flex:none; overflow:hidden; border-radius:1rem; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); font-size:1.8rem; font-weight:800; }
    .personal-photo-preview img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .personal-photo-help { margin:.4rem 0 0; color:var(--body-text-muted); font-size:.775rem; }
    .personal-section-title { margin:1.5rem 0 .8rem; color:var(--body-text); font-size:.95rem; font-weight:750; }
    .personal-day-card { height:100%; padding:1rem; border:1px solid var(--body-border); border-radius:.8rem; background:var(--card-bg); transition:border-color .15s ease,background .15s ease; }
    .personal-day-card.is-selected { border-color:var(--color-primary); background:var(--color-primary-light); }
    .personal-day-toggle { display:flex; align-items:center; gap:.55rem; margin:0; color:var(--body-text); font-size:.875rem; font-weight:650; cursor:pointer; }
    .personal-day-toggle input { width:1rem; height:1rem; accent-color:var(--color-primary); }
    .personal-day-schedule { margin-top:.85rem; }
    .personal-day-schedule[hidden] { display:none !important; }
    .personal-form-actions { display:flex; justify-content:flex-start; gap:.5rem; flex-wrap:wrap; padding-top:1.25rem; margin-top:1.25rem; border-top:1px solid var(--body-border); }
    .personal-form-actions i { font-size:15px; }
    @media(max-width:575px) { .personal-heading { align-items:stretch; flex-direction:column; } .personal-photo-row { align-items:flex-start; } }
</style>

<section class="personal-page">
    <header class="personal-heading">
        <div><h1>{{ $isEdit ? 'Editar personal' : 'Agregar personal' }}</h1><p>{{ $isEdit ? 'Actualiza los datos y el horario laboral del colaborador.' : 'Registra a un colaborador y configura sus días y horarios laborales.' }}</p></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn-hr btn-outline-hr" href="{{ route('personal.index', $selectedCompany ? ['empresa_id' => $selectedCompany] : []) }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
            @if($isEdit)
                <form method="POST" action="{{ route('personal.password.regenerate', $empleado) }}" data-confirm-regenerate-password>
                    @csrf
                    <button class="btn-hr btn-outline-hr" type="submit"><i class="fa-light fa-key" aria-hidden="true"></i> Regenerar contraseña</button>
                </form>
                <form method="POST" action="{{ route('personal.pin.resend', $empleado) }}" data-confirm-resend-pin data-recipient-email="{{ $empleado->email }}">
                    @csrf
                    <button class="btn-hr btn-outline-hr" type="submit"><i class="fa-light fa-envelope" aria-hidden="true"></i> Reenviar PIN</button>
                </form>
            @endif
        </div>
    </header>

    @if($errors->has('personal'))<div class="personal-error" role="alert">{{ $errors->first('personal') }}</div>@endif
    @if($errors->has('foto'))<div class="personal-error" role="alert">{{ $errors->first('foto') }}</div>@endif

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Información del colaborador</h2><p class="card-subtitle-hr">Los campos marcados con * son obligatorios.</p></div></div>
        <div class="card-bd">
            <form method="POST" action="{{ $isEdit ? route('personal.update', $empleado) : route('personal.store') }}" enctype="multipart/form-data" id="personal-form">
                @csrf @if($isEdit) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label-hr" for="empresa_id">Empresa <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('empresa_id') personal-input-error @enderror" id="empresa_id" name="empresa_id" required>
                            <option value="">Selecciona una empresa</option>
                            @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) $selectedCompany === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach
                        </select>
                        @error('empresa_id')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label-hr" for="centro_id">Centro de trabajo <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('centro_id') personal-input-error @enderror" id="centro_id" name="centro_id" required>
                            <option value="">Selecciona un centro de trabajo</option>
                            @foreach($centros as $centro)<option value="{{ $centro->id }}" data-company-id="{{ $centro->empresa_id }}" @selected((string) $selectedCenter === (string) $centro->id)>{{ $centro->nombre }}</option>@endforeach
                        </select>
                        @error('centro_id')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="personal-photo-row mt-1rem">
                    <div class="personal-photo-preview" id="foto-preview">
                        <span>{{ mb_strtoupper(mb_substr(trim($empleado->nombre ?? ''), 0, 1)) ?: 'P' }}</span>
                        @if($fotoUrl)<img src="{{ $fotoUrl }}" id="foto-preview-image" alt="Foto de perfil actual" onerror="this.remove()">@endif
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label-hr" for="foto">Foto de perfil</label>
                        <input class="hr-input @error('foto') personal-input-error @enderror" id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="foto-help">
                        <p class="personal-photo-help" id="foto-help">JPG, PNG o WebP. Tamaño máximo: 5 MB.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label-hr" for="codigo">Código</label>
                        <input class="hr-input @error('codigo') personal-input-error @enderror" id="codigo" name="codigo" type="text" value="{{ old('codigo', $empleado->codigo) }}" maxlength="100">
                        @error('codigo')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label-hr" for="pin">PIN del checador</label>
                        <div class="personal-pin-input">
                            <input class="hr-input @error('pin') personal-input-error @enderror" id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('pin', $empleado->pin) }}" autocomplete="off">
                            <button class="personal-pin-toggle" id="pin-visibility-toggle" type="button" aria-label="Mostrar PIN" aria-pressed="false" aria-controls="pin"><i class="fa-light fa-eye" aria-hidden="true"></i></button>
                        </div>
                        @error('pin')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-5 col-md-4">
                        <label class="form-label-hr" for="nombre">Nombre completo <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('nombre') personal-input-error @enderror" id="nombre" name="nombre" type="text" value="{{ old('nombre', $empleado->nombre) }}" maxlength="255" required autocomplete="name">
                        @error('nombre')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-3 col-md-2">
                        <label class="form-label-hr" for="sexo">Sexo</label>
                        <select class="hr-input select2 @error('sexo') personal-input-error @enderror" id="sexo" name="sexo">
                            @foreach(['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('sexo', $empleado->sexo ?: 'masculino') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('sexo')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-lg-6 col-md-6">
                        <label class="form-label-hr" for="departamento_id">Departamento</label>
                        <select class="hr-input select2 @error('departamento_id') personal-input-error @enderror" id="departamento_id" name="departamento_id">
                            <option value="">Selecciona un departamento</option>
                            @foreach($departamentos as $departamento)<option value="{{ $departamento->id }}" data-company-id="{{ $departamento->empresa_id }}" @selected((string) $selectedDepartment === (string) $departamento->id)>{{ $departamento->nombre }}</option>@endforeach
                        </select>
                        @error('departamento_id')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <label class="form-label-hr" for="puesto_id">Puesto</label>
                        <select class="hr-input select2 @error('puesto_id') personal-input-error @enderror" id="puesto_id" name="puesto_id">
                            <option value="">Selecciona un puesto</option>
                            @foreach($puestos as $puesto)<option value="{{ $puesto->id }}" data-company-id="{{ $puesto->empresa_id }}" @selected((string) $selectedPosition === (string) $puesto->id)>{{ $puesto->nombre }}</option>@endforeach
                        </select>
                        @error('puesto_id')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-lg-6 col-md-7">
                        <label class="form-label-hr" for="email">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('email') personal-input-error @enderror" id="email" name="email" type="email" value="{{ old('email', $empleado->email) }}" maxlength="255" autocomplete="email" required>
                        @error('email')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-6 col-md-5">
                        <label class="form-label-hr" for="profile">Perfil <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('profile') personal-input-error @enderror" id="profile" name="profile" required>
                            @foreach($personalProfiles as $profile)
                                <option value="{{ $profile['id'] }}" @selected((string) $selectedProfile === (string) $profile['id'])>{{ $profile['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('profile')<span class="personal-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <h3 class="personal-section-title">Días y horarios laborales <span class="text-danger" aria-hidden="true">*</span></h3>
                @error('laborados')<span class="personal-field-error mb-2">{{ $message }}</span>@enderror
                <div class="row g-3">
                    @foreach($days as $day => $label)
                        @php
                            $dayNumber = $loop->index + 1;
                            $selectedSchedule = $horariosSeleccionados->get($dayNumber);
                            $selectedScheduleId = old('horarios.' . $day, $selectedSchedule?->horario_id);
                            $isChecked = in_array($day, $selectedDays, true);
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="personal-day-card {{ $isChecked ? 'is-selected' : '' }}" data-day-card>
                                <label class="personal-day-toggle" for="laborados_{{ $day }}">
                                    <input class="working-day-check" type="checkbox" id="laborados_{{ $day }}" name="laborados[]" value="{{ $day }}" @checked($isChecked)>
                                    {{ $label }}
                                </label>
                                <div class="personal-day-schedule" @unless($isChecked) hidden @endunless>
                                    <label class="form-label-hr" for="horario_{{ $day }}">Horario <span class="text-danger" aria-hidden="true">*</span></label>
                                    <select class="hr-input select2 schedule-select @error('horarios.' . $day) personal-input-error @enderror" id="horario_{{ $day }}" name="horarios[{{ $day }}]" data-selected="{{ $selectedScheduleId }}" @if($isChecked) required @endif>
                                        <option value="">Selecciona un horario</option>
                                        @foreach($horarios as $horario)
                                            <option value="{{ $horario->id }}" data-company-id="{{ $horario->empresa_id }}" @selected((string) $selectedScheduleId === (string) $horario->id)>{{ $horario->nombre_horario }} · {{ substr($horario->hora_entrada, 0, 5) }}–{{ substr($horario->hora_salida, 0, 5) }}</option>
                                        @endforeach
                                    </select>
                                    @error('horarios.' . $day)<span class="personal-field-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="personal-form-actions">
                    <button class="btn-hr btn-primary-hr" type="submit"><i class="fa-light fa-floppy-disk" aria-hidden="true"></i> {{ $isEdit ? 'Guardar cambios' : 'Guardar personal' }}</button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('personal.index', $selectedCompany ? ['empresa_id' => $selectedCompany] : []) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pinInput = document.getElementById('pin');
        const pinToggle = document.getElementById('pin-visibility-toggle');
        pinToggle?.addEventListener('click', function () {
            const showPin = pinInput.type === 'password';
            pinInput.type = showPin ? 'text' : 'password';
            pinToggle.setAttribute('aria-pressed', String(showPin));
            pinToggle.setAttribute('aria-label', showPin ? 'Ocultar PIN' : 'Mostrar PIN');
            pinToggle.innerHTML = `<i class="fa-light ${showPin ? 'fa-eye-slash' : 'fa-eye'}" aria-hidden="true"></i>`;
        });

        document.querySelectorAll('[data-confirm-regenerate-password]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    icon: 'question',
                    title: '¿Regenerar contraseña?',
                    text: '¿Quieres reenviar al correo del usuario un link para que genere nuevamente su contraseña?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, enviar link',
                    cancelButtonText: 'Cancelar',
                    buttonsStyling: false,
                    reverseButtons: true,
                    customClass: { confirmButton: 'btn-hr btn-primary-hr', cancelButton: 'btn-hr btn-outline-hr' }
                }).then(function (result) {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        document.querySelectorAll('[data-confirm-resend-pin]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    icon: 'question',
                    title: '¿Reenviar PIN?',
                    text: `Se enviará el PIN del checador al correo ${form.dataset.recipientEmail}.`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, reenviar PIN',
                    cancelButtonText: 'Cancelar',
                    buttonsStyling: false,
                    reverseButtons: true,
                    customClass: { confirmButton: 'btn-hr btn-primary-hr', cancelButton: 'btn-hr btn-outline-hr' }
                }).then(function (result) {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        const $company = window.jQuery('#empresa_id');
        const $center = window.jQuery('#centro_id');
        const $department = window.jQuery('#departamento_id');
        const $position = window.jQuery('#puesto_id');
        const centerOptions = {!! $centerOptionsJson !!};
        const departmentOptions = {!! $departmentOptionsJson !!};
        const positionOptions = {!! $positionOptionsJson !!};
        const scheduleOptions = {!! $scheduleOptionsJson !!};

        function fillCompanySelect($select, rows, placeholder, companyId, preserveValue) {
            const oldValue = preserveValue ? $select.val() : '';
            $select.empty().append(new Option(placeholder, ''));
            rows.filter(row => String(row.empresa_id) === String(companyId)).forEach(row => {
                const label = row.entrada ? `${row.nombre} · ${row.entrada}–${row.salida}` : row.nombre;
                $select.append(new Option(label, row.id));
            });
            const stillExists = $select.find('option').toArray().some(option => option.value === String(oldValue));
            $select.val(stillExists ? oldValue : '').trigger('change.select2');
        }

        function refreshCompanyOptions(preserveValue) {
            const companyId = $company.val();
            fillCompanySelect($center, centerOptions, 'Selecciona un centro de trabajo', companyId, preserveValue);
            fillCompanySelect($department, departmentOptions, 'Selecciona un departamento', companyId, preserveValue);
            fillCompanySelect($position, positionOptions, 'Selecciona un puesto', companyId, preserveValue);
            window.jQuery('.schedule-select').each(function () {
                fillCompanySelect(window.jQuery(this), scheduleOptions, 'Selecciona un horario', companyId, preserveValue);
            });
        }

        refreshCompanyOptions(true);
        $company.on('change', function () { refreshCompanyOptions(false); });

        window.jQuery('.working-day-check').on('change', function () {
            const $card = window.jQuery(this).closest('[data-day-card]');
            const $schedule = $card.find('.personal-day-schedule');
            const $select = $card.find('.schedule-select');
            const selected = this.checked;
            $card.toggleClass('is-selected', selected);
            $schedule.prop('hidden', !selected);
            $select.prop('required', selected);
            if (!selected) $select.val('').trigger('change.select2');
        });

        document.getElementById('foto')?.addEventListener('change', function () {
            const file = this.files?.[0];
            if (!file) return;
            const preview = document.getElementById('foto-preview');
            let image = document.getElementById('foto-preview-image');
            if (!image) {
                image = document.createElement('img');
                image.id = 'foto-preview-image';
                image.alt = 'Vista previa de la foto de perfil';
                preview.appendChild(image);
            }
            image.src = URL.createObjectURL(file);
        });
    });
</script>
@endsection
