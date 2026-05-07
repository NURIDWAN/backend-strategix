<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    /**
     * Ensure the authenticated user's account is active.
     * This catches users who were banned/deactivated while already having a valid token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->account_status === 'banned') {
            // Revoke current token so banned users can't keep making requests
            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah diblokir. Silakan hubungi admin untuk informasi lebih lanjut.',
                'data' => ['account_status' => 'banned']
            ], 403);
        }

        if ($user->account_status === 'inactive') {
            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi admin untuk mengaktifkan kembali.',
                'data' => ['account_status' => 'inactive']
            ], 403);
        }

        return $next($request);
    }
}
