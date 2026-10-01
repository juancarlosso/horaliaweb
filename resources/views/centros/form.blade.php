@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar centro de trabajo' : 'Agregar centro de trabajo', 'activeSection' => 'centros-de-trabajo'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .catalog-form-page { width:100%; }
    .catalog-form-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .catalog-form-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .catalog-form-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .catalog-field-error { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    .catalog-input-error { border-color:#ef4444; }
    .catalog-help { margin:.35rem 0 0; color:var(--body-text-muted); font-size:.775rem; }
    .catalog-form-actions { display:flex; justify-content:flex-start; gap:.5rem; flex-wrap:wrap; padding-top:1.25rem; margin-top:1.25rem; border-top:1px solid var(--body-border); }
    .catalog-form-actions i,.catalog-map-button i { font-size:15px; }
    .catalog-map-row { display:flex; gap:.5rem; }
    .catalog-map-row .hr-input { min-width:0; flex:1; }
    @media(max-width:575px) { .catalog-form-heading { align-items:stretch; flex-direction:column; } .catalog-map-row { flex-direction:column; } .catalog-map-row .btn-hr { align-self:flex-start; } }
</style>

<section class="catalog-form-page">
    <header class="catalog-form-heading"><div><h1>{{ $isEdit ? 'Editar centro de trabajo' : 'Agregar centro de trabajo' }}</h1><p>{{ $isEdit ? 'Actualiza la información y ubicación del centro.' : 'Registra una ubicación para la empresa.' }}</p></div><a class="btn-hr btn-outline-hr" href="{{ route('centros.index', $centro->empresa_id ? ['empresa_id' => $centro->empresa_id] : []) }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a></header>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Información del centro</h2><p class="card-subtitle-hr">Los campos marcados con * son obligatorios.</p></div></div>
        <div class="card-bd">
            <form method="POST" action="{{ $isEdit ? route('centros.update', $centro) : route('centros.store') }}">
                @csrf @if($isEdit) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-lg-5 col-md-6">
                        <label class="form-label-hr" for="empresa_id">Empresa <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('empresa_id') catalog-input-error @enderror" id="empresa_id" name="empresa_id" required>
                            <option value="">Selecciona una empresa</option>
                            @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $centro->empresa_id ?? $selectedCompanyId ?? request('empresa_id')) === (string) $company->id)>{{ $company->razon_social }}</option>@endforeach
                        </select>
                        @error('empresa_id')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <label class="form-label-hr" for="nombre">Centro de trabajo <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('nombre') catalog-input-error @enderror" id="nombre" name="nombre" type="text" value="{{ old('nombre', $centro->nombre) }}" maxlength="255" required>
                        @error('nombre')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label-hr" for="activo">Estatus <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="hr-input select2 @error('activo') catalog-input-error @enderror" id="activo" name="activo" required>
                            <option value="1" @selected((string) old('activo', $centro->exists ? (int) $centro->activo : 1) === '1')>Activo</option>
                            <option value="0" @selected((string) old('activo', $centro->exists ? (int) $centro->activo : 1) === '0')>Inactivo</option>
                        </select>
                        @error('activo')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label-hr" for="geolocalizacion">Geolocalización <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="catalog-map-row">
                            <input class="hr-input @error('geolocalizacion') catalog-input-error @enderror" id="geolocalizacion" name="geolocalizacion" type="text" value="{{ old('geolocalizacion', $centro->geolocalizacion) }}" maxlength="255" required placeholder="latitud, longitud, ejemplo: 19.42698287419, -99.1676693843">
                            <button class="btn-hr btn-outline-hr catalog-map-button" type="button" id="open-map"><i class="fa-light fa-map-location-dot" aria-hidden="true"></i> Ver en Maps</button>
                        </div>
                        @error('geolocalizacion')<span class="catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="catalog-form-actions">
                    <button class="btn-hr btn-primary-hr" type="submit"><i class="fa-light fa-floppy-disk" aria-hidden="true"></i> {{ $isEdit ? 'Guardar cambios' : 'Guardar centro' }}</button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('centros.index', $centro->empresa_id ? ['empresa_id' => $centro->empresa_id] : []) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>
<script>
    document.getElementById('open-map')?.addEventListener('click', function () {
        const location = document.getElementById('geolocalizacion').value.trim();
        if (!location) return;
        window.open('https://www.google.com/maps?q=' + encodeURIComponent(location), '_blank', 'noopener');
    });
</script>
@endsection
