<?php

namespace App\Http\Controllers;

use App\Helper\Wasabi;
use App\Mail\ChecadorPinMail;
use App\Http\Controllers\Concerns\ManagesCompanyCatalogs;
use App\Models\Centro;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\HorarioEmpresa;
use App\Models\Personal;
use App\Models\Puesto;
use App\Models\User;
use App\Models\UserEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PersonalController extends Controller
{
    use ManagesCompanyCatalogs;

    private const LAST_ADMIN_ERROR = 'No se puede efectuar esta acción debido a que la empresa se quedaría sin Administradores';

    private const DAYS = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miércoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sábado' => 'Sábado',
        'domingo' => 'Domingo',
    ];

    public function index(Request $request)
    {
        $companies = $this->companiesFor($request);
        $companyIds = $companies->modelKeys();
        $selectedCompanyId = $this->selectedCompanyForFilter($request, $companies);

        if ($selectedCompanyId) {
            abort_unless(in_array($selectedCompanyId, $companyIds, true), 404);
        }

        $personal = Personal::query()
            ->with(['empresa', 'centro'])
            ->whereIn('empresa_id', $companyIds)
            ->when($selectedCompanyId, fn ($query) => $query->where('empresa_id', $selectedCompanyId))
            ->orderBy('nombre')
            ->paginate(config('constantes.itemsPorPagina'))
            ->withQueryString();

        return view('personal.index', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'personal',
            'personal' => $personal,
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'canCreate' => $this->companiesFor($request, true)->isNotEmpty(),
            'manageableIds' => $this->companyIdsFor($request, true),
        ]);
    }

    public function create(Request $request)
    {
        $companies = $this->companiesFor($request, true);
        abort_if($companies->isEmpty(), 403);

        $selectedCompanyId = $this->selectedCompanyForForm($request, $companies);

        return view('personal.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'personal',
            'empleado' => new Personal(),
            'companies' => $companies,
            'centros' => Centro::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre')->get(),
            'departamentos' => Departamento::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre')->get(),
            'puestos' => Puesto::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre')->get(),
            'horarios' => HorarioEmpresa::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre_horario')->get(),
            'selectedCompanyId' => $selectedCompanyId,
            'horariosSeleccionados' => collect(),
            'isEdit' => false,
            'days' => self::DAYS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $personalData = Arr::only($data, ['empresa_id', 'centro_id', 'departamento_id', 'puesto_id', 'codigo', 'nombre', 'email', 'sexo', 'pin']);
        $personalData['pin'] = $personalData['pin'] ?: $this->generatePin();
        $personalData['laborados'] = $this->laboradosValue($data['laborados']);
        $newPhotoPath = $this->uploadPhoto($request, (int) $data['empresa_id']);

        if ($request->hasFile('foto') && !$newPhotoPath) {
            return back()->withInput()->withErrors(['foto' => 'No se pudo guardar la foto. Inténtalo de nuevo.']);
        }

        if ($newPhotoPath) {
            $personalData['foto'] = $newPhotoPath;
        }

        try {
            $empleado = DB::transaction(function () use ($personalData, $data) {
                $empleado = Personal::create($personalData);
                $usuario = User::create([
                    'name' => $personalData['nombre'],
                    'email' => $personalData['email'],
                    'profile' => (int) $data['profile'],
                    'password' => Str::random(48),
                    'personal_id' => $empleado->id,
                ]);
                UserEmpresa::create([
                    'user_id' => $usuario->id,
                    'empresa_id' => $personalData['empresa_id'],
                    'control_total' => (int) $data['profile'] === $this->administratorProfileId(),
                ]);
                $this->syncWorkSchedules($empleado, $data['laborados'], $data['horarios'], (int) $data['empresa_id']);

                return $empleado;
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);
            report($exception);

            return back()->withInput()->withErrors(['personal' => 'No se pudo guardar el registro de personal. Inténtalo de nuevo.']);
        }

        $message = 'El personal se creó correctamente.';
        try {
            Mail::to($empleado->email, $empleado->nombre)->queue(new ChecadorPinMail(
                recipientName: (string) $empleado->nombre,
                pin: (string) $empleado->pin,
                companyName: (string) ($empleado->empresa?->razon_social ?? config('app.name', 'Horalia')),
            ));
        } catch (Throwable $exception) {
            report($exception);
        }

        $mailStatus = Password::sendResetLink(['email' => $empleado->email]);
        if ($mailStatus === Password::RESET_LINK_SENT) {
            $message .= ' También enviamos un enlace para crear su contraseña e ingresar.';
        } else {
            report(new \RuntimeException('No fue posible enviar el correo de acceso inicial: ' . $mailStatus));
            $message .= ' No se pudo enviar el correo de acceso; puede reenviarse desde recuperación de contraseña.';
        }

        return redirect()->route('personal.index', ['empresa_id' => $empleado->empresa_id])
            ->with('status', $message);
    }

    public function edit(Request $request, int $personal)
    {
        $empleado = Personal::query()
            ->with(['horarios', 'usuario'])
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($personal);

        $companies = $this->companiesFor($request, true);

        return view('personal.form', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'personal',
            'empleado' => $empleado,
            'companies' => $companies,
            'centros' => Centro::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre')->get(),
            'departamentos' => Departamento::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre')->get(),
            'puestos' => Puesto::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre')->get(),
            'horarios' => HorarioEmpresa::query()->whereIn('empresa_id', $companies->modelKeys())->orderBy('nombre_horario')->get(),
            'selectedCompanyId' => $empleado->empresa_id,
            'horariosSeleccionados' => $empleado->horarios->keyBy('dia'),
            'isEdit' => true,
            'days' => self::DAYS,
        ]);
    }

    public function update(Request $request, int $personal)
    {
        $empleado = Personal::query()
            ->with(['horarios', 'usuario'])
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($personal);
        $data = $this->validatedData($request);
        $this->authorizeManageableCompany($request, (int) $data['empresa_id']);
        $personalData = Arr::only($data, ['empresa_id', 'centro_id', 'departamento_id', 'puesto_id', 'codigo', 'nombre', 'email', 'sexo', 'pin']);
        $personalData['pin'] = $personalData['pin'] ?: $empleado->pin;
        $personalData['codigo'] = $personalData['codigo'] ?? null;
        $personalData['laborados'] = $this->laboradosValue($data['laborados']);
        $oldPhotoPath = $empleado->foto;
        $newPhotoPath = $this->uploadPhoto($request, (int) $data['empresa_id']);

        if ($request->hasFile('foto') && !$newPhotoPath) {
            return back()->withInput()->withErrors(['foto' => 'No se pudo guardar la foto. Inténtalo de nuevo.']);
        }

        if ($newPhotoPath) {
            $personalData['foto'] = $newPhotoPath;
        }

        $createdAccount = false;
        $adminRuleBlocked = false;
        try {
            DB::transaction(function () use ($empleado, $personalData, $data, &$createdAccount, &$adminRuleBlocked) {
                $oldCompanyId = (int) $empleado->empresa_id;
                $newCompanyId = (int) $personalData['empresa_id'];
                Empresa::query()
                    ->whereIn('id', collect([$oldCompanyId, $newCompanyId])->unique())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $usuario = $empleado->usuario()->lockForUpdate()->first();
                $wasCompanyAdmin = $usuario
                    && (int) $usuario->profile === $this->administratorProfileId()
                    && $usuario->empresas()->whereKey($oldCompanyId)->exists();
                $isLeavingAdminRole = $wasCompanyAdmin
                    && ($newCompanyId !== $oldCompanyId || (int) $data['profile'] !== $this->administratorProfileId());

                if ($isLeavingAdminRole && $this->adminCountForCompany($oldCompanyId, $empleado->id) === 0) {
                    $adminRuleBlocked = true;

                    return;
                }

                if ($newCompanyId !== $oldCompanyId
                    && (int) $data['profile'] !== $this->administratorProfileId()
                    && $this->adminCountForCompany($newCompanyId, $empleado->id) === 0) {
                    $adminRuleBlocked = true;

                    return;
                }

                $empleado->update($personalData);
                if ($usuario) {
                    $usuario->update([
                        'name' => $personalData['nombre'],
                        'email' => $personalData['email'],
                        'profile' => (int) $data['profile'],
                    ]);
                } else {
                    $usuario = User::create([
                        'name' => $personalData['nombre'],
                        'email' => $personalData['email'],
                        'profile' => (int) $data['profile'],
                        'password' => Str::random(48),
                        'personal_id' => $empleado->id,
                    ]);
                    $createdAccount = true;
                }
                $usuario->empresas()->sync([
                    $personalData['empresa_id'] => ['control_total' => (int) $data['profile'] === $this->administratorProfileId()],
                ]);
                $empleado->horarios()->delete();
                $this->syncWorkSchedules($empleado, $data['laborados'], $data['horarios'], (int) $data['empresa_id']);
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);
            report($exception);

            return back()->withInput()->withErrors(['personal' => 'No se pudieron guardar los cambios. Inténtalo de nuevo.']);
        }

        if ($adminRuleBlocked) {
            $this->deletePhoto($newPhotoPath);

            return back()->withInput()->withErrors(['personal' => self::LAST_ADMIN_ERROR]);
        }

        if ($newPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            $this->deletePhoto($oldPhotoPath);
        }

        $message = 'El registro de personal se actualizó correctamente.';
        if ($createdAccount) {
            $mailStatus = Password::sendResetLink(['email' => $personalData['email']]);
            $message .= $mailStatus === Password::RESET_LINK_SENT
                ? ' También enviamos un enlace para crear su contraseña e ingresar.'
                : ' No se pudo enviar el correo de acceso; puede reenviarse desde recuperación de contraseña.';
        }

        return redirect()->route('personal.index', ['empresa_id' => $empleado->empresa_id])
            ->with('status', $message);
    }

    public function regeneratePassword(Request $request, int $personal)
    {
        $empleado = Personal::query()
            ->with('usuario')
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($personal);

        if (!$empleado->usuario) {
            return back()->withErrors(['personal' => 'Este registro de personal no tiene un usuario asociado.']);
        }

        $status = Password::sendResetLink(['email' => $empleado->usuario->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            report(new \RuntimeException('No fue posible reenviar el enlace de contraseña: ' . $status));

            return back()->withErrors(['personal' => 'No se pudo enviar el enlace para regenerar la contraseña. Inténtalo de nuevo más tarde.']);
        }

        return back()->with('status', 'Enviamos al correo del usuario un enlace para que genere nuevamente su contraseña.');
    }

    public function resendPin(Request $request, int $personal)
    {
        $empleado = Personal::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->with('empresa')
            ->findOrFail($personal);

        if (!$empleado->pin || !$empleado->email) {
            return back()->withErrors(['personal' => 'El colaborador debe tener un PIN y un correo electrónico para reenviarlo.']);
        }

        try {
            Mail::to($empleado->email, $empleado->nombre)->queue(new ChecadorPinMail(
                recipientName: (string) $empleado->nombre,
                pin: (string) $empleado->pin,
                companyName: (string) ($empleado->empresa?->razon_social ?? config('app.name', 'Horalia')),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['personal' => 'No se pudo encolar el correo con el PIN. Inténtalo de nuevo.']);
        }

        return back()->with('status', 'El correo con el PIN del checador fue enviado a ' . $empleado->email . '.');
    }

    public function destroy(Request $request, int $personal)
    {
        $empleado = Personal::query()
            ->whereIn('empresa_id', $this->companyIdsFor($request, true))
            ->findOrFail($personal);
        $companyId = (int) $empleado->empresa_id;
        $photoPath = $empleado->foto;
        $deleted = DB::transaction(function () use ($empleado, $companyId) {
            Empresa::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();
            $usuario = $empleado->usuario()->lockForUpdate()->first();

            if ($usuario
                && (int) $usuario->profile === $this->administratorProfileId()
                && $usuario->empresas()->whereKey($companyId)->exists()
                && $this->adminCountForCompany($companyId, $empleado->id) === 0) {
                return false;
            }

            $empleado->delete();

            return true;
        });

        if (!$deleted) {
            return back()->withErrors(['personal' => self::LAST_ADMIN_ERROR]);
        }

        $this->deletePhoto($photoPath);

        return redirect()->route('personal.index', ['empresa_id' => $companyId])
            ->with('status', 'El registro de personal se eliminó correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $companyId = (int) $request->input('empresa_id');
        $personalId = $request->route('personal');
        $linkedUserId = $personalId
            ? User::query()->where('personal_id', $personalId)->value('id')
            : null;
        $selectedDays = (array) $request->input('laborados', []);
        $rules = [
            'empresa_id' => ['required', 'integer', Rule::exists('empresas', 'id')],
            'centro_id' => ['required', 'integer', Rule::exists('centros', 'id')->where('empresa_id', $companyId)],
            'departamento_id' => ['nullable', 'integer', Rule::exists('departamentos', 'id')->where('empresa_id', $companyId)],
            'puesto_id' => ['nullable', 'integer', Rule::exists('puestos', 'id')->where('empresa_id', $companyId)],
            'codigo' => ['nullable', 'string', 'max:100'],
            'pin' => ['nullable', 'string', 'regex:/^\\d{5}$/', Rule::unique('personal', 'pin')->ignore($personalId)],
            'nombre' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('personal', 'email')->ignore($personalId),
                Rule::unique('users', 'email')->ignore($linkedUserId),
            ],
            'profile' => ['required', 'integer', Rule::in(collect(config('constantes.perfilesPersonal', []))->pluck('id')->all())],
            'sexo' => ['nullable', Rule::in(['femenino', 'masculino', 'otro'])],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'laborados' => ['required', 'array', 'min:1'],
            'laborados.*' => ['required', Rule::in(array_keys(self::DAYS))],
            'horarios' => ['required', 'array'],
        ];
        $messages = [
            'empresa_id.required' => 'Selecciona una empresa.',
            'centro_id.required' => 'Selecciona un centro de trabajo.',
            'centro_id.exists' => 'El centro de trabajo debe pertenecer a la empresa seleccionada.',
            'departamento_id.exists' => 'El departamento debe pertenecer a la empresa seleccionada.',
            'puesto_id.exists' => 'El puesto debe pertenecer a la empresa seleccionada.',
            'nombre.required' => 'Escribe el nombre completo.',
            'pin.regex' => 'El PIN debe contener exactamente cinco dígitos.',
            'pin.unique' => 'Ese PIN ya está asignado a otra persona.',
            'email.required' => 'Escribe el correo electrónico del colaborador.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado en Personal o en Usuarios.',
            'profile.required' => 'Selecciona un perfil.',
            'profile.in' => 'Selecciona un perfil válido.',
            'foto.image' => 'Selecciona una imagen válida.',
            'foto.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'foto.max' => 'La foto no puede pesar más de 5 MB.',
            'laborados.required' => 'Selecciona al menos un día laboral.',
            'laborados.min' => 'Selecciona al menos un día laboral.',
        ];

        foreach (self::DAYS as $day => $label) {
            $rules['horarios.' . $day] = [
                Rule::requiredIf(in_array($day, $selectedDays, true)),
                'nullable',
                Rule::exists('horarios_empresas', 'id')->where('empresa_id', $companyId),
            ];
            $messages['horarios.' . $day . '.required'] = "Selecciona un horario para {$label}.";
            $messages['horarios.' . $day . '.exists'] = "El horario seleccionado para {$label} no corresponde a la empresa.";
        }

        return $request->validate($rules, $messages);
    }

    private function laboradosValue(array $selectedDays): string
    {
        $dayNumbers = [];
        foreach (array_keys(self::DAYS) as $index => $day) {
            if (in_array($day, $selectedDays, true)) {
                $dayNumbers[] = $index + 1;
            }
        }

        return implode('@', $dayNumbers);
    }

    private function generatePin(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (Personal::query()->where('pin', $pin)->exists());

        return $pin;
    }

    private function syncWorkSchedules(Personal $empleado, array $selectedDays, array $selectedSchedules, int $companyId): void
    {
        foreach (array_keys(self::DAYS) as $index => $day) {
            if (!in_array($day, $selectedDays, true)) {
                continue;
            }

            $horarioEmpresa = HorarioEmpresa::query()
                ->where('empresa_id', $companyId)
                ->findOrFail($selectedSchedules[$day]);

            Horario::create([
                'personal_id' => $empleado->id,
                'dia' => $index + 1,
                'entrada' => $horarioEmpresa->hora_entrada,
                'salida' => $horarioEmpresa->hora_salida,
                'horario_id' => $horarioEmpresa->id,
            ]);
        }
    }

    private function adminCountForCompany(int $companyId, ?int $exceptPersonalId = null): int
    {
        return User::query()
            ->where('profile', $this->administratorProfileId())
            ->whereHas('empresas', fn ($query) => $query->where('empresas.id', $companyId))
            ->whereHas('personal', fn ($query) => $query->where('empresa_id', $companyId))
            ->when($exceptPersonalId, fn ($query) => $query->where('personal_id', '!=', $exceptPersonalId))
            ->count();
    }

    private function administratorProfileId(): int
    {
        return (int) config('constantes.perfilesPersonal.administrador.id', 2);
    }

    private function uploadPhoto(Request $request, int $companyId): ?string
    {
        if (!$request->hasFile('foto')) {
            return null;
        }

        try {
            return Wasabi::upload('personal/perfiles/empresa-' . $companyId, $request->file('foto'));
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function deletePhoto(?string $path): void
    {
        if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        try {
            if (str_starts_with($path, 'storage/')) {
                Storage::disk('public')->delete(substr($path, strlen('storage/')));
            } else {
                Storage::disk('wasabi')->delete($path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
