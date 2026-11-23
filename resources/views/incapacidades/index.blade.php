@extends('layouts.dashboard', ['pageTitle' => 'Incapacidades', 'activeSection' => 'incapacidades'])

@section('content')
@include('components.datatable-assets')
@include('components.administrative-catalog-styles')
<style>
    .vacation-detail-modal { max-height:65vh; overflow:auto; padding:.75rem 1rem; border:1px solid #d7dee8; border-radius:.45rem; text-align:left; }
    .vacation-detail-heading { display:block; margin-bottom:.45rem; color:#252a32; font-size:.95rem; font-weight:700; }
    @media(max-width:700px) { .catalog-footer { align-items:flex-start; flex-direction:column; } }
    .catalog-state { display:inline-flex; align-items:center; gap:.4rem; padding:.28rem .55rem; border-radius:99px; font-size:.75rem; font-weight:650; }
    .catalog-state::before { width:6px; height:6px; border-radius:50%; background:currentColor; content:""; }
    .catalog-state-active { color:#087f5b; background:rgba(16,185,129,.1); }
    .catalog-state-inactive { color:#b42318; background:rgba(239,68,68,.09); }
    .vacation-detail-row { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:.35rem 0; border-top:1px solid #d7dee8; font-size:.85rem; text-align:left; }
    .vacation-detail-date { min-width:0; color:#303640; overflow-wrap:anywhere; }
    .vacation-detail-result { flex:none; text-align:right; }
    .vacation-detail-counted { color:#079455; }
    .vacation-detail-excluded { color:#667085; }
    @media(max-width:600px) { .vacation-detail-row { align-items:flex-start; flex-direction:column; gap:.15rem; } .vacation-detail-result { text-align:left; } }
</style>

<section class="catalog-page">
    <div class="catalog-toolbar">
        <div class="catalog-filter">
            <label for="empresa_id">Empresa</label>
            <select class="hr-input select2" id="empresa_id" name="empresa_id">
                @if($companies->count() !== 1)<option value="0" @selected(!$companyId)>Todas las empresas</option>@endif
                @foreach($companies as $company)<option value="{{ $company->id }}" @selected($companyId === (int)$company->id)>{{ $company->nombre }}</option>@endforeach
            </select>
        </div>
        <div class="catalog-filter">
            <label for="personal_id">Personal</label>
            <select class="hr-input select2" id="personal_id" name="personal_id">
                <option value="0" @selected(!$personalId)>Todo el personal</option>
                @foreach($personal as $person)<option value="{{ $person->id }}" @selected($personalId === $person->id)>{{ $person->nombre }}@if($companies->count() !== 1) — {{ $person->empresa?->nombre }}@endif</option>@endforeach
            </select>
        </div>
        @if($canManage)<a class="btn-hr btn-primary-hr catalog-toolbar-action" href="{{ route('incapacidades.create', $companyId ? ['empresa_id'=>$companyId] : []) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-plus" aria-hidden="true"></i> Registrar incapacidad</a>@endif
    </div>

    <div class="card-hr">
        <div class="card-hd"><div><h1 class="card-title-hr">Listado de incapacidades</h1><p class="card-subtitle-hr">{{ $records->count() }} {{ $records->count() === 1 ? 'registro' : 'registros' }}</p></div></div>
        @if($records->count())
            <div class="table-wrap"><table class="bs-table hover" data-datatable="{{ config('constantes.itemsPorPagina') }}">
                <thead><tr><th>Empresa</th><th>Colaborador</th><th>Periodo</th><th>Motivo</th><th>Jornadas</th><th>Estado</th><th class="no-search text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach($records as $record)
                    @php($recordInfo = [
                        'id' => '#'.$record->id, 'empresa' => $record->empresa?->nombre ?? '—', 'colaborador' => $record->personal?->nombre ?? '—',
                        'desde' => $record->fecha_inicio->format('d/m/Y'), 'hasta' => $record->fecha_fin->format('d/m/Y'), 'motivo' => $record->motivo,
                        'jornadas' => $record->jornadas_afectadas, 'observaciones' => $record->observaciones,
                        'estado' => $record->estado === 'vigente' ? 'Vigente' : 'Cancelada', 'registrado_por' => $record->registradoPor?->name ?? '—',
                        'registrado_en' => $record->created_at?->format('d/m/Y H:i') ?? '—', 'cancelado_por' => $record->canceladoPor?->name ?? '—',
                        'cancelado_en' => $record->cancelado_en?->format('d/m/Y H:i') ?? '—', 'snapshot_en' => $record->snapshot_generado_en?->format('d/m/Y H:i') ?? '—',
                        'dias' => $record->snapshot['fechas'] ?? [], 'comprobantes' => $record->comprobantes->map(fn($file) => [
                            'nombre' => $file->nombre_original, 'descargar' => route('incapacidades.comprobantes.download',[$record,$file]),
                        ])->all(),
                    ])
                    <tr>
                        <td>{{ $record->empresa?->nombre ?? '—' }}</td>
                        <td>{{ $record->personal?->nombre ?? '—' }}</td>
                        <td data-order="{{ $record->fecha_inicio->toDateString() }}">{{ $record->fecha_inicio->format('d/m/Y') }} – {{ $record->fecha_fin->format('d/m/Y') }}</td>
                        <td>{{ $record->motivo }}</td>
                        <td>{{ $record->jornadas_afectadas }}</td>
                        <td><span class="catalog-state {{ $record->estado === 'vigente' ? 'catalog-state-active' : 'catalog-state-inactive' }}">{{ $record->estado === 'vigente' ? 'Vigente' : 'Cancelada' }}</span></td>
                        <td><div class="catalog-actions">
                            <button type="button" class="vacation-info-button" data-incapacity-info="{{ json_encode($recordInfo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" aria-label="Información de la incapacidad de {{ $record->personal?->nombre }}" title="Información"><i class="{{ config('constantes.tipo_iconos') }} fa-circle-info" aria-hidden="true"></i></button>
                            @if($canManage && $record->estado === 'vigente')
                                <form method="POST" action="{{ route('incapacidades.cancel',$record) }}" data-confirm-incapacity-cancel data-employee="{{ $record->personal?->nombre }}">@csrf @method('DELETE')<button class="btn-hr btn-danger-hr btn-sm-hr" type="submit"><i class="{{ config('constantes.tipo_iconos') }} fa-ban" aria-hidden="true"></i> Cancelar</button></form>
                            @else<span class="company-rfc">—</span>@endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @else
            <div class="catalog-empty"><h2>Aún no hay incapacidades</h2><p>@if($canManage)Registra una incapacidad para que aparezca en este listado.@else>No hay incapacidades registradas para las empresas seleccionadas.@endif</p>@if($canManage)<a class="btn-hr btn-primary-hr" href="{{ route('incapacidades.create', $companyId ? ['empresa_id'=>$companyId] : []) }}">Registrar incapacidad</a>@endif</div>
        @endif
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.jQuery('#empresa_id').on('change', function () {
            const url = new URL(@json(route('incapacidades.index')), window.location.origin);
            if (this.value !== '0') url.searchParams.set('empresa_id', this.value);
            window.location.assign(url);
        });
        window.jQuery('#personal_id').on('change', function () {
            const url = new URL(@json(route('incapacidades.index')), window.location.origin);
            const companyId = document.getElementById('empresa_id').value;
            if (companyId !== '0') url.searchParams.set('empresa_id', companyId);
            if (this.value !== '0') url.searchParams.set('personal_id', this.value);
            window.location.assign(url);
        });
    });
    document.querySelectorAll('[data-confirm-incapacity-cancel]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            Swal.fire({ icon:'warning', title:'¿Cancelar la incapacidad?', text:'Se cancelará el registro de '+form.dataset.employee+'. El historial y los comprobantes se conservarán.', showCancelButton:true, confirmButtonText:'Sí, cancelar', cancelButtonText:'Volver', buttonsStyling:false, reverseButtons:true, customClass:{confirmButton:'btn-hr btn-danger-hr',cancelButton:'btn-hr btn-outline-hr'} }).then(function (result) { if (result.isConfirmed) form.submit(); });
        });
    });
    function showIncapacityInfo(info) {
        const content = document.createElement('div');
        const grid = document.createElement('div'); grid.className = 'vacation-info-grid';
        [['Folio interno',info.id],['Empresa',info.empresa],['Colaborador',info.colaborador],['Desde',info.desde],['Hasta',info.hasta],['Motivo',info.motivo],['Jornadas afectadas',info.jornadas],['Registró',info.registrado_por],['Fecha de registro',info.registrado_en],['Estado',info.estado],['Canceló',info.cancelado_por],['Fecha de cancelación',info.cancelado_en],['Snapshot generado',info.snapshot_en],['Observaciones',info.observaciones||'—']].forEach(function(field){const item=document.createElement('div');item.className='vacation-info-item';const label=document.createElement('strong');label.textContent=field[0];const value=document.createElement('span');value.textContent=field[1]??'—';item.append(label,value);grid.append(item);});
        content.append(grid);
        const filesPanel=document.createElement('div');filesPanel.className='vacation-detail-modal';
        const filesHeading=document.createElement('strong');filesHeading.className='vacation-detail-heading';filesHeading.textContent='Comprobantes';filesPanel.append(filesHeading);
        if(info.comprobantes.length){info.comprobantes.forEach(function(file){const row=document.createElement('div');row.className='vacation-detail-row';const name=document.createElement('span');name.className='vacation-detail-date';name.textContent=file.nombre;const download=document.createElement('a');download.href=file.descargar;download.className='btn-hr btn-outline-hr btn-sm-hr';download.textContent='Descargar';row.append(name,download);filesPanel.append(row);});}else{const row=document.createElement('div');row.className='vacation-detail-row';const empty=document.createElement('span');empty.className='vacation-detail-date';empty.textContent='Sin comprobantes.';row.append(empty);filesPanel.append(row);}content.append(filesPanel);
        const panel=document.createElement('div');panel.className='vacation-detail-modal mt-3';
        const heading=document.createElement('strong');heading.className='vacation-detail-heading';heading.textContent=info.jornadas+' jornada(s) afectada(s) · Snapshot generado '+info.snapshot_en;panel.append(heading);
        info.dias.forEach(function(day){const row=document.createElement('div');row.className='vacation-detail-row';const date=document.createElement('span');date.className='vacation-detail-date';const [year,month,dayOfMonth]=day.fecha.slice(0,10).split('-');const holidayNames=(day.festivos||[]).map(function(holiday){return holiday.nombre;});date.textContent=[dayOfMonth,month,year].join('/')+(holidayNames.length?' · '+holidayNames.join(', '):'');const result=document.createElement('span');result.className='vacation-detail-result '+(day.laborable?'vacation-detail-counted':'vacation-detail-excluded');result.textContent=(day.laborable?'Jornada afectada':'No afecta')+' · '+day.motivo;row.append(date,result);panel.append(row);});content.append(panel);
        Swal.fire({title:'Información del registro',html:content,width:'min(900px, calc(100vw - 2rem))',confirmButtonText:'Cerrar',buttonsStyling:false,customClass:{confirmButton:'btn-hr btn-primary-hr'}});
    }
    document.querySelectorAll('[data-incapacity-info]').forEach(function(button){button.addEventListener('click',function(){showIncapacityInfo(JSON.parse(button.dataset.incapacityInfo));});});
</script>
@endsection
