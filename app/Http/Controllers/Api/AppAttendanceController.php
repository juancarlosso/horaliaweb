<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\User;
use App\Services\AttendanceLocationService;
use App\Services\AttendancePhotoService;
use App\Services\AsistenciaPausaService;
use App\Services\RegistroAsistenciaService;
use App\Support\HoraliaTimezones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AppAttendanceController extends Controller
{
    public function verifyPausePin(Request $request, AsistenciaPausaService $pauses): JsonResponse
    {
        $data = $request->validate(['pin' => ['required', 'regex:/^\\d{5}$/']]);
        [$user, $companyId] = $this->authorizedCompany($request);
        $personal = $this->personalForPin($data['pin'], $companyId);
        if (! $personal) {
            return response()->json(['success' => false, 'message' => 'PIN no reconocido.'], 422);
        }

        $attendance = $pauses->currentAttendance($personal);
        if (! $attendance || ! $attendance->llegada || $attendance->salida) {
            return response()->json(['success' => false, 'message' => 'Necesitas registrar tu entrada antes de iniciar o finalizar una pausa.'], 422);
        }

        $activePause = $attendance->pausas()->whereNull('fin')->latest('inicio')->first();
        $token = $user->currentAccessToken();
        $proof = Str::random(64);
        Cache::put($this->pauseProofKey($proof), [
            'user_id' => $user->getKey(),
            'token_id' => $token->getKey(),
            'personal_id' => $personal->getKey(),
            'center_id' => $personal->centro_id,
            'pin_hash' => hash('sha256', $data['pin']),
        ], now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $personal->nombre,
                'employee_id' => $personal->codigo ?: str_pad((string) $personal->getKey(), 3, '0', STR_PAD_LEFT),
                'proof' => $proof,
                'active_pause' => $activePause ? $this->pauseData($activePause, $personal) : null,
                'types' => config('constantes.tiposPausa', []),
                'time' => now()->setTimezone(HoraliaTimezones::forCenter($personal->centro?->timezone))->format('H:i'),
            ],
        ]);
    }

    public function startPause(Request $request, AsistenciaPausaService $pauses): JsonResponse
    {
        return $this->handlePauseAction($request, $pauses, 'start');
    }

    public function finishPause(Request $request, AsistenciaPausaService $pauses): JsonResponse
    {
        return $this->handlePauseAction($request, $pauses, 'finish');
    }

    private function handlePauseAction(Request $request, AsistenciaPausaService $pauses, string $action): JsonResponse
    {
        $rules = ['pin' => ['required', 'regex:/^\\d{5}$/'], 'proof' => ['required', 'string', 'size:64']];
        if ($action === 'start') {
            $rules['tipo'] = ['required', 'string', 'in:'.implode(',', array_keys(config('constantes.tiposPausa', [])))];
        }
        $data = $request->validate($rules);
        [$user, $companyId] = $this->authorizedCompany($request);
        $token = $user->currentAccessToken();

        $verification = Cache::lock($this->pauseProofLockKey($data['proof']), 15)->block(3, function () use ($data) {
            return Cache::pull($this->pauseProofKey($data['proof']));
        });
        if (! is_array($verification)
            || (int) ($verification['user_id'] ?? 0) !== (int) $user->getKey()
            || (int) ($verification['token_id'] ?? 0) !== (int) $token->getKey()
            || ! hash_equals((string) ($verification['pin_hash'] ?? ''), hash('sha256', $data['pin']))) {
            return response()->json(['success' => false, 'message' => 'La validación del PIN venció. Inténtalo de nuevo.'], 422);
        }

        $personal = $this->personalForPin($data['pin'], $companyId);
        if (! $personal || (int) $personal->getKey() !== (int) ($verification['personal_id'] ?? 0)
            || (int) $personal->centro_id !== (int) ($verification['center_id'] ?? 0)) {
            return response()->json(['success' => false, 'message' => 'PIN no reconocido.'], 422);
        }

        try {
            $scope = ['empresa_id' => $companyId, 'centro_id' => $personal->centro_id];
            $pause = $action === 'start'
                ? $pauses->start($personal, $data['tipo'], $scope)
                : $pauses->finish($personal, $scope);
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => $exception->errors()['pausa'][0] ?? 'No se pudo registrar la pausa.'], 422);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['success' => false, 'message' => 'No se pudo registrar la pausa. Inténtalo de nuevo.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pausa de '.config('constantes.tiposPausa.'.$pause->tipo.'.nombre', strtoupper($pause->tipo)).($action === 'start' ? ' iniciada' : ' finalizada').' correctamente.',
            'data' => $this->pauseData($pause, $personal),
        ]);
    }

    public function verifyPin(Request $request, RegistroAsistenciaService $registration, AttendanceLocationService $locations): JsonResponse
    {
        $data = $request->validate(['pin' => ['required', 'regex:/^\\d{5}$/']]);
        [$user, $companyId] = $this->authorizedCompany($request);

        $personal = $this->personalForPin($data['pin'], $companyId);

        if (! $personal) {
            return response()->json(['success' => false, 'message' => 'PIN no reconocido.'], 422);
        }
        if (! $locations->parseCoordinates($personal->centro?->geolocalizacion)) {
            return response()->json(['success' => false, 'message' => 'El centro de trabajo del colaborador no tiene geolocalización configurada. Contacta a tu administrador.'], 422);
        }

        try {
            $kind = $registration->preview($personal, ['empresa_id' => $companyId, 'centro_id' => $personal->centro_id]);
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => $exception->errors()['pin'][0] ?? 'No se pudo validar el PIN.'], 422);
        }

        $token = $user->currentAccessToken();
        $proof = Str::random(64);
        Cache::put($this->proofKey($proof), [
            'user_id' => $user->getKey(),
            'token_id' => $token->getKey(),
            'personal_id' => $personal->getKey(),
            'center_id' => $personal->centro_id,
            'pin_hash' => hash('sha256', $data['pin']),
            'kind' => $kind,
        ], now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'message' => 'PIN válido.',
            'data' => ['kind' => $kind, 'employee' => $personal->nombre, 'proof' => $proof],
        ]);
    }

    public function mark(Request $request, RegistroAsistenciaService $registration, AttendancePhotoService $photos, AttendanceLocationService $locations): JsonResponse
    {
        $data = $request->validate([
            'pin' => ['required', 'regex:/^\\d{5}$/'],
            'proof' => ['required', 'string', 'size:64'],
            'latitud' => ['sometimes', 'required_with:longitud', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['sometimes', 'required_with:latitud', 'nullable', 'numeric', 'between:-180,180'],
            'foto' => ['sometimes', 'nullable', 'string', 'max:4200000'],
        ]);
        [$user, $companyId] = $this->authorizedCompany($request);
        $token = $user->currentAccessToken();

        // A proof can be consumed only once, even when concurrent requests race.
        $verification = Cache::lock($this->proofLockKey($data['proof']), 15)->block(3, function () use ($data) {
            return Cache::pull($this->proofKey($data['proof']));
        });

        if (! is_array($verification)
            || (int) ($verification['user_id'] ?? 0) !== (int) $user->getKey()
            || (int) ($verification['token_id'] ?? 0) !== (int) $token->getKey()
            || ! hash_equals((string) ($verification['pin_hash'] ?? ''), hash('sha256', $data['pin']))) {
            return response()->json(['success' => false, 'message' => 'La validación del PIN venció. Inténtalo de nuevo.'], 422);
        }

        $personal = $this->personalForPin($data['pin'], $companyId);

        if (! $personal || (int) $personal->getKey() !== (int) ($verification['personal_id'] ?? 0)
            || (int) $personal->centro_id !== (int) ($verification['center_id'] ?? 0)) {
            return response()->json(['success' => false, 'message' => 'PIN no reconocido.'], 422);
        }
        $kind = ($verification['kind'] ?? null) === 'salida' ? 'salida' : 'entrada';
        $hasCoordinates = isset($data['latitud'], $data['longitud']);
        $coordinates = $hasCoordinates
            ? ($kind === 'entrada'
                ? ['latitud' => $data['latitud'], 'longitud' => $data['longitud']]
                : ['latitud_salida' => $data['latitud'], 'longitud_salida' => $data['longitud']])
            : null;
        $photoPath = null;

        try {
            if (! empty($data['foto'])) {
                $photoPath = $photos->uploadBase64Jpeg($data['foto'], $companyId, $kind);
            }
            $range = $hasCoordinates
                ? $locations->rangeFor($personal, (float) $data['latitud'], (float) $data['longitud'])
                : null;
            $registration->register(
                $personal,
                $request->ip(),
                $coordinates,
                $photoPath,
                $kind,
                $range,
                ['empresa_id' => $companyId, 'centro_id' => $personal->centro_id],
            );
        } catch (ValidationException $exception) {
            if ($photoPath) $photos->delete($photoPath);
            return response()->json(['success' => false, 'message' => $exception->errors()['pin'][0] ?? $exception->errors()['foto'][0] ?? 'No se pudo registrar la asistencia.'], 422);
        } catch (Throwable $exception) {
            if ($photoPath) $photos->delete($photoPath);
            report($exception);
            return response()->json(['success' => false, 'message' => 'No se pudo registrar la asistencia. Inténtalo de nuevo.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $kind === 'entrada' ? 'Entrada registrada correctamente.' : 'Salida registrada correctamente.',
            'data' => [
                'kind' => $kind,
                'employee' => $personal->nombre,
                'time' => now()->setTimezone(HoraliaTimezones::forCenter($personal->centro?->timezone))->format('H:i'),
            ],
        ]);
    }

    /** @return array{User, int} */
    private function authorizedCompany(Request $request): array
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        abort_unless($user instanceof User && $token instanceof PersonalAccessToken && $token->can('horalia-app'), 401);

        $companyId = null;
        foreach ((array) $token->abilities as $ability) {
            if (is_string($ability) && preg_match('/^empresa:(\\d+)$/', $ability, $matches)) {
                $companyId = (int) $matches[1];
                break;
            }
        }
        abort_unless($companyId, 403, 'La sesión no tiene una empresa asignada.');

        $user->loadMissing('personal');
        $company = $user->empresas()
            ->wherePivot('control_total', true)
            ->where('empresas.activa', true)
            ->whereKey($companyId)
            ->first();
        abort_unless(
            (int) $user->profile === 2
                && (! $user->personal_id || $user->personal?->activo)
                && $company,
            403,
            'Esta cuenta ya no tiene autorización para usar el reloj checador.'
        );

        return [$user, $companyId];
    }

    private function proofKey(string $proof): string
    {
        return 'app:attendance-proof:'.hash('sha256', $proof);
    }

    private function personalForPin(string $pin, int $companyId): ?Personal
    {
        return Personal::query()
            ->with(['empresa', 'centro'])
            ->where('pin', $pin)
            ->where('activo', true)
            ->where('empresa_id', $companyId)
            ->whereHas('empresa', fn ($query) => $query->where('activa', true))
            ->whereHas('centro', fn ($query) => $query->where('activo', true)->whereColumn('centros.empresa_id', 'personal.empresa_id'))
            ->first();
    }

    private function pauseData($pause, Personal $personal): array
    {
        $start = $pause->inicio;
        $end = $pause->fin;
        $durationSeconds = $pause->inicio_utc && $pause->fin_utc
            ? (int) $pause->inicio_utc->diffInSeconds($pause->fin_utc)
            : null;

        return [
            'id' => $pause->getKey(),
            'key' => $pause->tipo,
            'type' => config('constantes.tiposPausa.'.$pause->tipo.'.nombre', strtoupper($pause->tipo)),
            'start_time' => $start?->format('H:i'),
            'end_time' => $end?->format('H:i'),
            'duration' => $durationSeconds === null ? null : sprintf('%02d:%02d', intdiv($durationSeconds, 3600), intdiv($durationSeconds % 3600, 60)),
            'time' => now()->setTimezone(HoraliaTimezones::forCenter($personal->centro?->timezone))->format('H:i'),
        ];
    }

    private function pauseProofKey(string $proof): string
    {
        return 'app:pause-proof:'.hash('sha256', $proof);
    }

    private function pauseProofLockKey(string $proof): string
    {
        return 'app:pause-proof-lock:'.hash('sha256', $proof);
    }

    private function proofLockKey(string $proof): string
    {
        return 'app:attendance-proof-lock:'.hash('sha256', $proof);
    }
}
