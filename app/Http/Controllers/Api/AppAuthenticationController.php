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
            'supports_multi_company' => ['sometimes', 'boolean'],
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

        $companies = $this->manageableActiveCompanies($user);
        if ($companies->isEmpty()) {
            return $this->error('Esta cuenta no tiene autorización para usar la aplicación, solo los administradores de la empresa pueden accesar', 403);
        }

        $supportsMultiCompany = (bool) ($credentials['supports_multi_company'] ?? false);
        $sessionCompanies = $supportsMultiCompany ? $companies : $companies->take(1)->values();
        $sessionCompanyIds = $sessionCompanies->modelKeys();
        $token = $user->createToken(
            'horalia-mobile',
            array_merge(['horalia-app'], array_map(fn ($id) => 'empresa:' . $id, $sessionCompanyIds))
        );

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa.',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $this->userData($user),
                'empresa' => $this->companyData($sessionCompanies->first()),
                'empresas' => $sessionCompanies->map(fn (Empresa $company) => $this->companyData($company))->values(),
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

        $companyIds = $this->companyIdsFromToken($token);
        $companies = $companyIds ? $this->manageableActiveCompanies($user, $companyIds) : collect();
        if ($companies->isEmpty()) {
            return $this->error('Esta cuenta ya no tiene autorización para usar la aplicación.', 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión válida.',
            'data' => [
                'user' => $this->userData($user),
                'empresa' => $this->companyData($companies->first()),
                'empresas' => $companies->map(fn (Empresa $company) => $this->companyData($company))->values(),
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

    private function manageableActiveCompanies(User $user, ?array $companyIds = null)
    {
        if ((int) $user->profile !== 2
            || ($user->personal_id !== null && (!$user->personal || !$user->personal->activo))) {
            return collect();
        }

        return $user->empresas()
            ->wherePivot('control_total', true)
            ->where('empresas.activa', true)
            ->when($companyIds !== null, fn ($query) => $query->whereIn('empresas.id', $companyIds))
            ->orderBy('empresas.nombre')
            ->get();
    }

    private function companyIdsFromToken(PersonalAccessToken $token): array
    {
        $ids = [];
        foreach ((array) $token->abilities as $ability) {
            if (is_string($ability) && preg_match('/^empresa:(\d+)$/', $ability, $matches)) {
                $ids[] = (int) $matches[1];
            }
        }

        return array_values(array_unique($ids));
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
            'direccion' => $this->companyAddress($company),
            'telefono' => $company->telefono,
        ];
    }

    private function companyAddress(Empresa $company): ?string
    {
        $legacyAddress = trim((string) $company->direccion);
        if ($legacyAddress !== '') {
            return $legacyAddress;
        }

        $exterior = trim((string) $company->domicilio_numero_exterior);
        $interior = trim((string) $company->domicilio_numero_interior);

        $street = array_values(array_filter([
            trim((string) $company->domicilio_calle),
            $exterior !== '' ? 'No. ext. '.$exterior : null,
            $interior !== '' ? 'Int. '.$interior : null,
        ], fn ($part) => $part !== null && $part !== ''));

        $parts = array_values(array_filter([
            $street ? implode(' ', $street) : null,
            trim((string) $company->domicilio_colonia),
            trim((string) $company->domicilio_municipio),
            trim((string) $company->domicilio_ciudad),
            trim((string) $company->domicilio_estado),
            $company->domicilio_codigo_postal
                ? 'C.P. '.trim((string) $company->domicilio_codigo_postal)
                : null,
        ], fn ($part) => $part !== null && $part !== ''));

        return $parts ? implode(', ', $parts) : null;
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
