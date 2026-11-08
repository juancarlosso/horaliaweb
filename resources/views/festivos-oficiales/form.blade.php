@extends('layouts.dashboard', ['pageTitle' => $isEdit ? 'Editar festivo' : 'Agregar festivo', 'activeSection' => 'festivos-oficiales'])

@section('content')
<style>
    .holiday-form-page { max-width:760px; }
    .holiday-form-heading { margin-bottom:1.25rem; }
    .holiday-form-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .holiday-form-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .holiday-field { margin-bottom:1rem; }
    .holiday-field label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .holiday-error { display:block; margin-top:.3rem; color:#dc2626; font-size:.78rem; }
    .holiday-form-actions { display:flex; justify-content:flex-end; gap:.6rem; margin-top:1.25rem; }
</style>
<section class="holiday-form-page">
    <header class="holiday-form-heading"><h1>{{ $isEdit ? 'Editar festivo' : 'Agregar festivo' }}</h1><p>Captura la fecha y su conmemoración.</p></header>
    <div class="card-hr"><div class="card-bd">
        <form method="POST" action="{{ $isEdit ? route('festivos-oficiales.update', $festivo) : route('festivos-oficiales.store') }}">
            @csrf @if($isEdit) @method('PUT') @endif
            <div class="holiday-field"><label for="fecha">Fecha</label><input class="hr-input @error('fecha') is-invalid @enderror" id="fecha" name="fecha" type="date" value="{{ old('fecha', $festivo->fecha?->format('Y-m-d')) }}" required>@error('fecha')<span class="holiday-error">{{ $message }}</span>@enderror</div>
            <div class="holiday-field"><label for="conmemoracion">Conmemoración</label><input class="hr-input @error('conmemoracion') is-invalid @enderror" id="conmemoracion" name="conmemoracion" type="text" maxlength="150" value="{{ old('conmemoracion', $festivo->conmemoracion) }}" required>@error('conmemoracion')<span class="holiday-error">{{ $message }}</span>@enderror</div>
            <div class="holiday-form-actions"><a class="btn-hr btn-outline-hr" href="{{ route('festivos-oficiales.index', ['anio' => old('fecha', $festivo->fecha?->format('Y')) ?: now()->year]) }}">Cancelar</a><button class="btn-hr btn-primary-hr" type="submit">{{ $isEdit ? 'Guardar cambios' : 'Agregar festivo' }}</button></div>
        </form>
    </div></div>
</section>
@endsection
