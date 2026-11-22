<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Exports\AsistenciaReporteExport;
use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Rules\ReportDateRange;
use App\Support\DataTable;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        $unfilteredTotal = 0;
        $fragment = $request->header('X-Datatable-Fragment') === '1';

        if ($request->hasAny(['empresa_id', 'desde', 'hasta'])) {
            $filters = $request->validate([
                'empresa_id' => ['required', 'integer', 'min:1'],
                'q' => ['nullable', 'string', 'max:150'],
                'desde' => ['required', 'date_format:Y-m-d'],
                'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
            ]);
            $selectedCompanyId = (int) $filters['empresa_id'];
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 403);
            $startDate = $filters['desde'];
            $endDate = $filters['hasta'];
            $reportGenerated = true;
        }

        if ($reportGenerated) {
            $sortColumns = [
                'nombre' => 'personal.nombre',
                'fecha' => 'asistencias.fecha',
                'modalidad' => 'asistencias.modalidad',
                'llegada' => 'asistencias.llegada',
                'rango_entrada' => 'asistencias.rango_entrada',
                'distancia_entrada' => 'asistencias.distancia_entrada_metros',
                'retardo' => 'asistencias.minutos_tarde',
                'salida' => 'asistencias.salida',
                'rango_salida' => 'asistencias.rango_salida',
                'distancia_salida' => 'asistencias.distancia_salida_metros',
                'salida_temprana' => 'asistencias.minutos_salida_temprano',
            ];
            [$perPage, $sort, $direction] = DataTable::options($request, array_keys($sortColumns));
            $query = $this->attendanceQuery($selectedCompanyId, $startDate, $endDate);

            if (!$fragment) {
                $summary = DB::table('asistencias')
                    ->where('asistencias.empresa_id', $selectedCompanyId)
                    ->where('asistencias.fecha', '>=', $startDate)
                    ->where('asistencias.fecha', '<', CarbonImmutable::parse($endDate)->addDay()->toDateString())
                    ->selectRaw('COUNT(*) as total')
                    ->selectRaw('SUM(CASE WHEN asistencias.minutos_tarde > 0 THEN 1 ELSE 0 END) as late')
                    ->selectRaw("SUM(CASE WHEN asistencias.rango_entrada = 'Fuera de rango' OR asistencias.rango_salida = 'Fuera de rango' THEN 1 ELSE 0 END) as out_of_range")
                    ->selectRaw('SUM(CASE WHEN asistencias.llegada IS NOT NULL AND asistencias.salida IS NULL THEN 1 ELSE 0 END) as without_exit')
                    ->first();
                $summary = [
                    'total' => (int) $summary->total,
                    'late' => (int) $summary->late,
                    'outOfRange' => (int) $summary->out_of_range,
                    'withoutExit' => (int) $summary->without_exit,
                ];
            }
            $unfilteredTotal = $summary['total'] ?? $query->count();

            $search = $filters['q'] ?? '';
            if ($search !== '') {
                $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $query->where(function ($query) use ($pattern) {
                    $query->whereRaw("personal.nombre LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("personal.codigo LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('personal.empresa', fn ($empresa) => $empresa->whereRaw("nombre LIKE ? ESCAPE '!'", [$pattern]));

                    foreach (['fecha', 'llegada', 'salida', 'rango_entrada', 'rango_salida', 'distancia_entrada_metros', 'distancia_salida_metros', 'minutos_tarde', 'minutos_salida_temprano'] as $column) {
                        $query->orWhereRaw("CAST(asistencias.$column AS CHAR) LIKE ? ESCAPE '!'", [$pattern]);
                    }

                    if (str_contains(mb_strtolower('Home Office'), mb_strtolower(trim($pattern, '%')))) {
                        $query->orWhere('asistencias.modalidad', 'HOME_OFFICE');
                    }
                    if (str_contains('presencial', mb_strtolower(trim($pattern, '%')))) {
                        $query->orWhere('asistencias.modalidad', 'PRESENCIAL')->orWhereNull('asistencias.modalidad');
                    }
                });
            }
            if ($sort !== null) {
                $query->reorder($sortColumns[$sort], $direction)->orderByDesc('asistencias.id');
            }
            $total = $search === '' ? $unfilteredTotal : $query->count();
            $attendance = $query->paginate($perPage, total: $total)->withQueryString();

            if ($fragment) {
                $table = view('reportes.partials.asistencia-table', compact(
                    'attendance', 'unfilteredTotal', 'selectedCompanyId', 'startDate', 'endDate',
                ));

                return response($table->render())->header('Cache-Control', 'no-store');
            }
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
            'unfilteredTotal' => $unfilteredTotal,
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
                'desde' => ['required', 'date_format:Y-m-d'],
                'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
            ]);
            $selectedCompanyId = (int) $filters['empresa_id'];
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 403);
            $startDate = $filters['desde'];
            $endDate = $filters['hasta'];
            $reportGenerated = true;
        }

        if ($reportGenerated) {
            $through = CarbonImmutable::parse($endDate)->addDay()->toDateString();
            $departments = Departamento::query()
                ->where('departamentos.empresa_id', $selectedCompanyId)
                ->leftJoin('personal', function ($join) use ($selectedCompanyId) {
                    $join->on('personal.departamento_id', '=', 'departamentos.id')
                        ->where('personal.empresa_id', '=', $selectedCompanyId);
                })
                ->leftJoin('asistencias', function ($join) use ($selectedCompanyId, $startDate, $through) {
                    $join->on('asistencias.personal_id', '=', 'personal.id')
                        ->where('asistencias.empresa_id', '=', $selectedCompanyId)
                        ->where('asistencias.fecha', '>=', $startDate)
                        ->where('asistencias.fecha', '<', $through);
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
                ->where('asistencias.empresa_id', $selectedCompanyId)
                ->whereNull('personal.departamento_id')
                ->where('asistencias.fecha', '>=', $startDate)
                ->where('asistencias.fecha', '<', $through)
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
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
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
            ->join('personal', 'personal.id', '=', 'asistencias.personal_id')
            ->select('asistencias.*')
            ->where('asistencias.fecha', '>=', $filters['desde'])
            ->where('asistencias.fecha', '<', CarbonImmutable::parse($filters['hasta'])->addDay()->toDateString())
            ->where('asistencias.empresa_id', $companyId);

        if ($department) {
            $query->where('personal.departamento_id', $department->id);
        } elseif ($withoutDepartment) {
            $query->whereNull('personal.departamento_id');
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

        $records = $query->orderByDesc('asistencias.fecha')->orderByDesc('asistencias.created_at')->orderByDesc('asistencias.id')
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
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
        ]);
        $companyId = (int) $filters['empresa_id'];
        abort_unless(in_array($companyId, $this->companyIdsFor($request), true), 403);

        $query = $this->attendanceQuery($companyId, $filters['desde'], $filters['hasta']);
        $filename = 'reporte_asistencia_' . $filters['desde'] . '_' . $filters['hasta'] . '.xlsx';
        $company = Empresa::query()->findOrFail($companyId);
        $file = (new AsistenciaReporteExport())->generate(
            $query,
            $company,
            $filters['desde'],
            $filters['hasta'],
        );

        return response()->download($file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function pauseDetail(Request $request, Asistencia $asistencia)
    {
        $filters = $request->validate([
            'empresa_id' => ['required', 'integer', 'min:1'],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
        ]);
        $allowedCompanyIds = $this->companyIdsFor($request);
        $companyId = (int) $filters['empresa_id'];
        abort_unless(in_array($companyId, $allowedCompanyIds, true), 403);
        abort_unless((int) $asistencia->personal()->value('empresa_id') === $companyId, 404);
        $attendanceDate = $asistencia->fecha->toDateString();
        abort_unless($attendanceDate >= $filters['desde'] && $attendanceDate <= $filters['hasta'], 404);

        $rows = $asistencia->pausas()->orderByDesc('inicio')->orderByDesc('id')->get()->map(function ($pause) {
            $type = config('constantes.tiposPausa.'.$pause->tipo);
            $minutes = $pause->fin_utc && $pause->inicio_utc ? $pause->inicio_utc->diffInMinutes($pause->fin_utc) : null;

            return [
                'tipo' => $type['nombre'] ?? strtoupper($pause->tipo),
                'inicio' => $pause->inicio?->format('d/m/Y H:i'),
                'fin' => $pause->fin?->format('d/m/Y H:i') ?? '—',
                'duracion' => $pause->fin ? Asistencia::formatoDuracion($minutes) : '—',
                'estado' => $pause->fin ? 'Finalizada' : 'En curso',
            ];
        });

        return response()->json(['pausas' => $rows]);
    }

    private function attendanceQuery(int $companyId, string $startDate, string $endDate)
    {
        return Asistencia::query()
            ->with(['personal.empresa', 'personal.horarios', 'pausas'])
            ->join('personal', 'personal.id', '=', 'asistencias.personal_id')
            ->select('asistencias.*')
            ->where('asistencias.empresa_id', $companyId)
            ->where('asistencias.fecha', '>=', $startDate)
            ->where('asistencias.fecha', '<', CarbonImmutable::parse($endDate)->addDay()->toDateString())
            ->orderByDesc('asistencias.fecha')
            ->orderByDesc('asistencias.created_at')
            ->orderByDesc('asistencias.id');
    }
}
