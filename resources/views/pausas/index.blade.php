@extends('layouts.dashboard', ['pageTitle' => 'Pausas', 'activeSection' => 'pausas'])

@section('content')
<style>
    .pauses-page{width:100%;max-width:1440px;margin:0 auto}.pauses-heading{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.25rem}.pauses-heading h1{margin:0 0 .35rem;font-size:1.6rem;font-weight:800;color:var(--body-text)}.pauses-heading p{margin:0;color:var(--body-text-muted)}.pauses-context{display:flex;gap:1.25rem;align-items:center;padding:1rem 1.25rem;border-radius:14px;background:#edf4ff;color:#1958d4}.pauses-context strong,.pauses-context small{display:block}.pauses-context small{color:#5d6c83;margin-top:.2rem}.pause-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;padding:.75rem;background:#fff;border:1px solid var(--border-color,#e5eaf1);border-radius:14px;margin-bottom:1.25rem}.pause-card{display:flex;flex-direction:column;align-items:center;text-align:center;min-width:0;padding:1.25rem 1rem;border-radius:12px;background:color-mix(in srgb,var(--pause-color) 8%,white);border:1px solid transparent}.pause-card.is-active{border-color:var(--pause-color)}.pause-icon{display:grid;place-items:center;width:4.5rem;height:4.5rem;border-radius:50%;background:var(--pause-color);color:#fff;font-size:1.8rem;margin-bottom:.75rem}.pause-card h2{font-size:1.05rem;font-weight:800;margin:0 0 .45rem;color:var(--body-text)}.pause-card p{min-height:2.7rem;margin:0 0 1rem;color:var(--body-text-muted);font-size:.9rem}.pause-card form{width:100%;margin-top:auto}.pause-card button{width:100%;min-height:2.9rem}.pause-finish-button,.pause-finish-button:hover{background:#EF4444;color:#fff;border-color:#EF4444;box-shadow:0 2px 8px rgba(239,68,68,.3)}.pause-status{min-height:2.5rem;display:flex;align-items:center;justify-content:center;gap:.4rem;margin-top:.45rem;color:#147a36;font-size:.85rem}.pause-status.is-placeholder{visibility:hidden}.pause-history{overflow:hidden}.pause-history .card-hd{padding:1.1rem 1.25rem}.pause-history-subtitle{margin:.25rem 0 0;color:var(--body-text-muted)}.pause-empty{padding:2.5rem 1rem;text-align:center;color:var(--body-text-muted)}.pause-state{display:inline-flex;padding:.3rem .7rem;border-radius:99px;font-size:.8rem;font-weight:700;background:#d9f5e2;color:#176b32}.pause-state.is-open{background:#ffedb0;color:#654800}.pause-type-cell{display:flex;align-items:center;gap:.65rem;font-weight:700}.pause-type-icon{display:grid;place-items:center;width:2rem;height:2rem;border-radius:50%;background:var(--pause-color);color:white}.pause-clock{font-variant-numeric:tabular-nums}@media(max-width:1000px){.pause-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.pauses-heading{align-items:stretch;flex-direction:column}.pauses-context{justify-content:space-between}.pause-grid{grid-template-columns:1fr}.pause-card{min-height:16rem}.pause-card p{min-height:0}.table-wrap{overflow-x:auto}}
</style>
<section class="pauses-page">
    <div class="pauses-heading">
        <div><h1><i class="fa-light fa-mug-hot" aria-hidden="true"></i> Pausas</h1><p>Registra y consulta las pausas de tu jornada actual.</p></div>
        <div class="pauses-context"><div><strong>Empleado</strong><small>{{ $personal->nombre }}</small></div><div><strong>Jornada</strong><small>{{ $attendance?->fecha?->format('d/m/Y') ?? 'Sin entrada vigente' }}</small></div></div>
    </div>

    <div class="pause-grid">
        @foreach($types as $key => $type)
            @php($isActive = $activePause?->tipo === $key)
            <article class="pause-card {{ $isActive ? 'is-active' : '' }}" style="--pause-color:{{ ['orange'=>'#f58220','green'=>'#16a34a','purple'=>'#9333ea','blue'=>'#1677f2'][$type['color']] ?? '#1677f2' }}">
                <div class="pause-icon"><i class="fa-light fa-{{ $type['icono'] }}" aria-hidden="true"></i></div>
                <h2>{{ $type['nombre'] }}</h2>
                <p>{{ $type['descripcion'] }}</p>
                @if($isActive)
                    <form method="POST" action="{{ route('pausas.finish') }}">@csrf<button class="btn-hr btn-danger-hr pause-finish-button" type="submit"><i class="fa-light fa-stop" aria-hidden="true"></i> FINALIZAR</button></form>
                    <div class="pause-status"><i class="fa-light fa-clock" aria-hidden="true"></i> En curso desde <span class="pause-clock">{{ $activePause->inicio->format('H:i') }}</span></div>
                @else
                    <form method="POST" action="{{ route('pausas.start') }}">@csrf<input type="hidden" name="tipo" value="{{ $key }}"><button class="btn-hr {{ $activePause || !$attendance ? 'btn-outline-hr' : 'btn-primary-hr' }}" type="submit" @disabled($activePause || !$attendance)><i class="fa-light fa-play" aria-hidden="true"></i> INICIAR</button></form>
                    <div class="pause-status is-placeholder" aria-hidden="true">En curso desde 00:00</div>
                @endif
            </article>
        @endforeach
    </div>

    <div class="card-hr pause-history">
        <div class="card-hd"><div><h2 class="card-title-hr">Historial de pausas del turno actual</h2><p class="pause-history-subtitle">Movimientos de esta jornada, del más reciente al más antiguo.</p></div></div>
        @if($history->isNotEmpty())
            <div class="table-wrap"><table class="bs-table hover"><thead><tr><th>#</th><th>Tipo de pausa</th><th>Inicio</th><th>Fin</th><th>Duración</th><th>Estado</th></tr></thead><tbody>
                @foreach($history as $index => $pause)
                    @php($type = $types[$pause->tipo] ?? ['nombre' => strtoupper($pause->tipo), 'color' => 'blue'])
                    @php($minutes = $pause->fin_utc ? $pause->inicio_utc->diffInMinutes($pause->fin_utc) : null)
                    <tr><td>{{ $index + 1 }}</td><td><div class="pause-type-cell" style="--pause-color:{{ ['orange'=>'#f58220','green'=>'#16a34a','purple'=>'#9333ea','blue'=>'#1677f2'][$type['color']] ?? '#1677f2' }}"><span class="pause-type-icon"><i class="fa-light fa-{{ $type['icono'] ?? 'mug-hot' }}" aria-hidden="true"></i></span>{{ $type['nombre'] }}</div></td><td>{{ $pause->inicio->format('H:i') }}</td><td>{{ $pause->fin?->format('H:i') ?? '—' }}</td><td>@if($minutes !== null){{ sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60) }}@else—@endif</td><td><span class="pause-state {{ $pause->fin ? '' : 'is-open' }}">{{ $pause->fin ? 'Finalizada' : 'En curso' }}</span></td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="pause-empty">Aún no hay pausas registradas en esta jornada.</div>
        @endif
    </div>
</section>
@endsection
