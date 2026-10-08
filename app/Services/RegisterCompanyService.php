<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use App\Models\UserEmpresa;
use App\Mail\ChecadorPinMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterCompanyService
{
    public function register(array $data): array
    {
        [$empresa, $usuario, $personal] = DB::transaction(function () use ($data) {
            $empresa = Empresa::create([
                'razon_social' => $data['razon_social'],
                'rfc' => $data['rfc'] ?: $this->generateUniquePlaceholderRfc(),
                'activa' => true,
            ]);

            $usuario = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'profile' => 2,
                'password' => $data['password'],
            ]);

            $personal = Personal::create([
                'nombre' => $data['name'],
                'email' => $data['email'],
                'activo' => true,
                'empresa_id' => $empresa->id,
                'pin' => $this->generateUniquePin(),
            ]);

            $usuario->personal()->associate($personal);
            $usuario->save();

            UserEmpresa::create([
                'user_id' => $usuario->id,
                'empresa_id' => $empresa->id,
                'control_total' => true,
            ]);

            return [$empresa, $usuario, $personal];
        });

        try {
            Mail::to($personal->email, $personal->nombre)->queue(new ChecadorPinMail(
                recipientName: (string) $personal->nombre,
                pin: (string) $personal->pin,
                companyName: (string) $empresa->razon_social,
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return [$empresa, $usuario];
    }

    private function generateUniquePin(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (Personal::query()->where('pin', $pin)->exists());

        return $pin;
    }

    private function generateUniquePlaceholderRfc(): string
    {
        do {
            $rfc = 'FAL' . now()->format('ymd') . Str::upper(Str::random(3));
        } while (Empresa::where('rfc', $rfc)->exists());

        return $rfc;
    }
}
