<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Puesto;
use Illuminate\Http\Request;

class PuestoController extends Controller
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

        $puestos = Puesto::query()
            ->with('empresa')
            ->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->orderBy('nombre')
            ->paginate(config('constantes.itemsPorPagina'))
            ->withQueryString();

        return view('puestos.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'puestos',
            'puestos' => $puestos,
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

        return view('puestos.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'puestos',
            'puesto' => new Puesto(['activo' => true]),
            'companies' => $companies,
            'selectedCompanyId' => $this->selectedCompanyForForm($request, $companies),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $puesto = Puesto::create($data);

        return redirect()->route('puestos.index', ['empresa_id' => $puesto->empresa_id])
            ->with('status', 'El puesto se creó correctamente.');
    }

    public function edit(Request $request, int $puesto)
    {
        $record = Puesto::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($puesto);

        return view('puestos.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'puestos',
            'puesto' => $record,
            'companies' => $this->companiesFor($request, true),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, int $puesto)
    {
        $record = Puesto::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($puesto);
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $record->update($data);

        return redirect()->route('puestos.index', ['empresa_id' => $record->empresa_id])
            ->with('status', 'El puesto se actualizó correctamente.');
    }

    public function destroy(Request $request, int $puesto)
    {
        $record = Puesto::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($puesto);
        $companyId = $record->empresa_id;
        $record->delete();

        return redirect()->route('puestos.index', ['empresa_id' => $companyId])
            ->with('status', 'El puesto se eliminó correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ], [
            'empresa_id.required' => 'Selecciona la empresa.',
            'nombre.required' => 'Escribe el nombre del puesto.',
            'activo.required' => 'Selecciona el estatus del puesto.',
        ]);
    }
}
