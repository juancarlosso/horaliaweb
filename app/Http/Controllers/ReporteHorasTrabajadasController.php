<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesGeneratedReports;
use App\Jobs\GenerarReporteHorasTrabajadas;
use App\Models\ReporteHorasTrabajadas;
use App\Models\ReporteHorasTrabajadasFila;
use App\Rules\ReportDateRange;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReporteHorasTrabajadasController extends Controller
{
    use ManagesGeneratedReports;

    public function index(Request $request)
    {
        $this->authorizeProfile($request);
        $companies = $this->companiesFor($request, true);

        return response()->view('reportes.horas-trabajadas', [
            'companies' => $companies,
            'latestDate' => ReportDateRange::latestDate(),
            'selectedCompanyId' => $this->defaultCompanyId($companies),
            'reports' => $this->reportHistory($request, ReporteHorasTrabajadas::HORAS),
            'navigation' => DashboardController::navigation(),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $this->authorizeProfile($request);
        $filters = $request->validate([
            'empresa_id' => ['required', 'integer', 'min:1'],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:desde', new ReportDateRange],
        ]);
        $this->authorizeManageableCompany($request, (int) $filters['empresa_id']);

        $reporte = DB::transaction(function () use ($request, $filters) {
            $reporte = ReporteHorasTrabajadas::query()->create($filters + [
                'user_id' => $request->user()->id,
                'estado' => ReporteHorasTrabajadas::PENDIENTE,
            ]);
            Bus::dispatch(new GenerarReporteHorasTrabajadas($reporte->id));

            return $reporte;
        });

        return redirect()->route('reportes.horas-trabajadas.show', $reporte);
    }

    public function show(Request $request, ReporteHorasTrabajadas $reporte)
    {
        $this->authorizeReport($request, $reporte);
        $data = ['reporte' => $reporte, 'rows' => null];
        if ($reporte->estado === ReporteHorasTrabajadas::LISTO) {
            $data = $this->resultados($request, $reporte) + $data;
            if ($request->header('X-Datatable-Fragment') === '1') {
                return response()->view('reportes.partials.horas-trabajadas-tabla', $data)->header('Cache-Control', 'no-store');
            }
        }

        $view = $request->header('X-Report-Fragment') === '1'
            ? 'reportes.partials.horas-trabajadas-resultado'
            : 'reportes.horas-trabajadas-resultado';

        return response()->view($view, $data + [
            'navigation' => DashboardController::navigation(),
        ])->header('Cache-Control', 'no-store');
    }

    private function resultados(Request $request, ReporteHorasTrabajadas $reporte): array
    {
        [$perPage, $sort, $direction] = DataTable::options($request, [
            'personal', 'semana', 'dias_registrados', 'dias_indeterminados',
            ...array_keys(ReporteHorasTrabajadasFila::COLUMNAS_DURACION),
        ]);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $search = $filters['q'] ?? '';
        $query = $reporte->filas();
        $unfilteredTotal = $query->count();
        if ($search !== '') {
            $term = strtr(mb_strtolower($search), ['!' => '!!', '%' => '!%', '_' => '!_']);
            $week = preg_replace('~^(\d{2})/(\d{2})/(\d{4})$~', '$3-$2-$1', $term);
            $query->where(fn ($searchQuery) => $searchQuery
                ->whereRaw("LOWER(personal) LIKE ? ESCAPE '!'", ['%'.$term.'%'])
                ->orWhereRaw("CAST(semana AS CHAR) LIKE ? ESCAPE '!'", ['%'.$week.'%']));
        }

        if ($sort !== null) {
            $query->orderBy($sort, $direction);
        }

        $rows = $query->orderBy('id')->paginate($perPage, total: $search === '' ? $unfilteredTotal : null)->withQueryString();

        return compact('rows', 'unfilteredTotal');
    }

    public function download(Request $request, ReporteHorasTrabajadas $reporte)
    {
        $this->authorizeReport($request, $reporte);
        $disk = Storage::disk('local');
        abort_unless($reporte->estado === ReporteHorasTrabajadas::LISTO && $disk->exists($reporte->archivo()), 404);

        return $disk->download($reporte->archivo(), $reporte->nombreArchivo(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeProfile(Request $request): void
    {
        abort_unless($this->hasReportManagementProfile($request), 403);
    }

    private function authorizeReport(Request $request, ReporteHorasTrabajadas $reporte): void
    {
        $this->authorizeProfile($request);
        abort_unless($reporte->tipo === ReporteHorasTrabajadas::HORAS, 404);
        $this->authorizeReportOwner($request, $reporte);
    }
}
