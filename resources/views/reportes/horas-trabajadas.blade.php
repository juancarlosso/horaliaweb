@extends('layouts.dashboard', ['pageTitle' => 'Horas trabajadas', 'activeSection' => 'reportes-horas-trabajadas'])

@push('scripts')
    <script defer src="{{ asset('assets/horalia/js/report-date-range.js') }}?v=2"></script>
@endpush

@section('content')
    <div class="card-hr">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Horas trabajadas</h2>
                <p class="card-subtitle-hr">Selecciona una empresa y un periodo de hasta {{ \App\Rules\ReportDateRange::MAX_DAYS }} días, con fecha final hasta ayer.</p>
            </div>
        </div>
        <div class="card-bd">
            <form class="row g-3 align-items-end" method="POST" action="{{ route('reportes.horas-trabajadas.store') }}" data-report-max-days="{{ \App\Rules\ReportDateRange::MAX_DAYS }}">
                @csrf
                @include('reportes.partials.campos-periodo')
                <div class="col-12 col-lg-auto">
                    <button type="submit" class="btn-hr btn-primary-hr" @disabled($companies->isEmpty())>
                        <i class="{{ config('constantes.tipo_iconos') }} fa-magnifying-glass" aria-hidden="true"></i> Generar reporte
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('reportes.partials.historial')
@endsection
