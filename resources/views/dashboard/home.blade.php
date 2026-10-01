@extends('layouts.dashboard', ['pageTitle' => 'Home'])

@section('content')
@if((int) auth()->user()->profile === 3)
<style>
    .page-inner { max-width:none; }
    .employee-dashboard { width:100%; }
    .welcome-banner { position:relative; display:flex; align-items:center; gap:1.25rem; overflow:hidden; padding:1.45rem 1.75rem; margin-bottom:1.25rem; border:1px solid rgba(255,255,255,.08); border-radius:1.15rem; color:#fff; background:linear-gradient(135deg,#0f0c29 0%,#302b63 56%,#24243e 100%); }
    .welcome-banner::before { content:""; position:absolute; inset:0; background:radial-gradient(ellipse 60% 80% at 90% 50%,rgba(79,110,247,.22),transparent 70%); pointer-events:none; }
    .wb-avatar { position:relative; display:flex; align-items:center; justify-content:center; width:60px; height:60px; flex:none; border:3px solid rgba(255,255,255,.2); border-radius:50%; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); box-shadow:0 0 0 4px rgba(79,110,247,.25); font-size:1.2rem; font-weight:900; }
    .wb-copy { position:relative; min-width:0; }
    .wb-greeting { margin:0; color:#fff; font-size:1.2rem; font-weight:800; line-height:1.25; }
    .wb-sub { margin-top:.35rem; color:rgba(255,255,255,.68); font-size:.82rem; line-height:1.5; }
    .wb-highlight { color:#9ab2ff; font-weight:700; }
    .wb-actions { position:relative; display:flex; gap:.6rem; margin-left:auto; flex:none; }
    .wb-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem .9rem; border:1px solid transparent; border-radius:.7rem; color:#fff; font:700 .8rem var(--font); text-decoration:none !important; white-space:nowrap; transition:filter .15s ease,background .15s ease; }
    .wb-btn:hover { color:#fff; filter:brightness(1.08); }
    .wb-btn-outline { border-color:rgba(255,255,255,.2); background:rgba(255,255,255,.1); }
    .wb-btn-primary { background:linear-gradient(135deg,#4f6ef7,#8b5cf6); box-shadow:0 4px 16px rgba(79,110,247,.35); }
    .metrics-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.875rem; margin-bottom:1.25rem; }
    .metric-tile { position:relative; display:flex; align-items:flex-start; gap:.8rem; min-width:0; overflow:hidden; padding:1rem 1.05rem; border:1px solid var(--card-border); border-radius:.95rem; background:var(--card-bg); transition:transform .18s ease,box-shadow .18s ease; }
    .metric-tile:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(27,35,72,.08); }
    .mt-icon { display:flex; align-items:center; justify-content:center; width:42px; height:42px; flex:none; border-radius:.8rem; font-size:1rem; }
    .mt-val { color:var(--body-text); font-size:1.25rem; font-weight:900; line-height:1.15; }
    .mt-label { margin-top:.25rem; color:var(--body-text-muted); font-size:.68rem; font-weight:700; letter-spacing:.055em; text-transform:uppercase; }
    .mt-trend { display:flex; align-items:center; gap:.25rem; margin-top:.35rem; font-size:.72rem; font-weight:700; }
    .employee-history { overflow:hidden; border:1px solid var(--card-border); border-radius:.875rem; background:var(--card-bg); }
    .employee-history-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.1rem 1.25rem; border-bottom:1px solid var(--body-border); }
    .employee-history-heading h2 { margin:0 0 .25rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .employee-history-heading p { margin:0; color:var(--body-text-muted); font-size:.8rem; }
    .employee-history table { width:100%; margin:0; }
    .employee-history-empty { padding:1.75rem 1rem; color:var(--body-text-muted); font-size:.85rem; text-align:center; }
    .employee-status { display:inline-flex; align-items:center; padding:.25rem .55rem; border-radius:999px; color:#687087; background:rgba(120,130,150,.1); font-size:.72rem; font-weight:700; white-space:nowrap; }
    .employee-status.is-complete { color:#087f5b; background:rgba(16,185,129,.1); }
    .employee-status.is-pending { color:#9a6700; background:rgba(234,179,8,.13); }
    @media(max-width:1050px) { .metrics-row { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:700px) { .welcome-banner { align-items:flex-start; flex-wrap:wrap; padding:1.2rem; } .wb-avatar { width:48px; height:48px; } .wb-copy { flex:1; } .wb-actions { width:100%; margin-left:0; } .wb-btn { justify-content:center; flex:1; } }
    @media(max-width:480px) { .metrics-row { grid-template-columns:1fr; gap:.65rem; } .metric-tile { padding:.85rem .95rem; } .employee-history-heading { align-items:flex-start; flex-direction:column; } }
</style>
<section class="employee-dashboard">
    @if(!$personal)
        <div class="welcome-banner">
            <div class="wb-avatar">{{ mb_strtoupper(mb_substr(trim(auth()->user()->name), 0, 1)) }}</div>
            <div class="wb-copy"><h1 class="wb-greeting">Hola, {{ auth()->user()->name }} 👋</h1><div class="wb-sub">No encontramos tu ficha de personal asociada. Contacta al administrador de tu empresa.</div></div>
        </div>
    @else
        <section class="welcome-banner" aria-labelledby="welcome-title">
            <div class="wb-avatar">{{ mb_strtoupper(mb_substr(trim($personal->nombre ?: auth()->user()->name), 0, 1)) }}</div>
            <div class="wb-copy">
                <h1 class="wb-greeting" id="welcome-title">Hola, {{ $personal->nombre ?: auth()->user()->name }} 👋</h1>
                <div class="wb-sub">
                    @if(!$worksToday)
                        Hoy no tienes jornada programada
                    @elseif(!$todaySchedule)
                        <span class="wb-highlight">Horario pendiente de configuración</span>
                    @elseif($todayAttendance?->salida)
                        Jornada completada · Entrada {{ $todayAttendance->llegada?->format('H:i') ?? '—' }} · Salida {{ $todayAttendance->salida->format('H:i') }}
                    @elseif($todayAttendance?->llegada)
                        Entrada registrada a las <span class="wb-highlight">{{ $todayAttendance->llegada->format('H:i') }}</span> · Tu salida está pendiente
                    @else
                        Tu horario de hoy es <span class="wb-highlight">{{ substr((string) $todaySchedule->entrada, 0, 5) }}–{{ substr((string) $todaySchedule->salida, 0, 5) }}</span>
                    @endif
                    @if($personal->centro) · {{ $personal->centro->nombre }}@endif
                </div>
            </div>
            @if($canMarkEntry || $canMarkExit)
                <div class="wb-actions"><a class="wb-btn {{ $canMarkEntry ? 'wb-btn-outline' : 'wb-btn-primary' }}" href="{{ route('asistencia.index') }}"><i class="fa-light {{ $canMarkEntry ? 'fa-right-to-bracket' : 'fa-right-from-bracket' }}" aria-hidden="true"></i>{{ $canMarkEntry ? 'Registrar entrada' : 'Registrar salida' }}</a></div>
            @endif
        </section>

        <section class="metrics-row" aria-label="Resumen de asistencia del mes">
            <article class="metric-tile" style="--mt-color:#4f6ef7">
                <div class="mt-icon" style="background:rgba(79,110,247,.12);color:#4f6ef7"><i class="fa-light fa-calendar-check" aria-hidden="true"></i></div>
                <div><div class="mt-val">{{ $monthAttendanceCount }}<span style="font-size:.78rem;color:var(--body-text-muted)">/{{ $monthScheduledDays }}</span></div><div class="mt-label">Asistencia este mes</div><div class="mt-trend" style="color:#4f6ef7">Jornadas registradas</div></div>
            </article>
            <article class="metric-tile" style="--mt-color:#f59e0b">
                <div class="mt-icon" style="background:rgba(245,158,11,.13);color:#d97706"><i class="fa-light fa-clock" aria-hidden="true"></i></div>
                <div><div class="mt-val">{{ $monthLateCount }}</div><div class="mt-label">Días con retardo</div><div class="mt-trend" style="color:#d97706">Este mes</div></div>
            </article>
            <article class="metric-tile" style="--mt-color:#06b6d4">
                <div class="mt-icon" style="background:rgba(6,182,212,.12);color:#0891b2"><i class="fa-light fa-hourglass-half" aria-hidden="true"></i></div>
                <div><div class="mt-val">{{ $monthLateMinutes }}</div><div class="mt-label">Minutos de retardo</div><div class="mt-trend" style="color:#0891b2">Acumulados este mes</div></div>
            </article>
            <article class="metric-tile" style="--mt-color:#10b981">
                <div class="mt-icon" style="background:rgba(16,185,129,.12);color:#059669"><i class="fa-light fa-right-from-bracket" aria-hidden="true"></i></div>
                <div><div class="mt-val">{{ $monthEarlyExitMinutes }}</div><div class="mt-label">Minutos de salida temprana</div><div class="mt-trend" style="color:#059669">Acumulados este mes</div></div>
            </article>
        </section>

        <section class="employee-history" aria-labelledby="employee-history-title">
            <header class="employee-history-heading"><div><h2 id="employee-history-title">Mis últimas asistencias</h2><p>Registros recientes de entrada y salida</p></div><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('asistencia.index') }}">Ver asistencia</a></header>
            @if($recentAttendance->isEmpty())
                <div class="employee-history-empty">Todavía no tienes asistencias registradas.</div>
            @else
                <div class="table-wrap"><table class="bs-table hover">
                    <thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Retardo</th><th>Estado</th></tr></thead>
                    <tbody>
                    @foreach($recentAttendance as $record)
                        <tr>
                            <td>{{ $record->fecha?->locale('es')->translatedFormat('D j M Y') ?? '—' }}</td>
                            <td>{{ $record->llegada?->format('H:i') ?? '—' }}</td>
                            <td>{{ $record->salida?->format('H:i') ?? '—' }}</td>
                            <td>{{ $record->minutos_tarde ? $record->minutos_tarde . ' min' : '—' }}</td>
                            <td><span class="employee-status {{ $record->salida ? 'is-complete' : 'is-pending' }}">{{ $record->salida ? 'Completa' : 'Salida pendiente' }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>
    @endif
</section>
@elseif((int) auth()->user()->profile === 2)
<style>
    .page-inner { max-width:none; }
    .manager-dashboard { width:100%; }
    .manager-banner { position:relative; display:flex; align-items:center; gap:1.25rem; overflow:hidden; padding:1.45rem 1.75rem; margin-bottom:1.25rem; border:1px solid rgba(255,255,255,.08); border-radius:1.15rem; color:#fff; background:linear-gradient(135deg,#0f0c29 0%,#302b63 56%,#24243e 100%); }
    .manager-banner::before { content:""; position:absolute; inset:0; background:radial-gradient(ellipse 60% 80% at 90% 50%,rgba(79,110,247,.22),transparent 70%); pointer-events:none; }
    .manager-avatar { position:relative; display:flex; align-items:center; justify-content:center; width:60px; height:60px; flex:none; border:3px solid rgba(255,255,255,.2); border-radius:50%; color:#fff; background:linear-gradient(135deg,#4f6ef7,#8b5cf6); box-shadow:0 0 0 4px rgba(79,110,247,.25); font-size:1.2rem; font-weight:900; }
    .manager-banner-copy { position:relative; min-width:0; }
    .manager-banner-copy h1 { margin:0; color:#fff; font-size:1.2rem; font-weight:800; line-height:1.25; }
    .manager-banner-copy p { margin:.35rem 0 0; color:rgba(255,255,255,.68); font-size:.82rem; line-height:1.5; }
    .manager-banner-copy strong { color:#9ab2ff; }
    .manager-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.875rem; margin-bottom:1.25rem; }
    .manager-metric { display:flex; align-items:flex-start; gap:.8rem; min-width:0; padding:1rem 1.05rem; border:1px solid var(--card-border); border-radius:.95rem; background:var(--card-bg); }
    .manager-metric-icon { display:flex; align-items:center; justify-content:center; width:42px; height:42px; flex:none; border-radius:.8rem; font-size:1rem; }
    .manager-metric-value { color:var(--body-text); font-size:1.25rem; font-weight:900; line-height:1.15; }
    .manager-metric-label { margin-top:.25rem; color:var(--body-text-muted); font-size:.68rem; font-weight:700; letter-spacing:.055em; text-transform:uppercase; }
    .manager-metric-note { margin-top:.35rem; font-size:.72rem; font-weight:700; }
    .manager-activity { overflow:hidden; border:1px solid var(--card-border); border-radius:.875rem; background:var(--card-bg); }
    .manager-activity-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.1rem 1.25rem; border-bottom:1px solid var(--body-border); }
    .manager-activity-heading h2 { margin:0 0 .25rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .manager-activity-heading p { margin:0; color:var(--body-text-muted); font-size:.8rem; }
    .manager-activity table { width:100%; margin:0; }
    .manager-employee { color:var(--body-text); font-weight:700; }
    .manager-company { margin-top:.15rem; color:var(--body-text-muted); font-size:.73rem; }
    .manager-status { display:inline-flex; align-items:center; padding:.25rem .55rem; border-radius:999px; color:#687087; background:rgba(120,130,150,.1); font-size:.72rem; font-weight:700; white-space:nowrap; }
    .manager-status.is-good { color:#087f5b; background:rgba(16,185,129,.1); }
    .manager-status.is-alert { color:#b42318; background:rgba(239,68,68,.08); }
    .manager-status.is-pending { color:#9a6700; background:rgba(234,179,8,.13); }
    .manager-empty { padding:1.75rem 1rem; color:var(--body-text-muted); font-size:.85rem; text-align:center; }
    .membership-alert { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .membership-modal { position:fixed; inset:0; z-index:11000; display:grid; place-items:center; padding:1rem; background:rgba(15,19,38,.62); }
    .membership-modal[hidden] { display:none; }
    .membership-modal-card { width:min(100%,560px); max-height:min(90vh,760px); overflow:auto; padding:1.35rem; border:1px solid var(--body-border); border-radius:1rem; background:var(--card-bg); box-shadow:0 24px 70px rgba(10,15,35,.28); }
    .membership-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .membership-modal-head h2 { margin:0; color:var(--body-text); font-size:1.15rem; font-weight:800; }
    .membership-modal-head p { margin:.3rem 0 0; color:var(--body-text-muted); font-size:.84rem; }
    .membership-card-option { display:flex; align-items:center; gap:.8rem; padding:.85rem; margin:.6rem 0; border:1px solid var(--body-border); border-radius:.75rem; cursor:pointer; }
    .membership-card-option:has(input:checked) { border-color:#536df5; background:rgba(83,109,245,.06); }
    .membership-card-option-copy { flex:1; color:var(--body-text); font-weight:700; }
    .membership-card-option small { display:block; margin-top:.15rem; color:var(--body-text-muted); font-weight:400; }
    .membership-default { color:#087f5b; font-size:.72rem; font-weight:750; white-space:nowrap; }
    .membership-payment-error { margin:.75rem 0; color:#b42318; font-size:.84rem; }
    .membership-modal-actions { display:flex; justify-content:flex-end; gap:.65rem; margin-top:1.1rem; }
    @media(max-width:1050px) { .manager-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:700px) { .manager-banner { align-items:flex-start; padding:1.2rem; } .manager-avatar { width:48px; height:48px; } }
    @media(max-width:480px) { .manager-metrics { grid-template-columns:1fr; gap:.65rem; } .manager-metric { padding:.85rem .95rem; } .manager-activity-heading { align-items:flex-start; flex-direction:column; } }
    @media(max-width:575px) { .membership-alert { align-items:flex-start; flex-direction:column; } .membership-modal-card { padding:1rem; } }
</style>
<section class="manager-dashboard">
    @if(($renewalCompanies ?? collect())->isNotEmpty())
        <div class="membership-alerts" aria-label="Pagos de membresía pendientes">
            @foreach($renewalCompanies as $renewal)
                @php($company = $renewal['empresa'])
                @php($modalId = 'membership-payment-' . $company->id)
                <div class="alert alert-danger membership-alert" role="alert" id="membership-alert-{{ $company->id }}">
                    <div><strong>Pago pendiente de la membresía de Horalia</strong><br>{{ $company->razon_social }} · Renovación vencida el {{ $company->fecha_renovacion->format('d/m/Y') }}</div>
                    <button class="btn-hr btn-primary-hr" type="button" data-open-membership-modal="{{ $modalId }}">Pagar ${{ number_format((float) $company->precio, 2) }}</button>
                </div>
                <div class="membership-modal" id="{{ $modalId }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" hidden>
                    <div class="membership-modal-card">
                        <div class="membership-modal-head">
                            <div><h2 id="{{ $modalId }}-title">Pagar membresía</h2><p>{{ $company->razon_social }}</p></div>
                            <button class="btn-hr btn-outline-hr" type="button" data-close-membership-modal aria-label="Cerrar">Cerrar</button>
                        </div>
                        <p><strong>Concepto:</strong> MEMBRESIA HORALIA<br><strong>Importe:</strong> ${{ number_format((float) $company->precio, 2) }} MXN</p>
                        <form class="membership-payment-form" method="POST" action="{{ route('membership.pay', ['empresa' => $company->id]) }}" data-alert="membership-alert-{{ $company->id }}">
                            @csrf
                            @if($renewal['cards'])
                                @if($renewal['automatic_pending'])
                                    <div class="membership-payment-error" role="status">Hay un cobro automático pendiente de confirmación. No intentes otro pago todavía; el sistema volverá a consultar el resultado para evitar un cargo duplicado.</div>
                                @endif
                                <fieldset style="border:0;padding:0;margin:0">
                                    <legend class="form-label-hr">Tarjeta</legend>
                                    @foreach($renewal['cards'] as $card)
                                        @php($cardData = $card['card'] ?? [])
                                        <label class="membership-card-option">
                                            <input type="radio" name="payment_method_id" value="{{ $card['id'] }}" @checked(($renewal['pending_payment_method_id'] ?? $company->stripe_default_payment_method_id) === $card['id'] || (!$renewal['pending_payment_method_id'] && !$company->stripe_default_payment_method_id && $loop->first)) required>
                                            <span class="membership-card-option-copy">{{ $cardData['brand'] ?? 'Tarjeta' }} ···· {{ $cardData['last4'] ?? '----' }}<small>Vence {{ sprintf('%02d', $cardData['exp_month'] ?? 0) }}/{{ $cardData['exp_year'] ?? '----' }}</small></span>
                                            @if($company->stripe_default_payment_method_id === $card['id'])<span class="membership-default">Predeterminada</span>@endif
                                        </label>
                                    @endforeach
                                </fieldset>
                                <div class="membership-payment-error" role="alert" hidden></div>
                                <div class="membership-modal-actions">
                                    <button class="btn-hr btn-outline-hr" type="button" data-close-membership-modal>Cancelar</button>
                                    <button class="btn-hr btn-primary-hr" type="submit" @disabled($renewal['automatic_pending'])>Confirmar pago</button>
                                </div>
                            @else
                                <div class="membership-payment-error" role="status">No hay tarjetas disponibles. Agrega una tarjeta para pagar la renovación.</div>
                                <div class="membership-modal-actions"><a class="btn-hr btn-primary-hr" href="{{ route('payment-methods.index', ['empresa_id' => $company->id]) }}">Administrar tarjetas</a><button class="btn-hr btn-outline-hr" type="button" data-close-membership-modal>Cerrar</button></div>
                            @endif
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <header class="manager-banner">
        <div class="manager-avatar">{{ mb_strtoupper(mb_substr(trim(auth()->user()->name), 0, 1)) }}</div>
        <div class="manager-banner-copy">
            <h1>Hola, {{ auth()->user()->name }} 👋</h1>
            <p>{{ $companyCount }} {{ $companyCount === 1 ? 'empresa asignada' : 'empresas asignadas' }} · <strong>{{ now()->locale('es')->translatedFormat('l, j \\d\\e F') }}</strong> · {{ $scheduledEmployeeCount }} {{ $scheduledEmployeeCount === 1 ? 'persona con jornada hoy' : 'personas con jornada hoy' }}</p>
        </div>
    </header>

    <section class="manager-metrics" aria-label="Resumen de asistencia de hoy">
        <article class="manager-metric">
            <div class="manager-metric-icon" style="background:rgba(79,110,247,.12);color:#4f6ef7"><i class="fa-light fa-users" aria-hidden="true"></i></div>
            <div><div class="manager-metric-value">{{ $activeEmployeeCount }}</div><div class="manager-metric-label">Personal activo</div><div class="manager-metric-note" style="color:#4f6ef7">En tus empresas</div></div>
        </article>
        <article class="manager-metric">
            <div class="manager-metric-icon" style="background:rgba(16,185,129,.12);color:#059669"><i class="fa-light fa-calendar-check" aria-hidden="true"></i></div>
            <div><div class="manager-metric-value">{{ $presentTodayCount }}<span style="font-size:.78rem;color:var(--body-text-muted)">/{{ $scheduledEmployeeCount }}</span></div><div class="manager-metric-label">Entradas registradas</div><div class="manager-metric-note" style="color:#059669">De las jornadas de hoy</div></div>
        </article>
        <article class="manager-metric">
            <div class="manager-metric-icon" style="background:rgba(245,158,11,.13);color:#d97706"><i class="fa-light fa-clock" aria-hidden="true"></i></div>
            <div><div class="manager-metric-value">{{ $lateTodayCount }}</div><div class="manager-metric-label">Retardos de hoy</div><div class="manager-metric-note" style="color:#d97706">Con entrada registrada</div></div>
        </article>
        <article class="manager-metric">
            <div class="manager-metric-icon" style="background:rgba(239,68,68,.1);color:#dc3545"><i class="fa-light fa-bell" aria-hidden="true"></i></div>
            <div><div class="manager-metric-value">{{ $pendingEntryCount }}</div><div class="manager-metric-label">Entradas pendientes</div><div class="manager-metric-note" style="color:#b42318">{{ $pendingExitCount }} {{ $pendingExitCount === 1 ? 'salida pendiente' : 'salidas pendientes' }}</div></div>
        </article>
    </section>

    <section class="manager-activity" aria-labelledby="manager-activity-title">
        <header class="manager-activity-heading"><div><h2 id="manager-activity-title">Actividad reciente</h2><p>Últimos registros de asistencia de tus empresas</p></div><a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('asistencia.index') }}">Ver asistencias</a></header>
        @php($recentAttendance = $recentAttendance ?? collect())
        @if($recentAttendance->isEmpty())
            <div class="manager-empty">Todavía no hay registros de asistencia para mostrar.</div>
        @else
            <div class="table-wrap"><table class="bs-table hover">
                <thead><tr><th>Colaborador</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Retardo</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recentAttendance as $attendanceRecord): ?>
                    <?php
                        $employee = $attendanceRecord->personal;
                        $outOfRange = $attendanceRecord->rango_entrada === 'Fuera de rango' || $attendanceRecord->rango_salida === 'Fuera de rango';
                    ?>
                    <tr>
                        <td><div class="manager-employee">{{ $employee?->nombre ?? 'No disponible' }}</div><div class="manager-company">{{ $employee?->empresa?->razon_social ?? '—' }}</div></td>
                        <td>{{ $attendanceRecord->fecha?->locale('es')->translatedFormat('D j M Y') ?? '—' }}</td>
                        <td>{{ $attendanceRecord->llegada?->format('H:i') ?? '—' }}</td>
                        <td>{{ $attendanceRecord->salida?->format('H:i') ?? '—' }}</td>
                        <td>{{ $attendanceRecord->minutos_tarde ? $attendanceRecord->minutos_tarde . ' min' : '—' }}</td>
                        <td>
                            @if($outOfRange)<span class="manager-status is-alert">Fuera de rango</span>
                            @elseif($attendanceRecord->minutos_tarde)<span class="manager-status is-pending">Retardo</span>
                            @elseif(!$attendanceRecord->salida)<span class="manager-status is-pending">Salida pendiente</span>
                            @else<span class="manager-status is-good">Registrada</span>@endif
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        @endif
    </section>
</section>
@if(($renewalCompanies ?? collect())->isNotEmpty())
<script src="https://js.stripe.com/v3/"></script>
<script>
    (() => {
        const publishableKey = @json(config('services.stripe.publishable_key'));
        const formatRenewalDate = (date) => {
            const match = String(date ?? '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return match ? `${match[3]}/${match[2]}/${match[1]}` : date;
        };
        const showSuccess = (form, message, date) => {
            const modal = form.closest('.membership-modal');
            modal.hidden = true;
            document.body.style.overflow = '';
            document.getElementById(form.dataset.alert)?.remove();
            const success = document.createElement('div');
            success.className = 'alert alert-success';
            success.setAttribute('role', 'status');
            success.textContent = message + (date ? ' Nueva fecha de renovación: ' + formatRenewalDate(date) + '.' : '');
            document.querySelector('.manager-dashboard').prepend(success);
        };
        document.querySelectorAll('[data-open-membership-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openMembershipModal);
                if (modal) { modal.hidden = false; document.body.style.overflow = 'hidden'; }
            });
        });
        document.querySelectorAll('[data-close-membership-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.membership-modal');
                if (modal) { modal.hidden = true; document.body.style.overflow = ''; }
            });
        });
        document.querySelectorAll('.membership-payment-form').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submit = form.querySelector('button[type="submit"]');
                const error = form.querySelector('.membership-payment-error[role="alert"]');
                const selected = form.querySelector('input[name="payment_method_id"]:checked');
                if (!selected) return;
                submit.disabled = true;
                submit.textContent = 'Procesando…';
                error.hidden = true;
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value },
                        body: new FormData(form),
                    });
                    const data = await response.json();
                    if (response.ok && data.status === 'succeeded') {
                        showSuccess(form, data.message, data.new_date);
                        return;
                    }
                    if (data.status === 'requires_action' && data.client_secret && data.payment_intent_id && window.Stripe && publishableKey) {
                        const stripe = window.Stripe(publishableKey);
                        await stripe.confirmCardPayment(data.client_secret);
                        const confirmData = new FormData();
                        confirmData.set('payment_intent_id', data.payment_intent_id);
                        const confirmed = await fetch(form.action.replace(/\/pagar$/, '/confirmar'), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value },
                            body: confirmData,
                        });
                        const finalData = await confirmed.json();
                        if (confirmed.ok && finalData.status === 'succeeded') {
                            showSuccess(form, finalData.message, finalData.new_date);
                            return;
                        }
                        error.textContent = finalData.message || 'El banco no pudo confirmar el pago.';
                        error.hidden = false;
                        if (finalData.status === 'failed') form.querySelectorAll('input[name="payment_method_id"]').forEach((radio) => radio.disabled = false);
                        else form.querySelectorAll('input[name="payment_method_id"]').forEach((radio) => radio.disabled = radio.value !== selected.value);
                        return;
                    }
                    error.textContent = data.message || 'No se pudo procesar el cobro.';
                    error.hidden = false;
                    if (data.status === 'failed') {
                        form.querySelectorAll('input[name="payment_method_id"]').forEach((radio) => radio.disabled = false);
                    } else if (data.status === 'indeterminate') {
                        const requiredId = data.required_payment_method_id || selected.value;
                        form.querySelectorAll('input[name="payment_method_id"]').forEach((radio) => {
                            radio.checked = radio.value === requiredId;
                            radio.disabled = radio.value !== requiredId;
                        });
                        if (data.pending_source === 'automatico') {
                            form.dataset.blocked = 'true';
                            submit.disabled = true;
                        }
                    }
                } catch (exception) {
                    error.textContent = 'No se pudo confirmar la respuesta. Reintenta con esta misma tarjeta para evitar un cobro duplicado.';
                    error.hidden = false;
                    form.querySelectorAll('input[name="payment_method_id"]').forEach((radio) => radio.disabled = radio.value !== selected.value);
                } finally {
                    if (form.dataset.blocked !== 'true') submit.disabled = false;
                    submit.textContent = form.dataset.blocked === 'true' ? 'Cobro pendiente' : 'Confirmar pago';
                }
            });
        });
    })();
