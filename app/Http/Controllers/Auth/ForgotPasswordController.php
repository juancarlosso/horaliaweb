<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Enviamos un enlace a la cuenta de correo para restablecer la contraseña.');
        }

        return back()->withErrors([
            'email' => $status === Password::INVALID_USER
                ? 'No encontramos una cuenta con ese correo electrónico.'
                : 'No fue posible enviar el enlace. Inténtalo de nuevo más tarde.',
        ]);
    }
}
