<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaPausa;
use App\Services\AsistenciaPausaService;
use App\Support\HoraliaTimezones;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AsistenciaPausaController extends Controller
{
    public function index(Request $request, AsistenciaPausaService $pauses)
    {
        abort_unless(in_array((int) $request->user()->profile, [2, 3], true) && $request->user()->personal, 403);
        $personal = $request->user()->personal->load(['empresa', 'centro']);
        $attendance = $pauses->currentAttendance($personal);
        $activePause = $attendance?->pausas()->whereNull('fin')->latest('inicio')->first();
        $history = $attendance?->pausas()->orderByDesc('inicio')->orderByDesc('id')->get() ?? collect();

        return view('pausas.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'pausas',
            'personal' => $personal,
            'attendance' => $attendance,
            'activePause' => $activePause,
            'history' => $history,
            'types' => config('constantes.tiposPausa'),
            'timezone' => HoraliaTimezones::forCenter($personal->centro?->timezone),
        ]);
    }

    public function start(Request $request, AsistenciaPausaService $pauses)
    {
        abort_unless(in_array((int) $request->user()->profile, [2, 3], true) && $request->user()->personal, 403);
        $data = $request->validate(['tipo' => ['required', 'string', 'in:'.implode(',', array_keys(config('constantes.tiposPausa', [])) )]]);

        try {
            $pauses->start($request->user()->personal, $data['tipo']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('pausas.index')->with('status', 'La pausa inició correctamente.');
    }

    public function finish(Request $request, AsistenciaPausaService $pauses)
    {
        abort_unless(in_array((int) $request->user()->profile, [2, 3], true) && $request->user()->personal, 403);

        try {
            $pauses->finish($request->user()->personal);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('pausas.index')->with('status', 'La pausa finalizó correctamente.');
    }
}
