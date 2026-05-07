<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowOrigin = $this->resolveAllowOrigin($origin);

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json([], 200)
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers to all responses
        return $response
            ->header('Access-Control-Allow-Origin', $allowOrigin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    private function resolveAllowOrigin(?string $origin): string
    {
        $allowedOrigins = array_values(array_filter([
            env('FRONTEND_URL', 'http://localhost:5173'),
            env('APP_FRONTEND_URL'),
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://localhost:3000',
        ]));

        if (!$origin) {
            return $allowedOrigins[0] ?? 'http://localhost:5173';
        }

        if (in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1):\d+$/', $origin) === 1) {
            return $origin;
        }

        return $allowedOrigins[0] ?? 'http://localhost:5173';
    }
}
