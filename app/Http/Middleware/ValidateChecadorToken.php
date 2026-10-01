<?php

namespace App\Http\Middleware;

use App\Models\ChecadorSesion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateChecadorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-Checador-Token');
        abort_unless(strlen($token) >= 40, 401);

        $session = ChecadorSesion::query()
            ->whereNotNull('activated_at')
            ->where('expires_at', '>', now())
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_unless($session, 401);
        $request->attributes->set('checadorSesion', $session);

        return $next($request);
    }
}
