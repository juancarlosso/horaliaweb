@extends('layouts.dashboard')

@push('styles')
<style>
    .news-detail { max-width:900px; margin:0 auto; overflow:hidden; border:1px solid var(--card-border); border-radius:1rem; background:var(--card-bg); }
    .news-markdown { padding:1.5rem 1.75rem 1.8rem; color:var(--body-text); font-size:.94rem; line-height:1.8; overflow-wrap:anywhere; }
    .news-markdown > :first-child { margin-top:0; }
    .news-markdown h1,.news-markdown h2,.news-markdown h3,.news-markdown h4 { margin:1.5em 0 .55em; color:var(--body-text); font-weight:800; line-height:1.35; }
    .news-markdown p,.news-markdown li { color:var(--body-text-muted); }
    .news-markdown a { color:#536df5; font-weight:700; }
    .news-markdown img { max-width:100%; height:auto; }
    .news-back { display:inline-flex; margin:0 0 1rem; color:#536df5; font-size:.85rem; font-weight:750; text-decoration:none; }
    @media(max-width:575px) { .news-markdown { padding:1.1rem; } }
</style>
@endpush

@section('content')
<a class="news-back" href="{{ route('novedades.index') }}">← Volver a Novedades de Horalia</a>
<article class="news-detail">
    <div class="news-markdown">{!! $contenidoHtml !!}</div>
</article>
@endsection
