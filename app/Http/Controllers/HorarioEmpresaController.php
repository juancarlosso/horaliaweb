<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\HorarioEmpresa;
use Illuminate\Http\Request;

class HorarioEmpresaController extends Controller
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

        $horarios = HorarioEmpresa::query()
            ->with('empresa')
            ->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->orderBy('nombre_horario')
            ->paginate(config('constantes.itemsPorPagina'))
            ->withQueryString();

        return view('horarios.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'horarios',
            'horarios' => $horarios,
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

        $selectedCompanyId = $this->selectedCompanyForForm($request, $companies);

        return view('horarios.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'horarios',
            'horario' => new HorarioEmpresa(),
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $horario = HorarioEmpresa::create($data);

        return redirect()->route('horarios.index', ['empresa_id' => $horario->empresa_id])
            ->with('status', 'El horario se creó correctamente.');
    }

    public function edit(Request $request, int $horario)
    {
        $record = HorarioEmpresa::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($horario);

        return view('horarios.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'horarios',
            'horario' => $record,
            'companies' => $this->companiesFor($request, true),
            'selectedCompanyId' => $record->empresa_id,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, int $horario)
    {
        $record = HorarioEmpresa::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($horario);
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $record->update($data);

        return redirect()->route('horarios.index', ['empresa_id' => $record->empresa_id])
            ->with('status', 'El horario se actualizó correctamente.');
    }

    public function destroy(Request $request, int $horario)
    {
        $record = HorarioEmpresa::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($horario);
        $companyId = $record->empresa_id;
        $record->delete();

        return redirect()->route('horarios.index', ['empresa_id' => $companyId])
            ->with('status', 'El horario se eliminó correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'nombre_horario' => ['required', 'string', 'max:255'],
            'hora_entrada' => ['required', 'date_format:H:i'],
            'hora_salida' => ['required', 'date_format:H:i'],
        ], [
            'empresa_id.required' => 'Selecciona la empresa.',
            'nombre_horario.required' => 'Escribe el nombre del horario.',
            'hora_entrada.required' => 'Indica la hora de entrada.',
            'hora_entrada.date_format' => 'La hora de entrada no tiene un formato válido.',
            'hora_salida.required' => 'Indica la hora de salida.',
            'hora_salida.date_format' => 'La hora de salida no tiene un formato válido.',
        ]);
    }
}
