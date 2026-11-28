@extends('layouts.dashboard', ['pageTitle' => 'Pago por transferencia', 'activeSection' => 'payment-history'])
@section('content')
<style>
    .tr-page { color:#191b46; display:grid; gap:1rem; font-size:.845rem; }
    .tr-hero,.tr-card { border:1px solid #e4e1fa; border-radius:1rem; box-shadow:0 5px 20px rgba(78,55,176,.08); }
    .tr-hero { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.3rem 1.5rem; background:linear-gradient(115deg,#f4f2ff,#e5e0ff); }
    .tr-hero-main,.tr-title { display:flex; align-items:center; gap:1rem; }
    .tr-icon { display:grid; place-items:center; width:52px; height:52px; flex:none; border-radius:.85rem; color:#5a35e8; background:#e9e4ff; font-size:1.35rem; }
    .tr-hero .tr-icon { color:#fff; background:linear-gradient(145deg,#9871ff,#5628e8); }
    .tr-hero h1 { margin:0 0 .25rem; font-size:1.1rem; font-weight:800; }
    .tr-hero p,.tr-title p { margin:0; color:#565b91; }
    .tr-amount { padding:.7rem 1rem; border:1px solid #fff; border-radius:2rem; color:#4d28ca; background:rgba(255,255,255,.7); font-weight:800; white-space:nowrap; }
    .tr-card { padding:1.4rem; background:#fff; }
    .tr-bank { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:center; }
    .tr-title h2 { margin:0; font-size:.95rem; font-weight:800; }
    .tr-bank-list { display:grid; grid-template-columns:max-content 1fr; gap:.45rem 1.5rem; margin:1rem 0 0; }
    .tr-bank-list dt { font-weight:700; } .tr-bank-list dd { margin:0; overflow-wrap:anywhere; }
    .tr-concept { padding:1.2rem; border-radius:.85rem; background:#eeeaff; color:#3215a0; }
    .tr-concept h2 { margin:0; font-size:.9rem; font-weight:800; }
    .tr-concept-main { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin:.6rem 0; }
    .tr-concept-value { margin:0; font-size:1.15rem; font-weight:800; overflow-wrap:anywhere; }
    .tr-concept p:last-child { margin:0; }
    .tr-warning { border:1px solid #ffd16a; background:#fff7df; }
    .tr-warning .tr-icon { color:#a96800; background:#ffecb6; }
    .tr-warning ul { display:grid; gap:.75rem; list-style:none; margin:1rem 0 0; padding:0; }
    .tr-warning li { display:flex; gap:.85rem; line-height:1.5; }
    .tr-warning li i { width:22px; flex:none; color:#6138e8; padding-top:.2rem; text-align:center; }
    .tr-upload-grid { display:grid; grid-template-columns:2fr 1fr; gap:1rem; margin-top:1rem; }
    .tr-drop { position:relative; display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:145px; padding:1rem; border:2px dashed #b7a7ff; border-radius:.85rem; color:#5027c9; text-align:center; cursor:pointer; }
    .tr-drop:focus-within,.tr-drop.is-dragging { outline:2px solid #7250f4; outline-offset:2px; background:#f8f6ff; }
    .tr-drop input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
    .tr-drop i { font-size:2rem; margin-bottom:.5rem; } .tr-drop small { margin-top:.35rem; color:#666b94; }
    .tr-next { padding:1.2rem; border-radius:.85rem; background:#eeebff; line-height:1.5; }
    .tr-next h3 { margin:0 0 .5rem; color:#3215a0; font-size:.9rem; } .tr-next p { margin:0; color:#555a87; font-size:.845rem; }
    .tr-actions { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; padding-top:1.2rem; margin-top:1.2rem; border-top:1px solid #e9e7f7; }
    .tr-actions label { display:flex; align-items:flex-start; gap:.6rem; max-width:75%; } .tr-actions input { margin-top:.3rem; accent-color:#6037e8; }
    .tr-submit { background:#5125db !important; border-color:#5125db !important; color:#fff !important; }
    .tr-alert { padding:1rem; border-radius:.7rem; background:#fff1ef; color:#a12929; }
    @media(max-width:800px) { .tr-bank,.tr-upload-grid { grid-template-columns:1fr; } .tr-hero { align-items:flex-start; flex-direction:column; } }
    @media(max-width:500px) { .tr-card,.tr-hero { padding:1rem; } .tr-hero h1 { font-size:1.05rem; } .tr-bank-list { grid-template-columns:1fr; gap:.2rem; } .tr-bank-list dd { margin-bottom:.4rem; } .tr-actions label { max-width:100%; } }
</style>
<section class="tr-page">
    <header class="tr-hero">
        <div class="tr-hero-main"><span class="tr-icon"><i class="fa-solid fa-right-left" aria-hidden="true"></i></span><div><h1>Pago por transferencia · {{ $empresa->nombre }}</h1><p>Realiza tu pago mediante transferencia bancaria con las siguientes indicaciones.</p></div></div>
        <div class="tr-amount"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Importe exacto: ${{ number_format((float) $transferencia->cantidad, 2) }} MXN</div>
    </header>
    @if($errors->any())<div class="tr-alert" role="alert"><strong>Revisa la información:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="tr-card tr-bank">
        <div><div class="tr-title"><span class="tr-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span><h2>Datos bancarios</h2></div>
            <dl class="tr-bank-list"><dt>Beneficiario:</dt><dd>{{ config('constantes.pagos_transferencia.beneficiario') ?: 'Pendiente de configuración' }}</dd><dt>Banco:</dt><dd>{{ config('constantes.pagos_transferencia.banco') ?: 'Pendiente de configuración' }}</dd><dt>CLABE:</dt><dd>{{ config('constantes.pagos_transferencia.clabe') ?: 'Pendiente de configuración' }}</dd>@if(config('constantes.pagos_transferencia.cuenta'))<dt>Cuenta:</dt><dd>{{ config('constantes.pagos_transferencia.cuenta') }}</dd>@endif</dl>
        </div>
        <div class="tr-concept"><h2><i class="fa-regular fa-copy" aria-hidden="true"></i> Concepto obligatorio</h2><div class="tr-concept-main"><p class="tr-concept-value" id="transfer-concept">{{ $transferencia->concepto }}</p><button class="btn-hr btn-outline-hr" type="button" id="copy-concept"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copiar</button></div><p>Escribe exactamente este concepto en tu transferencia.</p></div>
    </div>
    <aside class="tr-card tr-warning"><div class="tr-title"><span class="tr-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span><h2>¡Importante! Lee cuidadosamente estas indicaciones</h2></div><ul>
        <li><i class="fa-solid fa-coins" aria-hidden="true"></i><span><strong>Transfiere únicamente el importe exacto de tu mensualidad.</strong> Los excedentes no se aplicarán automáticamente a otras mensualidades; cualquier diferencia se revisará conforme a las condiciones aplicables.</span></li>
        <li><i class="fa-solid fa-tag" aria-hidden="true"></i><span><strong>Utiliza el concepto indicado: {{ $transferencia->concepto }}.</strong> Es indispensable para identificar tu pago.</span></li>
        <li><i class="fa-regular fa-clock" aria-hidden="true"></i><span><strong>La validación puede tomar hasta 48 horas hábiles</strong> desde la recepción del comprobante.</span></li>
        <li><i class="fa-regular fa-calendar" aria-hidden="true"></i><span><strong>Suspensión:</strong> Dispones de cinco días naturales, incluyendo la fecha de renovación, para que tu pago sea confirmado. Si al sexto día no se ha confirmado, el servicio será suspendido automáticamente. Te recomendamos realizar tu transferencia y cargar tu comprobante con suficiente anticipación, considerando los tiempos de validación.</span></li>
        <li><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span><strong>La renovación no es inmediata.</strong> Subir el comprobante no confirma el pago ni renueva el servicio.</span></li>
    </ul></aside>
    @if($transferencia->estado === 'pendiente_de_confirmar')
        <div class="tr-card"><strong>Estado: Pendiente de confirmar.</strong> No envíes otro comprobante para este periodo.</div>
    @elseif(in_array($transferencia->estado, ['pendiente_de_pago', 'rechazado'], true))
        <form class="tr-card" method="POST" enctype="multipart/form-data" action="{{ route('transferencias.upload', $empresa->id) }}">@csrf
            <div class="tr-title"><span class="tr-icon"><i class="fa-solid fa-paperclip" aria-hidden="true"></i></span><div><h2>Adjunta tu comprobante</h2><p>Una vez realizada tu transferencia, sube aquí el comprobante (PDF, JPG o PNG).</p></div></div>
            @if($transferencia->estado === 'rechazado')
                <div class="tr-alert">
                    @include('payments.transfer-rejection', ['rejectedTransfer' => $transferencia])
                </div>
            @endif
            <div class="tr-upload-grid"><label class="tr-drop" id="transfer-drop"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><strong id="transfer-file-label">Arrastra tu archivo aquí o selecciona un archivo</strong><small>Tamaño máximo: {{ rtrim(rtrim(number_format(config('constantes.pagos_transferencia.max_kb', 10240) / 1024, 2), '0'), '.') }} MB</small><input id="comprobante" name="comprobante" type="file" accept="application/pdf,image/jpeg,image/png" aria-label="Selecciona tu comprobante" required></label><div class="tr-next"><h3><i class="fa-solid fa-circle-check" aria-hidden="true"></i> ¿Qué sigue?</h3><p>Revisaremos tu comprobante. La validación puede tomar hasta 48 horas hábiles; el pago se confirma después de la revisión.</p></div></div>
            <div class="tr-actions">@if($requiresConsent)<label><input type="checkbox" name="terms_accepted" value="1" required><span>He leído y acepto las condiciones para realizar pagos mediante transferencia bancaria.</span></label>@endif<button class="btn-hr btn-primary-hr tr-submit" type="submit"><i class="fa-regular fa-paper-plane" aria-hidden="true"></i> Enviar comprobante</button></div>
        </form>
    @else
        <div class="tr-card"><strong>Estado: {{ ucfirst($transferencia->estado) }}.</strong></div>
    @endif
</section>
<script>
    (() => {
        const copy = document.getElementById('copy-concept');
        copy.addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(document.getElementById('transfer-concept').textContent.trim()); copy.textContent = 'Copiado'; }
            catch (error) { if (window.Swal) Swal.fire('No se pudo copiar', 'Selecciona y copia el concepto manualmente.', 'info'); }
        });
        const input = document.getElementById('comprobante');
        const drop = document.getElementById('transfer-drop');
        if (!input || !drop) return;
        input.addEventListener('change', () => { document.getElementById('transfer-file-label').textContent = input.files[0]?.name || 'Arrastra tu archivo aquí o selecciona un archivo'; });
        ['dragenter', 'dragover'].forEach(event => drop.addEventListener(event, () => drop.classList.add('is-dragging')));
        ['dragleave', 'drop'].forEach(event => drop.addEventListener(event, () => drop.classList.remove('is-dragging')));
    })();
</script>
@endsection
