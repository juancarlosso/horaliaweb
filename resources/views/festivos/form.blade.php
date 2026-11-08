@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar festivo empresarial' : 'Agregar festivo empresarial', 'activeSection' => 'festivos'])

@section('content')
<style>
    .company-holiday-form { max-width:800px; }
    .company-holiday-heading { margin-bottom:1.25rem; }
    .company-holiday-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .company-holiday-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .company-holiday-field { margin-bottom:1rem; }
    .company-holiday-field label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .company-holiday-error { display:block; margin-top:.3rem; color:#dc2626; font-size:.78rem; }
    .company-holiday-actions { display:flex; gap:.6rem; margin-top:1.25rem; padding-top:1.1rem; border-top:1px solid var(--body-border); }
</style>
<section class="company-holiday-form">
    <header class="company-holiday-heading"><h1>{{ $isEdit ? 'Editar festivo empresarial' : 'Agregar festivo empresarial' }}</h1><p>Este festivo solo aplicará a la empresa seleccionada.</p></header>
    <div class="card-hr"><div class="card-bd"><form method="POST" action="{{ $isEdit ? route('festivos.update', $festivo) : route('festivos.store') }}">
        @csrf @if($isEdit) @method('PUT') @endif
        <div class="company-holiday-field"><label for="empresa_id">Empresa</label><select class="hr-input select2 @error('empresa_id') is-invalid @enderror" id="empresa_id" name="empresa_id" required>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $selectedCompanyId) === (string) $company->id)>{{ $company->nombre }}</option>@endforeach</select>@error('empresa_id')<span class="company-holiday-error">{{ $message }}</span>@enderror</div>
        <div class="row g-3"><div class="col-md-5"><div class="company-holiday-field"><label for="fecha">Fecha</label><input class="hr-input @error('fecha') is-invalid @enderror" id="fecha" name="fecha" type="date" value="{{ old('fecha', $festivo->fecha?->format('Y-m-d')) }}" required>@error('fecha')<span class="company-holiday-error">{{ $message }}</span>@enderror</div></div><div class="col-md-7"><div class="company-holiday-field"><label for="conmemoracion">Conmemoración</label><input class="hr-input @error('conmemoracion') is-invalid @enderror" id="conmemoracion" name="conmemoracion" type="text" maxlength="150" value="{{ old('conmemoracion', $festivo->conmemoracion) }}" required>@error('conmemoracion')<span class="company-holiday-error">{{ $message }}</span>@enderror</div></div></div>
        <div class="company-holiday-actions"><button class="btn-hr btn-primary-hr" type="submit"><i class="{{ config('constantes.tipo_iconos') }} fa-floppy-disk" aria-hidden="true"></i> {{ $isEdit ? 'Guardar cambios' : 'Agregar festivo' }}</button><a class="btn-hr btn-outline-hr" href="{{ route('festivos.index', array_filter(['empresa_id' => $selectedCompanyId, 'anio' => request('anio', $festivo->fecha?->format('Y') ?? now()->year)])) }}">Cancelar</a></div>
    </form></div></div>
</section>
@endsection
