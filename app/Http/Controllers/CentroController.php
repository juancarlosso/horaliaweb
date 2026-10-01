<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Centro;
use Illuminate\Http\Request;

class CentroController extends Controller
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

        $centros = Centro::query()
            ->with('empresa')
            ->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->orderBy('nombre')
            ->paginate(config('constantes.itemsPorPagina'))
            ->withQueryString();

        return view('centros.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'centros-de-trabajo',
            'centros' => $centros,
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

        return view('centros.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'centros-de-trabajo',
            'centro' => new Centro(['activo' => true]),
            'companies' => $companies,
            'selectedCompanyId' => $this->selectedCompanyForForm($request, $companies),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $centro = Centro::create($data);

        return redirect()->route('centros.index', ['empresa_id' => $centro->empresa_id])
            ->with('status', 'El centro de trabajo se creó correctamente.');
    }

    public function edit(Request $request, int $centro)
    {
        $record = Centro::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($centro);

        return view('centros.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'centros-de-trabajo',
            'centro' => $record,
            'companies' => $this->companiesFor($request, true),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, int $centro)
    {
        $record = Centro::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($centro);
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $record->update($data);

        return redirect()->route('centros.index', ['empresa_id' => $record->empresa_id])
            ->with('status', 'El centro de trabajo se actualizó correctamente.');
    }

    public function destroy(Request $request, int $centro)
    {
        $record = Centro::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($centro);
        $companyId = $record->empresa_id;
        $record->delete();

        return redirect()->route('centros.index', ['empresa_id' => $companyId])
            ->with('status', 'El centro de trabajo se eliminó correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'geolocalizacion' => ['required', 'string', 'max:255'],
        ], [
            'empresa_id.required' => 'Selecciona la empresa.',
            'nombre.required' => 'Escribe el nombre del centro de trabajo.',
            'activo.required' => 'Selecciona el estatus del centro.',
            'geolocalizacion.required' => 'Indica la geolocalización del centro.',
        ]);
    }
}
