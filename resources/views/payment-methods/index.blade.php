@extends('layouts.dashboard', ['pageTitle' => 'Métodos de Pago', 'activeSection' => null])

@section('content')
<style>
    .pm-page { max-width:100%; }
    .pm-muted { margin:0; color:var(--body-text-muted); font-size:.875rem; line-height:1.6; }
    .pm-feedback { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(239,68,68,.2); border-radius:.625rem; color:#b42318; background:rgba(239,68,68,.07); font-size:.85rem; }
    .pm-success { color:#087f5b; border-color:rgba(16,185,129,.22); background:rgba(16,185,129,.08); }
    .pm-company { display:flex; align-items:flex-end; gap:.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    .pm-company-field { display:grid; gap:.35rem; width:min(100%,480px); }
    .pm-company-field .hr-input { width:100%; }
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
    .pm-transfer-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:2rem; align-items:start; }
    .pm-transfer-notice { text-align:left; }
    .pm-transfer-notice h3 { color:var(--body-text); }
    .pm-transfer-notice ul { padding-left:1.25rem; line-height:1.6; }
    @media(max-width:767px) { .pm-transfer-grid { grid-template-columns:1fr; } }
    .pm-config-head { display:flex; align-items:center; gap:1rem; }
    .pm-icon { display:grid; place-items:center; width:44px; height:44px; flex:none; border-radius:12px; color:#6240e8; background:#eeeaff; font-size:1.1rem; }
    .pm-company-field { flex:1 1 280px; width:auto; }
    .pm-options { display:flex; gap:.5rem; flex-wrap:wrap; }
    .pm-option { display:flex; align-items:center; gap:.55rem; padding:.7rem 1rem; border:1px solid #dcdff0; border-radius:.65rem; color:#202344; cursor:pointer; background:#fff; font-size:.85rem; font-weight:700; }
    .pm-option:has(input:checked) { border-color:#7656fa; background:#f2efff; color:#4b22c6; box-shadow:0 0 0 1px #7656fa; }
    .pm-option input { accent-color:#623be7; }
    .pm-status { display:flex; justify-content:space-between; flex-wrap:wrap; gap:.5rem; margin:0 0 1.25rem; padding:.8rem 1rem; border:1px solid #c9f0d4; border-radius:.65rem; color:#14652e; background:#e8f8ed; }
    .pm-transfer-grid { gap:1rem; align-items:stretch; }
    .pm-transfer-panel { overflow:hidden; border:1px solid #e8e5fa; border-radius:.85rem; background:#fff; box-shadow:0 5px 18px rgba(70,48,155,.08); text-align:left; }
    .pm-panel-head { display:flex; align-items:center; gap:.8rem; padding:1rem 1.2rem; background:#f1efff; }
    .pm-panel-head h3 { margin:0; color:#4c25c7; font-size:1.12rem; font-weight:800; }
    .pm-panel-head p { margin:.15rem 0 0; color:#665e99; font-size:.8rem; }
    .pm-panel-body { padding:1rem 1.25rem; }
    .pm-notice-list { display:grid; gap:.8rem; list-style:none; padding:0; margin:0 0 1.1rem; color:#272a46; font-size:.88rem; line-height:1.45; }
    .pm-notice-list li { display:flex; gap:.8rem; }
    .pm-notice-list i { width:20px; flex:none; color:#6944eb; font-size:1.05rem; text-align:center; padding-top:.15rem; }
    .pm-bank { background:linear-gradient(145deg,#fafaff,#f1efff); }
    .pm-bank-row { display:grid; grid-template-columns:minmax(130px, 40%) 1fr; border-bottom:1px solid #e2dff3; font-size:.85rem; }
    .pm-bank-row span,.pm-bank-row strong { padding:.65rem .4rem; overflow-wrap:anywhere; }
    .pm-bank-row span { color:#37316d; } .pm-bank-row strong { color:#20213b; }
    .pm-bank-row i { width:24px; color:#6944eb; }
    .pm-concept { margin-top:1rem; padding:1rem; border:1px solid #987cff; border-radius:.75rem; color:#4a27bc; background:#f3f0ff; }
    .pm-concept strong { font-size:.82rem; } .pm-concept-inner { display:flex; align-items:center; gap:.75rem; margin-top:.55rem; }
    .pm-concept-value { flex:1; margin:0; padding:.5rem; border-radius:.55rem; background:#e2d9ff; color:#5f35e5; text-align:center; font-size:1.3rem; font-weight:800; overflow-wrap:anywhere; }
    @media(max-width:575px) { .pm-concept-inner { flex-wrap:wrap; } .pm-bank-row { grid-template-columns:1fr; } .pm-bank-row strong { padding-top:0; } }
    .pm-stripe-logo { display:block; width:120px; max-width:32vw; height:auto; flex:none; }
    .pm-loading { position:fixed; inset:0; z-index:10000; display:grid; place-items:center; padding:1rem; background:rgba(15,19,38,.58); backdrop-filter:blur(3px); }
    .pm-loading[hidden] { display:none; }
    .pm-loading-card { display:flex; align-items:center; gap:1rem; width:min(100%,420px); padding:1.25rem; border:1px solid var(--body-border); border-radius:.9rem; color:var(--body-text); background:var(--card-bg); box-shadow:0 18px 55px rgba(10,15,35,.24); }
    .pm-spinner { width:30px; height:30px; flex:none; border:3px solid rgba(83,109,245,.2); border-top-color:#536df5; border-radius:50%; animation:pm-spin .8s linear infinite; }
    .pm-loading-title { margin:0 0 .2rem; font-weight:750; }
    .pm-loading-copy { margin:0; color:var(--body-text-muted); font-size:.84rem; line-height:1.5; }
    @keyframes pm-spin { to { transform:rotate(360deg); } }
    @media(max-width:575px) { .pm-card { align-items:flex-start; } }
</style>

<section class="pm-page">
    @if($errors->has('stripe')) <div class="pm-feedback" role="alert">{{ $errors->first('stripe') }}</div> @endif
    @if($errors->has('metodo_pago')) <div class="pm-feedback" role="alert">{{ $errors->first('metodo_pago') }}</div> @endif

    <div class="card-hr mb-1rem">
        <div class="card-hd"><div class="pm-config-head"><span class="pm-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span><div><h2 class="card-title-hr">Métodos de Pago</h2><p class="card-subtitle-hr">Selecciona la empresa y define su método de pago.</p></div></div></div>
        <div class="card-bd">
            @if($empresas->isEmpty())
                <div class="pm-empty">Tu usuario todavía no tiene empresas asignadas.</div>
            @else
                <div class="pm-company">
                    <form method="GET" action="{{ route('payment-methods.index') }}" class="pm-company-field">
                        <label class="form-label-hr" for="empresa_id">Empresa</label>
                        <select class="hr-input select2" id="empresa_id" name="empresa_id" onchange="this.form.submit()" required>
                            <option value="" disabled @selected(!$empresa)>Selecciona una empresa</option>
                            @foreach($empresas as $item)
                                <option value="{{ $item->id }}" @selected($empresa?->id === $item->id)>{{ $item->nombre ?: 'Empresa #' . $item->id }}</option>
                            @endforeach
                        </select>
                    </form>

                    @if($empresa && $canChangeMethod)
                        <form id="payment-mode-form" method="POST" action="{{ route('payment-methods.mode') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                        </form>
                        <div class="pm-company-field">
                            <span class="form-label-hr">Modalidad de pago</span>
                            <div class="pm-options">
                                <label class="pm-option"><input type="radio" name="metodo_pago" value="TARJETA" form="payment-mode-form" onchange="this.form.requestSubmit()" @checked($empresa->metodo_pago === 'TARJETA') required><i class="fa-regular fa-credit-card" aria-hidden="true"></i> Tarjeta (Débito/Crédito)</label>
                                <label class="pm-option"><input type="radio" name="metodo_pago" value="TRANSFERENCIA" form="payment-mode-form" onchange="this.form.requestSubmit()" @checked($empresa->metodo_pago === 'TRANSFERENCIA')><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Transferencia bancaria</label>
                            </div>
                        </div>
                    @endif
                </div>

                @if($empresa)
                    <div class="pm-status"><span><i class="fa-regular fa-circle-check" aria-hidden="true"></i> <strong>Modalidad actual:</strong> {{ $empresa->metodo_pago === 'TRANSFERENCIA' ? 'Transferencia bancaria' : 'Tarjeta' }}</span><span>Aplica para las próximas renovaciones.</span></div>
                    @if($empresa->metodo_pago === 'TRANSFERENCIA')
                        <div class="pm-transfer-grid">
                            <section class="pm-transfer-panel">
                                <div class="pm-panel-head"><span class="pm-icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span><div><h3>¡ IMPORTANTE !</h3><p>Considera lo siguiente para realizar tu pago:</p></div></div>
                                <div class="pm-panel-body"><ul class="pm-notice-list">
                                    <li><i class="fa-solid fa-tag" aria-hidden="true"></i><span><strong>Concepto obligatorio:</strong> escribe exactamente <strong>{{ $conceptoTransferencia }}</strong> en el campo «Concepto» de la transferencia.</span></li>
                                    <li><i class="fa-solid fa-coins" aria-hidden="true"></i><span><strong>Importe exacto:</strong> transfiere solo el importe del periodo; los excedentes no se aplican automáticamente a otras mensualidades.</span></li>
                                    <li><i class="fa-regular fa-clock" aria-hidden="true"></i><span><strong>Validación del pago:</strong> la revisión puede tomar hasta <strong>48 horas hábiles</strong> después de recibir el comprobante.</span></li>
                                    <li><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><span><strong>Comprobante:</strong> estará disponible para subirlo al llegar la fecha de renovación.</span></li>
                                    <li><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span><strong>Confirmación:</strong> subir el comprobante no confirma el pago ni renueva el servicio.</span></li>
                                    <li><i class="fa-regular fa-calendar" aria-hidden="true"></i><span><strong>Suspensión:</strong> el servicio puede suspenderse al agotar cinco intentos automáticos fallidos.</span></li>
                                    <li><i class="fa-regular fa-clock" aria-hidden="true"></i><span><strong>Horario de revisión:</strong>
                                        @if(filled(config('constantes.pagos_transferencia.horario.dias')) && filled(config('constantes.pagos_transferencia.horario.abre')) && filled(config('constantes.pagos_transferencia.horario.cierra')))
                                            {{ config('constantes.pagos_transferencia.horario.dias') }}, de {{ config('constantes.pagos_transferencia.horario.abre') }} a {{ config('constantes.pagos_transferencia.horario.cierra') }} ({{ config('constantes.pagos_transferencia.horario.timezone') }}).
                                            @if(filled(config('constantes.pagos_transferencia.horario.inhabiles'))) Días inhábiles: {{ config('constantes.pagos_transferencia.horario.inhabiles') }}. @endif
                                        @else
                                            Aún no está configurado.
                                        @endif
                                    </span></li>
                                </ul></div>
                            </section>
                            <section class="pm-transfer-panel pm-bank">
                                <div class="pm-panel-head"><span class="pm-icon"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span><div><h3>Pago por transferencia</h3><p>Realiza tu pago con los siguientes datos:</p></div></div>
                                <div class="pm-panel-body">
                                    <div class="pm-bank-row"><span><i class="fa-regular fa-user" aria-hidden="true"></i> Beneficiario</span><strong>{{ config('constantes.pagos_transferencia.beneficiario') ?: 'Pendiente de configuración' }}</strong></div>
                                    <div class="pm-bank-row"><span><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Banco</span><strong>{{ config('constantes.pagos_transferencia.banco') ?: 'Pendiente de configuración' }}</strong></div>
                                    <div class="pm-bank-row"><span><i class="fa-regular fa-credit-card" aria-hidden="true"></i> CLABE interbancaria</span><strong>{{ config('constantes.pagos_transferencia.clabe') ?: 'Pendiente de configuración' }}</strong></div>
                                    @if(config('constantes.pagos_transferencia.cuenta'))<div class="pm-bank-row"><span><i class="fa-regular fa-credit-card" aria-hidden="true"></i> Cuenta</span><strong>{{ config('constantes.pagos_transferencia.cuenta') }}</strong></div>@endif
                                    <div class="pm-bank-row"><span><i class="fa-regular fa-calendar" aria-hidden="true"></i> Fecha de pago</span><strong>{{ $empresa->fecha_renovacion?->format('d/m/Y') ?? 'No disponible' }}</strong></div>
                                    <div class="pm-concept"><strong>Concepto permanente de tu empresa</strong><div class="pm-concept-inner"><p class="pm-concept-value" id="payment-transfer-concept">{{ $conceptoTransferencia }}</p><button class="btn-hr btn-outline-hr" type="button" onclick="navigator.clipboard.writeText(document.getElementById('payment-transfer-concept').textContent.trim())"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copiar</button></div></div>
                                </div>
                            </section>
                        </div>
                    @else
                    <img class="pm-stripe-logo" src="{{ asset('assets/images/stripe.svg') }}" alt="Powered by Stripe">
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
