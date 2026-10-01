<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepartamentoController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request)
    {
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);

        if ($selectedCompanyId) {
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 404);
        }

        $departamentos = Departamento::query()
            ->with('empresa')
            ->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->orderBy('nombre')
            ->paginate(config('constantes.itemsPorPagina'))
            ->withQueryString();

        return view('departamentos.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'departamentos',
            'departamentos' => $departamentos,
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'canCreate' => $this->companiesFor($request, true)->isNotEmpty(),
            'manageableIds' => $this->companyIdsFor($request, true),
        ]);
    }

    public function create(Request $request)
    {
        $companies = $this->companiesFor($request, true);
        abort_if($companies->isEmpty(), 403);

        return view('departamentos.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'departamentos',
            'departamento' => new Departamento(['activo' => true]),
            'companies' => $companies,
            'selectedCompanyId' => $this->selectedCompanyForForm($request, $companies),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $departamento = Departamento::create($data);

        return redirect()->route('departamentos.index', ['empresa_id' => $departamento->empresa_id])
            ->with('status', 'El departamento se creó correctamente.');
    }

    public function edit(Request $request, int $departamento)
    {
        $record = Departamento::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($departamento);

        return view('departamentos.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'departamentos',
            'departamento' => $record,
            'companies' => $this->companiesFor($request, true),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, int $departamento)
    {
        $record = Departamento::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($departamento);
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $record->update($data);

        return redirect()->route('departamentos.index', ['empresa_id' => $record->empresa_id])
            ->with('status', 'El departamento se actualizó correctamente.');
    }

    public function destroy(Request $request, int $departamento)
    {
        $record = Departamento::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($departamento);
        $companyId = $record->empresa_id;
        $policyPath = $record->politicas;
        $record->delete();

        if ($policyPath) {
            $this->deletePolicyFile($policyPath);
        }

        return redirect()->route('departamentos.index', ['empresa_id' => $companyId])
            ->with('status', 'El departamento se eliminó correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ], [
            'empresa_id.required' => 'Selecciona la empresa.',
            'nombre.required' => 'Escribe el nombre del departamento.',
            'activo.required' => 'Selecciona el estatus del departamento.',
        ]);
    }

    private function deletePolicyFile(string $path): void
    {
        if (str_starts_with($path, 'departamentos/politicas/') || str_starts_with($path, 'politicas/')) {
            Storage::disk('public')->delete($path);
        } elseif (str_starts_with($path, 'storage/politicas/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
