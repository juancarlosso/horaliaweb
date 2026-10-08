@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar horario' : 'Agregar horario', 'activeSection' => 'horarios'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .schedule-form-page { width:100%; }
    .schedule-form-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .schedule-form-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .schedule-form-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .schedule-field-error { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    .schedule-input-error { border-color:#ef4444; }
    .schedule-form-actions { display:flex; justify-content:flex-start; gap:.5rem; flex-wrap:wrap; padding-top:1.25rem; margin-top:1.25rem; border-top:1px solid var(--body-border); }
    .schedule-form-actions i { font-size:15px; }
    @media(max-width:575px) { .schedule-form-heading { align-items:stretch; flex-direction:column; } }
</style>

<section class="schedule-form-page">
    <header class="schedule-form-heading">
        <div><h1>{{ $isEdit ? 'Editar horario' : 'Agregar horario' }}</h1><p>{{ $isEdit ? 'Actualiza la información del horario.' : 'Registra un horario para una empresa.' }}</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('horarios.index', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Información del horario</h2><p class="card-subtitle-hr">Los campos marcados con * son obligatorios.</p></div></div>
        <div class="card-bd">
            <form method="POST" action="{{ $isEdit ? route('horarios.update', $horario) : route('horarios.store') }}">
                @csrf @if($isEdit) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label-hr" for="empresa_id">Empresa <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('empresa_id') schedule-input-error @enderror" id="empresa_id" name="empresa_id" required>
                            <option value="">Selecciona una empresa</option>
                            @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $horario->empresa_id ?? $selectedCompanyId) === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach
                        </select>
                        @error('empresa_id')<span class="schedule-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label-hr" for="nombre_horario">Nombre del horario <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('nombre_horario') schedule-input-error @enderror" id="nombre_horario" name="nombre_horario" type="text" value="{{ old('nombre_horario', $horario->nombre_horario) }}" maxlength="255" required>
                        @error('nombre_horario')<span class="schedule-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label-hr" for="hora_entrada">Hora de entrada <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('hora_entrada') schedule-input-error @enderror" id="hora_entrada" name="hora_entrada" type="time" value="{{ old('hora_entrada', $horario->hora_entrada ? substr($horario->hora_entrada, 0, 5) : '') }}" required>
                        @error('hora_entrada')<span class="schedule-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label-hr" for="hora_salida">Hora de salida <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('hora_salida') schedule-input-error @enderror" id="hora_salida" name="hora_salida" type="time" value="{{ old('hora_salida', $horario->hora_salida ? substr($horario->hora_salida, 0, 5) : '') }}" required>
                        <small class="d-block mt-1 text-muted">Si la salida es igual o anterior a la entrada, se considera al día siguiente.</small>
                        @error('hora_salida')<span class="schedule-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="schedule-form-actions">
                    <button class="btn-hr btn-primary-hr" type="submit"><i class="fa-light fa-floppy-disk" aria-hidden="true"></i> {{ $isEdit ? 'Guardar cambios' : 'Guardar horario' }}</button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('horarios.index', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