</script>
@endif
@else
    <section class="dashboard-welcome" aria-labelledby="welcome-title">
        <h1 id="welcome-title">Hola, {{ auth()->user()->name }}</h1>
        <p>Este es el espacio de trabajo de {{ config('app.name') }}. Desde el menú puedes organizar tu empresa y gestionar la asistencia de tu equipo.</p>
    </section>

    <section class="dashboard-start" aria-label="Primeros pasos">
        <a class="start-item" href="{{ route('empresas.index') }}">
            <span class="start-item-icon">@include('dashboard.partials.icon', ['name' => 'building'])</span>
            <span><h2>Configura tu organización</h2><p>Administra empresas, centros de trabajo, departamentos y puestos.</p></span>
        </a>
        <a class="start-item" href="{{ route('dashboard.module', ['section' => 'horarios']) }}">
            <span class="start-item-icon">@include('dashboard.partials.icon', ['name' => 'calendar'])</span>
            <span><h2>Organiza a tu equipo</h2><p>Define horarios y registra al personal de tu empresa.</p></span>
        </a>
        <a class="start-item" href="{{ route('dashboard.module', ['section' => 'asistencia']) }}">
            <span class="start-item-icon">@include('dashboard.partials.icon', ['name' => 'clock'])</span>
            <span><h2>Da seguimiento</h2><p>Consulta la asistencia y prepara reportes de tu equipo.</p></span>
        </a>
    </section>

    <div class="dashboard-note">
        @include('dashboard.partials.icon', ['name' => 'info'])
        <span>Los módulos están integrándose. Por ahora, el menú muestra la estructura que tendrá el sistema; cada sección irá habilitándose conforme avancemos.</span>
    </div>
@endif
@endsection
