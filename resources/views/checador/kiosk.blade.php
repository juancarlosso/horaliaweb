<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>Checador · Horalia</title>
    <style>
        :root { color-scheme:light; }
        * { box-sizing:border-box; }
        body { min-height:100vh; min-height:100dvh; margin:0; display:grid; place-items:center; padding:clamp(1rem,4vw,3rem); color:#202544; background:linear-gradient(145deg,#f4f6ff,#e9edff); font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif; }
        .kiosk { width:min(100%,580px); padding:clamp(1.25rem,4vw,2.25rem); border:1px solid #dfe4fa; border-radius:1.5rem; background:#fff; box-shadow:0 20px 60px rgba(37,50,111,.12); text-align:center; }
        .kiosk-brand { margin:0; color:#506cf5; font-size:.9rem; font-weight:850; letter-spacing:.16em; text-transform:uppercase; }
        .kiosk-mode-header { display:flex; align-items:center; justify-content:center; gap:.8rem; margin:calc(clamp(1.25rem,4vw,2.25rem) * -1) calc(clamp(1.25rem,4vw,2.25rem) * -1) 1.25rem; padding:1rem; border-radius:1.4rem 1.4rem 0 0; color:#fff; background:linear-gradient(110deg,#3065ee,#3c91f1); font-size:1.1rem; font-weight:800; }
        .kiosk-company { margin:.4rem 0 0; color:#697294; font-size:.95rem; }
        .kiosk-clock { margin:1.3rem 0 .2rem; color:#202544; font-size:clamp(2.2rem,9vw,3.25rem); font-weight:800; letter-spacing:-.04em; font-variant-numeric:tabular-nums; }
        .kiosk-date { margin:0 0 1.35rem; color:#777f9e; font-size:.9rem; }
        .kiosk-prompt { margin:0 0 .9rem; color:#41496b; font-size:1rem; font-weight:650; }
        .kiosk-permissions { margin:1.25rem auto; max-width:430px; padding:1.1rem; border:1px solid #dfe4fa; border-radius:1rem; background:#f9faff; }
        .kiosk-permissions p { margin:0 0 .9rem; color:#697294; line-height:1.5; }
        .kiosk-start { min-height:48px; padding:.75rem 1.2rem; border:0; border-radius:.8rem; color:#fff; background:#526ff6; font:inherit; font-weight:750; cursor:pointer; }
        .kiosk-camera { position:fixed; top:0; left:-10000px; width:1px; height:1px; opacity:0; pointer-events:none; }
        [hidden] { display:none !important; }
        .kiosk-dots { display:flex; justify-content:center; gap:.8rem; margin:0 auto 1.2rem; }
        .kiosk-dot { width:14px; height:14px; border:2px solid #bcc5e7; border-radius:50%; background:transparent; }
        .kiosk-dot.is-filled { border-color:#526ff6; background:#526ff6; }
        .kiosk-keypad { display:grid; grid-template-columns:repeat(3,minmax(64px,1fr)); gap:.65rem; width:min(100%,380px); margin:auto; }
        .kiosk-key { min-height:clamp(58px,10vh,76px); border:1px solid #e0e5f5; border-radius:1rem; color:#262d4e; background:#f9faff; font:inherit; font-size:1.5rem; font-weight:750; touch-action:manipulation; cursor:pointer; transition:transform .1s,background .1s,border-color .1s; }
        .kiosk-key:active { transform:scale(.97); border-color:#526ff6; background:#eef1ff; }
        .kiosk-key.is-primary { color:#fff; border-color:#526ff6; background:#526ff6; }
        .kiosk-message { min-height:1.5rem; margin:1rem 0 0; color:#6f7898; font-size:.9rem; }
        .kiosk-message.is-success { color:#07835e; font-weight:750; }
        .kiosk-message.is-error { color:#c42b35; font-weight:650; }
        .kiosk-secondary { width:min(100%,380px); min-height:48px; margin:1rem auto 0; padding:.65rem 1rem; border:1px solid #d9e1f8; border-radius:.8rem; color:#315eea; background:#f3f6ff; font:inherit; font-weight:700; cursor:pointer; }
        .kiosk-pause-types { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; width:min(100%,500px); margin:1rem auto 0; }
        .kiosk-pause-type { min-height:132px; padding:1rem .75rem; border:1px solid #e3e8f6; border-radius:1rem; color:#202544; background:#f9faff; font:inherit; font-weight:750; cursor:pointer; }
        .kiosk-pause-type .pause-type-icon { display:grid; place-items:center; width:3.2rem; height:3.2rem; margin:0 auto .5rem; border-radius:50%; color:#fff; background:var(--pause-color); font-size:1.45rem; }
        .kiosk-pause-type span,.kiosk-pause-type small { display:block; }
        .kiosk-pause-type small { margin-top:.25rem; color:#6f7898; font-size:.72rem; font-weight:500; }
        .kiosk-employee { margin:1rem auto; padding:.8rem; border-radius:.8rem; color:#21345a; background:#edf4ff; }
        .kiosk-active-pause,.kiosk-confirmation { width:min(100%,500px); margin:1rem auto; padding:1.2rem; border-radius:1rem; background:#e9f8ef; color:#123b2c; }
        .kiosk-active-pause strong,.kiosk-active-pause span,.kiosk-confirmation strong { display:block; }
        .kiosk-finish { width:100%; min-height:52px; margin-top:.8rem; border:0; border-radius:.8rem; color:#fff; background:#ed1c24; font:inherit; font-weight:800; cursor:pointer; }
        .kiosk-confirmation-icon { display:grid; place-items:center; width:3.5rem; height:3.5rem; margin:0 auto .7rem; border-radius:50%; color:#fff; background:#11a260; font-size:1.6rem; }
        .kiosk-foot { margin:1rem 0 0; color:#a0a6bd; font-size:.75rem; }
        @media (orientation:landscape) and (max-height:560px) { .kiosk { width:min(100%,850px); display:grid; grid-template-columns:1fr 1fr; align-items:center; gap:0 2rem; } .kiosk-head { grid-column:1; } .kiosk-keypad { grid-column:2; grid-row:1 / span 6; } .kiosk-message,.kiosk-foot { grid-column:1; } .kiosk-clock { margin-top:.5rem; } .kiosk-key { min-height:48px; } }
    </style>
</head>
<body>
    <main class="kiosk" id="kiosk" data-token="{{ $token }}" data-now="{{ $serverTime }}" data-timezone="{{ config('app.timezone') }}" data-post-url="{{ route('checador.mark') }}" data-verify-url="{{ route('checador.verify-pin') }}" data-clock-url="{{ route('checador.clock') }}" data-pause-verify-url="{{ route('checador.pauses.verify-pin') }}" data-pause-start-url="{{ route('checador.pauses.start') }}" data-pause-finish-url="{{ route('checador.pauses.finish') }}">
        <div class="kiosk-mode-header" id="kiosk-mode-header" hidden><span aria-hidden="true">☕</span> MODO PAUSAS</div>
        <header class="kiosk-head"><p class="kiosk-brand" id="kiosk-brand">Horalia</p><p class="kiosk-company">{{ $session->empresa->nombre }}</p><div class="kiosk-clock" id="kiosk-clock">--:--:--</div><p class="kiosk-date" id="kiosk-date"></p><p class="kiosk-prompt" id="kiosk-prompt" hidden>Ingresa tu PIN de 5 dígitos</p></header>
        <section class="kiosk-permissions" id="kiosk-permissions">
            <p>Para registrar asistencia, permite el acceso a la cámara y a tu ubicación.</p>
            <button class="kiosk-start" id="kiosk-start" type="button">Iniciar checador</button>
        </section>
        <video class="kiosk-camera" id="kiosk-camera" autoplay playsinline muted aria-hidden="true"></video>
        <canvas id="kiosk-canvas" hidden></canvas>
        <div class="kiosk-dots" id="kiosk-dots" aria-label="PIN" hidden><span class="kiosk-dot"></span><span class="kiosk-dot"></span><span class="kiosk-dot"></span><span class="kiosk-dot"></span><span class="kiosk-dot"></span></div>
        <div class="kiosk-keypad" id="kiosk-keypad" aria-label="Teclado numérico" hidden>
            @foreach(['1','2','3','4','5','6','7','8','9','borrar','0','limpiar'] as $key)
                <button class="kiosk-key {{ $key === '0' ? 'is-primary' : '' }}" type="button" data-key="{{ $key }}">{{ $key === 'borrar' ? '⌫' : ($key === 'limpiar' ? 'C' : $key) }}</button>
            @endforeach
        </div>
        <button class="kiosk-secondary" id="kiosk-pause-enter" type="button" hidden>☕ PAUSAS</button>
        <button class="kiosk-secondary" id="kiosk-pause-back" type="button" hidden>← Volver al reloj checador</button>
        <div class="kiosk-employee" id="kiosk-pause-employee" hidden></div>
        <div class="kiosk-pause-types" id="kiosk-pause-types" hidden></div>
        <div class="kiosk-active-pause" id="kiosk-active-pause" hidden><strong id="kiosk-active-type"></strong><span id="kiosk-active-since"></span><button class="kiosk-finish" id="kiosk-pause-finish" type="button">■ FINALIZAR PAUSA</button></div>
        <div class="kiosk-confirmation" id="kiosk-pause-confirmation" hidden><div class="kiosk-confirmation-icon" aria-hidden="true">✓</div><strong id="kiosk-pause-confirmation-title"></strong><span id="kiosk-pause-confirmation-time"></span><p>Regresando al reloj checador…</p></div>
        <p class="kiosk-message" id="kiosk-message" role="status" aria-live="polite"></p><p class="kiosk-foot">La sesión de este checador vence {{ $session->expires_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
    </main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('kiosk'), dots = [...document.querySelectorAll('.kiosk-dot')], message = document.getElementById('kiosk-message');
    const permissionPanel = document.getElementById('kiosk-permissions'), startButton = document.getElementById('kiosk-start');
    const keypad = document.getElementById('kiosk-keypad'), pinPrompt = document.getElementById('kiosk-prompt');
    const pauseEnter = document.getElementById('kiosk-pause-enter'), pauseBack = document.getElementById('kiosk-pause-back');
    const pauseHeader = document.getElementById('kiosk-mode-header'), pauseBrand = document.getElementById('kiosk-brand');
    const pauseEmployee = document.getElementById('kiosk-pause-employee'), pauseTypes = document.getElementById('kiosk-pause-types');
    const activePausePanel = document.getElementById('kiosk-active-pause'), pauseConfirmation = document.getElementById('kiosk-pause-confirmation');
    const video = document.getElementById('kiosk-camera'), canvas = document.getElementById('kiosk-canvas');
    let pin = '', pausePin = '', busy = false, permissionsReady = false, cameraStream = null, kioskMode = 'attendance', pauseProof = null, pauseType = null, activePauseType = null, idleTimer = null, clockBase = new Date(root.dataset.now).getTime(), clockStarted = Date.now();
    const draw = () => dots.forEach((dot, index) => dot.classList.toggle('is-filled', index < pin.length));
    const renderClock = () => {
        const date = new Date(clockBase + Date.now() - clockStarted), zone = root.dataset.timezone;
        document.getElementById('kiosk-clock').textContent = new Intl.DateTimeFormat('es-MX', {hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true,timeZone:zone}).format(date);
        document.getElementById('kiosk-date').textContent = new Intl.DateTimeFormat('es-MX', {weekday:'long',day:'numeric',month:'long',year:'numeric',timeZone:zone}).format(date);
    };
    renderClock(); setInterval(renderClock,1000);
    fetch(root.dataset.clockUrl).then(r=>r.json()).then(data=>{clockBase=new Date(data.now).getTime();clockStarted=Date.now();renderClock();}).catch(()=>{});
    const requestLocation = () => new Promise((resolve, reject) => {
        if (!navigator.geolocation) return reject(new Error('Este dispositivo no permite obtener la ubicación.'));
        navigator.geolocation.getCurrentPosition(resolve, error => {
            const messages = {1:'Debes permitir el acceso a la ubicación para registrar asistencia.',2:'No se pudo obtener tu ubicación. Verifica la señal e inténtalo de nuevo.',3:'La ubicación tardó demasiado. Inténtalo de nuevo.'};
            reject(new Error(messages[error.code] || 'No se pudo obtener tu ubicación.'));
        }, {enableHighAccuracy:true, timeout:15000, maximumAge:0});
    });
    const stopCamera = () => { cameraStream?.getTracks().forEach(track => track.stop()); cameraStream = null; video.srcObject = null; video.classList.remove('is-visible'); };
    const startKiosk = async () => {
        if (busy || permissionsReady) return;
        busy = true; startButton.disabled = true; message.className='kiosk-message'; message.textContent='Solicitando permisos…';
        let permissionStream = null;
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('La cámara requiere un navegador compatible y una conexión segura (HTTPS).');
            const cameraPermission = navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false}).then(stream => { permissionStream=stream; return stream; });
            const permissionResults = await Promise.allSettled([cameraPermission, requestLocation()]);
            permissionStream?.getTracks().forEach(track => track.stop()); permissionStream = null;
            const deniedPermission = permissionResults.find(result => result.status === 'rejected');
            if (deniedPermission) throw deniedPermission.reason;
            permissionsReady = true; permissionPanel.hidden = true; keypad.hidden = false; document.getElementById('kiosk-dots').hidden = false; pinPrompt.hidden = false; pauseEnter.hidden = false;
            message.textContent='Listo. Ingresa tu PIN para registrar asistencia.'; message.className='kiosk-message';
        } catch (error) {
            permissionStream?.getTracks().forEach(track => track.stop());
            stopCamera(); message.className='kiosk-message is-error'; message.textContent=error.message || 'Permite el acceso a cámara y ubicación para continuar.';
        } finally { busy = false; startButton.disabled = false; }
    };
    const capturePhoto = async () => {
        cameraStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});
        video.srcObject = cameraStream;
        await video.play();
        if (video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => reject(new Error('No se pudo iniciar la cámara. Inténtalo de nuevo.')),10000);
                video.addEventListener('loadeddata', () => { clearTimeout(timeout); resolve(); }, {once:true});
            });
        }
        const scale = Math.min(1, 1280 / video.videoWidth), width = Math.round(video.videoWidth * scale), height = Math.round(video.videoHeight * scale);
        canvas.width=width; canvas.height=height; canvas.getContext('2d').drawImage(video,0,0,width,height);
        const photo = canvas.toDataURL('image/jpeg',.8); stopCamera(); return photo;
    };
    const clearPauseIdle = () => { clearTimeout(idleTimer); if (kioskMode === 'pauses') idleTimer = setTimeout(() => resetPauseMode('La interacción expiró. Ingresa de nuevo para continuar.'), 90000); };
    const resetPausePanels = () => { pauseEmployee.hidden = true; pauseTypes.hidden = true; activePausePanel.hidden = true; pauseConfirmation.hidden = true; pauseTypes.replaceChildren(); };
    const resetPauseMode = (notice = '') => {
        clearTimeout(idleTimer); kioskMode = 'attendance'; pin = ''; pausePin = ''; pauseProof = null; pauseType = null; activePauseType = null; draw(); resetPausePanels();
        pauseHeader.hidden = true; pauseBrand.hidden = false; pauseBack.hidden = true; pauseEnter.hidden = !permissionsReady;
        keypad.hidden = !permissionsReady; document.getElementById('kiosk-dots').hidden = !permissionsReady; pinPrompt.hidden = !permissionsReady; pinPrompt.textContent = 'Ingresa tu PIN de 5 dígitos';
        message.className = 'kiosk-message'; message.textContent = notice;
    };
    const enterPauseMode = () => {
        kioskMode = 'pauses'; pin = ''; pausePin = ''; pauseProof = null; activePauseType = null; draw(); resetPausePanels();
        pauseHeader.hidden = false; pauseBrand.hidden = true; pauseEnter.hidden = true; pauseBack.hidden = false;
        keypad.hidden = false; document.getElementById('kiosk-dots').hidden = false; pinPrompt.hidden = false; pinPrompt.textContent = 'Ingresa tu PIN para registrar una pausa';
        message.className = 'kiosk-message'; message.textContent = ''; clearPauseIdle();
    };
    const postJson = async (url, payload) => {
        const response = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-Checador-Token':root.dataset.token,'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(payload)});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || data.errors?.pin?.[0] || data.errors?.pausa?.[0] || 'No se pudo registrar la asistencia.');
        return data;
    };
    const showPauseTypes = (data) => {
        pauseEmployee.hidden = false; pauseEmployee.textContent = `${data.employee} · ID: ${data.employee_id}`;
        pauseTypes.replaceChildren();
        const colors = {comida:'#f58220',sanitario:'#16a34a',descanso:'#9333ea',permiso:'#1677f2'};
        const icons = {comida:'🍽️',sanitario:'🚻',descanso:'☕',permiso:'👤'};
        for (const [key, item] of Object.entries(data.types || {})) {
            const button = document.createElement('button'); button.type='button'; button.className='kiosk-pause-type'; button.style.setProperty('--pause-color', colors[key] || '#1677f2');
            const icon = document.createElement('span'); icon.className='pause-type-icon'; icon.textContent=icons[key] || '☕'; icon.setAttribute('aria-hidden','true');
            const title = document.createElement('span'); title.textContent=item.nombre;
            const description = document.createElement('small'); description.textContent=item.descripcion;
            button.append(icon,title,description); button.addEventListener('click',()=>startPause(key)); pauseTypes.appendChild(button);
        }
        pauseTypes.hidden = false; keypad.hidden = true; document.getElementById('kiosk-dots').hidden = true; pinPrompt.hidden = true;
        message.textContent = 'Selecciona el tipo de pausa.'; clearPauseIdle();
    };
    const showActivePause = (data) => {
        pauseEmployee.hidden = false; pauseEmployee.textContent = `${data.employee} · ID: ${data.employee_id}`;
        activePauseType = data.active_pause.tipo;
        document.getElementById('kiosk-active-type').textContent=activePauseType;
        document.getElementById('kiosk-active-since').textContent=`En curso desde ${data.active_pause.inicio}`;
        activePausePanel.hidden=false; keypad.hidden=true; document.getElementById('kiosk-dots').hidden=true; pinPrompt.hidden=true;
        message.textContent=''; clearPauseIdle();
    };
    const completePause = (data, action) => {
        pauseType = data.type; resetPausePanels();
        document.getElementById('kiosk-pause-confirmation-title').textContent=data.message;
        document.getElementById('kiosk-pause-confirmation-time').textContent=data.time;
        pauseConfirmation.hidden=false; keypad.hidden=true; document.getElementById('kiosk-dots').hidden=true; pinPrompt.hidden=true;
        message.textContent=''; clearTimeout(idleTimer); setTimeout(()=>resetPauseMode(),3500);
    };
    const retryPausePin = (text) => {
        pauseProof=null; pausePin=''; pin=''; draw(); resetPausePanels(); keypad.hidden=false; document.getElementById('kiosk-dots').hidden=false; pinPrompt.hidden=false;
        pinPrompt.textContent='Ingresa tu PIN para registrar una pausa'; message.className='kiosk-message is-error'; message.textContent=text; clearPauseIdle();
    };
    const submitPausePin = async () => {
        busy=true; message.className='kiosk-message'; message.textContent='Validando PIN…'; clearPauseIdle();
        try {
            const data=await postJson(root.dataset.pauseVerifyUrl,{pin}); pauseProof=data.proof; pausePin=pin; pin=''; draw();
            data.active_pause ? showActivePause(data) : showPauseTypes(data);
        } catch(error) { retryPausePin(error.message || 'No se pudo validar el PIN.'); }
        finally { busy=false; }
    };
    const startPause = async (type) => {
        if (busy || !pauseProof) return;
        busy=true; pauseType=type; message.className='kiosk-message'; message.textContent='Registrando pausa…'; clearPauseIdle();
        try { const data=await postJson(root.dataset.pauseStartUrl,{pin:pausePin,proof:pauseProof,tipo:type}); pauseProof=null; pausePin=''; completePause(data,'start'); }
        catch(error) { retryPausePin(error.message || 'No se pudo iniciar la pausa.'); }
        finally { busy=false; }
    };
    const finishPause = async () => {
        if (busy || !pauseProof) return;
        busy=true; message.className='kiosk-message'; message.textContent='Finalizando pausa…'; clearPauseIdle();
        try { const data=await postJson(root.dataset.pauseFinishUrl,{pin:pausePin,proof:pauseProof}); pauseProof=null; pausePin=''; completePause(data,'finish'); }
        catch(error) { retryPausePin(error.message || 'No se pudo finalizar la pausa.'); }
        finally { busy=false; }
    };
    const submit = async () => {
        if (busy || pin.length !== 5) return;
        if (kioskMode === 'pauses') return submitPausePin();
        busy = true; message.className='kiosk-message'; message.textContent='Validando PIN…';
        try {
            const verified = await postJson(root.dataset.verifyUrl, {pin});
            message.textContent=`Hola, ${verified.employee}. Preparando registro…`;
            const position = await requestLocation();
            message.textContent='Tomando foto…';
            const photo = await capturePhoto();
            message.textContent='Registrando asistencia…';
            const data = await postJson(root.dataset.postUrl, {pin,proof:verified.proof,latitud:position.coords.latitude,longitud:position.coords.longitude,foto:photo});
            message.className='kiosk-message is-success'; message.textContent=`${data.message} · ${data.employee} · ${data.time}`;
        } catch (error) { stopCamera(); message.className='kiosk-message is-error'; message.textContent=error.message || 'No se pudo conectar con Horalia.'; }
        pin=''; draw(); setTimeout(()=>{message.textContent='';message.className='kiosk-message';busy=false;},3000);
    };
    startButton.addEventListener('click', startKiosk);
    pauseEnter.addEventListener('click', enterPauseMode);
    pauseBack.addEventListener('click',()=>resetPauseMode());
    document.getElementById('kiosk-pause-finish').addEventListener('click',finishPause);
    document.querySelectorAll('[data-key]').forEach(button=>button.addEventListener('click',()=>{
        if (busy || !permissionsReady) return;
        clearPauseIdle();
        const key=button.dataset.key;
        if (key==='borrar') pin=pin.slice(0,-1); else if (key==='limpiar') pin=''; else if (/^\d$/.test(key) && pin.length<5) pin+=key;
        draw(); if (pin.length===5) setTimeout(submit,180);
    }));
    document.addEventListener('keydown',event=>{if (!permissionsReady) return; if (event.key==='Escape' && kioskMode==='pauses') { resetPauseMode(); return; } if (/^\d$/.test(event.key)) document.querySelector(`[data-key="${event.key}"]`)?.click(); else if(event.key==='Backspace') document.querySelector('[data-key="borrar"]').click(); else if(event.key==='Escape') document.querySelector('[data-key="limpiar"]').click();});
});
</script>
</body>
</html>
