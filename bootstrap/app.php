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
    ->withMiddleware(function (Middleware $middleware) {
        // Add FIRST in middleware stack
        $middleware->prepend([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Railway proxy support
        $middleware->trustProxies(at: [
            '*.railway.app',
            '*.up.railway.app'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
