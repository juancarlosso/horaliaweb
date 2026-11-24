<div class="{{ $buscador ? 'col-sm-6 col-lg-4' : 'catalog-filter' }}">
    <label for="empresa_id" class="form-label-hr">Empresa @if($buscador)<span class="text-danger" aria-hidden="true">*</span>@endif</label>
    <select name="empresa_id" id="empresa_id" class="hr-input select2" @required($buscador)>
        @if($empresas->count() > 1)
            <option value="{{ $buscador ? '' : '0' }}">{{ $buscador ? 'Selecciona una empresa' : 'Todas las empresas' }}</option>
        @endif
        @foreach($empresas as $opcion)
            <option value="{{ $opcion->id }}" @selected($empresa === (int) $opcion->id)>{{ $opcion->nombre }}</option>
        @endforeach
    </select>
</div>
