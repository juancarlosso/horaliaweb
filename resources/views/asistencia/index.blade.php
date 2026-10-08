@extends('layouts.dashboard', ['pageTitle' => 'Asistencias', 'activeSection' => 'asistencia'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .attendance-page { width:100%; }
    .attendance-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .attendance-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .attendance-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .attendance-mark-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; margin-bottom:1.25rem; }
    .attendance-today { margin-bottom:1.25rem; overflow:hidden; }
    .attendance-today .card-hd { min-height:3.4rem; }
    .attendance-today-body { padding:1.35rem 1.5rem 1.5rem; text-align:center; }
    .attendance-live-clock { color:var(--body-text); font-size:clamp(2.25rem,5vw,3rem); font-weight:900; letter-spacing:-.045em; line-height:1; font-variant-numeric:tabular-nums; }
    .attendance-live-date { margin-top:.35rem; color:var(--body-text-muted); font-size:.95rem; }
    .attendance-check-actions { display:flex; gap:.5rem; margin:1.4rem auto 1rem; max-width:38rem; }
    .attendance-check-button { display:flex; align-items:center; justify-content:center; gap:.6rem; flex:1; min-height:2.9rem; padding:.65rem 1rem; border:0; border-radius:.7rem; font:inherit; font-size:.92rem; font-weight:700; transition:filter .15s,opacity .15s; }
    .attendance-check-button:not(:disabled) { cursor:pointer; }
    .attendance-check-button:not(:disabled):hover { filter:brightness(.96); }
    .attendance-check-button.is-entry { color:#079669; background:rgba(16,185,129,.09); }
    .attendance-check-button.is-exit { color:#dc3545; background:rgba(239,68,68,.08); }
    .attendance-check-button:disabled { cursor:not-allowed; opacity:.58; }
    .attendance-today-log { max-width:38rem; margin:auto; padding:.75rem .9rem; border:1px solid var(--body-border); border-radius:.75rem; background:var(--body-bg); }
    .attendance-today-log-title { margin-bottom:.35rem; color:var(--body-text-muted); font-size:.72rem; font-weight:750; letter-spacing:.07em; text-transform:uppercase; }
    .attendance-today-log-row { display:flex; justify-content:space-between; gap:1rem; margin-top:.25rem; color:var(--body-text-muted); font-size:.82rem; text-align:left; }
    .attendance-today-log-row strong { color:var(--body-text); font-weight:700; text-align:right; }
    .attendance-today-log-row strong.is-entry { color:#079669; }
    .attendance-today-log-row strong.is-hours { color:var(--color-primary); }
    .attendance-mark-card { display:flex; align-items:center; gap:1rem; min-height:128px; padding:1.25rem; border:1px solid var(--card-border); border-radius:.875rem; background:var(--card-bg); box-shadow:0 4px 14px rgba(27,35,72,.035); }
    .attendance-mark-icon { display:grid; place-items:center; width:48px; height:48px; flex:none; border-radius:.875rem; color:#536df5; background:rgba(83,109,245,.1); font-size:1.2rem; }
    .attendance-mark-icon.is-entry { color:#079669; background:rgba(16,185,129,.11); }
    .attendance-mark-icon.is-exit { color:#dc3545; background:rgba(239,68,68,.1); }
    .attendance-mark-copy { min-width:0; flex:1; }
    .attendance-mark-copy h2 { margin:0 0 .3rem; color:var(--body-text-muted); font-size:.76rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .attendance-mark-copy strong { display:block; color:var(--body-text); font-size:1.25rem; font-weight:800; }
    .attendance-mark-copy small { display:block; margin-top:.2rem; color:var(--body-text-muted); font-size:.78rem; }
    .attendance-toolbar { display:grid; grid-template-columns:minmax(300px,1fr) minmax(320px,.75fr) auto; align-items:end; gap:1rem; width:100%; margin-bottom:1.25rem; }
    .attendance-toolbar.is-personal { grid-template-columns:minmax(320px,.75fr) auto; }
    .attendance-filter,.attendance-filter-period { width:100%; min-width:0; }
    .attendance-period-selects { display:grid; grid-template-columns:minmax(0,1fr) minmax(100px,.55fr); gap:.5rem; }
    .attendance-filter label { display:block; margin:0 0 .4rem; color:var(--body-text); font-size:.82rem; font-weight:650; }
    .attendance-filter select,.attendance-filter-period select { width:100%; }
    .attendance-actions { display:flex; gap:.5rem; white-space:nowrap; }
    .attendance-actions .btn-hr { text-decoration:none; }
    .attendance-user { display:flex; align-items:center; gap:.65rem; min-width:205px; }
    .attendance-avatar { position:relative; display:grid; place-items:center; width:38px; height:38px; flex:none; overflow:hidden; border-radius:.7rem; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); font-size:.9rem; font-weight:800; }
    .attendance-avatar img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .attendance-user-name { color:var(--body-text); font-weight:700; }
    .attendance-user-company { margin-top:.15rem; color:var(--body-text-muted); font-size:.75rem; }
    .attendance-pill { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .55rem; border-radius:999px; color:#687087; background:rgba(120,130,150,.1); font-size:.74rem; font-weight:700; white-space:nowrap; }
    .attendance-pill.is-in-range { color:#087f5b; background:rgba(16,185,129,.1); }
    .attendance-pill.is-out-range { color:#b42318; background:rgba(239,68,68,.08); }
    .attendance-time-cell { display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .attendance-icon-action { display:inline-grid; place-items:center; width:27px; height:27px; padding:0; border:1px solid var(--body-border); border-radius:.45rem; color:var(--body-text-muted); background:var(--card-bg); text-decoration:none !important; }
    .attendance-icon-action:hover { border-color:var(--color-primary); color:var(--color-primary); }
    .attendance-icon-action i { font-size:.8rem; }
    .attendance-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .attendance-pagination { display:flex; align-items:center; gap:.55rem; }
    .attendance-pagination [aria-disabled="true"] { pointer-events:none; opacity:.55; }
    .attendance-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .attendance-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .attendance-empty p { margin:0; font-size:.845rem; }
    .attendance-camera-frame { position:relative; display:grid; min-height:300px; place-items:center; overflow:hidden; border-radius:.75rem; background:#111426; }
    .attendance-camera-video { display:block; width:100%; max-height:65vh; min-height:300px; object-fit:cover; transform:scaleX(-1); }
    .attendance-camera-processing { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:.8rem; color:#fff; background:rgba(17,20,38,.78); text-align:center; }
    .attendance-camera-processing[hidden] { display:none; }
    .attendance-camera-spinner { width:2.25rem; height:2.25rem; border:.24rem solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; animation:attendance-spin .75s linear infinite; }
    .attendance-camera-error { margin:0; padding:.7rem .85rem; border-radius:.55rem; color:#b42318; background:rgba(239,68,68,.08); font-size:.84rem; }
    @keyframes attendance-spin { to { transform:rotate(360deg); } }
    #attendance-camera-modal .modal-content { border:1px solid var(--card-border); border-radius:1rem; color:var(--body-text); background:var(--card-bg); }
    #attendance-camera-modal .modal-header,#attendance-camera-modal .modal-footer { border-color:var(--body-border); }
    #attendance-camera-modal .modal-title { color:var(--body-text); font-weight:800; }
    #attendance-camera-modal .modal-body p { color:var(--body-text-muted); font-size:.85rem; }
    #attendance-camera-modal .btn-close { filter:var(--modal-close-filter,none); }
    @media(max-width:850px) { .attendance-toolbar { grid-template-columns:minmax(0,1fr) minmax(0,1fr); align-items:stretch; } .attendance-actions { grid-column:1 / -1; } }
    @media(max-width:700px) { .attendance-mark-grid { grid-template-columns:1fr; } .attendance-mark-card { flex-wrap:wrap; } .attendance-footer { align-items:flex-start; flex-direction:column; } }
    @media(max-width:420px) { .attendance-today-body { padding:1.15rem .85rem; } .attendance-check-actions { flex-direction:column; } }
</style>

<section class="attendance-page">
    <header class="attendance-heading"><div><h1>Asistencias</h1><p>Consulta y registra las entradas y salidas del personal.</p></div></header>
    @if($canMarkAttendance)
        @php
            $displayAttendance = $todayAttendance ?? $overnightAttendance;
            $workedSeconds = $displayAttendance?->llegada
                ? max(0, ($displayAttendance->salida ?? now())->getTimestamp() - $displayAttendance->llegada->getTimestamp())
                : 0;
            $workedHours = intdiv($workedSeconds, 3600);
            $workedMinutes = intdiv($workedSeconds % 3600, 60);
            $entryUnavailableReason = $overnightAttendance
                ? 'Registra primero la salida de tu jornada anterior.'
                : ($todayAttendance?->llegada
                ? 'La entrada ya está registrada.'
                : ($todaySchedule ? 'No disponible por el momento.' : 'No tienes un horario laboral configurado para hoy.'));
            $exitUnavailableReason = $todayAttendance?->salida
                ? 'La salida ya está registrada.'
                : (!$todayAttendance?->llegada && !$overnightAttendance ? 'Registra primero tu entrada.' : 'No disponible por el momento.');
        @endphp
        <article class="card-hr attendance-today">
            <div class="card-hd"><h2 class="card-title-hr">{{ $overnightAttendance && !$todayAttendance ? 'Mi jornada pendiente' : 'Mi asistencia de hoy' }}</h2></div>
            <div class="attendance-today-body">
                <div class="attendance-live-clock" id="attendance-live-clock" data-now="{{ now()->toIso8601String() }}" data-timezone="{{ config('app.timezone') }}">{{ now()->format('h:i:s A') }}</div>
                <div class="attendance-live-date" id="attendance-live-date">{{ now()->locale('es_MX')->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</div>
                <div class="attendance-check-actions">
                    <button class="attendance-check-button is-entry" type="button" @if($canMarkEntry) data-open-attendance data-kind="entrada" data-url="{{ route('asistencia.entrada') }}" @else disabled title="{{ $entryUnavailableReason }}" @endif><i class="fa-light fa-right-to-bracket" aria-hidden="true"></i> Registrar entrada</button>
                    <button class="attendance-check-button is-exit" type="button" @if($canMarkExit) data-open-attendance data-kind="salida" data-url="{{ route('asistencia.salida') }}" @else disabled title="{{ $exitUnavailableReason }}" @endif><i class="fa-light fa-right-from-bracket" aria-hidden="true"></i> Registrar salida</button>
                </div>
                <div class="attendance-today-log">
                    <div class="attendance-today-log-title">{{ $overnightAttendance && !$todayAttendance ? 'Jornada iniciada el ' . $overnightAttendance->fecha->format('d/m/Y') : 'Registro de hoy' }}</div>
                    <div class="attendance-today-log-row"><span>Entrada</span><strong class="is-entry">{{ $displayAttendance?->llegada?->format('h:i A') ?? 'Sin registrar' }}</strong></div>
                    <div class="attendance-today-log-row"><span>Salida</span><strong>{{ $displayAttendance?->salida?->format('h:i A') ?? 'Pendiente' }}</strong></div>
                    <div class="attendance-today-log-row"><span>Horas trabajadas</span><strong class="is-hours" id="attendance-worked-time" @if($displayAttendance?->llegada && !$displayAttendance?->salida) data-start="{{ $displayAttendance->llegada->getTimestamp() }}" @endif>{{ $workedHours }} h {{ $workedMinutes }} min</strong></div>
                </div>
            </div>
        </article>
    @endif

    <form class="attendance-toolbar {{ (int) auth()->user()->profile === 3 ? 'is-personal' : '' }}" method="GET" action="{{ route('asistencia.index') }}" id="attendance-filter-form">
        @if((int) auth()->user()->profile !== 3)
            <div class="attendance-filter"><label for="empresa_id">Empresa</label>
                <select class="hr-input select2" id="empresa_id" name="empresa_id">
                    @if($companies->count() !== 1)<option value="0" @selected(!$selectedCompanyId)>Todas las empresas</option>@endif
                    @foreach($companies as $company)<option value="{{ $company->id }}" @selected($selectedCompanyId === $company->id)>{{ $company->razon_social }}</option>@endforeach
                </select>
            </div>
        @endif
        <div class="attendance-filter-period"><label for="mes">Mes y año</label><div class="attendance-period-selects"><select class="hr-input select2" id="mes" name="mes" aria-label="Mes">@foreach($monthNames as $monthNumber => $monthName)<option value="{{ $monthNumber }}" @selected($selectedMonth === $monthNumber)>{{ $monthName }}</option>@endforeach</select><select class="hr-input select2" id="anio" name="anio" aria-label="Año">@foreach($availableYears as $year)<option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>@endforeach</select></div></div>
        <div class="attendance-actions"><button class="btn-hr btn-primary-hr" type="submit"><i class="fa-light fa-magnifying-glass" aria-hidden="true"></i> Buscar</button><a class="btn-hr btn-outline-hr" href="{{ route('asistencia.index', $selectedCompanyId ? ['empresa_id' => $selectedCompanyId] : []) }}"><i class="fa-light fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
    </form>

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Registro de asistencias</h2><p class="card-subtitle-hr">{{ $attendance->total() }} {{ $attendance->total() === 1 ? 'registro' : 'registros' }} · {{ $periodLabel }}</p></div></div>
        @if($attendance->count())
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Colaborador</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Retardo</th><th>Salida temprana</th><th>Rango</th></tr></thead>
                <tbody>
                @foreach($attendance as $record)
                    @php
                        $employee = $record->personal;
                        $photoUrl = $employee?->fotoUrl();
                        $entryEvidenceUrl = $record->fotoEntradaUrl();
                        $exitEvidenceUrl = $record->fotoSalidaUrl();
                        $initial = mb_strtoupper(mb_substr(trim($employee?->nombre ?? ''), 0, 1)) ?: 'P';
                    @endphp
                    <tr>
                        <td><div class="attendance-user"><div class="attendance-avatar"><span>{{ $initial }}</span>@if($photoUrl)<img src="{{ $photoUrl }}" alt="" loading="lazy" onerror="this.remove()">@endif</div><div><div class="attendance-user-name">{{ $employee?->nombre ?? 'No disponible' }}</div><div class="attendance-user-company">{{ $employee?->empresa?->razon_social ?? '—' }}</div></div></div></td>
                        <td>{{ $record->fecha?->format('d/m/Y') ?? '—' }}</td>
                        <td><div class="attendance-time-cell"><span>{{ $record->llegada?->format('H:i') ?? '—' }}</span>
                            @if($entryEvidenceUrl)<a class="attendance-icon-action" href="{{ $entryEvidenceUrl }}" target="_blank" rel="noopener" title="Ver foto de entrada" aria-label="Ver foto de entrada de {{ $employee?->nombre }}"><i class="fa-light fa-camera" aria-hidden="true"></i></a>@endif
                            @if($record->latitud !== null && $record->longitud !== null)<a class="attendance-icon-action" href="https://www.google.com/maps?q={{ $record->latitud }},{{ $record->longitud }}" target="_blank" rel="noopener" title="Ver ubicación de entrada" aria-label="Ver ubicación de entrada de {{ $employee?->nombre }}"><i class="fa-light fa-location-dot" aria-hidden="true"></i></a>@endif
                        </div></td>
                        <td><div class="attendance-time-cell"><span>{{ $record->salida?->format('H:i') ?? '—' }}</span>
                            @if($exitEvidenceUrl)<a class="attendance-icon-action" href="{{ $exitEvidenceUrl }}" target="_blank" rel="noopener" title="Ver foto de salida" aria-label="Ver foto de salida de {{ $employee?->nombre }}"><i class="fa-light fa-camera" aria-hidden="true"></i></a>@endif
                            @if($record->latitud_salida !== null && $record->longitud_salida !== null)<a class="attendance-icon-action" href="https://www.google.com/maps?q={{ $record->latitud_salida }},{{ $record->longitud_salida }}" target="_blank" rel="noopener" title="Ver ubicación de salida" aria-label="Ver ubicación de salida de {{ $employee?->nombre }}"><i class="fa-light fa-location-dot" aria-hidden="true"></i></a>@endif
                        </div></td>
                        <td>{{ $record->minutos_tarde ? $record->minutos_tarde . ' min' : '—' }}</td>
                        <td>{{ $record->minutos_salida_temprano ? $record->minutos_salida_temprano . ' min' : '—' }}</td>
                        <td><div class="d-flex flex-column gap-1"><span class="attendance-pill {{ $record->rango_entrada === 'En rango' ? 'is-in-range' : ($record->rango_entrada === 'Fuera de rango' ? 'is-out-range' : '') }}">E: {{ $record->rango_entrada ?? '—' }}</span><span class="attendance-pill {{ $record->rango_salida === 'En rango' ? 'is-in-range' : ($record->rango_salida === 'Fuera de rango' ? 'is-out-range' : '') }}">S: {{ $record->rango_salida ?? '—' }}</span></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($attendance->hasPages())<div class="attendance-footer"><span>Mostrando {{ $attendance->firstItem() }}–{{ $attendance->lastItem() }} de {{ $attendance->total() }}</span><div class="attendance-pagination"><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->previousPageUrl() ?? '#' }}" @if($attendance->onFirstPage()) aria-disabled="true" @endif>Anterior</a><span>Página {{ $attendance->currentPage() }} de {{ $attendance->lastPage() }}</span><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $attendance->nextPageUrl() ?? '#' }}" @unless($attendance->hasMorePages()) aria-disabled="true" @endunless>Siguiente</a></div></div>@endif
        @else
            <div class="attendance-empty"><h2>No hay asistencias para este periodo</h2><p>No se encontraron registros para {{ $periodLabel }}.</p></div>
        @endif
    </div>
</section>
<div class="modal fade" id="attendance-camera-modal" tabindex="-1" aria-labelledby="attendance-camera-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <form id="attendance-camera-form" method="POST" action="">
            @csrf
            <input id="attendance-camera-latitude" type="hidden">
            <input id="attendance-camera-longitude" type="hidden">
            <input id="attendance-camera-photo" type="hidden">
            <div class="modal-header"><h2 class="modal-title fs-5" id="attendance-camera-title">Registrar asistencia</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <p class="mb-3" id="attendance-camera-instructions">Toma una foto para confirmar tu asistencia. También obtendremos tu ubicación.</p>
                <div class="attendance-camera-frame">
                    <video id="attendance-camera-video" class="attendance-camera-video" autoplay muted playsinline></video>
                    <div class="attendance-camera-processing" id="attendance-camera-processing" hidden><span class="attendance-camera-spinner" aria-hidden="true"></span><span id="attendance-camera-processing-text" role="status">Activando la cámara…</span></div>
                </div>
                <p class="attendance-camera-error mt-3" id="attendance-camera-error" role="alert" hidden></p>
                <canvas id="attendance-camera-canvas" hidden></canvas>
            </div>
            <div class="modal-footer"><button class="btn-hr btn-outline-hr" type="button" id="attendance-camera-cancel" data-bs-dismiss="modal">Cancelar</button><button class="btn-hr btn-success-hr" type="button" id="attendance-camera-submit" disabled>ENTRADA</button></div>
        </form>
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const liveClock = document.getElementById('attendance-live-clock');
    const liveDate = document.getElementById('attendance-live-date');
    const workedTime = document.getElementById('attendance-worked-time');
    if (liveClock) {
        const clockBase = new Date(liveClock.dataset.now).getTime();
        const clockStartedAt = Date.now();
        const timezone = liveClock.dataset.timezone;
        const renderAttendanceClock = function () {
            const currentTime = new Date(clockBase + Date.now() - clockStartedAt);
            liveClock.textContent = new Intl.DateTimeFormat('es-MX', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: timezone
            }).format(currentTime);
            liveDate.textContent = new Intl.DateTimeFormat('es-MX', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: timezone
            }).format(currentTime);
            if (workedTime && workedTime.dataset.start) {
                const seconds = Math.max(0, Math.floor(currentTime.getTime() / 1000) - Number(workedTime.dataset.start));
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                workedTime.textContent = hours + ' h ' + minutes + ' min';
            }
        };
        renderAttendanceClock();
        window.setInterval(renderAttendanceClock, 1000);
    }

    window.jQuery('#empresa_id').on('change', function () {
        const form = document.getElementById('attendance-filter-form');
        const params = new URLSearchParams();
        if (this.value !== '0') params.set('empresa_id', this.value);
        params.set('mes', document.getElementById('mes').value);
        params.set('anio', document.getElementById('anio').value);
        window.location.assign(form.action + (params.toString() ? '?' + params.toString() : ''));
    });

    const cameraModalElement = document.getElementById('attendance-camera-modal');
    const cameraModal = window.bootstrap.Modal.getOrCreateInstance(cameraModalElement);
    const cameraForm = document.getElementById('attendance-camera-form');
    const video = document.getElementById('attendance-camera-video');
    const canvas = document.getElementById('attendance-camera-canvas');
    const submitButton = document.getElementById('attendance-camera-submit');
    const cancelButton = document.getElementById('attendance-camera-cancel');
    const loading = document.getElementById('attendance-camera-processing');
    const loadingText = document.getElementById('attendance-camera-processing-text');
    const errorMessage = document.getElementById('attendance-camera-error');
    let cameraStream = null;
    let currentKind = null;
    let cameraRequestId = 0;

    function showLoading(message) {
        loadingText.textContent = message;
        loading.hidden = false;
    }
    function hideLoading() { loading.hidden = true; }
    function showCameraError(message) {
        errorMessage.textContent = message;
        errorMessage.hidden = false;
    }
    function stopCamera() {
        if (cameraStream) cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        video.srcObject = null;
    }
    function setBusy(busy) {
        submitButton.disabled = busy || !cameraStream;
        cancelButton.disabled = busy;
        cameraModalElement.querySelector('.btn-close').disabled = busy;
    }
    function startCamera() {
        const requestId = ++cameraRequestId;
        errorMessage.hidden = true;
        showLoading('Activando la cámara…');
        submitButton.disabled = true;
        cancelButton.disabled = false;
        cameraModalElement.querySelector('.btn-close').disabled = false;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            hideLoading();
            showCameraError('Este navegador no permite usar la cámara.');
            setBusy(false);
            return;
        }

        navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false}).then(function (stream) {
            if (requestId !== cameraRequestId) {
                stream.getTracks().forEach(track => track.stop());
                return null;
            }
            cameraStream = stream;
            video.srcObject = stream;
            return video.play();
        }).then(function () {
            if (requestId !== cameraRequestId || !cameraStream) return;
            hideLoading();
            setBusy(false);
        }).catch(function () {
            if (requestId !== cameraRequestId) return;
            hideLoading();
            showCameraError('No fue posible acceder a la cámara. Revisa el permiso del navegador e inténtalo de nuevo.');
            setBusy(false);
        });
    }

    document.querySelectorAll('[data-open-attendance]').forEach(function (button) {
        button.addEventListener('click', function () {
            currentKind = button.dataset.kind;
            const isEntry = currentKind === 'entrada';
            document.getElementById('attendance-camera-title').textContent = isEntry ? 'Registrar entrada' : 'Registrar salida';
            document.getElementById('attendance-camera-instructions').textContent = isEntry
                ? 'Toma una foto para confirmar tu entrada. También obtendremos tu ubicación.'
                : 'Toma una foto para confirmar tu salida. También obtendremos tu ubicación.';
            cameraForm.action = button.dataset.url;
            document.getElementById('attendance-camera-latitude').name = isEntry ? 'latitud' : 'latitud_salida';
            document.getElementById('attendance-camera-longitude').name = isEntry ? 'longitud' : 'longitud_salida';
            document.getElementById('attendance-camera-photo').name = isEntry ? 'foto_entrada' : 'foto_salida';
            submitButton.textContent = isEntry ? 'ENTRADA' : 'SALIDA';
            submitButton.classList.toggle('btn-success-hr', isEntry);
            submitButton.classList.toggle('btn-danger-hr', !isEntry);
            submitButton.classList.remove(isEntry ? 'btn-danger-hr' : 'btn-success-hr');
            document.getElementById('attendance-camera-latitude').value = '';
            document.getElementById('attendance-camera-longitude').value = '';
            document.getElementById('attendance-camera-photo').value = '';
            cameraModal.show();
        });
    });

    cameraModalElement.addEventListener('shown.bs.modal', startCamera);
    cameraModalElement.addEventListener('hidden.bs.modal', function () {
        cameraRequestId++;
        stopCamera();
        hideLoading();
        errorMessage.hidden = true;
        currentKind = null;
    });

    submitButton.addEventListener('click', function () {
        if (!cameraStream || !video.videoWidth || !video.videoHeight) {
            showCameraError('Espera a que la cámara esté lista para tomar la foto.');
            return;
        }

        setBusy(true);
        errorMessage.hidden = true;
        showLoading('Procesando la fotografía…');
        const scale = Math.min(1, 1280 / video.videoWidth);
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(function (blob) {
            if (!blob) {
                hideLoading();
                showCameraError('No se pudo tomar la foto. Inténtalo nuevamente.');
                setBusy(false);
                return;
            }

            const reader = new FileReader();
            reader.onerror = function () {
                hideLoading();
                showCameraError('No se pudo procesar la foto. Inténtalo nuevamente.');
                setBusy(false);
            };
            reader.onloadend = function () {
                if (reader.error) return;
                document.getElementById('attendance-camera-photo').value = reader.result;
                if (!navigator.geolocation) {
                    hideLoading();
                    showCameraError('Este navegador no permite obtener tu ubicación.');
                    setBusy(false);
                    return;
                }

                showLoading('Obteniendo ubicación…');
                navigator.geolocation.getCurrentPosition(function (position) {
                    const isEntry = currentKind === 'entrada';
                    document.getElementById('attendance-camera-latitude').value = position.coords.latitude;
                    document.getElementById('attendance-camera-longitude').value = position.coords.longitude;
                    showLoading('Guardando asistencia…');
                    stopCamera();
                    cameraForm.submit();
                }, function () {
                    hideLoading();
                    showCameraError('Activa el permiso de ubicación para registrar tu asistencia.');
                    setBusy(false);
                }, {enableHighAccuracy:true,timeout:15000,maximumAge:0});
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', .82);
    });
});
</script>
@endsection
