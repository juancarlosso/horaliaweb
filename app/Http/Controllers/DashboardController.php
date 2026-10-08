<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\Personal;
use App\Services\RegistroAsistenciaService;
use App\Services\StripeCardService;
use Illuminate\Http\Request;
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
        'reloj-checador' => ['label' => 'Reloj Checador', 'icon' => 'tablet', 'group' => 'Operación'],
        'reportes-general' => ['label' => 'General', 'icon' => 'chart', 'group' => 'REPORTES'],
        'reportes-departamento' => ['label' => 'Por Departamento', 'icon' => 'chart', 'group' => 'REPORTES'],
    ];

    public static function navigation(): array
    {
        return self::SECTIONS;
    }

    public function index(Request $request, StripeCardService $stripe, RegistroAsistenciaService $registration)
    {
        $data = [
            'navigation' => self::SECTIONS,
            'activeSection' => 'home',
        ];

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
            $companies = $request->user()->empresas;
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
            'navigation' => self::SECTIONS,
            'activeSection' => $section,
            'section' => self::SECTIONS[$section],
        ]);
    }
}
