<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        //
    })->create();
