<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Exports\AsistenciaReporteExport;
use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empresa;
use Illuminate\Http\Request;

class ReporteAsistenciaController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request)
    {
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        $startDate = $request->input('desde', now()->startOfMonth()->toDateString());
        $endDate = $request->input('hasta', now()->endOfMonth()->toDateString());
        $reportGenerated = $request->filled(['empresa_id', 'desde', 'hasta']);
        $attendance = null;
        $summary = null;

        if ($request->hasAny(['empresa_id', 'desde', 'hasta'])) {
            $filters = $request->validate([
                'empresa_id' => ['required', 'integer', 'min:1'],
                'desde' => ['required', 'date'],
                'hasta' => ['required', 'date', 'after_or_equal:desde'],
            ]);
            $selectedCompanyId = (int) $filters['empresa_id'];
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 403);
            $startDate = $filters['desde'];
            $endDate = $filters['hasta'];
            $reportGenerated = true;
        }

        if ($reportGenerated) {
            $query = $this->attendanceQuery($selectedCompanyId, $startDate, $endDate);
            $summary = [
                'total' => (clone $query)->count(),
                'late' => (clone $query)->where('minutos_tarde', '>', 0)->count(),
                'outOfRange' => (clone $query)->where(function ($rangeQuery) {
                    $rangeQuery->where('rango_entrada', 'Fuera de rango')
                        ->orWhere('rango_salida', 'Fuera de rango');
                })->count(),
                'withoutExit' => (clone $query)->whereNotNull('llegada')->whereNull('salida')->count(),
            ];
            $attendance = $query->paginate(config('constantes.itemsPorPagina'))->withQueryString();
        }

        return view('reportes.asistencia', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'reportes-general',
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportGenerated' => $reportGenerated,
            'attendance' => $attendance,
            'summary' => $summary,
        ]);
    }

    public function byDepartment(Request $request)
    {
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        $startDate = $request->input('desde', now()->startOfMonth()->toDateString());
        $endDate = $request->input('hasta', now()->endOfMonth()->toDateString());
        $reportGenerated = $request->filled(['empresa_id', 'desde', 'hasta']);
        $departments = collect();

        if ($request->hasAny(['empresa_id', 'desde', 'hasta'])) {
            $filters = $request->validate([
                'empresa_id' => ['required', 'integer', 'min:1'],
                'desde' => ['required', 'date'],
                'hasta' => ['required', 'date', 'after_or_equal:desde'],
            ]);
            $selectedCompanyId = (int) $filters['empresa_id'];
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 403);
            $startDate = $filters['desde'];
            $endDate = $filters['hasta'];
            $reportGenerated = true;
        }

        if ($reportGenerated) {
            $departments = Departamento::query()
                ->where('departamentos.empresa_id', $selectedCompanyId)
                ->leftJoin('personal', 'personal.departamento_id', '=', 'departamentos.id')
                ->leftJoin('asistencias', function ($join) use ($startDate, $endDate) {
                    $join->on('asistencias.personal_id', '=', 'personal.id')
                        ->whereBetween('asistencias.fecha', [$startDate, $endDate]);
                })
                ->select('departamentos.id', 'departamentos.nombre')
                ->selectRaw('COUNT(asistencias.id) as total')
                ->selectRaw('SUM(CASE WHEN asistencias.minutos_tarde > 0 THEN 1 ELSE 0 END) as late')
                ->selectRaw('SUM(CASE WHEN asistencias.minutos_salida_temprano > 0 THEN 1 ELSE 0 END) as early_departure')
                ->selectRaw("SUM(CASE WHEN asistencias.rango_entrada = 'Fuera de rango' OR asistencias.rango_salida = 'Fuera de rango' THEN 1 ELSE 0 END) as out_of_range")
                ->selectRaw('SUM(CASE WHEN asistencias.llegada IS NOT NULL AND asistencias.salida IS NULL THEN 1 ELSE 0 END) as without_exit')
                ->groupBy('departamentos.id', 'departamentos.nombre')
                ->orderBy('departamentos.nombre')
                ->get();

            $withoutDepartment = Asistencia::query()
                ->join('personal', 'personal.id', '=', 'asistencias.personal_id')
                ->where('personal.empresa_id', $selectedCompanyId)
                ->whereNull('personal.departamento_id')
                ->whereBetween('asistencias.fecha', [$startDate, $endDate])
                ->selectRaw("'Sin departamento' as nombre")
                ->selectRaw('COUNT(asistencias.id) as total')
                ->selectRaw('SUM(CASE WHEN asistencias.minutos_tarde > 0 THEN 1 ELSE 0 END) as late')
                ->selectRaw('SUM(CASE WHEN asistencias.minutos_salida_temprano > 0 THEN 1 ELSE 0 END) as early_departure')
                ->selectRaw("SUM(CASE WHEN asistencias.rango_entrada = 'Fuera de rango' OR asistencias.rango_salida = 'Fuera de rango' THEN 1 ELSE 0 END) as out_of_range")
                ->selectRaw('SUM(CASE WHEN asistencias.llegada IS NOT NULL AND asistencias.salida IS NULL THEN 1 ELSE 0 END) as without_exit')
                ->first();

            if ((int) $withoutDepartment->total > 0) {
                $departments->push($withoutDepartment);
            }
        }

        return view('reportes.departamento', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'reportes-departamento',
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportGenerated' => $reportGenerated,
            'departments' => $departments,
        ]);
    }

    public function departmentDetail(Request $request)
    {
        $filters = $request->validate([
            'empresa_id' => ['required', 'integer', 'min:1'],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'tipo' => ['required', 'in:total,late,early_departure,out_of_range,without_exit'],
            'departamento_id' => ['nullable', 'integer', 'min:1'],
            'sin_departamento' => ['nullable', 'boolean'],
        ]);

        $companyId = (int) $filters['empresa_id'];
        abort_unless(in_array($companyId, $this->companyIdsFor($request), true), 403);
        $company = Empresa::query()->findOrFail($companyId);

        $department = null;
        $withoutDepartment = $request->boolean('sin_departamento');
        if (!empty($filters['departamento_id'])) {
            $department = Departamento::query()->findOrFail((int) $filters['departamento_id']);
            abort_unless((int) $department->empresa_id === $companyId, 403);
        }
        abort_if($department && $withoutDepartment, 422);

        $types = [
            'total' => ['title' => 'Todos los registros', 'description' => 'Asistencias registradas en el periodo.'],
            'late' => ['title' => 'Llegadas tarde', 'description' => 'Asistencias con minutos de retardo registrados.'],
            'early_departure' => ['title' => 'Salidas anticipadas', 'description' => 'Asistencias con minutos de salida temprana registrados.'],
            'out_of_range' => ['title' => 'Fuera de rango', 'description' => 'Asistencias cuya entrada o salida quedó fuera del rango permitido.'],
            'without_exit' => ['title' => 'Sin salida', 'description' => 'Asistencias con entrada registrada y sin salida.'],
        ];
        $type = $filters['tipo'];

        $query = Asistencia::query()
            ->with(['personal.departamento'])
            ->whereBetween('fecha', [$filters['desde'], $filters['hasta']])
            ->whereHas('personal', fn ($personalQuery) => $personalQuery->where('empresa_id', $companyId));

        if ($department) {
            $query->whereHas('personal', fn ($personalQuery) => $personalQuery->where('departamento_id', $department->id));
        } elseif ($withoutDepartment) {
            $query->whereHas('personal', fn ($personalQuery) => $personalQuery->whereNull('departamento_id'));
        }

        match ($type) {
            'late' => $query->where('minutos_tarde', '>', 0),
            'early_departure' => $query->where('minutos_salida_temprano', '>', 0),
            'out_of_range' => $query->where(fn ($rangeQuery) => $rangeQuery
                ->where('rango_entrada', 'Fuera de rango')
                ->orWhere('rango_salida', 'Fuera de rango')),
            'without_exit' => $query->whereNotNull('llegada')->whereNull('salida'),
            default => null,
        };

        $records = $query->orderByDesc('fecha')->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(config('constantes.itemsPorPagina'))
            ->withQueryString();

        return view('reportes.departamento-detalle', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'reportes-departamento',
            'records' => $records,
            'filters' => $filters,
            'company' => $company,
            'department' => $department,
            'withoutDepartment' => $withoutDepartment,
            'type' => $type,
            'typeInfo' => $types[$type],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'empresa_id' => ['required', 'integer', 'min:1'],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);
        $companyId = (int) $filters['empresa_id'];
        abort_unless(in_array($companyId, $this->companyIdsFor($request), true), 403);

        $query = $this->attendanceQuery($companyId, $filters['desde'], $filters['hasta']);
        $filename = 'reporte_asistencia_' . $filters['desde'] . '_' . $filters['hasta'] . '.xlsx';
        $file = (new AsistenciaReporteExport())->generate(
            $query,
            Empresa::query()->findOrFail($companyId)->razon_social,
            $filters['desde'],
            $filters['hasta'],
        );

        return response()->download($file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function attendanceQuery(int $companyId, string $startDate, string $endDate)
    {
        return Asistencia::query()
            ->with(['personal.empresa'])
            ->whereHas('personal', fn ($query) => $query->where('empresa_id', $companyId))
            ->whereBetween('fecha', [$startDate, $endDate])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
