@extends('layouts.dashboard')

@push('styles')
<style>
    .news-page { --news-accent:#536df5; --news-soft:rgba(83,109,245,.1); }
    .news-intro { display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; padding:1.35rem 1.5rem; border-radius:1rem; color:#fff; background:linear-gradient(120deg,#151832 0%,#25295a 62%,#343c79 100%); }
    .news-intro-icon { display:grid; place-items:center; width:50px; height:50px; flex:none; border:1px solid rgba(255,255,255,.18); border-radius:.9rem; background:rgba(255,255,255,.1); }
    .news-intro-icon svg { width:25px; height:25px; stroke:currentColor; }
    .news-intro h1 { margin:0 0 .25rem; color:#fff; font-size:1.45rem; font-weight:800; }
    .news-intro p { margin:0; color:rgba(255,255,255,.75); font-size:.88rem; }
    .news-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); align-items:stretch; gap:1rem; }
    .news-card { display:flex; flex-direction:column; min-width:0; padding:1.2rem; border:1px solid var(--card-border); border-radius:1rem; background:var(--card-bg); }
    .news-date { display:inline-flex; align-items:center; align-self:flex-start; gap:.4rem; margin-bottom:.85rem; padding:.38rem .58rem; border-radius:999px; color:#4359c5; background:var(--news-soft); font-size:.72rem; font-weight:750; }
    .news-date svg { width:14px; height:14px; stroke:currentColor; }
    .news-card h2 { margin:0 0 .6rem; color:var(--body-text); font-size:1.02rem; font-weight:800; line-height:1.4; overflow-wrap:anywhere; }
    .news-card p { margin:0 0 1rem; color:var(--body-text-muted); font-size:.83rem; line-height:1.65; }
    .news-read { display:inline-flex; align-items:center; gap:.35rem; margin-top:auto; color:#536df5; font-size:.82rem; font-weight:750; text-decoration:none; }
    .news-read:hover,.news-read:focus-visible { color:#3448bd; text-decoration:underline; }
    .news-empty { padding:2.5rem 1.25rem; border:1px dashed var(--card-border); border-radius:1rem; background:var(--card-bg); color:var(--body-text-muted); text-align:center; }
    .news-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1.05rem; }
    .news-empty p { margin:0; font-size:.85rem; }
    .news-pagination { display:flex; justify-content:center; margin-top:1.25rem; }
    .news-pagination nav { display:flex; gap:.3rem; }
    .news-pagination a,.news-pagination span { display:grid; min-width:2.35rem; height:2.35rem; place-items:center; padding:0 .65rem; border:1px solid var(--card-border); border-radius:.6rem; color:var(--body-text-muted); background:var(--card-bg); font-size:.82rem; text-decoration:none; }
    .news-pagination [aria-current="page"] span { border-color:#536df5; color:#fff; background:#536df5; }
    @media(max-width:900px) { .news-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:575px) { .news-grid { grid-template-columns:1fr; } .news-intro { align-items:flex-start; padding:1.1rem; } }
</style>
@endpush

@section('content')
<div class="news-page">
    <header class="news-intro">
        <span class="news-intro-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><path d="M4 4.5h13a2 2 0 0 1 2 2v13H6a2 2 0 0 1-2-2z"/><path d="M4 17.5a2 2 0 0 1 2-2h13M8 8h7M8 11h7"/></svg></span>
        <div><h1>Novedades de Horalia</h1><p>Entérate de las últimas actualizaciones, mejoras y noticias del sistema.</p></div>
    </header>

    @if($novedades->isEmpty())
        <div class="news-empty"><h2>Por ahora no hay novedades</h2><p>Cuando haya nuevas actualizaciones disponibles, aparecerán aquí.</p></div>
    @else
        <div class="news-grid">
            @foreach($novedades as $novedad)
                <article class="news-card">
                    <span class="news-date"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M7.5 3v4M16.5 3v4M3.5 10h17"/></svg>{{ $novedad['publicada_at']->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</span>
                    <h2>{{ $novedad['titulo'] }}</h2>
                    <p>{{ $novedad['resumen'] }}</p>
                    <a class="news-read" href="{{ route('novedades.show', $novedad['id']) }}">Continuar leyendo <span aria-hidden="true">→</span></a>
                </article>
            @endforeach
        </div>
        @if($novedades->hasPages())
            <div class="news-pagination">{{ $novedades->onEachSide(1)->links('pagination::simple-bootstrap-5') }}</div>
        @endif
    @endif
</div>
@endsection
