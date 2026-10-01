<?php

namespace App\Http\Controllers;

use App\Helper\Wasabi;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load('personal');

        return view('profile.edit', [
            'user' => $user,
            'avatarUrl' => $user->avatarUrl(),
            'navigation' => DashboardController::navigation(),
            'activeSection' => null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Escribe tu nombre.',
            'foto.image' => 'Selecciona una imagen válida.',
            'foto.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'foto.max' => 'La foto no puede pesar más de 5 MB.',
        ]);

        $user = $request->user();
        $personal = $user->personal ?? new Personal([
            'email' => $user->email,
            'activo' => true,
            'empresa_id' => $user->empresas()->first()?->id,
        ]);
        $oldPhotoPath = $personal->foto;
        $newPhotoPath = null;

        if ($request->hasFile('foto')) {
            try {
                $newPhotoPath = Wasabi::upload('usuarios/perfiles/' . $user->id, $request->file('foto'));
            } catch (Throwable $exception) {
                report($exception);
            }

            if (!$newPhotoPath) {
                return back()->withInput($request->only('name'))
                    ->withErrors(['foto' => 'No se pudo guardar la foto. Inténtalo de nuevo.']);
            }
        }

        try {
            DB::transaction(function () use ($user, $personal, $data, $newPhotoPath) {
                $user->name = $data['name'];
                $user->save();

                $personal->nombre = $data['name'];
                if ($newPhotoPath) {
                    $personal->foto = $newPhotoPath;
                }
                $personal->save();

                if ($user->personal_id !== $personal->id) {
                    $user->personal()->associate($personal);
                    $user->save();
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            if ($newPhotoPath) {
                try {
                    Storage::disk('wasabi')->delete($newPhotoPath);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            return back()->withInput($request->only('name'))
                ->withErrors(['profile' => 'No se pudieron guardar los cambios. Inténtalo de nuevo.']);
        }

        if ($newPhotoPath && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            try {
                Storage::disk('wasabi')->delete($oldPhotoPath);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('profile.edit')->with('status', 'Tu perfil se actualizó correctamente.');
    }

    public function editPassword()
    {
        return view('profile.password', [
            'navigation' => DashboardController::navigation(),
            'activeSection' => null,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'actual' => ['required', 'string'],
            'nuevo' => ['required', 'string', 'min:8', 'max:72', 'different:actual'],
            'confirmar' => ['required', 'string', 'same:nuevo'],
        ], [
            'actual.required' => 'Escribe tu contraseña actual.',
            'nuevo.required' => 'Escribe la nueva contraseña.',
            'nuevo.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'nuevo.max' => 'La nueva contraseña no puede exceder 72 caracteres.',
            'nuevo.different' => 'La nueva contraseña debe ser diferente de la actual.',
            'confirmar.required' => 'Confirma la nueva contraseña.',
            'confirmar.same' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        $user = $request->user();

        if (!Hash::check($data['actual'], $user->password)) {
            return back()->withErrors(['actual' => 'La contraseña actual no es correcta.']);
        }

        $user->password = $data['nuevo'];
        $user->save();

        return redirect()->route('profile.password.edit')->with('status', 'Tu contraseña se actualizó correctamente.');
    }
}
