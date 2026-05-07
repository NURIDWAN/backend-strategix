<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isConsultant()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya konsultan yang dapat mengakses endpoint ini.',
            ], 403);
        }

        return $next($request);
    }
}
