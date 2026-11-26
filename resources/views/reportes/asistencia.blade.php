@extends('layouts.dashboard', ['pageTitle' => 'General', 'activeSection' => 'reportes-general'])

@push('scripts')
    <script defer src="{{ asset('assets/horalia/js/report-date-range.js') }}?v=2"></script>
@endpush

@section('content')
@include('components.datatable-assets')
@include('reportes.partials.asistencia-estilos')

<section class="attendance-report-page">

    <div class="card-hr">
        <div class="card-hd"><div><h2 class="card-title-hr">Reporte general de asistencias</h2><p class="card-subtitle-hr">Selecciona una empresa y un periodo de hasta {{ \App\Rules\ReportDateRange::MAX_DAYS }} días, con fecha final hasta ayer.</p></div></div>
        <div class="card-bd">
            <form data-report-max-days="{{ \App\Rules\ReportDateRange::MAX_DAYS }}" id="attendance-report-form" class="report-filters" method="POST" action="{{ route('reportes.generados.store', ['tipoReporte' => 'general']) }}">
                @csrf
                <div class="report-filter"><label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                    <select id="empresa_id" name="empresa_id" class="hr-input select2" required data-placeholder="Selecciona una empresa">
                        @if($companies->count() !== 1)<option value="">Selecciona una empresa</option>@endif
                        @foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('empresa_id', $selectedCompanyId) === (string) $company->id)>{{ $company->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="report-filter"><label for="desde">Desde <span class="text-danger">*</span></label><input class="hr-input" id="desde" name="desde" type="date" max="{{ $latestDate->toDateString() }}" value="{{ old('desde', $startDate) }}" required></div>
                <div class="report-filter"><label for="hasta">Hasta <span class="text-danger">*</span></label><input class="hr-input" id="hasta" name="hasta" type="date" max="{{ $latestDate->toDateString() }}" value="{{ old('hasta', $endDate) }}" required></div>
                <div class="report-form-actions"><button type="submit" data-loading-message="Generando reporte…" class="btn-hr btn-primary-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-magnifying-glass" aria-hidden="true"></i> Generar reporte</button><a href="{{ route('reportes.general') }}" class="btn-hr btn-outline-hr"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-rotate-left" aria-hidden="true"></i> Limpiar</a></div>
            </form>
        </div>
    </div>

    @if($reportGenerated)
        @include('reportes.partials.asistencia-resumen')

        <div class="card-hr" data-datatable-source>
            <div class="card-hd report-card-heading"><div><h2 class="card-title-hr">Detalle de asistencias</h2><p>Periodo del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p></div><button type="submit" form="attendance-report-form" formaction="{{ route('reportes.asistencia.exportar') }}" data-loading-message="Preparando el archivo de Excel…" class="btn-hr btn-outline-hr report-export-btn" @disabled($unfilteredTotal === 0)><i class="{{ config('constantes.tipo_iconos') }} fa-file-excel" aria-hidden="true"></i> Exportar todo a Excel</button></div>
            @include('reportes.partials.asistencia-table')
        </div>
    @endif
    @include('reportes.partials.historial')
</section>

<div id="attendance-report-loader" class="attendance-loader" role="status" aria-live="assertive" aria-atomic="true" aria-busy="false" hidden>
    <div class="attendance-loader-card"><span class="attendance-loader-spinner" aria-hidden="true"></span><span id="attendance-report-loader-message">Generando reporte…</span></div>
</div>

@include('reportes.partials.asistencia-modales')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const reportForm = document.getElementById('attendance-report-form');
    const loader = document.getElementById('attendance-report-loader');
    const loaderMessage = document.getElementById('attendance-report-loader-message');
    const showLoader = (message = 'Generando reporte…') => {
        loaderMessage.textContent = message;
        loader.hidden = false;
        loader.setAttribute('aria-busy', 'true');
    };
    const hideLoader = () => {
        loader.hidden = true;
        loader.setAttribute('aria-busy', 'false');
    };

    reportForm?.addEventListener('submit', (event) => {
        const submitter = event.submitter;
        const exportUrl = submitter?.getAttribute('formaction');
        if (!exportUrl) {
            showLoader(submitter?.dataset.loadingMessage || 'Generando reporte…');
            return;
        }

        event.preventDefault();
        showLoader(submitter.dataset.loadingMessage || 'Preparando el archivo de Excel…');
        const url = new URL(exportUrl, window.location.href);
        url.search = new URLSearchParams(new FormData(reportForm)).toString();
        url.searchParams.delete('_token');
        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(async (response) => {
                if (!response.ok) throw new Error('No se pudo generar el archivo de Excel.');
                const contentType = response.headers.get('Content-Type') || '';
                if (!contentType.includes('spreadsheetml')) throw new Error('El servidor no devolvió un archivo de Excel.');
                const filenameHeader = response.headers.get('Content-Disposition') || '';
                const filename = filenameHeader.match(/filename="?([^";]+)"?/i)?.[1]
                    || `reporte_asistencia_${reportForm.elements.desde.value}_${reportForm.elements.hasta.value}.xlsx`;
                return { file: await response.blob(), filename };
            })
            .then(({ file, filename }) => {
                const downloadUrl = URL.createObjectURL(file);
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 60000);
            })
            .catch((error) => Swal.fire({
                icon: 'error',
                title: 'No se pudo exportar',
                text: error.message || 'Ocurrió un error al generar el archivo de Excel.',
                confirmButtonText: 'Entendido',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn-hr btn-primary-hr' }
            }))
            .finally(hideLoader);
    });
    document.querySelectorAll('.report-pagination a:not([aria-disabled="true"])').forEach((link) => {
        link.addEventListener('click', () => showLoader('Cargando página…'));
    });

});
</script>
@endsection
