<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Personal;
use App\Models\Vacacion;
use App\Services\VacacionesService;
use Illuminate\Http\Request;

class VacacionesController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request)
    {
        $companies = $this->companiesFor($request, true);
        abort_if($companies->isEmpty(), 403);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        if ($selectedCompanyId) {
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 404);
        }
        $personalOptions = Personal::query()
            ->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->with('empresa:id,nombre')
            ->orderBy('nombre')
            ->get(['id', 'empresa_id', 'nombre']);
        $selectedPersonalId = $request->integer('personal_id') ?: null;
        if ($selectedPersonalId) {
            abort_unless($personalOptions->contains('id', $selectedPersonalId), 404);
        }
        $records = Vacacion::query()->with(['empresa', 'personal', 'autorizadoPor', 'registradoPor', 'canceladoPor', 'fechas', 'detalles'])
            ->whereIn('empresa_id', $companyIds)->when($selectedCompanyId, fn($q) => $q->where('empresa_id', $selectedCompanyId))
            ->when($selectedPersonalId, fn ($query) => $query->where('personal_id', $selectedPersonalId))
            ->orderByDesc('fecha_inicio')->paginate(config('constantes.itemsPorPagina'))->withQueryString();
        return view('vacaciones.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'vacaciones',
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'personalOptions' => $personalOptions,
            'selectedPersonalId' => $selectedPersonalId,
            'vacaciones' => $records,
            'manageableIds' => $companyIds,
            'canCreate' => $companyIds !== [],
        ]);
    }

    public function create(Request $request)
    {
        $companies = $this->companiesFor($request, true);
        abort_if($companies->isEmpty(), 403);
        $selected = $this->selectedCompanyForForm($request, $companies);
        return view('vacaciones.form', ['navigation' => DashboardController::navigation(), 'activeSection' => 'vacaciones', 'companies' => $companies, 'selectedCompanyId' => $selected, 'personal' => $selected ? $this->eligiblePersonal($selected) : collect()]);
    }

    public function preview(Request $request, VacacionesService $service)
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer'], 'personal_id' => ['required', 'integer'],
            'fecha_inicio' => ['required', 'date'], 'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);
        $this->authorizeManageableCompany($request, (int)$data['empresa_id']);
        $employee = Personal::query()->where('empresa_id', $data['empresa_id'])->whereKey($data['personal_id'])->firstOrFail();
        abort_unless($employee->activo, 422);
        return response()->json($service->calculate($employee, $data['fecha_inicio'], $data['fecha_fin']));
    }

    public function store(Request $request, VacacionesService $service)
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer'], 'personal_id' => ['required', 'integer'],
            'autorizado_por_personal_id' => ['required', 'integer'], 'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);
        $this->authorizeManageableCompany($request, (int)$data['empresa_id']);
        foreach (['personal_id', 'autorizado_por_personal_id'] as $field) {
            $valid = Personal::query()->where('empresa_id', $data['empresa_id'])->where('activo', true)->whereKey($data[$field])->exists();
            if (!$valid) return back()->withInput()->withErrors([$field => 'Selecciona personal activo de la empresa indicada.']);
        }
        $service->register($data, (int)$request->user()->id);
        return redirect()->route('vacaciones.index', ['empresa_id' => $data['empresa_id']])->with('success', 'El periodo vacacional se registró correctamente.');
    }

    public function cancel(Request $request, int $vacacion)
    {
        $record = Vacacion::query()->whereIn('empresa_id', $this->companyIdsFor($request, true))->findOrFail($vacacion);
        $this->authorizeManageableCompany($request, (int)$record->empresa_id);
        if ($record->estado === 'registrado') {
            $record->update(['estado' => 'cancelado', 'cancelado_por_user_id' => $request->user()->id, 'cancelado_en' => now()]);
        }
        return back()->with('success', 'El periodo vacacional fue cancelado.');
    }

    private function eligiblePersonal(int $companyId)
    {
        return Personal::query()->where('empresa_id', $companyId)->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
    }
}
