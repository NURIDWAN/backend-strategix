<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckFeatureEnabled
{
    /**
     * Check if a feature is enabled via system settings.
     *
     * Usage in routes:
     *   ->middleware('feature:feature_forecast')
     *   ->middleware('feature:feature_pdf_export')
     *   ->middleware('feature:feature_articles')
     *
     * The setting key must exist in the settings table with type 'boolean'.
     * If the setting doesn't exist, the feature is considered enabled (fail-open).
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $enabled = Setting::getValue($featureKey, true);

        if (!$enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini sedang dinonaktifkan oleh administrator.',
                'data' => ['feature' => $featureKey],
            ], 403);
        }

        return $next($request);
    }
}
