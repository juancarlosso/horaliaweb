@extends('layouts.dashboard', ['pageTitle' => 'Suscripción', 'activeSection' => null])

@section('content')
<style>
    .subscription-page { width:100%; max-width:none; }
    .subscription-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .subscription-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .subscription-heading p,.subscription-muted { margin:0; color:var(--body-text-muted); font-size:.875rem; line-height:1.6; }
    .subscription-feedback { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(16,185,129,.22); border-radius:.625rem; color:#087f5b; background:rgba(16,185,129,.08); font-size:.85rem; }
    .subscription-feedback.error { color:#b42318; border-color:rgba(239,68,68,.2); background:rgba(239,68,68,.07); }
    .subscription-company { max-width:480px; margin-bottom:1.25rem; }
    .subscription-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
    .subscription-detail { padding:1rem; border:1px solid var(--body-border); border-radius:.65rem; background:var(--card-bg); }
    .subscription-detail-label { margin-bottom:.3rem; color:var(--body-text-muted); font-size:.78rem; }
    .subscription-detail-value { color:var(--body-text); font-size:1rem; font-weight:750; }
    .subscription-actions { display:flex; flex-wrap:wrap; gap:.65rem; margin-top:1.25rem; }
    .subscription-empty { padding:1.5rem; color:var(--body-text-muted); text-align:center; }
    @media(max-width:575px) { .subscription-heading { flex-direction:column; } .subscription-details { grid-template-columns:1fr; } }
</style>

<section class="subscription-page">
    <header class="subscription-heading">
        <div><h1>Suscripción</h1><p>Consulta y administra la suscripción de cada empresa.</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('home') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    @if($errors->has('subscription')) <div class="subscription-feedback error" role="alert">{{ $errors->first('subscription') }}</div> @endif

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Datos de la suscripción</h2><p class="card-subtitle-hr">Cada empresa tiene su propio periodo y renovación.</p></div></div>
        <div class="card-bd">
            @if($companies->isEmpty())
                <div class="subscription-empty">Tu cuenta todavía no tiene empresas asignadas.</div>
            @else
                <form method="GET" action="{{ route('subscription.index') }}" class="subscription-company">
                    <label class="form-label-hr" for="empresa_id">Empresa</label>
                    <select class="hr-input select2" id="empresa_id" name="empresa_id" onchange="this.form.submit()">
                        @foreach($companies as $item)
                            <option value="{{ $item->id }}" @selected($company?->id === $item->id)>{{ $item->razon_social ?: 'Empresa #' . $item->id }}</option>
                        @endforeach
                    </select>
                </form>

                @if($company)
                    @php
                        $periodVigente = $company->activa && $company->fecha_renovacion && $company->fecha_renovacion->toDateString() > today()->toDateString();
                        $subscriptionStatus = $company->cancelar_al_renovar
                            ? 'Cancelación programada'
                            : ($periodVigente ? 'Activa' : ($company->activa ? 'Renovación pendiente' : 'Inactiva'));
                    @endphp
                    <div class="subscription-details">
                        <div class="subscription-detail"><div class="subscription-detail-label">Estado</div><div class="subscription-detail-value">{{ $subscriptionStatus }}</div></div>
                        <div class="subscription-detail"><div class="subscription-detail-label">Precio mensual</div><div class="subscription-detail-value">${{ number_format((float) $company->precio, 2) }} MXN</div></div>
                        <div class="subscription-detail"><div class="subscription-detail-label">Fecha de inicio</div><div class="subscription-detail-value">{{ $company->fecha_inicio?->format('d/m/Y') ?? '—' }}</div></div>
                        <div class="subscription-detail"><div class="subscription-detail-label">Próxima fecha de renovación</div><div class="subscription-detail-value">{{ $company->fecha_renovacion?->format('d/m/Y') ?? '—' }}</div></div>
                    </div>

                    @if($company->cancelar_al_renovar)
                        <p class="subscription-muted" style="margin-top:1rem">Seguirás teniendo acceso hasta el {{ $company->fecha_renovacion?->format('d/m/Y') ?? 'fin del periodo actual' }}. No se realizarán más renovaciones automáticas.</p>
                        @if($periodVigente)
                            <form method="POST" action="{{ route('subscription.resume', $company) }}" class="subscription-actions">
                                @csrf @method('DELETE')
                                <button class="btn-hr btn-outline-hr" type="submit">Reanudar renovación</button>
                            </form>
                        @endif
                    @elseif($periodVigente)
                        <form method="POST" action="{{ route('subscription.cancel', $company) }}" class="subscription-actions" data-confirm-subscription data-renewal-date="{{ $company->fecha_renovacion->format('d/m/Y') }}">
                            @csrf
                            <button class="btn-hr btn-danger-hr" type="submit">Cancelar suscripción</button>
                        </form>
                    @else
                        <p class="subscription-muted" style="margin-top:1rem">No hay un periodo activo pendiente de renovación. Consulta el historial de pagos si necesitas revisar un cobro.</p>
                    @endif
                @endif
            @endif
        </div>
    </div>
</section>
<script>
    document.querySelectorAll('[data-confirm-subscription]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === 'true') return;

            event.preventDefault();
            const message = 'Conservarás el acceso hasta el ' + form.dataset.renewalDate + ' y no se realizará la siguiente renovación.';
            if (!window.Swal) {
                if (window.confirm('¿Cancelar la suscripción? ' + message)) {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: '¿Cancelar suscripción?',
                text: message,
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar suscripción',
                cancelButtonText: 'Conservar suscripción',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn-hr btn-danger-hr',
                    cancelButton: 'btn-hr btn-outline-hr'
                },
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
            });
        });
    });
</script>
@endsection
