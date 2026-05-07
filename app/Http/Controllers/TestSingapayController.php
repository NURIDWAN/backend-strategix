<?php

namespace App\Http\Controllers;

use App\Services\Singapay\SingapayApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
     * Create test payment link with NO minimum amount restriction
     * POST /api/test/singapay/payment
     */
    public function createTestPayment(Request $request): JsonResponse
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:virtual_account,qris',
            'bank_code'      => 'nullable|string',
            'description'    => 'nullable|string|max:255',
        ]);

        try {
            $amount      = (float) $request->amount;
            $method      = $request->payment_method;
            $bankCode    = $request->bank_code;
            $description = $request->description ?? 'Test Payment';
            $reffNo      = 'TEST-' . strtoupper(Str::random(8)) . '-' . time();

            $whitelist = match ($method) {
                'virtual_account' => $bankCode
                    ? [strtoupper($bankCode)]
                    : array_map('strtoupper', config('singapay.virtual_account.banks', ['BRI'])),
                'qris' => ['QRIS'],
                default => []
            };

            $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            $returnUrl   = $frontendUrl . '/payment/success?transaction_code=' . $reffNo;

            $payload = [
                'reff_no'         => $reffNo,
                'title'           => $description,
                'total_amount'    => $amount,
                'payment_channel' => $whitelist,
                'expired_at'      => now()->addMinutes(60)->timestamp * 1000,
                'return_url'      => $returnUrl,
                'max_usage'       => 1,
            ];

            $response = $this->singapayService->createPaymentLink($payload);

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to create payment link',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test payment link created',
                'data'    => [
                    'reff_no'      => $reffNo,
                    'amount'       => $amount,
                    'method'       => $method,
                    'payment_url'  => $response['data']['payment_url'] ?? null,
                    'mode'         => $this->singapayService->getMode(),
                    'raw_response' => $response['data'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ], 500);
        }
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
