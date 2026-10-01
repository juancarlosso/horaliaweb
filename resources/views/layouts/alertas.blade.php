@php
    $alerts = [];

    foreach (['success', 'warning', 'error', 'info'] as $type) {
        if (session()->has($type)) {
            $alerts[] = ['type' => $type, 'message' => session($type)];
        }
    }

    if (session()->has('status')) {
        $alerts[] = ['type' => 'success', 'message' => session('status')];
    }

    if (request()->boolean('saved')) {
        $alerts[] = ['type' => 'success', 'message' => 'La tarjeta se agregó correctamente a la empresa.'];
    }

    if (!empty($stripeError)) {
        $alerts[] = ['type' => 'error', 'message' => $stripeError];
    }

    $user = auth()->user();
    if ((int) $user->profile === 3 && $user->personal?->empresa && !$user->personal->empresa->activa) {
        $alerts[] = ['type' => 'error', 'message' => 'EMPRESA INACTIVA, no podrás marcar asistencias.'];
    }

    if ((int) $user->profile === 2) {
        $inactiveCompanies = $user->empresas->where('activa', false);
        if ($inactiveCompanies->isNotEmpty()) {
            $alerts[] = [
                'type' => 'error',
                'message' => 'EMPRESAS INACTIVAS: ' . $inactiveCompanies->pluck('razon_social')->join(', ') . '. El personal de estas empresas no podrá marcar asistencia.',
            ];
        }
    }

    $alertStyles = [
        'success' => ['container' => 'flex-g35-bg-green-7-br3-p-4-5-mb3-b-1', 'icon' => 'fa-circle-check', 'iconClass' => 'fs11-c-green-dk-mt04-shrink0', 'title' => 'fs875-fw7-c-green-dk-mb1'],
        'warning' => ['container' => 'flex-g35-bg-amber-7-br3-p-4-5-mb3-b-1', 'icon' => 'fa-triangle-exclamation', 'iconClass' => 'fs11-c-amber-dk-mt04-shrink0', 'title' => 'fs875-fw7-c-amber-dk-mb1'],
        'error'   => ['container' => 'flex-g35-bg-red-7-br3-p-4-5-mb3-b-1',   'icon' => 'fa-circle-xmark', 'iconClass' => 'fs11-c-red-dk-mt04-shrink0', 'title' => 'fs875-fw7-c-red-dk-mb1'],
        'info'    => ['container' => 'flex-g35-bg-blue-7-br3-p-4-5-mb3-b-1',  'icon' => 'fa-circle-info', 'iconClass' => 'fs11-c-blue-dk2-mt04-shrink0', 'title' => 'fs875-fw7-c-blue-dk2-mb1'],
    ];
@endphp

<style>
    .alert-dismiss-button { align-self:flex-start; border:0; padding:0 .15rem; color:inherit; background:transparent; font:inherit; font-size:1.35rem; line-height:1; opacity:.65; cursor:pointer; }
    .alert-dismiss-button:hover,.alert-dismiss-button:focus-visible { opacity:1; }
</style>

@foreach($alerts as $alert)
    @php($style = $alertStyles[$alert['type']] ?? $alertStyles['info'])
    <div class="{{ $style['container'] }}" role="alert">
        <i class="fa-light {{ $style['icon'] }} {{ $style['iconClass'] }}" aria-hidden="true"></i>
        <div class="fl1">
            <div class="{{ $style['title'] }}">{{ $alert['type'] === 'success' ? '¡ Muy bien !' : ucfirst($alert['type']) }}</div>
            <div class="fs845-c-body-lh-16">{{ $alert['message'] }}</div>
        </div>
        <button class="alert-dismiss-button" type="button" onclick="this.parentNode.style.display='none'" aria-label="Cerrar aviso">&times;</button>
    </div>
@endforeach
