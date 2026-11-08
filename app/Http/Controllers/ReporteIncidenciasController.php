<?php

namespace App\Http\Controllers;

use App\Exports\ReporteIncidenciasExport;
use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Empresa;
use App\Services\ReporteIncidenciasService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ReporteIncidenciasController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request, ReporteIncidenciasService $service)
    {
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        $startDate = $request->input('desde', now()->startOfMonth()->toDateString());
        $endDate = $request->input('hasta', now()->subDay()->toDateString());
        $selectedTypes = $request->has('tipos') ? (array) $request->input('tipos') : ReporteIncidenciasService::TYPES;
        $rows = null; $reportGenerated = $request->hasAny(['empresa_id', 'desde', 'hasta', 'tipos']);
        if ($reportGenerated) {
            $filters = $request->validate([
                'empresa_id' => ['required', 'integer', 'min:1'], 'desde' => ['required', 'date'],
                'hasta' => ['required', 'date', 'after_or_equal:desde'], 'tipos' => ['required', 'array', 'min:1'],
                'tipos.*' => ['required', 'string', 'in:'.implode(',', ReporteIncidenciasService::TYPES)],
            ]);
            $selectedCompanyId = (int) $filters['empresa_id']; abort_unless(in_array($selectedCompanyId, $companyIds, true), 403);
            $startDate = $filters['desde']; $endDate = $filters['hasta']; $selectedTypes = array_values(array_unique($filters['tipos']));
            $allRows = $service->generate($selectedCompanyId, $startDate, $endDate, $selectedTypes);
            $perPage = (int) config('constantes.itemsPorPagina');
            $page = LengthAwarePaginator::resolveCurrentPage();
            $rows = new LengthAwarePaginator($allRows->forPage($page, $perPage)->values(), $allRows->count(), $perPage, $page, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]);
        }
        return view('reportes.incidencias', compact('companies', 'selectedCompanyId', 'startDate', 'endDate', 'selectedTypes', 'rows', 'reportGenerated') + [
            'navigation' => DashboardController::navigation(), 'activeSection' => 'reportes-incidencias',
            'types' => ReporteIncidenciasService::TYPES, 'labels' => ReporteIncidenciasService::LABELS, 'descriptions' => ReporteIncidenciasService::DESCRIPTIONS,
            'typeColors' => [
                'retardos' => '#F59E0B', 'posibles_faltas' => '#EF4444', 'salidas_pendientes' => '#06B6D4',
                'vacaciones' => '#8B5CF6', 'vacaciones_trabajadas' => '#10B981', 'festivos_trabajados' => '#4F6EF7',
            ],
        ]);
    }

    public function export(Request $request, ReporteIncidenciasService $service, ReporteIncidenciasExport $exporter)
    {
        $filters = $request->validate([
            'empresa_id' => ['required', 'integer', 'min:1'], 'desde' => ['required', 'date'], 'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'tipos' => ['required', 'array', 'min:1'], 'tipos.*' => ['required', 'string', 'in:'.implode(',', ReporteIncidenciasService::TYPES)],
        ]);
        $companyId = (int) $filters['empresa_id']; abort_unless(in_array($companyId, $this->companyIdsFor($request), true), 403);
        $types = array_values(array_unique($filters['tipos']));
        $rows = $service->generate($companyId, $filters['desde'], $filters['hasta'], $types)->all();
        $company = Empresa::query()->findOrFail($companyId);
        $path = $exporter->generate($rows, $company, $filters['desde'], $filters['hasta'], $types);
        return response()->download($path, 'horalia_incidencias_'.str_replace('-', '', $filters['desde']).'_'.str_replace('-', '', $filters['hasta']).'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
