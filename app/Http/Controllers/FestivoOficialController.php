<?php

namespace App\Http\Controllers;

use App\Models\FestivoOficial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FestivoOficialController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $years = config('constantes.aniosAsistencia', []);
        $requestedYear = $request->integer('anio');
        $selectedYear = in_array($requestedYear, $years, true)
            ? $requestedYear
            : (in_array((int) today()->year, $years, true) ? (int) today()->year : (int) ($years[0] ?? today()->year));

        $festivos = FestivoOficial::query()->whereYear('fecha', $selectedYear)->orderBy('fecha')->get();

        return view('festivos-oficiales.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'festivos-oficiales',
            'festivos' => $festivos,
            'years' => $years,
            'selectedYear' => $selectedYear,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeAdmin($request);
        return view('festivos-oficiales.form', ['navigation' => DashboardController::navigation(), 'activeSection' => 'festivos-oficiales', 'festivo' => new FestivoOficial(), 'isEdit' => false]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        FestivoOficial::create($this->validatedData($request));
        return redirect()->route('festivos-oficiales.index', ['anio' => substr($request->input('fecha'), 0, 4)])->with('status', 'El festivo se agregó correctamente.');
    }

    public function edit(Request $request, int $festivo)
    {
        $this->authorizeAdmin($request);
        return view('festivos-oficiales.form', ['navigation' => DashboardController::navigation(), 'activeSection' => 'festivos-oficiales', 'festivo' => FestivoOficial::findOrFail($festivo), 'isEdit' => true]);
    }

    public function update(Request $request, int $festivo)
    {
        $this->authorizeAdmin($request);
        $record = FestivoOficial::findOrFail($festivo);
        $record->update($this->validatedData($request, $record));
        return redirect()->route('festivos-oficiales.index', ['anio' => substr($record->fecha->format('Y-m-d'), 0, 4)])->with('status', 'El festivo se actualizó correctamente.');
    }

    public function destroy(Request $request, int $festivo)
    {
        $this->authorizeAdmin($request);
        $record = FestivoOficial::findOrFail($festivo);
        $year = $record->fecha->format('Y');
        $record->delete();
        return redirect()->route('festivos-oficiales.index', ['anio' => $year])->with('status', 'El festivo se eliminó correctamente.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless((int) $request->user()->profile === 1, 403);
    }

    private function validatedData(Request $request, ?FestivoOficial $festivo = null): array
    {
        return $request->validate([
            'fecha' => ['required', 'date', Rule::unique('festivos_oficiales', 'fecha')->ignore($festivo?->id)],
            'conmemoracion' => ['required', 'string', 'max:150'],
        ]);
    }
}
