@extends('layouts.dashboard', ['pageTitle' => 'Validar Transferencias', 'activeSection' => 'transferencias-admin'])
@section('content')
@include('components.datatable-assets')
<style>
    .tv-page h1 { margin:0; font-size:1.5rem; font-weight:800; color:var(--body-text); }
    .tv-subtitle { margin:.35rem 0 1.2rem; color:var(--body-text-muted); font-size:.875rem; }
    .tv-filters { display:flex; align-items:end; flex-wrap:wrap; gap:.75rem; margin-bottom:1.2rem; }
    .tv-field { display:grid; gap:.35rem; min-width:190px; color:var(--body-text); font-size:.85rem; font-weight:700; }
    .tv-search { flex:1; } .tv-table-wrap { overflow-x:auto; } .tv-table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .tv-table th,.tv-table td { padding:.85rem .7rem; border-bottom:1px solid var(--body-border); text-align:left; vertical-align:middle; }
    .tv-table th { color:var(--body-text-muted); white-space:nowrap; font-size:.78rem; } .tv-table td { color:var(--body-text); }
    .tv-badge { display:inline-block; padding:.3rem .6rem; border-radius:999px; font-size:.75rem; font-weight:700; white-space:nowrap; background:#fff3d6; color:#8c5a00; }
    .tv-badge.confirmado { background:#e4f8ed; color:#087a42; } .tv-badge.rechazado { background:#fff0ef; color:#ad3030; }
    .tv-icon-action { display:inline-grid; place-items:center; width:28px; height:28px; padding:0; border:1px solid var(--body-border); border-radius:.45rem; color:var(--body-text-muted); background:var(--card-bg); text-decoration:none !important; }
    .tv-icon-action:hover { border-color:var(--color-primary); color:var(--color-primary); }
    .tv-icon-action i { font-size:.8rem; }
    .tv-empty { padding:3rem 1rem; text-align:center; color:var(--body-text-muted); }
    .tv-feedback { padding:.8rem 1rem; margin-bottom:1rem; border-radius:.6rem; background:#e7f8ee; color:#126236; }
    .tv-feedback.error { background:#fff0ef; color:#a12e2e; }
    .tv-dialog { position:fixed; inset:0; margin:auto; width:min(700px, calc(100% - 2rem)); max-height:calc(100dvh - 2rem); overflow:auto; padding:0; border:1px solid var(--body-border); border-radius:1rem; background:var(--card-bg); color:var(--body-text); box-shadow:0 20px 70px rgba(20,20,50,.3); }
    .tv-dialog::backdrop { background:rgba(15,19,38,.6); } .tv-modal-head,.tv-modal-body { padding:1.25rem 1.5rem; } .tv-modal-head { display:flex; justify-content:space-between; gap:1rem; border-bottom:1px solid var(--body-border); }
    .tv-modal-head h2 { margin:0 0 .4rem; font-size:1.2rem; } .tv-close { border:0; background:transparent; color:var(--body-text); font-size:1.5rem; cursor:pointer; }
    .tv-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.9rem; margin:0 0 1rem; } .tv-details dt { color:var(--body-text-muted); font-size:.75rem; } .tv-details dd { margin:.2rem 0 0; font-weight:700; overflow-wrap:anywhere; }
    .tv-notice { padding:.8rem; margin:1rem 0; border-radius:.6rem; background:#fff7df; color:#765100; font-size:.85rem; }
    .tv-form-fields { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; } .tv-form-fields label,.tv-reject label { display:grid; gap:.35rem; font-size:.82rem; font-weight:700; }
    .tv-actions { display:flex; flex-wrap:wrap; gap:.65rem; margin-top:1rem; } .tv-reject { margin-top:1rem; padding-top:1rem; border-top:1px solid var(--body-border); } .tv-reject textarea { min-height:85px; }
    @media(max-width:600px) { .tv-details,.tv-form-fields { grid-template-columns:1fr; } .tv-modal-head,.tv-modal-body { padding:1rem; } .tv-field { width:100%; } }
</style>
<section class="tv-page">
    <h1>Validar Transferencias</h1><p class="tv-subtitle">Consulta los comprobantes y revisa los pagos recibidos.</p>
    @if(session('status'))<div class="tv-feedback" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="tv-feedback error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="card-hr"><div class="card-bd">
        <form class="tv-filters" method="GET" action="{{ route('transferencias.admin.index') }}">
            <label class="tv-field">Estado<select class="hr-input" name="estado">@foreach(['pendiente_de_confirmar' => 'Pendientes', 'rechazado' => 'Rechazadas', 'confirmado' => 'Confirmadas', 'todas' => 'Todas'] as $key => $label)<option value="{{ $key }}" @selected(request('estado', 'pendiente_de_confirmar') === $key)>{{ $label }}</option>@endforeach</select></label>
            <label class="tv-field tv-search">Buscar<input class="hr-input" type="search" name="buscar" value="{{ request('buscar') }}" maxlength="100" placeholder="Empresa o concepto"></label>
            <button class="btn-hr btn-primary-hr" type="submit">Buscar</button>
        </form>
        @if($records->isEmpty())<div class="tv-empty">{{ request('estado', 'pendiente_de_confirmar') === 'pendiente_de_confirmar' && !request('buscar') ? '✅ No hay transferencias pendientes de confirmar' : 'No se encontraron transferencias con estos filtros.' }}</div>
        @else
        <div class="tv-table-wrap"><table class="tv-table" data-datatable="{{ config('constantes.itemsPorPagina') }}"><thead><tr><th>Empresa</th><th>Concepto</th><th>Importe esperado</th><th>Vencimiento</th><th>Fecha de carga</th><th>Estado</th><th class="no-search">Acción</th></tr></thead><tbody>
        @foreach($records as $record)
            <tr><td>{{ $record->empresa->nombre }}</td><td>{{ $record->concepto }}</td><td data-order="{{ $record->cantidad }}">${{ number_format((float) $record->cantidad, 2) }} MXN</td><td data-order="{{ $record->fecha_renovacion->toDateString() }}">{{ $record->fecha_renovacion->format('d/m/Y') }}</td><td data-order="{{ $record->cargado_en?->format('Y-m-d H:i:s') }}">{{ $record->cargado_en?->format('d/m/Y H:i') ?? '—' }}</td><td><span class="tv-badge {{ $record->estado }}">{{ ['pendiente_de_confirmar' => 'Pendiente de confirmar', 'rechazado' => 'Rechazado', 'confirmado' => 'Confirmado'][$record->estado] }}</span></td><td><div class="tv-actions" style="margin:0;flex-wrap:nowrap">@if($record->estado === 'pendiente_de_confirmar')<button type="button" class="btn-hr btn-primary-hr tv-open" data-dialog="transfer-dialog-{{ $record->id }}">Revisar</button>@endif @if($record->comprobanteActual)<a class="tv-icon-action" href="{{ route('transferencias.admin.file', $record->comprobanteActual->id) }}" target="_blank" rel="noopener" aria-label="Ver comprobante de {{ $record->empresa->nombre }}" title="Ver comprobante"><i class="fa-regular fa-eye" aria-hidden="true"></i></a><a class="tv-icon-action" href="{{ route('transferencias.admin.file', ['proof' => $record->comprobanteActual->id, 'descargar' => 1]) }}" aria-label="Descargar comprobante de {{ $record->empresa->nombre }}" title="Descargar comprobante"><i class="fa-solid fa-download" aria-hidden="true"></i></a>@elseif($record->estado !== 'pendiente_de_confirmar') — @endif</div></td></tr>
        @endforeach
        </tbody></table></div>
        @endif
    </div></div>
    @foreach($records as $record)
        @if($record->estado === 'pendiente_de_confirmar')
        <dialog class="tv-dialog" id="transfer-dialog-{{ $record->id }}" aria-labelledby="transfer-title-{{ $record->id }}">
            <div class="tv-modal-head"><div><h2 id="transfer-title-{{ $record->id }}">Revisar transferencia</h2><span>{{ $record->empresa->nombre }}</span> <span class="tv-badge">Pendiente de confirmar</span></div><button type="button" class="tv-close" aria-label="Cerrar">&times;</button></div>
            <div class="tv-modal-body">
                <dl class="tv-details"><div><dt>Empresa</dt><dd>{{ $record->empresa->nombre }}</dd></div><div><dt>Concepto permanente</dt><dd>{{ $record->concepto }}</dd></div><div><dt>Importe esperado</dt><dd>${{ number_format((float) $record->cantidad, 2) }} MXN</dd></div><div><dt>Fecha de vencimiento</dt><dd>{{ $record->fecha_renovacion->format('d/m/Y') }}</dd></div><div><dt>Fecha de carga</dt><dd>{{ $record->cargado_en?->format('d/m/Y H:i') ?? '—' }}</dd></div><div><dt>Enviado por</dt><dd>{{ $record->usuarioCarga?->name ?? 'No disponible' }}</dd></div></dl>
                @if($record->comprobanteActual)<a class="btn-hr btn-outline-hr" href="{{ route('transferencias.admin.file', $record->comprobanteActual->id) }}" target="_blank" rel="noopener">Abrir comprobante</a>@else<div class="tv-feedback error">No hay comprobante disponible para esta solicitud.</div>@endif
                <p class="tv-notice">Aprueba únicamente después de verificar en tu banco la recepción del depósito, su importe y el concepto.</p>
                <form method="POST" action="{{ route('transferencias.admin.review', $record->id) }}" class="tv-approve" data-amount="{{ number_format((float) $record->cantidad, 2) }}" data-concept="{{ $record->concepto }}">@csrf<input type="hidden" name="accion" value="confirmar">
                    <div class="tv-form-fields"><label>Importe recibido verificado (MXN)<input class="hr-input" name="importe_recibido" type="number" min="0.01" step="0.01" required></label><label>Concepto observado en el banco<input class="hr-input" name="concepto_recibido" maxlength="100" required></label></div>
                    <div class="tv-actions"><button class="btn-hr btn-primary-hr" type="submit" @disabled(!$record->comprobanteActual)>Aprobar pago</button><button class="btn-hr btn-outline-hr tv-show-reject" type="button">Rechazar comprobante</button><button class="btn-hr btn-outline-hr tv-cancel" type="button">Cerrar</button></div>
                </form>
                <form method="POST" action="{{ route('transferencias.admin.review', $record->id) }}" class="tv-reject" hidden>@csrf<input type="hidden" name="accion" value="rechazar"><label>Motivo del rechazo<textarea class="hr-input" name="motivo" maxlength="2000" required></textarea></label><div class="tv-actions"><button class="btn-hr btn-outline-hr" type="submit">Confirmar rechazo</button><button class="btn-hr btn-outline-hr tv-hide-reject" type="button">Cancelar</button></div></form>
            </div>
        </dialog>
        @endif
    @endforeach
</section>
<script>
(() => {
    let opener = null;
    document.querySelectorAll('.tv-open').forEach(button => button.addEventListener('click', () => { opener = button; document.getElementById(button.dataset.dialog).showModal(); }));
    document.querySelectorAll('.tv-dialog').forEach(dialog => {
        dialog.addEventListener('close', () => { if (opener) opener.focus(); });
        dialog.querySelectorAll('.tv-close, .tv-cancel').forEach(button => button.addEventListener('click', () => dialog.close()));
        const reject = dialog.querySelector('.tv-reject');
        dialog.querySelector('.tv-show-reject').addEventListener('click', () => { reject.hidden = false; reject.querySelector('textarea').focus(); });
        dialog.querySelector('.tv-hide-reject').addEventListener('click', () => { reject.hidden = true; dialog.querySelector('.tv-show-reject').focus(); });
        dialog.querySelectorAll('form').forEach(form => form.addEventListener('submit', async event => {
            event.preventDefault();
            const approve = form.classList.contains('tv-approve');
            const result = await Swal.fire({ target: dialog, title: approve ? '¿Confirmas que verificaste el pago?' : '¿Confirmas el rechazo?', text: approve ? `Verificaste la recepción de $${form.dataset.amount} MXN con concepto ${form.dataset.concept}. Al aprobar se renovará el servicio de esta empresa.` : 'Se notificará a los administradores de la empresa.', icon: 'warning', showCancelButton: true, confirmButtonText: approve ? 'Aprobar pago' : 'Rechazar comprobante', cancelButtonText: 'Cancelar', reverseButtons: true });
            if (result.isConfirmed) { form.querySelectorAll('button').forEach(button => button.disabled = true); form.submit(); }
        }));
    });
})();
</script>
@endsection
