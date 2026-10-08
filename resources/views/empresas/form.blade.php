@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar empresa' : 'Agregar empresa', 'activeSection' => 'empresas'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .company-form-page { width:100%; }
    .company-form-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .company-form-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .company-form-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .company-form-field { margin-bottom:0; }
    .company-form-error { display:block; margin-top:.35rem; color:#dc2626; font-size:.775rem; }
    .company-form-input-error { border-color:#ef4444; }
    .company-form-feedback { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(239,68,68,.2); border-radius:.625rem; color:#b42318; background:rgba(239,68,68,.07); font-size:.845rem; }
    .company-logo-preview { position:relative; display:grid; place-items:center; width:88px; height:88px; flex:none; overflow:hidden; border:1px solid var(--body-border); border-radius:1rem; color:var(--color-primary); background:var(--color-primary-light); }
    .company-logo-preview img { position:absolute; inset:0; display:block; width:100%; height:100%; object-fit:contain; background:var(--card-bg); }
    .company-logo-preview svg { width:32px; height:32px; stroke:currentColor; stroke-width:1.6; }
    .company-logo-row { display:flex; align-items:center; gap:1rem; padding-bottom:1.25rem; margin-bottom:1.25rem; border-bottom:1px solid var(--body-border); }
    .company-logo-actions { min-width:0; }
    .company-logo-actions p { margin:.35rem 0 0; color:var(--body-text-muted); font-size:.775rem; }
    .company-logo-file { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    .company-logo-file:focus-visible + .company-logo-label { outline:0; box-shadow:0 0 0 3px rgba(79,110,247,.22); }
    .company-logo-filename { display:block; max-width:360px; overflow:hidden; color:var(--body-text-muted); font-size:.775rem; text-overflow:ellipsis; white-space:nowrap; }
    .company-form-actions { display:flex; justify-content:flex-start; gap:.5rem; flex-wrap:wrap; padding-top:1.25rem; margin-top:1.25rem; border-top:1px solid var(--body-border); }
    .company-form-actions svg,.company-logo-label svg { width:16px; height:16px; stroke:currentColor; stroke-width:1.8; }
    .company-form-section { padding-top:1.25rem; margin-top:1.25rem; border-top:1px solid var(--body-border); }
    .company-form-section h3 { margin:0 0 .35rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .company-form-section p { margin:0 0 1rem; color:var(--body-text-muted); font-size:.825rem; }
    @media(max-width:575px) { .company-form-heading { align-items:stretch; flex-direction:column; } .company-logo-row { align-items:flex-start; } .company-logo-preview { width:68px; height:68px; } }
</style>

<section class="company-form-page">
    <header class="company-form-heading">
        <div><h1>{{ $isEdit ? 'Editar empresa' : 'Agregar empresa' }}</h1><p>{{ $isEdit ? 'Actualiza la información de la empresa.' : 'Registra los datos de una empresa.' }}</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('empresas.index') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    <div class="card-hr">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Datos de la empresa</h2>
                <p class="card-subtitle-hr">Completa los campos para guardar la información.</p>
            </div>
        </div>
        <div class="card-bd">
            <form method="POST" action="{{ $isEdit ? route('empresas.update', $empresa) : route('empresas.store') }}" enctype="multipart/form-data">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="company-logo-row">
                    <div class="company-logo-preview" id="logo-preview">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21h18M5 21V7l8-4v18m0-12h6v12M8 9v.01M8 12v.01M8 15v.01M8 18v.01M16 13v.01M16 16v.01M16 19v.01"/></svg>
                        @if($empresa->logo)
                            <img id="logo-preview-image" src="{{ $empresa->logoUrl() }}" alt="Logotipo actual" width="88" height="88" onerror="this.remove()">
                        @endif
                    </div>
                    <div class="company-logo-actions">
                        <input class="company-logo-file" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="logo-help logo-filename">
                        <label class="btn-hr btn-outline-hr btn-sm-hr company-logo-label" for="logo">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
                            {{ $empresa->logo ? 'Cambiar logotipo' : 'Agregar logotipo' }}
                        </label>
                        <p id="logo-help">JPG, PNG o WebP. Tamaño máximo: 5 MB.</p>
                        <span class="company-logo-filename" id="logo-filename" aria-live="polite"></span>
                        @error('logo') <span class="company-form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4 col-md-6 company-form-field">
                        <label class="form-label-hr" for="rfc">RFC <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('rfc') company-form-input-error @enderror" id="rfc" name="rfc" type="text" value="{{ old('rfc', $empresa->rfc) }}" minlength="12" maxlength="13" required autocomplete="off">
                        @error('rfc') <span class="company-form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-lg-8 col-md-6 company-form-field">
                        <label class="form-label-hr" for="razon_social">Razón social <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('razon_social') company-form-input-error @enderror" id="razon_social" name="razon_social" type="text" value="{{ old('razon_social', $empresa->razon_social) }}" maxlength="255" required autocomplete="organization">
                        @error('razon_social') <span class="company-form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-lg-6 col-md-12 company-form-field">
                        <label class="form-label-hr" for="telefono">Teléfono</label>
                        <input class="hr-input @error('telefono') company-form-input-error @enderror" id="telefono" name="telefono" type="tel" value="{{ old('telefono', $empresa->telefono) }}" maxlength="100" autocomplete="tel">
                        @error('telefono') <span class="company-form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-lg-3 col-md-6 company-form-field">
                        <label class="form-label-hr" for="minutos_tolerancia_entrada">Minutos de tolerancia <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('minutos_tolerancia_entrada') company-form-input-error @enderror" id="minutos_tolerancia_entrada" name="minutos_tolerancia_entrada" type="number" min="1" step="1" value="{{ old('minutos_tolerancia_entrada', $empresa->minutos_tolerancia_entrada) }}" required>
                        @error('minutos_tolerancia_entrada') <span class="company-form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-lg-3 col-md-6 company-form-field">
                        <label class="form-label-hr" for="metros_distancia_entrada">Metros de tolerancia <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="hr-input @error('metros_distancia_entrada') company-form-input-error @enderror" id="metros_distancia_entrada" name="metros_distancia_entrada" type="number" min="1" step="1" value="{{ old('metros_distancia_entrada', $empresa->metros_distancia_entrada) }}" required>
                        @error('metros_distancia_entrada') <span class="company-form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <section class="company-form-section" aria-labelledby="fiscal-details-heading">
                    <h3 id="fiscal-details-heading">Domicilio fiscal</h3>
                    <p>Completa tus datos fiscales para facilitar la emisión de facturas por tus pagos de Horalia</p>
                    <div class="row g-3">
                        @php
                            $fiscalFields = [
                                'domicilio_calle' => ['Calle', 'text', 255, 'address-line1', 'col-md-8'],
                                'domicilio_numero_exterior' => ['Número exterior', 'text', 30, 'off', 'col-md-2'],
                                'domicilio_numero_interior' => ['Número interior', 'text', 30, 'off', 'col-md-2'],
                                'domicilio_colonia' => ['Colonia', 'text', 150, 'address-level3', 'col-md-6'],
                                'domicilio_municipio' => ['Alcaldía / Municipio', 'text', 150, 'address-level2', 'col-md-6'],
                                'domicilio_ciudad' => ['Ciudad', 'text', 150, 'address-level2', 'col-md-5'],
                            ];
                        @endphp
                        @foreach($fiscalFields as $name => $field)
                            @php [$label, $type, $maxlength, $autocomplete] = array_slice($field, 0, 4); @endphp
                            <div class="{{ $field[4] ?? 'col-lg-4 col-md-6' }} company-form-field">
                                <label class="form-label-hr" for="{{ $name }}">{{ $label }}</label>
                                <input class="hr-input @error($name) company-form-input-error @enderror" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $empresa->{$name}) }}" maxlength="{{ $maxlength }}" autocomplete="{{ $autocomplete }}">
                                @error($name) <span class="company-form-error">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                        <div class="col-md-3 company-form-field">
                            <label class="form-label-hr" for="domicilio_codigo_postal">Código postal fiscal</label>
                            <input class="hr-input @error('domicilio_codigo_postal') company-form-input-error @enderror" id="domicilio_codigo_postal" name="domicilio_codigo_postal" type="text" inputmode="numeric" pattern="[0-9]{5}" minlength="5" maxlength="5" value="{{ old('domicilio_codigo_postal', $empresa->domicilio_codigo_postal) }}" autocomplete="postal-code">
                            @error('domicilio_codigo_postal') <span class="company-form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4 company-form-field">
                            <label class="form-label-hr" for="domicilio_estado">Estado</label>
                            <select class="hr-input select2 @error('domicilio_estado') company-form-input-error @enderror" id="domicilio_estado" name="domicilio_estado" autocomplete="address-level1">
                                <option value="">Selecciona un estado</option>
                                @foreach($entidadesFederativas as $estado)
                                    <option value="{{ $estado }}" @selected(old('domicilio_estado', $empresa->domicilio_estado) === $estado)>{{ $estado }}</option>
                                @endforeach
                            </select>
                            @error('domicilio_estado') <span class="company-form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 company-form-field">
                            <label class="form-label-hr" for="regimen_fiscal">Régimen fiscal</label>
                            <select class="hr-input select2 @error('regimen_fiscal') company-form-input-error @enderror" id="regimen_fiscal" name="regimen_fiscal">
                                <option value="">Selecciona un régimen</option>
                                @foreach($regimenesFiscales as $clave => $regimen)
                                    <option value="{{ $clave }}" @selected((string) old('regimen_fiscal', $empresa->regimen_fiscal) === (string) $clave)>{{ $clave }} — {{ $regimen['descripcion'] }}</option>
                                @endforeach
                            </select>
                            @error('regimen_fiscal') <span class="company-form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 company-form-field">
                            <label class="form-label-hr" for="correo_facturacion">Correo de facturación</label>
                            <input class="hr-input @error('correo_facturacion') company-form-input-error @enderror" id="correo_facturacion" name="correo_facturacion" type="email" value="{{ old('correo_facturacion', $empresa->correo_facturacion) }}" maxlength="255" autocomplete="email">
                            @error('correo_facturacion') <span class="company-form-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                <div class="company-form-actions">
                    <button class="btn-hr btn-primary-hr" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h9"/></svg>
                        {{ $isEdit ? 'Guardar cambios' : 'Guardar empresa' }}
                    </button>
                    <a class="btn-hr btn-outline-hr" href="{{ route('empresas.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    document.getElementById('logo')?.addEventListener('change', function () {
        const file = this.files?.[0];
        document.getElementById('logo-filename').textContent = file?.name ?? '';
        if (!file) return;
        const preview = document.getElementById('logo-preview');
        let image = document.getElementById('logo-preview-image');
        if (!image) {
            image = document.createElement('img');
            image.id = 'logo-preview-image';
            image.alt = 'Vista previa del logotipo';
            image.width = 88;
            image.height = 88;
            preview.appendChild(image);
        }
        image.src = URL.createObjectURL(file);
    });
</script>
@endsection
