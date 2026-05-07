<?php

namespace App\Http\Controllers;

use App\Services\Singapay\SingapayApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Test Controller untuk Singapay Integration
 * HANYA untuk debugging - disable di production dengan APP_DEBUG=false
 */
class TestSingapayController extends Controller
{
    protected $singapayService;

    public function __construct(SingapayApiService $singapayService)
    {
        // Only allow in debug mode
        if (!config('app.debug')) {
            abort(404);
        }

        $this->singapayService = $singapayService;
    }

    /**
     * Test access token generation
     * GET /api/test/singapay/token
     */
    public function testAccessToken(): JsonResponse
    {
        try {
            $token = $this->singapayService->getAccessToken();

            if ($token) {
                return response()->json([
                    'success' => true,
                    'message' => 'Access token generated successfully',
                    'data' => [
                        'token_length' => strlen($token),
                        'token_preview' => substr($token, 0, 20) . '...' . substr($token, -10),
                        'mode' => $this->singapayService->getMode(),
                        'merchant_account_id' => $this->singapayService->getMerchantAccountId(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate access token',
                'data' => [
                    'mode' => $this->singapayService->getMode(),
                ],
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'error' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    /**
     * Test Singapay configuration
     * GET /api/test/singapay/config
     */
    public function testConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'mode' => config('singapay.mode'),
                'sandbox_url' => config('singapay.sandbox_url'),
                'production_url' => config('singapay.production_url'),
                'partner_id_set' => !empty(config('singapay.partner_id')),
                'client_id_set' => !empty(config('singapay.client_id')),
                'client_secret_set' => !empty(config('singapay.client_secret')),
                'merchant_account_id_set' => !empty(config('singapay.merchant_account_id')),
                'cache_enabled' => config('singapay.cache.enabled'),
                'logging_enabled' => config('singapay.logging.enabled'),
                'log_level' => config('singapay.logging.level'),
            ],
        ]);
    }

    /**
     * Clear token cache
     * POST /api/test/singapay/clear-cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->singapayService->clearTokenCache();

            return response()->json([
                'success' => true,
                'message' => 'Token cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }
}
