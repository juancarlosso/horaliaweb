@extends('layouts.dashboard', ['pageTitle' => 'Métodos de Pago', 'activeSection' => null])

@section('content')
<style>
    .pm-page { max-width:100%; }
    .pm-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .pm-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .pm-heading p,.pm-muted { margin:0; color:var(--body-text-muted); font-size:.875rem; line-height:1.6; }
    .pm-feedback { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(239,68,68,.2); border-radius:.625rem; color:#b42318; background:rgba(239,68,68,.07); font-size:.85rem; }
    .pm-success { color:#087f5b; border-color:rgba(16,185,129,.22); background:rgba(16,185,129,.08); }
    .pm-company { max-width:480px; margin-bottom:1.25rem; }
    .pm-list { display:grid; gap:.75rem; margin:1rem 0 1.25rem; }
    .pm-card { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.1rem; border:1px solid var(--body-border); border-radius:.75rem; background:var(--card-bg); }
    .pm-card-copy { display:flex; align-items:center; gap:.9rem; }
    .pm-card-icon { display:grid; place-items:center; width:42px; height:42px; border-radius:.65rem; color:#536df5; background:rgba(83,109,245,.1); }
    .pm-card-icon svg { width:21px; height:21px; fill:none; stroke:currentColor; stroke-width:1.7; }
    .pm-card-name { color:var(--body-text); font-weight:700; text-transform:capitalize; }
    .pm-card-meta { margin-top:.15rem; color:var(--body-text-muted); font-size:.82rem; }
    .pm-card-actions { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; }
    .pm-default-badge { padding:.3rem .6rem; border-radius:999px; color:#087f5b; background:rgba(16,185,129,.1); font-size:.75rem; font-weight:700; white-space:nowrap; }
    .pm-form { display:flex; flex-direction:column; gap:1rem; }
    #payment-element { padding:1rem; border:1px solid var(--body-border); border-radius:.75rem; background:var(--card-bg); }
    .pm-consent { display:flex; align-items:flex-start; gap:.6rem; color:var(--body-text-muted); font-size:.84rem; line-height:1.55; }
    .pm-consent input { margin-top:.2rem; }
    .pm-empty { padding:1.25rem; border:1px dashed var(--body-border); border-radius:.75rem; color:var(--body-text-muted); text-align:center; }
    .pm-loading { position:fixed; inset:0; z-index:10000; display:grid; place-items:center; padding:1rem; background:rgba(15,19,38,.58); backdrop-filter:blur(3px); }
    .pm-loading[hidden] { display:none; }
    .pm-loading-card { display:flex; align-items:center; gap:1rem; width:min(100%,420px); padding:1.25rem; border:1px solid var(--body-border); border-radius:.9rem; color:var(--body-text); background:var(--card-bg); box-shadow:0 18px 55px rgba(10,15,35,.24); }
    .pm-spinner { width:30px; height:30px; flex:none; border:3px solid rgba(83,109,245,.2); border-top-color:#536df5; border-radius:50%; animation:pm-spin .8s linear infinite; }
    .pm-loading-title { margin:0 0 .2rem; font-weight:750; }
    .pm-loading-copy { margin:0; color:var(--body-text-muted); font-size:.84rem; line-height:1.5; }
    @keyframes pm-spin { to { transform:rotate(360deg); } }
    @media(max-width:575px) { .pm-heading { flex-direction:column; } .pm-card { align-items:flex-start; } }
</style>

<section class="pm-page">
    <header class="pm-heading">
        <div><h1>Métodos de Pago</h1><p>Administra las tarjetas que podrán utilizarse para realizar cobros a cada empresa.</p></div>
        <a class="btn-hr btn-outline-hr" href="{{ route('home') }}"><i class="fa-light fa-arrow-left" aria-hidden="true"></i> Regresar</a>
    </header>

    @if($errors->has('stripe')) <div class="pm-feedback" role="alert">{{ $errors->first('stripe') }}</div> @endif

    <div class="card-hr mb-1rem">
        <div class="card-hd"><div><h2 class="card-title-hr">Tarjetas por empresa</h2><p class="card-subtitle-hr">Cada empresa mantiene sus propias tarjetas en Stripe.</p></div></div>
        <div class="card-bd">
            @if($empresas->isEmpty())
                <div class="pm-empty">Tu usuario todavía no tiene empresas asignadas.</div>
            @else
                <form method="GET" action="{{ route('payment-methods.index') }}" class="pm-company">
                    <label class="form-label-hr" for="empresa_id">Empresa</label>
                    <select class="hr-input select2" id="empresa_id" name="empresa_id" onchange="this.form.submit()" required>
                        <option value="" disabled @selected(!$empresa)>Selecciona una empresa</option>
                        @foreach($empresas as $item)
                            <option value="{{ $item->id }}" @selected($empresa?->id === $item->id)>{{ $item->razon_social ?: 'Empresa #' . $item->id }}</option>
                        @endforeach
                    </select>
                </form>

                @if($empresa)
                    @if(!$stripeReady)
                        <div class="pm-feedback" role="status">La conexión con Stripe aún no está configurada. Contacta al administrador.</div>
                    @else
                        @if(count($cards))
                            <div class="pm-list" aria-label="Tarjetas guardadas">
                                @foreach($cards as $card)
                                    @php($cardData = $card['card'] ?? [])
                                    <article class="pm-card">
                                        <div class="pm-card-copy">
                                            <span class="pm-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18m-14 5h4"/></svg></span>
                                            <div><div class="pm-card-name">{{ $cardData['brand'] ?? 'Tarjeta' }} ···· {{ $cardData['last4'] ?? '----' }}</div><div class="pm-card-meta">Vence {{ sprintf('%02d', $cardData['exp_month'] ?? 0) }}/{{ $cardData['exp_year'] ?? '----' }}</div></div>
                                        </div>
                                        <div class="pm-card-actions">
                                            @if($empresa->stripe_default_payment_method_id === $card['id'])
                                                <span class="pm-default-badge">Predeterminada</span>
                                            @else
                                                <form method="POST" action="{{ route('payment-methods.default', ['paymentMethod' => $card['id'], 'empresa_id' => $empresa->id]) }}">
                                                    @csrf @method('PUT')
                                                    <button class="btn-hr btn-outline-hr" type="submit">Hacer predeterminada</button>
                                                </form>
                                            @endif
                                        <form method="POST" action="{{ route('payment-methods.destroy', ['paymentMethod' => $card['id'], 'empresa_id' => $empresa->id]) }}" onsubmit="return confirm('¿Eliminar esta tarjeta de la empresa?')">
                                            @csrf @method('DELETE')
                                            <button class="btn-hr btn-outline-hr" type="submit">Eliminar</button>
                                        </form>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="pm-empty">Esta empresa todavía no tiene tarjetas registradas.</div>
                        @endif

                        <div class="flex-g2-wrap">
                            <button class="btn-hr btn-primary-hr" id="add-card" type="button">{{ count($cards) ? 'Agregar otra tarjeta' : 'Agregar tarjeta' }}</button>
                        </div>
                        <div id="card-error" class="pm-feedback" role="alert" hidden></div>
                        <form class="pm-form mt-1rem" id="card-form" hidden>
                            <div id="payment-element"></div>
                            <label class="pm-consent"><input id="save-consent" type="checkbox" required><span>Autorizo guardar esta tarjeta para que la empresa pueda realizar futuros cobros. La tarjeta se almacenará de forma segura en Stripe.</span></label>
                            <div class="flex-g2-wrap">
                                <button class="btn-hr btn-primary-hr" id="save-card" type="submit">Guardar tarjeta</button>
                                <button class="btn-hr btn-outline-hr" id="cancel-card" type="button">Cancelar</button>
                            </div>
                        </form>
                    @endif
                @endif
            @endif
        </div>
    </div>
</section>

<div class="pm-loading" id="payment-loading" role="status" aria-live="assertive" aria-atomic="true" aria-busy="true" hidden>
    <div class="pm-loading-card">
        <span class="pm-spinner" aria-hidden="true"></span>
        <div><p class="pm-loading-title" id="payment-loading-title">Conectando con Stripe…</p><p class="pm-loading-copy">Espera un momento. No hagas clic de nuevo ni actualices esta página.</p></div>
    </div>
</div>

@if($empresa && $stripeReady)
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (() => {
            const addButton = document.getElementById('add-card');
            const form = document.getElementById('card-form');
            const errorBox = document.getElementById('card-error');
            const loader = document.getElementById('payment-loading');
            const loaderTitle = document.getElementById('payment-loading-title');
            const companyId = @json($empresa->id);
            const confirmationUrl = @json(route('payment-methods.confirm'));
            let stripeElements;
            let paymentElement;
            let isProcessing = false;
            let allowStripeNavigation = false;

            window.addEventListener('beforeunload', (event) => {
                if (!isProcessing || allowStripeNavigation) return;
                event.preventDefault();
                event.returnValue = '';
            });

            function startProcessing(message) {
                isProcessing = true;
                loaderTitle.textContent = message;
                loader.hidden = false;
                loader.setAttribute('aria-busy', 'true');
                document.getElementById('empresa_id').disabled = true;
                document.getElementById('add-card').disabled = true;
                document.getElementById('save-card').disabled = true;
                document.getElementById('cancel-card').disabled = true;
            }

            function stopProcessing() {
                isProcessing = false;
                loader.hidden = true;
                loader.setAttribute('aria-busy', 'false');
                document.getElementById('empresa_id').disabled = false;
                document.getElementById('add-card').disabled = false;
                document.getElementById('save-card').disabled = false;
                document.getElementById('cancel-card').disabled = false;
            }

            const returnParams = new URLSearchParams(location.search);
            const returnedSetupIntent = returnParams.get('setup_intent');
            if (returnedSetupIntent) {
                const cleanUrl = new URL(location.href);
                cleanUrl.searchParams.delete('setup_intent');
                cleanUrl.searchParams.delete('setup_intent_client_secret');
                cleanUrl.searchParams.delete('consent');
                history.replaceState({}, document.title, cleanUrl.pathname + cleanUrl.search);
                if (returnParams.get('consent') === '1') confirmSavedCard(returnedSetupIntent);
            }

            addButton?.addEventListener('click', async () => {
                if (isProcessing) return;
                startProcessing('Conectando con Stripe…');
                try {
                    const response = await fetch(@json(route('payment-methods.setup-intent')), {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                        body: JSON.stringify({empresa_id: companyId})
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'No se pudo iniciar el registro de la tarjeta.');
                    const stripe = Stripe(@json($publishableKey));
                    stripeElements = stripe.elements({
                        clientSecret: data.client_secret,
                        customerSessionClientSecret: data.customer_session_client_secret
                    });
                    paymentElement = stripeElements.create('payment');
                    paymentElement.mount('#payment-element');
                    form.dataset.stripeReady = 'true';
                    window.horaliaStripe = stripe;
                    form.hidden = false;
                    addButton.hidden = true;
                } catch (error) {
                    showError(error.message);
                } finally {
                    stopProcessing();
                }
            });

            document.getElementById('cancel-card')?.addEventListener('click', () => {
                if (!isProcessing) location.reload();
            });
            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (isProcessing || !document.getElementById('save-consent').checked) return;
                startProcessing('Guardando tarjeta…');
                try {
                    const returnUrl = new URL(location.href);
                    returnUrl.searchParams.set('consent', '1');
                    allowStripeNavigation = true;
                    const result = await window.horaliaStripe.confirmSetup({
                        elements: stripeElements,
                        confirmParams: {return_url: returnUrl.toString()},
                        redirect: 'if_required'
                    });
                    allowStripeNavigation = false;
                    if (result.error) {
                        showError(result.error.message || 'No se pudo guardar la tarjeta.');
                        stopProcessing();
                        return;
                    }
                    await confirmSavedCard(result.setupIntent.id);
                } catch (error) {
                    showError(error.message || 'No se pudo guardar la tarjeta. Inténtalo de nuevo.');
                    stopProcessing();
                }
            });

            async function confirmSavedCard(setupIntentId) {
                if (!isProcessing) startProcessing('Confirmando registro de tarjeta…');
                try {
                    const response = await fetch(confirmationUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                        body: JSON.stringify({empresa_id: companyId, setup_intent_id: setupIntentId, consent: '1'})
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        showError(data.message || 'No se pudo confirmar la tarjeta. Actualiza la página para revisar el estado.');
                        stopProcessing();
                        return;
                    }
                    isProcessing = false;
                    loader.setAttribute('aria-busy', 'false');
                    location.href = @json(route('payment-methods.index', ['empresa_id' => $empresa->id])) + '&saved=1';
                } catch (error) {
                    showError(error.message || 'No se pudo confirmar la tarjeta. Actualiza la página para revisar el estado.');
                    stopProcessing();
                }
            }

            function showError(message) {
                errorBox.textContent = message;
                errorBox.hidden = false;
            }
        })();
    </script>
@endif
@endsection
