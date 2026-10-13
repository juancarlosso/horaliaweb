<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveLinkedPersonal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $personal = $user?->personal;

        if ($personal && !$personal->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Esta cuenta no tiene acceso al sistema. Contacta al administrador de tu empresa.',
            ]);
        }

        return $next($request);
    }
}
