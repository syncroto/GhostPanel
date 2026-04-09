<?php

use App\Http\Middleware\CheckSetupComplete;
use App\Http\Middleware\RequireAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware global de setup — verifica se o painel foi configurado
        $middleware->web(append: [
            CheckSetupComplete::class,
        ]);

        // Alias para uso em rotas
        $middleware->alias([
            'gpanel.setup'  => CheckSetupComplete::class,
            'admin'         => RequireAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
