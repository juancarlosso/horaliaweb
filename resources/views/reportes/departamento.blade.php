@extends('layouts.dashboard', ['pageTitle' => 'Reporte por departamento', 'activeSection' => 'reportes-departamento'])

@push('scripts')
    <script defer src="{{ asset('assets/horalia/js/report-date-range.js') }}?v=2"></script>
@endpush

@section('content')
@include('reportes.partials.departamento-estilos')

<section class="department-report-page">

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Reporte por departamento</h2><p class="card-subtitle-hr">Selecciona una empresa y un periodo de hasta {{ \App\Rules\ReportDateRange::MAX_DAYS }} días, con fecha final hasta ayer.</p></div></div>
        <div class="card-bd">
            <form data-report-max-days="{{ \App\Rules\ReportDateRange::MAX_DAYS }}" class="department-report-filters" method="POST" action="{{ route('reportes.generados.store', ['tipoReporte' => 'departamento']) }}">
                @csrf
                <div class="department-report-filter"><label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                    <select id="empresa_id" name="empresa_id" class="hr-input select2" required data-placeholder="Selecciona una empresa">
                        @if($companies->count() !== 1)<option value="">Selecciona una empresa</option>@endif
                        @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $selectedCompanyId) === (string) $company->id)>{{ $company->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="department-report-filter"><label for="desde">Desde <span class="text-danger">*</span></label><input class="hr-input" id="desde" name="desde" type="date" max="{{ $latestDate->toDateString() }}" value="{{ old('desde', $startDate) }}" required></div>
                <div class="department-report-filter"><label for="hasta">Hasta <span class="text-danger">*</span></label><input class="hr-input" id="hasta" name="hasta" type="date" max="{{ $latestDate->toDateString() }}" value="{{ old('hasta', $endDate) }}" required></div>
                <div class="department-report-actions"><button type="submit" class="btn-hr btn-primary-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-magnifying-glass" aria-hidden="true"></i> Generar reporte</button><a href="{{ route('reportes.departamento') }}" class="btn-hr btn-outline-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
            </form>
        </div>
    </div>

    @if($reportGenerated)
        @include('reportes.partials.departamento-resumen', [
            'detailUrl' => fn (array $filters) => route('reportes.departamento.detalle', $filters + ['empresa_id' => $selectedCompanyId, 'desde' => $startDate, 'hasta' => $endDate]),
        ])
    @endif
    @include('reportes.partials.historial')
</section>
@endsection
