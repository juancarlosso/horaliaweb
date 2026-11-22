<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Asistencia;
use App\Models\Personal;
use App\Services\AttendanceLocationService;
use App\Services\AttendancePhotoService;
use App\Services\RegistroAsistenciaService;
use App\Support\DataTable;
use App\Support\HoraliaTimezones;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AsistenciaController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request, RegistroAsistenciaService $registration)
    {
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $isEmployeeProfile = (int) $request->user()->profile === 3;
        $personal = $request->user()->personal;
        $personal?->load('centro');
        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:150'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'anio' => ['nullable', 'integer', 'in:'.implode(',', config('constantes.aniosAsistencia', []))],
        ]);

        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);
        if ((int) $request->user()->profile === 1 && ! isset($validated['empresa_id'])) {
            $selectedCompanyId = $companyIds[0] ?? null;
        }

        if ($selectedCompanyId) {
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 404);
        }

        $availableYears = config('constantes.aniosAsistencia', []);
        $selectedYear = (int) ($validated['anio'] ?? (in_array((int) today()->year, $availableYears, true) ? today()->year : ($availableYears[0] ?? today()->year)));
        $selectedMonth = (int) ($validated['mes'] ?? today()->month);
        $monthStart = CarbonImmutable::create($selectedYear, $selectedMonth, 1);
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $periodLabel = $monthNames[$selectedMonth].' '.$selectedYear;

        $query = Asistencia::query()
            ->with(['personal.empresa', 'personal.centro'])
            ->whereHas('personal', function ($query) use ($companyIds, $selectedCompanyId) {
                $query->whereIn('empresa_id', $companyIds)
                    ->when($selectedCompanyId, fn ($personalQuery) => $personalQuery->where('empresa_id', $selectedCompanyId));
            })
            ->when($isEmployeeProfile, fn ($query) => $query->where('personal_id', $personal?->id ?? 0))
            ->where('fecha', '>=', $monthStart->toDateString())
            ->where('fecha', '<', $monthStart->addMonth()->toDateString());

        $search = trim($validated['q'] ?? '');
        $unfilteredTotal = $search !== '' ? (clone $query)->count() : null;
        [$perPage, $sort, $direction] = DataTable::options($request, [
            'colaborador', 'fecha', 'modalidad', 'llegada', 'salida', 'minutos_tarde', 'minutos_salida_temprano',
        ]);
        $sortColumn = $sort === 'colaborador'
            ? Personal::query()->select('nombre')->whereColumn('personal.id', 'asistencias.personal_id')
            : ($sort ?? 'fecha');
        $attendance = $query
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

                $query->whereHas('personal', fn ($personal) => $personal->whereRaw("nombre LIKE ? ESCAPE '!'", [$pattern]));
            })
            ->orderBy($sortColumn, $sort ? $direction : 'desc')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $unfilteredTotal ??= $attendance->total();
        if ($request->header('X-Datatable-Fragment') === '1') {
            $view = view('asistencia.table', compact('attendance', 'unfilteredTotal', 'periodLabel'));

            return response($view->render())->header('Cache-Control', 'no-store');
        }

        $canMarkAttendance = $personal && $personal->empresa?->activa && in_array($personal->empresa_id, $companyIds, true);
        $attendanceTimezone = $canMarkAttendance
            ? HoraliaTimezones::forCenter($personal->centro?->timezone)
            : config('app.timezone');
        $localNow = $canMarkAttendance
            ? now('UTC')->setTimezone($attendanceTimezone)
            : null;
        $todayAttendance = $canMarkAttendance
            ? Asistencia::query()
                ->where('personal_id', $personal->id)
                ->where('fecha', '>=', $localNow->toDateString())
                ->where('fecha', '<', $localNow->copy()->addDay()->toDateString())
                ->first()
            : null;
        $todaySchedule = $canMarkAttendance
            ? $personal->horarios()->where('dia', $localNow->dayOfWeekIso)->first()
            : null;
        $holidayCommemorations = $canMarkAttendance
            ? $registration->holidayCommemorations($personal, $localNow->toDateString())
            : collect();
        $overnightAttendance = $canMarkAttendance
            ? $registration->pendingOvernightAttendance($personal)
            : null;
        $canMarkEntry = $canMarkAttendance && ! $todayAttendance && ! $overnightAttendance && $todaySchedule !== null
            && ($holidayCommemorations->isEmpty() || (bool) $todaySchedule->trabaja_festivos);
        $canMarkExit = $canMarkAttendance
            && (($todayAttendance?->llegada && ! $todayAttendance?->salida) || (! $todayAttendance && $overnightAttendance));

        return view('asistencia.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'asistencia',
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'monthNames' => $monthNames,
            'periodLabel' => $periodLabel,
            'attendance' => $attendance,
            'unfilteredTotal' => $unfilteredTotal,
            'personal' => $personal,
            'attendanceTimezone' => $attendanceTimezone,
            'todayAttendance' => $todayAttendance,
            'todaySchedule' => $todaySchedule,
            'holidayCommemorations' => $holidayCommemorations,
            'overnightAttendance' => $overnightAttendance,
            'canMarkAttendance' => $canMarkAttendance,
            'canMarkEntry' => $canMarkEntry,
            'canMarkExit' => $canMarkExit,
        ]);
    }

    public function markEntry(Request $request, RegistroAsistenciaService $registration, AttendancePhotoService $photos, AttendanceLocationService $locations)
    {
        [$personal] = $this->currentPersonalAndSchedule($request);
        $coordinates = $this->validatedCoordinates($request, 'latitud', 'longitud');
        $photoPath = $this->uploadAttendancePhoto($request, 'foto_entrada', $personal, 'entrada', $photos);

        try {
            $range = isset($coordinates['latitud'], $coordinates['longitud'])
                ? $locations->rangeFor($personal, $coordinates['latitud'], $coordinates['longitud'])
                : 'Sin geolocalización';
            $registration->register($personal, $request->ip(), $coordinates, $photoPath, 'entrada', $range);
        } catch (Throwable $exception) {
            if ($photoPath) $photos->delete($photoPath);
            if ($exception instanceof ValidationException) {
                return back()->with('error', $exception->errors()['pin'][0] ?? 'No se pudo registrar la entrada.');
            }
            report($exception);

            return back()->with('error', 'No se pudo registrar la entrada. Inténtalo de nuevo.');
        }

        return back()->with('status', 'La entrada se registró correctamente.');
    }

    public function markExit(Request $request, RegistroAsistenciaService $registration, AttendancePhotoService $photos, AttendanceLocationService $locations)
    {
        [$personal] = $this->currentPersonalAndSchedule($request);
        $coordinates = $this->validatedCoordinates($request, 'latitud_salida', 'longitud_salida');
        $photoPath = $this->uploadAttendancePhoto($request, 'foto_salida', $personal, 'salida', $photos);

        try {
            $range = isset($coordinates['latitud_salida'], $coordinates['longitud_salida'])
                ? $locations->rangeFor($personal, $coordinates['latitud_salida'], $coordinates['longitud_salida'])
                : 'Sin geolocalización';
            $registration->register($personal, $request->ip(), $coordinates, $photoPath, 'salida', $range);
        } catch (Throwable $exception) {
            if ($photoPath) $photos->delete($photoPath);
            if ($exception instanceof ValidationException) {
                return back()->with('error', $exception->errors()['pin'][0] ?? 'No se pudo registrar la salida.');
            }
            report($exception);

            return back()->with('error', 'No se pudo registrar la salida. Inténtalo de nuevo.');
        }

        return back()->with('status', 'La salida se registró correctamente.');
    }

    private function currentPersonalAndSchedule(Request $request): array
    {
        $personal = $request->user()->personal;
        abort_unless($personal && in_array($personal->empresa_id, $this->companyIdsFor($request), true), 403);

        $personal->load(['empresa', 'centro']);
        if ((int) $request->user()->profile === 3 && ! $personal->empresa?->activa) {
            throw ValidationException::withMessages(['asistencia' => 'EMPRESA INACTIVA, no podrás marcar asistencias.']);
        }

        if (! $personal->centro) {
            throw ValidationException::withMessages(['asistencia' => 'Configura un centro de trabajo antes de registrar asistencia.']);
        }

        return [$personal, null];
    }

    private function validatedCoordinates(Request $request, string $latitudeKey, string $longitudeKey): array
    {
        $coordinates = $request->validate([
            $latitudeKey => ['nullable', 'numeric', 'between:-90,90'],
            $longitudeKey => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($request->filled($latitudeKey) xor $request->filled($longitudeKey)) {
            throw ValidationException::withMessages([
                $latitudeKey => 'No se recibió una ubicación completa.',
            ]);
        }

        return array_filter($coordinates, fn ($value) => $value !== null);
    }

    private function uploadAttendancePhoto(Request $request, string $field, Personal $personal, string $type, AttendancePhotoService $photos): ?string
    {
        if (! $request->filled($field)) {
            return null;
        }

        $photoData = $request->validate([
            $field => ['nullable', 'string', 'max:4200000'],
        ])[$field];

        return $photoData !== null
            ? $photos->uploadBase64Jpeg($photoData, (int) $personal->empresa_id, $type, $field, $personal->centro?->timezone)
            : null;
    }

    private function deleteAttendancePhoto(string $path, AttendancePhotoService $photos): void
    {
        $photos->delete($path);
    }
}
