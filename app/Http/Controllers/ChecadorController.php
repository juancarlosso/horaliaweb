<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Mail\ChecadorActivationLinkMail;
use App\Models\ChecadorSesion;
use App\Models\Empresa;
use App\Models\Personal;
use App\Services\AttendanceLocationService;
use App\Services\AttendancePhotoService;
use App\Services\RegistroAsistenciaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChecadorController extends Controller
{
    use ManagesCompanyCatalogs;

    public function index(Request $request)
    {
        $companies = $this->companiesFor($request, true)->where('activa', true)->values();
        abort_if($companies->isEmpty(), 403);

        ChecadorSesion::query()
            ->where(function ($query) {
                $query->where(function ($pending) {
                    $pending->whereNull('activated_at')->where('created_at', '<', now()->subMinutes(15));
                })->orWhere(function ($expired) {
                    $expired->whereNotNull('activated_at')->where('expires_at', '<=', now());
                });
            })
            ->delete();

        $selectedCompanyId = $this->selectedCompanyForForm($request, $companies);
        $sessions = ChecadorSesion::query()
            ->with('empresa')
            ->whereIn('empresa_id', $companies->modelKeys())
            ->whereHas('empresa', fn ($query) => $query->where('activa', true))
            ->where(function ($query) {
                $query->where(function ($pending) {
                    $pending->whereNull('activated_at')->where('created_at', '>=', now()->subMinutes(15));
                })->orWhere(function ($active) {
                    $active->whereNotNull('activated_at')->where('expires_at', '>', now());
                });
            })
            ->latest('id')
            ->get()
            ->map(function (ChecadorSesion $session) {
                $linkExpiration = $session->activated_at
                    ? $session->expires_at
                    : $session->created_at->copy()->addMinutes(15);
                $session->activation_url = URL::temporarySignedRoute(
                    'checador.activate',
                    $linkExpiration,
                    ['session' => $session->id],
                );
                $session->link_status = $session->activated_at
                    ? 'Sesión activa hasta ' . $session->expires_at?->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : 'Pendiente de activar · duración: ' . $session->duracion_dias . ' ' . ($session->duracion_dias === 1 ? 'día' : 'días');

                return $session;
            });

        return view('checador.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'reloj-checador',
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'activationSessions' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'duracion_dias' => ['required', 'integer', 'between:1,7'],
        ]);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $company = Empresa::query()->whereKey($data['empresa_id'])->where('activa', true)->first();
        abort_unless($company, 422, 'Selecciona una empresa activa.');

        $token = Str::random(64);
        $session = ChecadorSesion::create([
            'empresa_id' => $data['empresa_id'],
            'centro_id' => null,
            'user_id' => $request->user()->id,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'duracion_dias' => $data['duracion_dias'],
        ]);
        return redirect()->route('checador.index');
    }

    public function emailActivationLink(Request $request, int $session)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'email_secondary' => ['nullable', 'email', 'max:255', 'different:email'],
        ], [
            'email.required' => 'Escribe el correo electrónico principal.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email_secondary.email' => 'Escribe un correo electrónico válido en el segundo campo.',
            'email_secondary.different' => 'Los dos correos deben ser diferentes.',
        ]);

        $activationSession = ChecadorSesion::query()
            ->with('empresa')
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->whereHas('empresa', fn ($query) => $query->where('activa', true))
            ->findOrFail($session);

        $isPending = !$activationSession->activated_at
            && $activationSession->created_at?->gte(now()->subMinutes(15));
        $isActive = $activationSession->activated_at
            && $activationSession->expires_at?->isFuture();
        abort_unless($isPending || $isActive, 404);

        $linkExpiration = $activationSession->activated_at
            ? $activationSession->expires_at
            : $activationSession->created_at->copy()->addMinutes(15);
        $activationUrl = URL::temporarySignedRoute(
            'checador.activate',
            $linkExpiration,
            ['session' => $activationSession->id],
        );
        $recipients = array_values(array_filter([$data['email'], $data['email_secondary'] ?? null]));

        try {
            Mail::to($recipients)->queue(new ChecadorActivationLinkMail(
                companyName: (string) $activationSession->empresa->razon_social,
                activationUrl: $activationUrl,
                durationDays: (int) $activationSession->duracion_dias,
                expiresAt: $activationSession->activated_at
                    ? $activationSession->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : null,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'No se pudo enviar el enlace del checador. Inténtalo de nuevo más tarde.']);
        }

        return redirect()->route('checador.index')
            ->with('status', 'El enlace del reloj checador se envió a las direcciones indicadas.');
    }

    public function activate(Request $request, int $session)
    {
        $session = ChecadorSesion::query()->with(['empresa', 'centro'])->findOrFail($session);
        abort_unless($session->empresa?->activa && (!$session->centro_id || $session->centro?->activo), 403, 'La empresa o el centro de trabajo no está disponible.');
        if ($session->activated_at) {
            abort_unless($session->expires_at?->isFuture(), 410, 'Esta sesión de checador expiró.');
        } else {
            abort_if($session->created_at->lt(now()->subMinutes(15)), 410, 'El enlace de activación expiró. Genera uno nuevo desde Horalia.');
            $session->update([
                'activated_at' => now(), 'expires_at' => now()->addDays((int) $session->duracion_dias),
                'activation_ip' => $request->ip(),
                'activation_user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);
        }

        return view('checador.kiosk', [
            'session' => $session,
            'token' => Crypt::decryptString($session->getRawOriginal('token')),
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function clock(): JsonResponse
    {
        return response()->json(['now' => now()->toIso8601String()]);
    }

    public function verifyPin(Request $request, RegistroAsistenciaService $registration, AttendanceLocationService $locations): JsonResponse
    {
        $data = $request->validate(['pin' => ['required', 'regex:/^\d{5}$/']]);
        /** @var ChecadorSesion $session */
        $session = $request->attributes->get('checadorSesion');
        $personal = $this->personalForSession($session, $data['pin']);

        if (!$personal) {
            throw ValidationException::withMessages(['pin' => 'PIN no reconocido.']);
        }

        try {
            if (!$locations->parseCoordinates($personal->centro?->geolocalizacion)) {
                throw ValidationException::withMessages(['pin' => 'El centro de trabajo no tiene geolocalización configurada. Contacta a tu administrador.']);
            }
            $scope = $this->expectedScope($session);
            $kind = $registration->preview($personal, $scope);
            $proof = Str::random(64);
            Cache::put($this->verificationCacheKey($proof), [
                'session_id' => $session->id,
                'personal_id' => $personal->id,
                'pin_hash' => hash('sha256', $data['pin']),
                'kind' => $kind,
            ], now()->addMinutes(2));
        } catch (ValidationException $exception) {
            $message = $exception->errors()['pin'][0] ?? 'No se pudo registrar la asistencia.';
            return response()->json(['message' => $message], 422);
        }

        return response()->json([
            'message' => 'PIN válido',
            'kind' => $kind,
            'employee' => $personal->nombre,
            'proof' => $proof,
        ]);
    }

    public function mark(Request $request, RegistroAsistenciaService $registration, AttendancePhotoService $photos, AttendanceLocationService $locations): JsonResponse
    {
        $data = $request->validate([
            'pin' => ['required', 'regex:/^\d{5}$/'],
            'proof' => ['required', 'string', 'size:64'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'foto' => ['required', 'string', 'max:4200000'],
        ]);
        /** @var ChecadorSesion $session */
        $session = $request->attributes->get('checadorSesion');
        $verification = Cache::pull($this->verificationCacheKey($data['proof']));
        if (!is_array($verification)
            || (int) ($verification['session_id'] ?? 0) !== (int) $session->id
            || !hash_equals((string) ($verification['pin_hash'] ?? ''), hash('sha256', $data['pin']))) {
            return response()->json(['message' => 'La validación del PIN venció. Inténtalo de nuevo.'], 422);
        }

        $personal = $this->personalForSession($session, $data['pin']);
        if (!$personal || (int) $personal->id !== (int) ($verification['personal_id'] ?? 0)) {
            return response()->json(['message' => 'PIN no reconocido.'], 422);
        }
        if (!$locations->parseCoordinates($personal->centro?->geolocalizacion)) {
            return response()->json(['message' => 'El centro de trabajo no tiene geolocalización configurada. Contacta a tu administrador.'], 422);
        }

        $kind = $verification['kind'] === 'salida' ? 'salida' : 'entrada';
        $coordinates = $kind === 'entrada'
            ? ['latitud' => $data['latitud'], 'longitud' => $data['longitud']]
            : ['latitud_salida' => $data['latitud'], 'longitud_salida' => $data['longitud']];
        $photoPath = null;

        try {
            $photoPath = $photos->uploadBase64Jpeg($data['foto'], (int) $personal->empresa_id, $kind);
            $range = $locations->rangeFor($personal, (float) $data['latitud'], (float) $data['longitud']);
            $registration->register(
                $personal,
                $request->ip(),
                $coordinates,
                $photoPath,
                $kind,
                $range,
                $this->expectedScope($session),
            );
        } catch (ValidationException $exception) {
            if ($photoPath) {
                $photos->delete($photoPath);
            }

            return response()->json(['message' => $exception->errors()['pin'][0] ?? $exception->errors()['foto'][0] ?? 'No se pudo registrar la asistencia.'], 422);
        } catch (Throwable $exception) {
            if ($photoPath) {
                $photos->delete($photoPath);
            }
            report($exception);

            return response()->json(['message' => 'No se pudo registrar la asistencia. Inténtalo de nuevo.'], 500);
        }

        return response()->json([
            'message' => $kind === 'entrada' ? 'Entrada registrada' : 'Salida registrada',
            'kind' => $kind,
            'employee' => $personal->nombre,
            'time' => now()->format('H:i'),
        ]);
    }

    private function personalForSession(ChecadorSesion $session, string $pin): ?Personal
    {
        return Personal::query()
            ->with(['empresa', 'centro'])
            ->where('pin', $pin)->where('activo', true)
            ->where('empresa_id', $session->empresa_id)
            ->when($session->centro_id, fn ($query) => $query->where('centro_id', $session->centro_id))
            ->whereHas('empresa', fn ($query) => $query->where('activa', true))
            ->when($session->centro_id, fn ($query) => $query->whereHas('centro', fn ($centerQuery) => $centerQuery->where('activo', true)))
            ->first();
    }

    private function expectedScope(ChecadorSesion $session): array
    {
        return [
            'empresa_id' => $session->empresa_id,
            ...($session->centro_id ? ['centro_id' => $session->centro_id] : []),
        ];
    }

    private function verificationCacheKey(string $proof): string
    {
        return 'checador:pin-proof:' . hash('sha256', $proof);
    }
}
