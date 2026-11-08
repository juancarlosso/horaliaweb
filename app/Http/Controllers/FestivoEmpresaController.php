<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FestivoEmpresaController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request)
    {
        $this->authorizeProfile($request);
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        if ($selectedCompanyId) {
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 404);
        }

        $years = config('constantes.aniosAsistencia', []);
        $requestedYear = $request->integer('anio');
        $selectedYear = in_array($requestedYear, $years, true)
            ? $requestedYear
            : (in_array((int) today()->year, $years, true) ? (int) today()->year : (int) ($years[0] ?? today()->year));
        $official = FestivoOficial::query()->whereYear('fecha', $selectedYear)->orderBy('fecha')->get()
            ->map(fn (FestivoOficial $holiday) => (object) [
                'id' => $holiday->id,
                'fecha' => $holiday->fecha,
                'conmemoracion' => $holiday->conmemoracion,
                'tipo' => 'Oficial',
                'empresa_id' => null,
            ]);
        $business = FestivoEmpresa::query()->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->whereYear('fecha', $selectedYear)->orderBy('fecha')->get()
            ->map(fn (FestivoEmpresa $holiday) => (object) [
                'id' => $holiday->id,
                'fecha' => $holiday->fecha,
                'conmemoracion' => $holiday->conmemoracion,
                'tipo' => 'Empresarial',
                'empresa_id' => $holiday->empresa_id,
                'empresa' => $companies->firstWhere('id', (int) $holiday->empresa_id)?->nombre,
            ]);
        $festivos = $official->concat($business)->sortBy(fn ($holiday) => $holiday->fecha->format('Y-m-d'))->values();

        return view('festivos.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'festivos',
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedYear' => $selectedYear,
            'years' => $years,
            'festivos' => $festivos,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeProfile($request);
        $companies = $this->companiesFor($request);
        abort_if($companies->isEmpty(), 403);
        return view('festivos.form', [
            'navigation' => DashboardController::navigation(), 'activeSection' => 'festivos',
            'festivo' => new FestivoEmpresa(), 'companies' => $companies,
            'selectedCompanyId' => $this->selectedCompanyForForm($request, $companies), 'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeProfile($request);
        $data = $this->validatedData($request);
        abort_unless(in_array((int) $data['empresa_id'], $this->companyIdsFor($request), true), 403);
        FestivoEmpresa::create($data);
        return redirect()->route('festivos.index', ['empresa_id' => $data['empresa_id'], 'anio' => substr($data['fecha'], 0, 4)])
            ->with('status', 'El festivo empresarial se agregó correctamente.');
    }

    public function edit(Request $request, int $festivo)
    {
        $this->authorizeProfile($request);
        $companies = $this->companiesFor($request);
        $record = FestivoEmpresa::query()->whereIn('empresa_id', $companies->modelKeys())->findOrFail($festivo);
        return view('festivos.form', [
            'navigation' => DashboardController::navigation(), 'activeSection' => 'festivos',
            'festivo' => $record, 'companies' => $companies, 'selectedCompanyId' => $record->empresa_id, 'isEdit' => true,
        ]);
    }

    public function update(Request $request, int $festivo)
    {
        $this->authorizeProfile($request);
        $companies = $this->companiesFor($request);
        $record = FestivoEmpresa::query()->whereIn('empresa_id', $companies->modelKeys())->findOrFail($festivo);
        $data = $this->validatedData($request, $record);
        abort_unless(in_array((int) $data['empresa_id'], $companies->modelKeys(), true), 403);
        $record->update($data);
        return redirect()->route('festivos.index', ['empresa_id' => $record->empresa_id, 'anio' => $record->fecha->format('Y')])
            ->with('status', 'El festivo empresarial se actualizó correctamente.');
    }

    public function destroy(Request $request, int $festivo)
    {
        $this->authorizeProfile($request);
        $record = FestivoEmpresa::query()->whereIn('empresa_id', $this->companyIdsFor($request))->findOrFail($festivo);
        $empresaId = $record->empresa_id;
        $year = $record->fecha->format('Y');
        $record->delete();
        return redirect()->route('festivos.index', ['empresa_id' => $empresaId, 'anio' => $year])
            ->with('status', 'El festivo empresarial se eliminó correctamente.');
    }

    private function authorizeProfile(Request $request): void
    {
        abort_unless(in_array((int) $request->user()->profile, [1, 2], true), 403);
    }

    private function validatedData(Request $request, ?FestivoEmpresa $festivo = null): array
    {
        return $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'fecha' => ['required', 'date', Rule::unique('festivos_empresas', 'fecha')->where('empresa_id', $request->input('empresa_id'))->ignore($festivo?->id)],
            'conmemoracion' => ['required', 'string', 'max:150'],
        ]);
    }
}
