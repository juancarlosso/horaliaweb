@extends('layouts.dashboard', ['pageTitle' => 'Horas trabajadas', 'activeSection' => 'reportes-horas-trabajadas'])

@section('content')
    @if($rows !== null)
        @include('components.datatable-assets')
    @endif
    <style>
        .hours-report-fields { min-width: 1050px; }
        .hours-report-fields th { vertical-align: middle; white-space: normal; }
        .hours-report-fields th:first-child { min-width: 160px; }
        .hours-report-fields td { font-variant-numeric: tabular-nums; }
        :is(.hours-report-fields, .horalia-datatable table.dataTable.hours-report-fields) tbody td:nth-child(5) {
            background: rgba(16, 185, 129, .14);
            font-weight: 700;
        }
        :is(.hours-report-fields, .horalia-datatable table.dataTable.hours-report-fields) tbody td:last-child {
            background: rgba(245, 158, 11, .14);
            font-weight: 700;
        }
    </style>
    <div id="hours-report-result" data-reload-ready data-status-url="{{ route('reportes.horas-trabajadas.show', $reporte) }}">
        @include('reportes.partials.horas-trabajadas-resultado')
    </div>
@endsection

@push('scripts')
    @if($reporte->enProceso())
        <script defer src="{{ asset('assets/horalia/js/report-hours-status.js') }}?v=2"></script>
    @endif
@endpush
