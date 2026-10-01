@extends('layouts.dashboard', ['pageTitle' => $section['label']])

@section('content')
    <section class="module-placeholder" aria-labelledby="module-title">
        <div class="module-placeholder-inner">
            <div class="module-placeholder-icon">@include('dashboard.partials.icon', ['name' => $section['icon']])</div>
            <h1 id="module-title">{{ $section['label'] }}</h1>
            <p>Esta sección forma parte del espacio de trabajo de {{ config('app.name') }}. Estamos preparando sus herramientas para que puedas administrarla desde aquí.</p>
        </div>
    </section>
@endsection
