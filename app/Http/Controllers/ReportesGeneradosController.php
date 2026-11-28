<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesGeneratedReports;
use App\Jobs\GenerarReporte;
use App\Models\Asistencia;
use App\Models\ReporteGenerado;
use App\Rules\ReportDateRange;
use App\Services\ReporteAsistenciasSnapshot;
use App\Services\ReporteIncidenciasService;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReportesGeneradosController extends Controller
{
    use ManagesGeneratedReports;

    public function store(Request $request, string $tipoReporte)
    {
        $rules = [
            'empresa_id' => ['required', 'integer', 'min:1'],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
        ];
        if ($tipoReporte === ReporteGenerado::INCIDENCIAS) {
            $rules['tipos'] = ['required', 'array', 'min:1'];
            $rules['tipos.*'] = ['required', 'string', Rule::in(ReporteIncidenciasService::TYPES)];
        }
        $filters = $request->validate($rules);
        $this->authorizeReportCompany($request, $tipoReporte, (int) $filters['empresa_id']);

        $reporte = DB::transaction(function () use ($request, $tipoReporte, $filters) {
            $reporte = ReporteGenerado::query()->create([
                'tipo' => $tipoReporte,
                'user_id' => $request->user()->id,
                'empresa_id' => $filters['empresa_id'],
                'desde' => $filters['desde'],
                'hasta' => $filters['hasta'],
                'filtros' => isset($filters['tipos']) ? ['tipos' => array_values(array_unique($filters['tipos']))] : null,
                'estado' => ReporteGenerado::PENDIENTE,
            ]);
            Bus::dispatch(new GenerarReporte($reporte->id));

            return $reporte;
        });

        return redirect()->route('reportes.generados.show', $reporte);
    }

    public function show(Request $request, ReporteGenerado $reporte, ReporteAsistenciasSnapshot $snapshot)
    {
        $this->authorizeReportOwner($request, $reporte);
        if ($reporte->tipo === ReporteGenerado::HORAS) {
            return redirect()->route('reportes.horas-trabajadas.show', $reporte);
        }
        if ($request->header('X-Report-Fragment') === '1') {
            return response()->view('reportes.partials.generado-estado', compact('reporte'))->header('Cache-Control', 'no-store');
        }

        $data = $reporte->estado === ReporteGenerado::LISTO
            ? match ($reporte->tipo) {
                ReporteGenerado::GENERAL => $this->asistencias($request, $reporte, $snapshot),
                ReporteGenerado::INCIDENCIAS => $this->incidencias($request, $reporte),
                ReporteGenerado::DEPARTAMENTO => [],
            }
        : [];

        if ($request->header('X-Datatable-Fragment') === '1' && isset($data['table'])) {
            return response()->view($data['table'], $data)->header('Cache-Control', 'no-store');
        }

        return response()->view('reportes.generado', $data + [
            'reporte' => $reporte,
            'navigation' => DashboardController::navigation(),
        ])->header('Cache-Control', 'no-store');
    }

    public function download(Request $request, ReporteGenerado $reporte)
    {
        $this->authorizeReportOwner($request, $reporte);
        $disk = Storage::disk('reportes');
        abort_unless($reporte->estado === ReporteGenerado::LISTO && $disk->exists($reporte->archivo()), 404);

        return $disk->download($reporte->archivo(), $reporte->nombreArchivo(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function pauses(Request $request, ReporteGenerado $reporte, int $asistencia, ReporteAsistenciasSnapshot $snapshot)
    {
        $this->authorizeReportOwner($request, $reporte);
        abort_unless($reporte->tipo === ReporteGenerado::GENERAL && $reporte->estado === ReporteGenerado::LISTO, 404);
        $fila = $reporte->resultado()->where('origen_id', $asistencia)->firstOrFail();
        $pausas = $snapshot->restaurar($fila->datos)->pausas->sortByDesc('inicio')->sortByDesc('id')->values()
            ->map(fn ($pause) => [
                'tipo' => config('constantes.tiposPausa.'.$pause->tipo.'.nombre', strtoupper($pause->tipo)),
                'inicio' => $pause->inicio?->format('d/m/Y H:i'),
                'fin' => $pause->fin?->format('d/m/Y H:i') ?? '—',
                'duracion' => $pause->fin ? Asistencia::formatoDuracion($pause->inicio_utc && $pause->fin_utc ? $pause->inicio_utc->diffInMinutes($pause->fin_utc) : null) : '—',
                'estado' => $pause->fin ? 'Finalizada' : 'En curso',
            ]);

        return response()->json(compact('pausas'))->header('Cache-Control', 'no-store');
    }

    public function departmentDetail(Request $request, ReporteGenerado $reporte, ReporteAsistenciasSnapshot $snapshot)
    {
        $this->authorizeReportOwner($request, $reporte);
        abort_unless($reporte->tipo === ReporteGenerado::DEPARTAMENTO && $reporte->estado === ReporteGenerado::LISTO, 404);
        $filters = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(ReporteAsistenciasSnapshot::INDICADORES))],
            'departamento_id' => ['nullable', 'integer', 'min:1'],
            'sin_departamento' => ['nullable', 'boolean'],
        ]);
        $type = $filters['tipo'];
        $withoutDepartment = $request->boolean('sin_departamento');
        $department = null;
        $query = $reporte->resultado();
        if (isset($filters['departamento_id'])) {
            abort_if($withoutDepartment, 422);
            $department = collect($reporte->resumen['departamentos'])->firstWhere('id', (int) $filters['departamento_id']);
            abort_unless($department, 404);
            $query->where('departamento_id', $department['id']);
        } elseif ($withoutDepartment) {
            $query->whereNull('departamento_id');
        }
        if ($type !== 'total') {
            $query->where('datos->indicadores->'.$type, 1);
        }
        $records = $query->orderByDesc('fecha')->orderByDesc('origen_id')->paginate((int) config('constantes.itemsPorPagina'))
            ->withQueryString()->through(fn ($fila) => $snapshot->restaurar($fila->datos));

        return response()->view('reportes.departamento-detalle', [
            'navigation' => DashboardController::navigation(),
            'records' => $records,
            'filters' => ['desde' => $reporte->desde->toDateString(), 'hasta' => $reporte->hasta->toDateString()],
            'company' => $reporte->empresa,
            'department' => $department ? (object) $department : null,
            'withoutDepartment' => $withoutDepartment,
            'typeInfo' => ['title' => ReporteAsistenciasSnapshot::INDICADORES[$type], 'description' => 'Registros del reporte generado.'],
            'backUrl' => route('reportes.generados.show', $reporte),
        ])->header('Cache-Control', 'no-store');
    }

    private function asistencias(Request $request, ReporteGenerado $reporte, ReporteAsistenciasSnapshot $snapshot): array
    {
        $columns = [
            'nombre' => 'nombre', 'fecha' => 'fecha', 'modalidad' => 'modalidad',
            'llegada' => 'llegada', 'rango_entrada' => 'rango_entrada', 'distancia_entrada' => 'distancia_entrada_metros',
            'retardo' => 'minutos_tarde', 'salida' => 'salida', 'rango_salida' => 'rango_salida',
            'distancia_salida' => 'distancia_salida_metros', 'salida_temprana' => 'minutos_salida_temprano',
        ];
        [$perPage, $sort, $direction] = DataTable::options($request, array_keys($columns));
        $query = $this->buscar($request, $reporte);
        if ($sort !== null) {
            $column = $columns[$sort];
            $selector = in_array($column, ['nombre', 'fecha'], true) ? $column : 'datos->asistencia->'.$column;
            if (in_array($sort, ['distancia_entrada', 'distancia_salida', 'retardo', 'salida_temprana'], true)) {
                $query->orderByRaw($query->getQuery()->getGrammar()->wrap($selector).' + 0 '.$direction);
            } else {
                $query->orderBy($selector, $direction);
            }
        } else {
            $query->orderByDesc('fecha');
        }
        $attendance = $query->orderByDesc('origen_id')->paginate($perPage)->withQueryString()
            ->through(fn ($row) => $snapshot->restaurar($row->datos));

        return [
            'table' => 'reportes.partials.asistencia-table',
            'attendance' => $attendance,
            'unfilteredTotal' => $reporte->resumen['totales']['total'],
            'summary' => $reporte->resumen['totales'],
            'snapshotReport' => $reporte,
        ];
    }

    private function incidencias(Request $request, ReporteGenerado $reporte): array
    {
        $columns = ['fecha' => 'fecha', 'empleado' => 'nombre', 'codigo' => 'datos->codigo', 'tipo' => 'datos->etiqueta', 'detalle' => 'datos->detalle'];
        [$perPage, $sort, $direction] = DataTable::options($request, array_keys($columns));
        $query = $this->buscar($request, $reporte);
        if ($sort !== null) {
            $query->orderBy($columns[$sort], $direction);
        }
        $rows = $query->orderBy('fecha')->orderBy('nombre')->orderBy('datos->tipo')->orderBy('id')
            ->paginate($perPage)->withQueryString()->through(fn ($row) => $row->datos);

        return [
            'table' => 'reportes.partials.incidencias-table',
            'rows' => $rows,
            'labels' => ReporteIncidenciasService::LABELS,
            'unfilteredTotal' => $reporte->resumen['total'],
        ];
    }

    private function buscar(Request $request, ReporteGenerado $reporte)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $query = $reporte->resultado();
        $search = $filters['q'] ?? '';
        if ($search !== '') {
            $term = strtr(mb_strtolower($search), ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereRaw("busqueda LIKE ? ESCAPE '!'", ['%'.$term.'%']);
        }

        return $query;
    }
}
