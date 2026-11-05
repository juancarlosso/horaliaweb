<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\HorarioEmpresa;
use App\Models\IntentoPago;
use App\Models\Personal;
use App\Services\RegistroAsistenciaService;
use App\Services\StripeCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    private const SECTIONS = [
        'empresas' => ['label' => 'Empresas', 'icon' => 'building', 'group' => 'Organización'],
        'centros-de-trabajo' => ['label' => 'Centros de Trabajo', 'icon' => 'pin', 'group' => 'Organización'],
        'departamentos' => ['label' => 'Departamentos', 'icon' => 'hierarchy', 'group' => 'Organización'],
        'puestos' => ['label' => 'Puestos', 'icon' => 'briefcase', 'group' => 'Organización'],
        'horarios' => ['label' => 'Horarios', 'icon' => 'calendar', 'group' => 'Operación'],
        'personal' => ['label' => 'Personal', 'icon' => 'people', 'group' => 'Operación'],
        'asistencia' => ['label' => 'Asistencia', 'icon' => 'clock', 'group' => 'Operación'],
        'pausas' => ['label' => 'Pausas', 'icon' => 'coffee', 'group' => 'Operación'],
        'reloj-checador' => ['label' => 'Reloj Checador', 'icon' => 'tablet', 'group' => 'Operación'],
        'reportes-general' => ['label' => 'General', 'icon' => 'chart', 'group' => 'REPORTES'],
        'reportes-departamento' => ['label' => 'Por Departamento', 'icon' => 'chart', 'group' => 'REPORTES'],
    ];

    public static function navigation(): array
    {
        $sections = self::SECTIONS;
        $user = auth()->user();
        if (! $user || ! in_array((int) $user->profile, [2, 3], true) || ! $user->personal) {
            unset($sections['pausas']);
        }

        return $sections;
    }

    public function index(Request $request, StripeCardService $stripe, RegistroAsistenciaService $registration)
    {
        $data = [
            'navigation' => self::navigation(),
            'activeSection' => 'home',
        ];

        if ((int) $request->user()->profile === 1) {
            $data += $this->adminDashboardData($request);

            return view('dashboard.home', $data);
        }

        if ((int) $request->user()->profile === 3) {
            $personal = $request->user()->personal;
            $today = today();
            $todayAttendance = $personal
                ? Asistencia::query()->where('personal_id', $personal->id)->whereDate('fecha', $today)->first()
                : null;
            $workDays = array_filter(explode('@', (string) $personal?->laborados));
            $schedules = $personal?->horarios()->get()->keyBy('dia') ?? collect();
            $worksToday = $personal && in_array((string) $today->dayOfWeekIso, $workDays, true);
            $todaySchedule = $worksToday ? $schedules->get($today->dayOfWeekIso) : null;
            $overnightAttendance = $personal
                ? $registration->pendingOvernightAttendance($personal)
                : null;
            $monthScheduledDays = 0;
            if ($personal) {
                for ($date = $today->copy()->startOfMonth(); $date->lte($today); $date->addDay()) {
                    $day = (string) $date->dayOfWeekIso;
                    if (in_array($day, $workDays, true) && $schedules->has((int) $day)) {
                        $monthScheduledDays++;
                    }
                }
            }
            $monthAttendance = $personal
                ? Asistencia::query()->where('personal_id', $personal->id)->whereBetween('fecha', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                : null;

            $data += [
                'personal' => $personal,
                'todayAttendance' => $todayAttendance,
                'todaySchedule' => $todaySchedule,
                'overnightAttendance' => $overnightAttendance,
                'worksToday' => $worksToday,
                'canMarkEntry' => $personal?->empresa?->activa && $worksToday && $todaySchedule && !$todayAttendance && !$overnightAttendance,
                'canMarkExit' => $personal?->empresa?->activa
                    && (($todayAttendance?->llegada && !$todayAttendance?->salida) || (!$todayAttendance && $overnightAttendance)),
                'monthAttendanceCount' => $monthAttendance ? (clone $monthAttendance)->count() : 0,
                'monthScheduledDays' => $monthScheduledDays,
                'monthLateCount' => $monthAttendance ? (clone $monthAttendance)->where('minutos_tarde', '>', 0)->count() : 0,
                'monthLateMinutes' => $monthAttendance ? (clone $monthAttendance)->sum('minutos_tarde') : 0,
                'monthEarlyExitMinutes' => $monthAttendance ? (clone $monthAttendance)->sum('minutos_salida_temprano') : 0,
                'recentAttendance' => $personal
                    ? Asistencia::query()->where('personal_id', $personal->id)->orderByDesc('fecha')->orderByDesc('created_at')->limit(5)->get()
                    : collect(),
            ];
        } elseif ((int) $request->user()->profile === 2) {
            $companies = (int) $request->user()->profile === 1
                ? Empresa::query()->get()
                : $request->user()->empresas;
            $companyIds = $companies->modelKeys();
            $renewalCompanies = $companies
                ->filter(fn (Empresa $company) => $company->fecha_renovacion && $company->fecha_renovacion->toDateString() <= today()->toDateString())
                ->map(function (Empresa $company) use ($stripe) {
                    $cards = [];
                    try {
                        $cards = $stripe->cards($company);
                    } catch (Throwable $exception) {
                        Log::warning('No se pudieron cargar las tarjetas para el pago pendiente.', [
                            'empresa_id' => $company->id,
                            'exception' => get_class($exception),
                        ]);
                    }

                    $pendingAttempt = IntentoPago::query()
                        ->where('empresa_id', $company->id)
                        ->whereDate('fecha_renovacion', $company->fecha_renovacion->toDateString())
                        ->where('origen', 'manual')
                        ->where('resultado', 'pendiente')
                        ->latest('id')
                        ->first();
                    $automaticPending = IntentoPago::query()
                        ->where('empresa_id', $company->id)
                        ->whereDate('fecha_renovacion', $company->fecha_renovacion->toDateString())
                        ->where('origen', 'automatico')
                        ->where('resultado', 'pendiente')
                        ->exists();

                    return [
                        'empresa' => $company,
                        'cards' => $cards,
                        'pending_payment_method_id' => $pendingAttempt?->tarjeta_id,
                        'automatic_pending' => $automaticPending,
                    ];
                })->values();
            $today = today();
            $todayDay = (string) $today->dayOfWeekIso;
            $scheduledEmployees = Personal::query()
                ->whereIn('empresa_id', $companyIds)
                ->where('activo', true)
                ->whereHas('horarios', fn ($query) => $query->where('dia', $today->dayOfWeekIso))
                ->get(['id', 'laborados'])
                ->filter(fn (Personal $employee) => in_array($todayDay, array_filter(explode('@', (string) $employee->laborados)), true));
            $scheduledEmployeeIds = $scheduledEmployees->modelKeys();
            $todayAttendance = Asistencia::query()
                ->whereDate('fecha', $today)
                ->whereHas('personal', fn ($query) => $query->whereIn('empresa_id', $companyIds));
            $todayScheduledAttendance = (clone $todayAttendance)
                ->whereIn('personal_id', $scheduledEmployeeIds)
                ->whereNotNull('llegada');
            $recentAttendance = Asistencia::query()
                ->with(['personal.empresa'])
                ->whereHas('personal', fn ($query) => $query->whereIn('empresa_id', $companyIds))
                ->orderByDesc('fecha')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();

            $data += [
                'companyCount' => count($companyIds),
                'activeEmployeeCount' => Personal::query()->whereIn('empresa_id', $companyIds)->where('activo', true)->count(),
                'scheduledEmployeeCount' => $scheduledEmployees->count(),
                'presentTodayCount' => (clone $todayScheduledAttendance)->count(),
                'lateTodayCount' => (clone $todayScheduledAttendance)->where('minutos_tarde', '>', 0)->count(),
                'pendingEntryCount' => max(0, $scheduledEmployees->count() - (clone $todayScheduledAttendance)->count()),
                'pendingExitCount' => (clone $todayAttendance)->whereNotNull('llegada')->whereNull('salida')->count(),
                'recentAttendance' => $recentAttendance,
                'renewalCompanies' => $renewalCompanies,
            ];
        }

        return view('dashboard.home', $data);
    }

    public function summary(Request $request, StripeCardService $stripe)
    {
        abort_unless((int) $request->user()->profile === 1, 403);

        $companies = Empresa::query()->orderBy('nombre')->get();
        $localPaymentMethodCounts = DB::table('payment_method_consents')
            ->select('empresa_id', DB::raw('COUNT(*) as total'))
            ->groupBy('empresa_id')
            ->pluck('total', 'empresa_id');

        $companies->loadCount(['centros', 'horarios', 'personal']);
        $attendanceCounts = DB::table('asistencias')
            ->join('personal', 'personal.id', '=', 'asistencias.personal_id')
            ->select('personal.empresa_id', DB::raw('COUNT(asistencias.id) as total'))
            ->groupBy('personal.empresa_id')
            ->pluck('total', 'personal.empresa_id');

        $companies->each(function (Empresa $company) use ($attendanceCounts, $localPaymentMethodCounts, $stripe): void {
            $company->setAttribute('asistencias_count', (int) ($attendanceCounts[$company->id] ?? 0));
            $fallbackCount = (int) ($localPaymentMethodCounts[$company->id] ?? 0);
            $paymentMethodCount = Cache::remember(
                'admin-summary-payment-method-count-' . $company->id,
                now()->addMinutes(10),
                function () use ($company, $stripe, $fallbackCount): int {
                    if (! $stripe->ready() || ! $company->stripe_customer_id) {
                        return $fallbackCount;
                    }

                    try {
                        return count($stripe->cards($company));
                    } catch (Throwable $exception) {
                        Log::warning('No se pudieron contar los métodos de pago de la empresa para el resumen.', [
                            'empresa_id' => $company->id,
                            'exception' => get_class($exception),
                        ]);

                        return $fallbackCount;
                    }
                }
            );
            $company->setAttribute('metodos_pago_count', $paymentMethodCount);
        });

        return view('dashboard.summary', [
            'companies' => $companies,
            'navigation' => self::navigation(),
            'activeSection' => 'summary',
        ]);
    }

    private function adminDashboardData(Request $request): array
    {
        $firstRecordedYear = collect([
            Empresa::query()->min('created_at'),
            Asistencia::query()->min('fecha'),
        ])->filter()->map(fn ($date) => (int) date('Y', strtotime((string) $date)))->min();
        $currentYear = (int) today()->format('Y');
        $firstYear = min($firstRecordedYear ?: $currentYear, $currentYear);

        $filters = $request->validate([
            'metric' => ['nullable', Rule::in(['empresas', 'asistencias'])],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', "between:{$firstYear},{$currentYear}"],
        ]);

        $month = (int) ($filters['month'] ?? today()->month);
        $year = (int) ($filters['year'] ?? $currentYear);
        $metric = $filters['metric'] ?? 'empresas';
        $start = today()->setDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $daysInMonth = $start->daysInMonth;
        $dailyCounts = array_fill(1, $daysInMonth, 0);

        $dailyRecords = $metric === 'empresas'
            ? Empresa::query()
                ->whereBetween('created_at', [$start, $end->copy()->endOfDay()])
                ->selectRaw('DATE(created_at) as chart_date, COUNT(*) as total')
                ->groupBy('chart_date')
                ->get()
            : Asistencia::query()
                ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('DATE(fecha) as chart_date, COUNT(*) as total')
                ->groupBy('chart_date')
                ->get();

        foreach ($dailyRecords as $record) {
            $dailyCounts[(int) date('j', strtotime((string) $record->chart_date))] = (int) $record->total;
        }

        $maxValue = max(1, ...array_values($dailyCounts));
        $axisStep = $maxValue <= 10 ? 1 : $this->chartAxisStep($maxValue);
        $axisMax = (int) (ceil($maxValue / $axisStep) * $axisStep);
        $plot = ['left' => 56, 'top' => 20, 'width' => 870, 'height' => 220];
        $barWidth = round(($plot['width'] / $daysInMonth) * 0.62, 2);
        $chartPoints = [];
        foreach ($dailyCounts as $day => $value) {
            $x = round($plot['left'] + (($day - 0.5) / $daysInMonth) * $plot['width'], 2);
            $y = round($plot['top'] + $plot['height'] - ($value / $axisMax) * $plot['height'], 2);
            $chartPoints[] = [
                'day' => $day,
                'value' => $value,
                'x' => $x,
                'barX' => round($x - ($barWidth / 2), 2),
                'barWidth' => $barWidth,
                'y' => $y,
                'height' => round($plot['top'] + $plot['height'] - $y, 2),
            ];
        }

        $attendanceCount = Asistencia::query()->count();

        return [
            'isSystemAdminDashboard' => true,
            'adminTotals' => [
                'companies' => Empresa::query()->count(),
                'schedules' => HorarioEmpresa::query()->count(),
                'employees' => Personal::query()->count(),
                'attendance' => $attendanceCount,
            ],
            'chartMetric' => $metric,
            'chartMonth' => $month,
            'chartYear' => $year,
            'chartYears' => range($currentYear, $firstYear),
            'chartDaysInMonth' => $daysInMonth,
            'chartPoints' => $chartPoints,
            'chartGrid' => collect(range(0, (int) ($axisMax / $axisStep)))->map(function (int $step) use ($plot, $axisMax, $axisStep): array {
                $value = $axisMax - ($step * $axisStep);

                return [
                    'y' => round($plot['top'] + ($step * $axisStep / $axisMax) * $plot['height'], 2),
                    'value' => (int) $value,
                ];
            }),
            'chartLabel' => $metric === 'empresas' ? 'Empresas registradas' : 'Registros de asistencia',
            'chartMonthlyTotal' => array_sum($dailyCounts),
            'chartMonthName' => ucfirst($start->locale('es')->translatedFormat('F')),
        ];
    }

    private function chartAxisStep(int $maxValue): int
    {
        $rawStep = $maxValue / 4;
        $magnitude = 10 ** (int) floor(log10($rawStep));
        $normalized = $rawStep / $magnitude;

        return (int) (ceil($normalized) * $magnitude);
    }

    public function module(string $section)
    {
        abort_unless((isset(self::SECTIONS[$section]) || $section === 'reportes') && $section !== 'reportes-departamento', 404);

        if ($section === 'empresas') {
            return redirect()->route('empresas.index');
        }
        if ($section === 'centros-de-trabajo') {
            return redirect()->route('centros.index');
        }
        if ($section === 'departamentos') {
            return redirect()->route('departamentos.index');
        }
        if ($section === 'puestos') {
            return redirect()->route('puestos.index');
        }
        if ($section === 'horarios') {
            return redirect()->route('horarios.index');
        }
        if ($section === 'personal') {
            return redirect()->route('personal.index');
        }
        if ($section === 'asistencia') {
            return redirect()->route('asistencia.index');
        }
        if ($section === 'reportes') {
            return redirect()->route('reportes.general');
        }

        return view('dashboard.module', [
            'navigation' => self::navigation(),
            'activeSection' => $section,
            'section' => self::SECTIONS[$section],
        ]);
    }
}
