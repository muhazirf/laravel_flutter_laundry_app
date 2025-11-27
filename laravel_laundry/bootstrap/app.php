<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        then: function () {
            Route::middleware(['api'])->group(base_path('routes/ai.php'));
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => App\Http\Middleware\ApiKeyMiddleware::class,
            'mcp.cors' => App\Http\Middleware\McpCors::class,
        ]);

        // Apply CORS globally to all web routes
        $middleware->web(\App\Http\Middleware\McpCors::class);

        // Or apply to API routes
        $middleware->api(\App\Http\Middleware\McpCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
