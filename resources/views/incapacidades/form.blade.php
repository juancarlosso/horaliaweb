@extends('layouts.dashboard', ['pageTitle' => 'Registrar incapacidad', 'activeSection' => 'incapacidades'])

@section('content')
<section class="page-inner" style="width:100%;max-width:none">
    <div class="card-hr">
        <div class="card-hd d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h1 class="card-title-hr">Registrar incapacidad</h1>
            <a class="btn-hr btn-outline-hr" href="{{ route('incapacidades.index') }}"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-left" aria-hidden="true"></i> Regresar</a>
        </div>
        <div class="card-bd">
        <form id="incapacity-form" method="POST" action="{{ route('incapacidades.store') }}" enctype="multipart/form-data">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label-hr" for="empresa_id">Empresa <span class="text-danger" aria-hidden="true">*</span></label><select class="hr-input select2" id="empresa_id" name="empresa_id" required><option value="">Selecciona una empresa</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)old('empresa_id', $companyId)===(string)$company->id)>{{ $company->nombre }}</option>@endforeach</select>@error('empresa_id')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-6"><label class="form-label-hr" for="personal_id">Colaborador <span class="text-danger" aria-hidden="true">*</span></label><select class="hr-input select2" id="personal_id" name="personal_id" required><option value="">Selecciona un colaborador</option>@foreach($personal as $person)<option value="{{ $person->id }}" @selected((string)old('personal_id')===(string)$person->id)>{{ $person->nombre }}</option>@endforeach</select>@error('personal_id')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-3"><label class="form-label-hr" for="fecha_inicio">Desde <span class="text-danger" aria-hidden="true">*</span></label><input class="hr-input" type="date" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio') }}" required>@error('fecha_inicio')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-3"><label class="form-label-hr" for="fecha_fin">Hasta <span class="text-danger" aria-hidden="true">*</span></label><input class="hr-input" type="date" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin') }}" required>@error('fecha_fin')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-6"><label class="form-label-hr" for="motivo">Motivo <span class="text-danger" aria-hidden="true">*</span></label><select class="hr-input" id="motivo" name="motivo" required><option value="">Selecciona un motivo</option>@foreach(['Enfermedad general','Riesgo de trabajo','Maternidad'] as $reason)<option value="{{ $reason }}" @selected(old('motivo')===$reason)>{{ $reason }}</option>@endforeach</select>@error('motivo')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-12"><label class="form-label-hr" for="observaciones">Observaciones</label><textarea class="hr-input" id="observaciones" name="observaciones" rows="3" maxlength="5000">{{ old('observaciones') }}</textarea>@error('observaciones')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-12"><label class="form-label-hr" for="comprobantes">Comprobantes (opcional, máximo 3)</label><input class="hr-input" id="comprobantes" name="comprobantes[]" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple><small class="text-muted d-block mt-1">PDF o imagen JPG, PNG o WebP. Hasta {{ number_format((int)config('filesystems.personal_document_max_kb',10240)/1024, 0) }} MB por archivo.</small>@error('comprobantes')<small class="text-danger d-block">{{ $message }}</small>@enderror @error('comprobantes.*')<small class="text-danger d-block">{{ $message }}</small>@enderror</div>
            </div>
            <div id="schedule-note" class="mt-4 p-3 rounded border text-muted" aria-live="polite">Al guardar se conservará el calendario laboral usado para calcular las jornadas afectadas.</div>
            <div class="d-flex gap-2 mt-4"><button class="btn-hr btn-primary-hr" type="submit">Registrar incapacidad</button><a class="btn-hr btn-outline-hr" href="{{ route('incapacidades.index') }}">Cancelar</a></div>
        </form>
    </div></div>
</section>
<script>
(() => {
    const form=document.getElementById('incapacity-form'), company=document.getElementById('empresa_id'), files=document.getElementById('comprobantes');
    company.addEventListener('change',function(){const url=new URL(@json(route('incapacidades.create')),window.location.origin);if(this.value)url.searchParams.set('empresa_id',this.value);window.location.assign(url);});
    files.addEventListener('change',function(){if(this.files.length>3){this.value='';Swal.fire({icon:'error',title:'Demasiados comprobantes',text:'Puedes adjuntar hasta tres archivos.',confirmButtonText:'Entendido',buttonsStyling:false,customClass:{confirmButton:'btn-hr btn-primary-hr'}});}});
    form.addEventListener('submit',function(event){event.preventDefault();Swal.fire({icon:'question',title:'¿Registrar incapacidad?',text:'Se guardará el periodo junto con la configuración laboral vigente al momento del registro.',showCancelButton:true,confirmButtonText:'Sí, registrar',cancelButtonText:'Revisar',buttonsStyling:false,reverseButtons:true,customClass:{confirmButton:'btn-hr btn-primary-hr',cancelButton:'btn-hr btn-outline-hr'}}).then(function(result){if(result.isConfirmed)HTMLFormElement.prototype.submit.call(form);});});
})();
</script>
@endsection
