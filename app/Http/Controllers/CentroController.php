<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Asistencia;
use App\Models\Centro;
use App\Support\HoraliaTimezones;
use Illuminate\Database\QueryException;
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
            'timezones' => HoraliaTimezones::mexicoOptions(),
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
            'timezones' => HoraliaTimezones::mexicoOptions(),
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

        if ($this->hasAttendanceHistory($record->id)) {
            return redirect()->route('centros.index', ['empresa_id' => $companyId])
                ->with('error', $this->attendanceDeletionMessage());
        }

        try {
            $record->delete();
        } catch (QueryException $exception) {
            // The FK remains the final guard if an attendance is added after the pre-check.
            if (! $this->hasAttendanceHistory($record->id) || ! in_array($exception->getCode(), ['23000', '23503'], true)) {
                throw $exception;
            }

            return redirect()->route('centros.index', ['empresa_id' => $companyId])
                ->with('error', $this->attendanceDeletionMessage());
        }

        return redirect()->route('centros.index', ['empresa_id' => $companyId])
            ->with('status', 'El centro de trabajo se eliminó correctamente.');
    }

    private function hasAttendanceHistory(int $centerId): bool
    {
        return Asistencia::query()
            ->where('centro_trabajo_entrada_id', $centerId)
            ->orWhere('centro_trabajo_salida_id', $centerId)
            ->exists();
    }

    private function attendanceDeletionMessage(): string
    {
        return 'No es posible eliminar este centro de trabajo. Este centro de trabajo tiene registros de asistencia asociados, por lo que no puede eliminarse. Para proteger el historial de asistencia de tus colaboradores, estos registros deben conservarse. Si necesitas eliminar definitivamente este centro de trabajo, comunícate con nuestro equipo de soporte a contacto@solventia.com.mx.';
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'geolocalizacion' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone', 'max:64'],
        ], [
            'empresa_id.required' => 'Selecciona la empresa.',
            'nombre.required' => 'Escribe el nombre del centro de trabajo.',
            'activo.required' => 'Selecciona el estatus del centro.',
            'geolocalizacion.required' => 'Indica la geolocalización del centro.',
            'timezone.required' => 'Selecciona la zona horaria del centro.',
        ]);
    }
}
