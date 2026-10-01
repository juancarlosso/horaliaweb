@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar puesto' : 'Agregar puesto', 'activeSection' => 'puestos'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .catalog-form-page { width:100%; }
    .catalog-form-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .catalog-form-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .catalog-form-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .catalog-field-error { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    .catalog-input-error { border-color:#ef4444; }
    .catalog-form-actions { display:flex; justify-content:flex-start; gap:.5rem; flex-wrap:wrap; padding-top:1.25rem; margin-top:1.25rem; border-top:1px solid var(--body-border); }
    .catalog-form-actions i { font-size:15px; }
    @media(max-width:575px) { .catalog-form-heading { align-items:stretch; flex-direction:column; } }
</style>

<section class="catalog-form-page">
    <header class="catalog-form-heading">
        <div><h1>{{ $isEdit ? 'Editar puesto' : 'Agregar puesto' }}</h1><p>{{ $isEdit ? 'Actualiza los datos del puesto.' : 'Registra un puesto para tu empresa.' }}</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('puestos.index', $puesto->empresa_id ? ['empresa_id' => $puesto->empresa_id] : []) }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Información del puesto</h2><p class="card-subtitle-hr">Los campos marcados con * son obligatorios.</p></div></div>
        <div class="card-bd">
            <form method="POST" action="{{ $isEdit ? route('puestos.update', $puesto) : route('puestos.store') }}">
                @csrf @if($isEdit) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-lg-5 col-md-6">
                        <label class="form-label-hr" for="empresa_id">Empresa <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('empresa_id') catalog-input-error @enderror" id="empresa_id" name="empresa_id" required>
                            <option value="">Selecciona una empresa</option>
                            @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $puesto->empresa_id ?? $selectedCompanyId ?? request('empresa_id')) === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach
                        </select>
                        @error('empresa_id')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <label class="form-label-hr" for="nombre">Puesto <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('nombre') catalog-input-error @enderror" id="nombre" name="nombre" type="text" value="{{ old('nombre', $puesto->nombre) }}" maxlength="255" required>
                        @error('nombre')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label-hr" for="activo">Estatus <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('activo') catalog-input-error @enderror" id="activo" name="activo" required>
                            <option value="1" @selected((string) old('activo', $puesto->exists ? (int) $puesto->activo : 1) === '1')>Activo</option>
                            <option value="0" @selected((string) old('activo', $puesto->exists ? (int) $puesto->activo : 1) === '0')>Inactivo</option>
                        </select>
                        @error('activo')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="catalog-form-actions">
                    <button class="btn-hr btn-primary-hr" type="submit"><i class="fa-light fa-floppy-disk" aria-hidden="true"></i> {{ $isEdit ? 'Guardar cambios' : 'Guardar puesto' }}</button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('puestos.index', $puesto->empresa_id ? ['empresa_id' => $puesto->empresa_id] : []) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
