<?php

namespace App\Http\Controllers;

use App\Exports\ReporteIncidenciasExport;
use App\Http\Controllers\Concerns\ManagesGeneratedReports;
use App\Models\Empresa;
use App\Models\ReporteGenerado;
use App\Services\ReporteIncidenciasService;
use App\Rules\ReportDateRange;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ReporteIncidenciasController extends Controller
{
    use ManagesGeneratedReports;

    public function index(Request $request, ReporteIncidenciasService $service)
    {
        abort_unless(in_array((int) $request->user()->profile, [1, 2], true), 403);
        $companies = $this->companiesFor($request, true);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        $latestDate = ReportDateRange::latestDate();
        $startDate = $request->input('desde', $latestDate->startOfMonth()->toDateString());
        $endDate = $request->input('hasta', $latestDate->toDateString());
        $selectedTypes = $request->has('tipos') ? (array) $request->input('tipos') : ReporteIncidenciasService::TYPES;
        $rows = null;
        $unfilteredTotal = 0;
        $reportGenerated = $request->hasAny(['empresa_id', 'desde', 'hasta', 'tipos']);
        if ($reportGenerated) {
            $filters = $request->validate([
                'empresa_id' => ['required', 'integer', 'min:1'], 'desde' => ['required', 'date_format:Y-m-d'],
                'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange], 'tipos' => ['required', 'array', 'min:1'],
                'tipos.*' => ['required', 'string', 'in:'.implode(',', ReporteIncidenciasService::TYPES)],
                'q' => ['nullable', 'string', 'max:150'],
            ]);
            $selectedCompanyId = (int) $filters['empresa_id'];
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 403);
            $startDate = $filters['desde'];
            $endDate = $filters['hasta'];
            $selectedTypes = array_values(array_unique($filters['tipos']));
            [$perPage, $sort, $direction] = DataTable::options($request, ['fecha', 'empleado', 'codigo', 'tipo', 'detalle']);
            $labels = ReporteIncidenciasService::LABELS;

            $allRows = $service->generate($selectedCompanyId, $startDate, $endDate, $selectedTypes);
            $unfilteredTotal = $allRows->count();
            $search = Str::lower($filters['q'] ?? '');
            if ($search !== '') {
                $allRows = $allRows->filter(function (array $row) use ($search, $labels) {
                    $date = substr($row['fecha'], 8, 2).'/'.substr($row['fecha'], 5, 2).'/'.substr($row['fecha'], 0, 4);
                    $text = implode(' ', [$row['fecha'], $date, $row['empleado'], $row['codigo'], $labels[$row['tipo']], $row['detalle']]);

                    return str_contains(Str::lower($text), $search);
                });
            }
            if ($sort !== null) {
                $allRows = $allRows->sortBy(
                    fn (array $row) => $sort === 'tipo' ? $labels[$row['tipo']] : $row[$sort],
                    SORT_NATURAL | SORT_FLAG_CASE,
                    $direction === 'desc',
                );
            }
            $page = LengthAwarePaginator::resolveCurrentPage();
            $rows = new LengthAwarePaginator($allRows->forPage($page, $perPage)->values(), $allRows->count(), $perPage, $page, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]);

            if ($request->header('X-Datatable-Fragment') === '1') {
                $table = view('reportes.partials.incidencias-table', compact('rows', 'labels', 'unfilteredTotal'));

                return response($table->render())->header('Cache-Control', 'no-store');
            }
        }
        return view('reportes.incidencias', compact('companies', 'selectedCompanyId', 'startDate', 'endDate', 'latestDate', 'selectedTypes', 'rows', 'unfilteredTotal', 'reportGenerated') + [
            'navigation' => DashboardController::navigation(), 'activeSection' => 'reportes-incidencias',
            'reports' => $this->reportHistory($request, ReporteGenerado::INCIDENCIAS),
            'types' => ReporteIncidenciasService::TYPES, 'labels' => ReporteIncidenciasService::LABELS, 'descriptions' => ReporteIncidenciasService::DESCRIPTIONS,
            'typeColors' => [
                'retardos' => '#F59E0B', 'posibles_faltas' => '#EF4444', 'salidas_pendientes' => '#06B6D4',
                'vacaciones' => '#8B5CF6', 'vacaciones_trabajadas' => '#10B981', 'festivos_trabajados' => '#4F6EF7',
                'incapacidades' => '#E11D48', 'incapacidades_trabajadas' => '#BE123C',
            ],
        ]);
    }

    public function export(Request $request, ReporteIncidenciasService $service, ReporteIncidenciasExport $exporter)
    {
        abort_unless(in_array((int) $request->user()->profile, [1, 2], true), 403);
        $filters = $request->validate([
            'empresa_id' => ['required', 'integer', 'min:1'], 'desde' => ['required', 'date_format:Y-m-d'], 'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
            'tipos' => ['required', 'array', 'min:1'], 'tipos.*' => ['required', 'string', 'in:'.implode(',', ReporteIncidenciasService::TYPES)],
        ]);
        $companyId = (int) $filters['empresa_id']; abort_unless(in_array($companyId, $this->companyIdsFor($request, true), true), 403);
        $types = array_values(array_unique($filters['tipos']));
        $rows = $service->generate($companyId, $filters['desde'], $filters['hasta'], $types)->all();
        $company = Empresa::query()->findOrFail($companyId);
        $path = $exporter->generate($rows, $company, $filters['desde'], $filters['hasta'], $types);
        return response()->download($path, 'horalia_incidencias_'.str_replace('-', '', $filters['desde']).'_'.str_replace('-', '', $filters['hasta']).'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
