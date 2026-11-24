<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // El checador autentica cada POST con su token de sesión firmado/hash;
        // no depende de la sesión CSRF del navegador, que puede quedar obsoleta
        // mientras la pantalla permanece abierta en una tablet.
        $middleware->validateCsrfTokens(except: [
            'checador/validar-pin',
            'checador/marcar',
            'checador/pausas/validar-pin',
            'checador/pausas/iniciar',
            'checador/pausas/finalizar',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $exception, Request $request): void {
            if (! $request->routeIs('checador.activate')) {
                return;
            }

            $expires = filter_var($request->query('expires'), FILTER_VALIDATE_INT);

            Log::warning('checador.activation.invalid_signature', [
                'session_id' => (int) $request->route('session'),
                'scheme' => $request->getScheme(),
                'host' => $request->getHttpHost(),
                'expires_at_unix' => $expires === false ? null : $expires,
                'checked_at_unix' => now()->timestamp,
                'expired' => ! URL::signatureHasNotExpired($request),
                'signature_matches' => URL::hasCorrectSignature($request),
                'has_signature' => $request->query->has('signature'),
                'unexpected_query_parameter_count' => count(array_diff(
                    array_keys($request->query()),
                    ['expires', 'signature'],
                )),
            ]);
        });
    })->create();
