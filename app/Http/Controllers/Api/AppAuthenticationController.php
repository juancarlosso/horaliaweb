<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AppAuthenticationController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los datos enviados.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();
        $user = User::query()
            ->where('email', mb_strtolower(trim($credentials['email'])))
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->error('El correo o la contraseña no son correctos.', 401);
        }

        $company = $this->manageableActiveCompany($user);
        if (!$company) {
            return $this->error('Esta cuenta no tiene autorización para usar la aplicación, solo los administradores de la empresa pueden accesar', 403);
        }

        $token = $user->createToken(
            'horalia-mobile',
            ['horalia-app', 'empresa:' . $company->getKey()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa.',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $this->userData($user),
                'empresa' => $this->companyData($company),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$token instanceof PersonalAccessToken || !$token->can('horalia-app')) {
            return $this->error('La sesión no es válida.', 401);
        }

        $companyId = $this->companyIdFromToken($token);
        $company = $companyId ? $this->manageableActiveCompany($user, $companyId) : null;
        if (!$company) {
            return $this->error('Esta cuenta ya no tiene autorización para usar la aplicación.', 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión válida.',
            'data' => [
                'user' => $this->userData($user),
                'empresa' => $this->companyData($company),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if (!$token instanceof PersonalAccessToken || !$token->can('horalia-app')) {
            return $this->error('La sesión no es válida.', 401);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    private function manageableActiveCompany(User $user, ?int $companyId = null): ?Empresa
    {
        if ((int) $user->profile !== 2
            || ($user->personal_id !== null && (!$user->personal || !$user->personal->activo))) {
            return null;
        }

        return $user->empresas()
            ->wherePivot('control_total', true)
            ->where('empresas.activa', true)
            ->when($companyId, fn ($query) => $query->where('empresas.id', $companyId))
            ->orderBy('empresas.nombre')
            ->first();
    }

    private function companyIdFromToken(PersonalAccessToken $token): ?int
    {
        foreach ((array) $token->abilities as $ability) {
            if (is_string($ability) && preg_match('/^empresa:(\d+)$/', $ability, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    private function companyData(Empresa $company): array
    {
        return [
            'id' => $company->getKey(),
            'nombre' => $company->nombre ?: $company->razon_social,
            'direccion' => $company->direccion,
            'telefono' => $company->telefono,
        ];
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
