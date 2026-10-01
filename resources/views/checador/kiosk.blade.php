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
        .kiosk-foot { margin:1rem 0 0; color:#a0a6bd; font-size:.75rem; }
        @media (orientation:landscape) and (max-height:560px) { .kiosk { width:min(100%,850px); display:grid; grid-template-columns:1fr 1fr; align-items:center; gap:0 2rem; } .kiosk-head { grid-column:1; } .kiosk-keypad { grid-column:2; grid-row:1 / span 6; } .kiosk-message,.kiosk-foot { grid-column:1; } .kiosk-clock { margin-top:.5rem; } .kiosk-key { min-height:48px; } }
    </style>
</head>
<body>
    <main class="kiosk" id="kiosk" data-token="{{ $token }}" data-now="{{ $serverTime }}" data-timezone="{{ config('app.timezone') }}" data-post-url="{{ route('checador.mark') }}" data-verify-url="{{ route('checador.verify-pin') }}" data-clock-url="{{ route('checador.clock') }}">
        <header class="kiosk-head"><p class="kiosk-brand">Horalia</p><p class="kiosk-company">{{ $session->empresa->razon_social }}</p><div class="kiosk-clock" id="kiosk-clock">--:--:--</div><p class="kiosk-date" id="kiosk-date"></p><p class="kiosk-prompt" id="kiosk-prompt" hidden>Ingresa tu PIN de 5 dígitos</p></header>
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
        <p class="kiosk-message" id="kiosk-message" role="status" aria-live="polite"></p><p class="kiosk-foot">La sesión de este checador vence {{ $session->expires_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
    </main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('kiosk'), dots = [...document.querySelectorAll('.kiosk-dot')], message = document.getElementById('kiosk-message');
    const permissionPanel = document.getElementById('kiosk-permissions'), startButton = document.getElementById('kiosk-start');
    const keypad = document.getElementById('kiosk-keypad'), pinPrompt = document.getElementById('kiosk-prompt');
    const video = document.getElementById('kiosk-camera'), canvas = document.getElementById('kiosk-canvas');
    let pin = '', busy = false, permissionsReady = false, cameraStream = null, clockBase = new Date(root.dataset.now).getTime(), clockStarted = Date.now();
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
            permissionsReady = true; permissionPanel.hidden = true; keypad.hidden = false; document.getElementById('kiosk-dots').hidden = false; pinPrompt.hidden = false;
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
    const postJson = async (url, payload) => {
        const response = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-Checador-Token':root.dataset.token,'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(payload)});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || data.errors?.pin?.[0] || 'No se pudo registrar la asistencia.');
        return data;
    };
    const submit = async () => {
        if (busy || pin.length !== 5) return;
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
    document.querySelectorAll('[data-key]').forEach(button=>button.addEventListener('click',()=>{
        if (busy || !permissionsReady) return;
        const key=button.dataset.key;
        if (key==='borrar') pin=pin.slice(0,-1); else if (key==='limpiar') pin=''; else if (/^\d$/.test(key) && pin.length<5) pin+=key;
        draw(); if (pin.length===5) setTimeout(submit,180);
    }));
    document.addEventListener('keydown',event=>{if (!permissionsReady) return; if (/^\d$/.test(event.key)) document.querySelector(`[data-key="${event.key}"]`)?.click(); else if(event.key==='Backspace') document.querySelector('[data-key="borrar"]').click(); else if(event.key==='Escape') document.querySelector('[data-key="limpiar"]').click();});
});
</script>
</body>
</html>
