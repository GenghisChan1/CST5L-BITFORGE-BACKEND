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
        // Add global middleware
        $middleware->append([
            \Illuminate\Http\Middleware\HandleCors::class, // Laravel's built-in CORS
        ]);
        
        // Or if using custom CORS middleware:
        // $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
        
        // API middleware group (applied to routes in routes/api.php)
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
