<?php

namespace App\Http\Controllers;

use App\Helper\Wasabi;
use App\Models\Empresa;
use App\Models\UserEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = (int) $user->profile === 1;
        $query = Empresa::query();
        if (!$isAdmin) {
            $query->whereHas('usuarios', fn ($users) => $users->where('users.id', $user->id));
        }
        $empresas = $query->orderBy('razon_social')->paginate(config('constantes.itemsPorPagina'));
        $manageableIds = $isAdmin
            ? []
            : $user->empresas()->wherePivot('control_total', true)->pluck('empresas.id')->map(fn ($id) => (int) $id)->all();

        return view('empresas.index', [
            'empresas' => $empresas,
            'isAdmin' => $isAdmin,
            'manageableIds' => $manageableIds,
            'canCreate' => $isAdmin || $user->empresas()->wherePivot('control_total', true)->exists(),
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'empresas',
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeCompanyCreation($request);

        return view('empresas.form', [
            'empresa' => new Empresa([
                'minutos_tolerancia_entrada' => 10,
                'metros_distancia_entrada' => 20,
            ]),
            'isEdit' => false,
            'entidadesFederativas' => config('constantes.entidades_federativas'),
            'regimenesFiscales' => config('constantes.regimenes_fiscales'),
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'empresas',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyCreation($request);
        $data = $this->validatedData($request);
        try {
            $logoPath = $request->hasFile('logo')
                ? Wasabi::upload('empresas/logos', $request->file('logo'))
                : null;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['logo' => 'No se pudo guardar el logotipo. Inténtalo de nuevo.']);
        }
        if ($request->hasFile('logo') && !$logoPath) {
            return back()->withInput()->withErrors(['logo' => 'No se pudo guardar el logotipo. Inténtalo de nuevo.']);
        }

        try {
            DB::transaction(function () use ($request, $data, $logoPath) {
                $empresa = Empresa::create($data + ['logo' => $logoPath]);

                if ((int) $request->user()->profile !== 1) {
                    UserEmpresa::create([
                        'user_id' => $request->user()->id,
                        'empresa_id' => $empresa->id,
                        'control_total' => true,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteLogo($logoPath);
            throw $exception;
        }

        return redirect()->route('empresas.index')->with('status', 'La empresa se creó correctamente.');
    }

    public function edit(Request $request, int $empresa)
    {
        $record = $this->manageableCompany($request, $empresa);

        return view('empresas.form', [
            'empresa' => $record,
            'isEdit' => true,
            'entidadesFederativas' => config('constantes.entidades_federativas'),
            'regimenesFiscales' => config('constantes.regimenes_fiscales'),
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'empresas',
        ]);
    }

    public function update(Request $request, int $empresa)
    {
        $record = $this->manageableCompany($request, $empresa);
        $data = $this->validatedData($request, $record);
        $oldLogoPath = $record->logo;
        try {
            $newLogoPath = $request->hasFile('logo')
                ? Wasabi::upload('empresas/logos', $request->file('logo'))
                : null;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['logo' => 'No se pudo guardar el logotipo. Inténtalo de nuevo.']);
        }
        if ($request->hasFile('logo') && !$newLogoPath) {
            return back()->withInput()->withErrors(['logo' => 'No se pudo guardar el logotipo. Inténtalo de nuevo.']);
        }

        try {
            DB::transaction(function () use ($record, $data, $newLogoPath) {
                $record->fill($data);
                if ($newLogoPath) {
                    $record->logo = $newLogoPath;
                }
                $record->save();
            });
        } catch (Throwable $exception) {
            $this->deleteLogo($newLogoPath);
            throw $exception;
        }

        if ($newLogoPath && $oldLogoPath !== $newLogoPath) {
            $this->deleteLogo($oldLogoPath);
        }

        return redirect()->route('empresas.index')->with('status', 'La empresa se actualizó correctamente.');
    }

    public function destroy(Request $request, int $empresa)
    {
        $record = $this->manageableCompany($request, $empresa);
        $logoPath = $record->logo;

        DB::transaction(fn () => $record->delete());
        $this->deleteLogo($logoPath);

        return redirect()->route('empresas.index')->with('status', 'La empresa se eliminó correctamente.');
    }

    private function validatedData(Request $request, ?Empresa $empresa = null): array
    {
        $uniqueRfc = Rule::unique('empresas', 'rfc');
        if ($empresa) {
            $uniqueRfc->ignore($empresa->id);
        }

        $regimenes = config('constantes.regimenes_fiscales');

        $data = $request->validate([
            'rfc' => [
                'required', 'string', 'min:12', 'max:13',
                $uniqueRfc,
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'domicilio_calle' => ['nullable', 'string', 'max:255'],
            'domicilio_numero_exterior' => ['nullable', 'string', 'max:30'],
            'domicilio_numero_interior' => ['nullable', 'string', 'max:30'],
            'domicilio_colonia' => ['nullable', 'string', 'max:150'],
            'domicilio_codigo_postal' => ['nullable', 'regex:/^\d{5}$/'],
            'domicilio_municipio' => ['nullable', 'string', 'max:150'],
            'domicilio_ciudad' => ['nullable', 'string', 'max:150'],
            'domicilio_estado' => ['nullable', Rule::in(config('constantes.entidades_federativas'))],
            'regimen_fiscal' => ['nullable', Rule::in(array_keys($regimenes))],
            'correo_facturacion' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:100'],
            'minutos_tolerancia_entrada' => ['required', 'integer', 'min:1'],
            'metros_distancia_entrada' => ['required', 'integer', 'min:1'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'rfc.required' => 'Escribe el RFC de la empresa.',
            'rfc.min' => 'El RFC debe tener entre 12 y 13 caracteres.',
            'rfc.max' => 'El RFC debe tener entre 12 y 13 caracteres.',
            'razon_social.required' => 'Escribe la razón social.',
            'rfc.unique' => 'Ya existe una empresa con ese RFC.',
            'razon_social.max' => 'La razón social no puede exceder 255 caracteres.',
            'domicilio_calle.max' => 'La calle no puede exceder 255 caracteres.',
            'domicilio_numero_exterior.max' => 'El número exterior no puede exceder 30 caracteres.',
            'domicilio_numero_interior.max' => 'El número interior no puede exceder 30 caracteres.',
            'domicilio_colonia.max' => 'La colonia no puede exceder 150 caracteres.',
            'domicilio_codigo_postal.regex' => 'El código postal fiscal debe contener exactamente 5 dígitos.',
            'domicilio_municipio.max' => 'La alcaldía o municipio no puede exceder 150 caracteres.',
            'domicilio_ciudad.max' => 'La ciudad no puede exceder 150 caracteres.',
            'domicilio_estado.in' => 'Selecciona una entidad federativa válida.',
            'regimen_fiscal.in' => 'Selecciona una clave del catálogo de regímenes fiscales.',
            'correo_facturacion.email' => 'Escribe un correo de facturación válido.',
            'correo_facturacion.max' => 'El correo de facturación no puede exceder 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 100 caracteres.',
            'minutos_tolerancia_entrada.integer' => 'Los minutos de tolerancia deben ser un número entero.',
            'minutos_tolerancia_entrada.required' => 'Indica los minutos de tolerancia.',
            'minutos_tolerancia_entrada.min' => 'Los minutos de tolerancia deben ser al menos 1.',
            'metros_distancia_entrada.integer' => 'Los metros de tolerancia deben ser un número entero.',
            'metros_distancia_entrada.required' => 'Indica los metros de tolerancia.',
            'metros_distancia_entrada.min' => 'Los metros de tolerancia deben ser al menos 1.',
            'logo.image' => 'Selecciona un archivo de imagen válido para el logotipo.',
            'logo.mimes' => 'El logotipo debe estar en formato JPG, PNG o WebP.',
            'logo.max' => 'El logotipo no puede pesar más de 5 MB.',
        ]);

        $data['rfc'] = mb_strtoupper(trim($data['rfc']));
        $data['razon_social'] = trim($data['razon_social']);
        foreach (['domicilio_calle', 'domicilio_numero_exterior', 'domicilio_numero_interior', 'domicilio_colonia', 'domicilio_codigo_postal', 'domicilio_municipio', 'domicilio_ciudad', 'domicilio_estado', 'regimen_fiscal', 'correo_facturacion'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]) === '' ? null : trim($data[$field]);
            }
        }
        unset($data['logo']);

        return $data;
    }

    private function authorizeCompanyCreation(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            (int) $user->profile === 1 || $user->empresas()->wherePivot('control_total', true)->exists(),
            403
        );
    }

    private function manageableCompany(Request $request, int $id): Empresa
    {
        $user = $request->user();

        if ((int) $user->profile === 1) {
            return Empresa::findOrFail($id);
        }

        return $user->empresas()
            ->wherePivot('control_total', true)
            ->where('empresas.id', $id)
            ->firstOrFail();
    }

    private function deleteLogo(?string $path): void
    {
        if ($path && str_starts_with($path, 'empresas/logos/')) {
            Storage::disk('public')->delete($path);
        } elseif ($path) {
            Storage::disk('wasabi')->delete($path);
        }
    }
}
