<div class="card-hr mt-4" id="historial-reportes">
    <div class="card-hd"><h2 class="card-title-hr">Mis reportes</h2></div>
    @if($reports->isEmpty())
        <p class="card-bd mb-0">Todavía no has solicitado un reporte.</p>
    @else
        <div class="table-wrap">
            <table class="bs-table">
                <thead><tr><th>Empresa</th><th>Periodo</th><th>Solicitado</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach($reports as $report)
                        <tr>
                            <td>{{ $report->empresa->nombre ?: $report->empresa->razon_social }}</td>
                            <td class="text-nowrap">{{ $report->desde->format('d/m/Y') }} al {{ $report->hasta->format('d/m/Y') }}</td>
                            <td class="text-nowrap">{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @php
                                    $badge = match ($report->estado) {
                                        \App\Models\ReporteGenerado::PROCESANDO => 'st-processing',
                                        \App\Models\ReporteGenerado::LISTO => 'st-active',
                                        \App\Models\ReporteGenerado::FALLIDO => 'st-terminated',
                                        default => 'st-pending',
                                    };
                                @endphp
                                <span class="badge-st {{ $badge }}">{{ ucfirst($report->estado) }}</span>
                            </td>
                            <td><a class="btn-hr btn-outline-hr btn-sm-hr text-nowrap" href="{{ route($report->tipo === \App\Models\ReporteGenerado::HORAS ? 'reportes.horas-trabajadas.show' : 'reportes.generados.show', $report) }}">Ver reporte</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('reportes.partials.horas-trabajadas-paginacion', ['paginator' => $reports])
    @endif
</div>
