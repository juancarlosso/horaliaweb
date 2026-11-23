<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\HoraExtra;
use App\Models\Personal;
use App\Models\User;
use App\Services\HorasExtrasPdfService;
use App\Services\HorasExtrasService;
use App\Services\PersonalDocumentosService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class HorasExtrasController extends Controller
{
    private const MAX_DIAS_BUSQUEDA = 90;

    private function empresas(User $user): Builder
    {
        abort_unless(in_array((int) $user->profile, [1, 2], true), 403);

        return Empresa::query()->when((int) $user->profile === 2, fn ($query) => $query->whereIn(
            'id', $user->empresas()->wherePivot('control_total', true)->select('empresas.id')
        ));
    }

    private function empleadosActivos(int $empresa): Builder
    {
        return Personal::query()->where('empresa_id', $empresa)->where('activo', true)
            ->orderBy('nombre')->orderBy('id');
    }

    public function empleados(Request $request, int $empresa)
    {
        abort_unless((int) $request->user()->profile === 2, 403);
        $this->empresas($request->user())->findOrFail($empresa);

        return response()->json($this->empleadosActivos($empresa)->get(['id', 'nombre']));
    }

    private function vista(string $view, array $data = [])
    {
        return view('horas-extras.'.$view, $data + [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'horas-extras',
        ]);
    }

    public function index(Request $request)
    {
        $empresas = $this->empresas($request->user())->orderBy('nombre')->get(['id', 'nombre']);
        abort_if($empresas->isEmpty(), 403);
        $filtros = $request->validate([
            'empresa_id' => ['nullable', 'integer'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'anio' => ['nullable', 'integer', 'between:1000,9999'],
        ]);
        $empresa = (int) ($filtros['empresa_id'] ?? ($empresas->count() === 1 ? $empresas->first()->id : 0));
        abort_if($empresa && ! $empresas->contains('id', $empresa), 404);
        $mes = $filtros['mes'] ?? null;
        $anio = $filtros['anio'] ?? null;
        $registros = HoraExtra::whereIn('empresa_id', $empresas->modelKeys())
            ->withExists(['constanciaFirmada as tiene_constancia_firmada'])
            ->when($empresa, fn ($query) => $query->where('empresa_id', $empresa))
            ->when($mes, fn ($query) => $query->whereMonth('fecha', $mes))
            ->when($anio, fn ($query) => $query->whereYear('fecha', $anio))
            ->orderByDesc('fecha')->orderByDesc('id')->get();
        $anios = HoraExtra::whereIn('empresa_id', $empresas->modelKeys())
            ->selectRaw('DISTINCT substr(fecha, 1, 4) as anio')->orderByDesc('anio')->pluck('anio');
        $puedeRegistrar = (int) $request->user()->profile === 2;

        return $this->vista('index', compact('empresas', 'empresa', 'mes', 'anio', 'anios', 'registros', 'puedeRegistrar'));
    }

    public function create(Request $request, HorasExtrasService $service)
    {
        abort_unless((int) $request->user()->profile === 2, 403);
        $empresas = $this->empresas($request->user())->orderBy('nombre')->get(['id', 'nombre']);
        abort_if($empresas->isEmpty(), 403);
        $filtros = $request->validate([
            'empresa_id' => ['required_with:desde,hasta', 'nullable', 'integer', 'min:1'],
            'personal_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['required_with:hasta', 'nullable', 'date_format:Y-m-d'],
            'hasta' => ['required_with:desde', 'nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
        $empresa = (int) ($filtros['empresa_id'] ?? ($empresas->count() === 1 ? $empresas->first()->id : 0));
        abort_if($empresa && ! $empresas->contains('id', $empresa), 404);
        $empleado = isset($filtros['personal_id']) ? (int) $filtros['personal_id'] : null;
        $empleados = $empresa ? $this->empleadosActivos($empresa)->get(['id', 'nombre']) : collect();
        if ($empleado && ! $empleados->contains('id', $empleado)) {
            throw ValidationException::withMessages(['personal_id' => 'Selecciona un empleado activo de la empresa.']);
        }
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        $buscado = $empresa && $desde && $hasta;
        $jornadas = collect();
        $maxDias = self::MAX_DIAS_BUSQUEDA;

        if ($buscado) {
            $hastaExclusivo = CarbonImmutable::parse($hasta)->addDay();
            if (CarbonImmutable::parse($desde)->diffInDays($hastaExclusivo) > $maxDias) {
                throw ValidationException::withMessages(['hasta' => "Selecciona un periodo de hasta {$maxDias} días, incluyendo desde y hasta."]);
            }
            $jornadas = $service->pendientes($empresa, $desde, $hastaExclusivo->toDateString())
                ->when($empleado, fn ($query) => $query->where('asistencias.personal_id', $empleado))
                ->orderByDesc('fecha')->orderByDesc('id')->lazy(250)
                ->map(function ($asistencia) use ($service) {
                    $jornada = $service->detectar($asistencia);

                    return $jornada ? ['id' => $asistencia->id, 'personal_id' => $asistencia->personal_id, 'revision' => $this->revision($jornada)] + $jornada : null;
                })->filter()->collect();
        }

        return $this->vista('create', compact('empresas', 'empresa', 'empleados', 'empleado', 'desde', 'hasta', 'buscado', 'jornadas', 'maxDias'));
    }

    public function store(Request $request, HorasExtrasService $service)
    {
        abort_unless((int) $request->user()->profile === 2, 403);
        $data = $request->validate([
            'empresa_id' => ['required', 'integer'],
            'asistencia_id' => ['required', 'integer'],
            'revision' => ['required', 'string', 'size:64'],
            'horas' => ['required', 'integer', 'between:0,71582787'],
            'minutos' => ['required', 'integer', 'between:0,59'],
            'autorizador_personal_id' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'string', 'max:5000'],
            'observaciones' => ['required', 'string', 'max:5000'],
        ]);
        $empresa = (int) $data['empresa_id'];
        $this->empresas($request->user())->findOrFail($empresa);
        $total = $data['horas'] * 60 + $data['minutos'];
        if ($total <= 0) {
            throw ValidationException::withMessages(['horas' => 'El tiempo autorizado debe ser mayor a cero.']);
        }

        try {
            $registro = DB::transaction(function () use ($request, $service, $data, $empresa, $total) {
                $asistencia = $service->asistencias($empresa)->lockForUpdate()->find($data['asistencia_id']);
                if (! $asistencia) {
                    throw ValidationException::withMessages(['jornada' => 'La jornada completa ya no está disponible para esta empresa.']);
                }
                if (HoraExtra::where('personal_id', $asistencia->personal_id)
                    ->where('fecha', '>=', $asistencia->fecha->toDateString())
                    ->where('fecha', '<', $asistencia->fecha->copy()->addDay()->toDateString())
                    ->exists()) {
                    $this->duplicado();
                }

                $jornada = $service->detectar($asistencia);
                if (! $jornada || ! $jornada['minutos'] || ! hash_equals($this->revision($jornada), $data['revision'])) {
                    throw ValidationException::withMessages(['jornada' => 'La jornada cambió o ya no es elegible. Vuelve a consultar sus datos antes de registrar.']);
                }
                if ((int) $data['autorizador_personal_id'] === (int) $asistencia->personal_id) {
                    throw ValidationException::withMessages(['autorizador_personal_id' => 'El empleado de la jornada no puede autorizar sus propias horas extras.']);
                }
                $autorizador = $this->empleadosActivos($empresa)->find($data['autorizador_personal_id']);
                if (! $autorizador) {
                    throw ValidationException::withMessages(['autorizador_personal_id' => 'Selecciona un empleado activo de la empresa.']);
                }

                $registro = HoraExtra::create([
                    'asistencia_id' => $asistencia->id, 'empresa_id' => $empresa,
                    'personal_id' => $asistencia->personal_id, 'fecha' => $jornada['fecha'],
                    'detectados' => $jornada['minutos'], 'autorizados' => $total,
                    'autorizador_personal_id' => $autorizador->id, 'capturista_id' => $request->user()->id,
                    'motivo' => $data['motivo'], 'observaciones' => $data['observaciones'],
                    'jornada' => $jornada + [
                        'autorizador' => $autorizador->nombre, 'capturista' => $request->user()->name,
                        'registro_local' => now($jornada['zona'])->format('d/m/Y H:i:s T'),
                    ],
                ]);
                $registro->asignarFolio();

                return $registro;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $this->duplicado();
        }

        return redirect()->route('horas-extras.index', [
            'empresa_id' => $empresa, 'mes' => $registro->fecha->month, 'anio' => $registro->fecha->year,
        ])->with('horas_extras_success', 'La autorización de horas extras quedó registrada.');
    }

    private function revision(array $jornada): string
    {
        return hash('sha256', json_encode($jornada, JSON_THROW_ON_ERROR));
    }

    private function duplicado(): never
    {
        throw ValidationException::withMessages(['jornada' => 'Esta jornada ya tiene una autorización registrada.']);
    }

    private function registro(Request $request, int $id): HoraExtra
    {
        return HoraExtra::whereIn('empresa_id', $this->empresas($request->user())->select('id'))->findOrFail($id);
    }

    public function show(Request $request, int $horaExtra)
    {
        $registro = $this->registro($request, $horaExtra);
        $expediente = Personal::where('empresa_id', $registro->empresa_id)->whereKey($registro->personal_id)->exists();
        $constancia = $registro->constanciaFirmada;
        $puedeCargarConstancia = $expediente && (int) $request->user()->profile === 2;

        return $this->vista('show', compact('registro', 'expediente', 'constancia', 'puedeCargarConstancia'));
    }

    public function subirConstancia(Request $request, int $horaExtra, PersonalDocumentosService $documentos)
    {
        abort_unless((int) $request->user()->profile === 2, 403);
        $registro = $this->registro($request, $horaExtra);
        $empleado = Personal::where('empresa_id', $registro->empresa_id)->findOrFail($registro->personal_id);
        $request->validate(['archivo' => PersonalDocumentosService::reglasArchivo()]);

        $detalle = redirect()->route('horas-extras.show', $registro);
        $yaAsociada = 'La autorización ya tiene una constancia firmada asociada.';
        if ($registro->constanciaFirmada()->exists()) {
            return $detalle->withErrors(['archivo' => $yaAsociada]);
        }

        try {
            $documentos->guardar($empleado, $request->file('archivo'), $request->user()->id, [
                'tipo' => config('constantes.tipo_documento_horas_extra'),
                'hora_extra_id' => $registro->id,
            ]);
        } catch (UniqueConstraintViolationException $error) {
            if ($registro->constanciaFirmada()->exists()) {
                return $detalle->withErrors(['archivo' => $yaAsociada]);
            }
            report($error);

            return $detalle->withErrors(['archivo' => 'No se pudo guardar la constancia. Inténtalo de nuevo.']);
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            report($error);

            return $detalle->withErrors(['archivo' => 'No se pudo guardar la constancia. Inténtalo de nuevo.']);
        }

        return $detalle->with('constancia_cargada', 'La constancia firmada quedó asociada y se guardó en el expediente del empleado.');
    }

    public function pdf(Request $request, int $horaExtra, HorasExtrasPdfService $pdf)
    {
        $registro = $this->registro($request, $horaExtra);

        return $pdf->generar($registro)
            ->download('constancia-horas-extras-'.$registro->id.'.pdf')
            ->header('Cache-Control', 'private, no-store');
    }

    public function descargarConstancia(Request $request, int $horaExtra, PersonalDocumentosService $documentos)
    {
        $registro = $this->registro($request, $horaExtra);
        $constancia = $registro->constanciaFirmada()
            ->where('empresa_id', $registro->empresa_id)->where('personal_id', $registro->personal_id)
            ->firstOrFail();

        return $documentos->descargar($constancia);
    }
}
