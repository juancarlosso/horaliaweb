@extends('layouts.dashboard')

@push('styles')
<style>
    .manual-page { --manual-accent:#536df5; --manual-soft:rgba(83,109,245,.09); }
    .manual-intro { display:flex; align-items:center; gap:1.2rem; margin-bottom:1.25rem; padding:1.5rem; border-radius:1rem; color:#fff; background:linear-gradient(120deg,#151832 0%,#25295a 62%,#343c79 100%); }
    .manual-intro-icon { display:grid; place-items:center; width:54px; height:54px; flex:none; border:1px solid rgba(255,255,255,.18); border-radius:1rem; background:rgba(255,255,255,.1); }
    .manual-intro-icon svg { width:26px; height:26px; stroke:currentColor; }
    .manual-intro h1 { margin:0 0 .25rem; color:#fff; font-size:1.5rem; font-weight:800; }
    .manual-intro p { margin:0; color:rgba(255,255,255,.75); font-size:.9rem; }
    .manual-version { margin-left:auto; color:rgba(255,255,255,.7); font-size:.78rem; white-space:nowrap; }
    .manual-layout { display:grid; grid-template-columns:250px minmax(0,1fr); align-items:start; gap:1.25rem; }
    .manual-index { position:sticky; top:calc(var(--topbar-height) + 1rem); max-height:calc(100vh - var(--topbar-height) - 2rem); overflow:auto; padding:.8rem; border:1px solid var(--card-border); border-radius:1rem; background:var(--card-bg); }
    .manual-index h2 { margin:.3rem .55rem .7rem; color:var(--body-text); font-size:.83rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .manual-index a { display:flex; gap:.6rem; align-items:flex-start; padding:.55rem .6rem; border-radius:.55rem; color:var(--body-text-muted); font-size:.78rem; line-height:1.4; text-decoration:none; }
    .manual-index a:hover,.manual-index a:focus-visible { color:var(--manual-accent); background:var(--manual-soft); outline:none; }
    .manual-index-number { flex:none; color:var(--manual-accent); font-size:.7rem; font-weight:800; }
    .manual-chapters { display:grid; gap:1rem; min-width:0; }
    .manual-chapter { scroll-margin-top:calc(var(--topbar-height) + 1rem); overflow:hidden; border:1px solid var(--card-border); border-radius:1rem; background:var(--card-bg); }
    .manual-chapter-header { display:flex; align-items:flex-start; gap:.9rem; padding:1.2rem 1.35rem; border-bottom:1px solid var(--card-border); }
    .manual-chapter-number { display:grid; place-items:center; width:42px; height:42px; flex:none; border-radius:.75rem; color:var(--manual-accent); background:var(--manual-soft); font-size:.78rem; font-weight:800; }
    .manual-chapter-header h2 { margin:.1rem 0 0; color:var(--body-text); font-size:1.13rem; font-weight:800; line-height:1.4; }
    .manual-chapter-body { padding:1.1rem 1.35rem 1.35rem; color:var(--body-text); }
    .manual-section { margin:0 0 1.15rem; }
    .manual-section:last-child { margin-bottom:0; }
    .manual-section h3 { margin:0 0 .5rem; color:var(--body-text); font-size:.96rem; font-weight:750; }
    .manual-section p { margin:0 0 .6rem; color:var(--body-text-muted); font-size:.85rem; line-height:1.7; }
    .manual-bullets { display:grid; gap:.5rem; margin:.7rem 0; padding:0; list-style:none; }
    .manual-step-row { display:grid; grid-template-columns:25px minmax(0,1fr); align-items:start; gap:.65rem; margin:.5rem 0; color:var(--body-text-muted); font-size:.84rem; line-height:1.6; }
    .manual-step-number { display:grid; place-items:center; width:24px; height:24px; border-radius:50%; color:var(--manual-accent); background:var(--manual-soft); font-size:.72rem; font-weight:800; }
    .manual-bullets li { position:relative; padding-left:1.1rem; color:var(--body-text-muted); font-size:.84rem; line-height:1.6; }
    .manual-bullets li::before { position:absolute; left:.25rem; color:var(--manual-accent); content:"•"; }
    .manual-callout { display:flex; align-items:flex-start; gap:.4rem; margin:.8rem 0; font-size:.82rem; line-height:1.65; }
    .manual-callout strong { flex:none; }
    .manual-table-wrap { overflow:auto; margin:.75rem 0; border:1px solid var(--card-border); border-radius:.65rem; }
    .manual-table { width:100%; border-collapse:collapse; font-size:.82rem; }
    .manual-table th,.manual-table td { padding:.65rem .8rem; border-bottom:1px solid var(--card-border); color:var(--body-text-muted); text-align:left; }
    .manual-table tr:last-child td { border-bottom:0; }
    .manual-table tr:first-child th { color:var(--body-text); background:var(--body-bg); font-weight:750; }
    .manual-image-link { display:block; cursor:zoom-in; }
    .manual-image-link:focus-visible { border-radius:.75rem; outline:2px solid var(--manual-accent); outline-offset:3px; }
    .manual-image { display:block; max-width:100%; height:auto; margin:.75rem auto .35rem; border:1px solid var(--card-border); border-radius:.75rem; }
    .manual-caption { margin:0; color:var(--body-text-muted); font-size:.74rem; font-style:italic; text-align:center; }
    .manual-link { color:var(--manual-accent); font-weight:700; }
    @media(max-width:800px) { .manual-layout { grid-template-columns:1fr; } .manual-index { position:static; max-height:none; } .manual-index nav { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:575px) { .manual-intro { align-items:flex-start; padding:1.15rem; } .manual-version { display:none; } .manual-index nav { grid-template-columns:1fr; } .manual-chapter-header { padding:1rem; } .manual-chapter-body { padding:1rem; } .manual-callout { display:block; } .manual-callout strong { display:block; margin-bottom:.2rem; } }
</style>
@endpush

@section('content')
<div class="manual-page">
    <header class="manual-intro">
        <span class="manual-intro-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M9.6 9a2.5 2.5 0 1 1 4.2 1.8c-1.1 1-1.8 1.4-1.8 2.7m0 3h.01"/></svg></span>
        <div><h1>Manual de usuario</h1><p>Guía de configuración y control de asistencia</p></div>
        <span class="manual-version">Versión 1.0 · Octubre 2026</span>
    </header>

    <div class="manual-layout">
        <aside class="manual-index" aria-label="Índice del manual">
            <h2>Contenido</h2>
            <nav>
                @foreach($chapters as $chapter)
                    <a href="#{{ $chapter['id'] }}"><span class="manual-index-number">{{ $chapter['number'] }}</span><span>{{ $chapter['title'] }}</span></a>
                @endforeach
            </nav>
        </aside>

        <div class="manual-chapters">
            @foreach($chapters as $chapter)
                @include('manual.partials.chapter', ['chapter' => $chapter])
            @endforeach
        </div>
    </div>
</div>
@endsection
