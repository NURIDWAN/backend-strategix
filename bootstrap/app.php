<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\CheckFeatureEnabled;
use App\Http\Middleware\ConsultantMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register CORS middleware globally untuk semua request
        $middleware->append(CorsMiddleware::class);

        // Middleware API
        $middleware->api(prepend: [
            CorsMiddleware::class,
        ]);

        // Middleware Web
        $middleware->web(prepend: [
            CorsMiddleware::class,
        ]);

        // Alias middleware
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'cors' => CorsMiddleware::class,
            'admin' => AdminMiddleware::class,
            'consultant' => ConsultantMiddleware::class,
            'account.active' => EnsureAccountActive::class,
            'feature' => CheckFeatureEnabled::class,
        ]);

        $middleware->redirectGuestsTo(fn () => '/');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return null;
        });
    })
    ->create();
