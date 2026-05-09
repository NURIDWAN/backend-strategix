<?php

namespace App\Http\Controllers;

use App\Services\FaspayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Test Controller untuk Faspay Integration
 * HANYA untuk debugging - disable di production dengan APP_DEBUG=false
 */
class TestFaspayController extends Controller
{
    protected FaspayService $faspayService;

    public function __construct(FaspayService $faspayService)
    {
        if (!config('app.debug')) {
            abort(404);
        }

        $this->faspayService = $faspayService;
    }

    /**
     * Check Faspay configuration
     * GET /api/test/faspay/config
     */
    public function testConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'environment'       => config('faspay.environment'),
                'invoice_prefix'    => config('faspay.invoice_prefix', 'GRPD'),
                'invoice_expiration'=> config('faspay.invoice_expiration', 30),
                'merchant_id_set'   => !empty(config('faspay.merchant_id')),
                'user_id_set'       => !empty(config('faspay.user_id')),
                'password_set'      => !empty(config('faspay.password')),
                'is_configured'     => $this->faspayService->isConfigured(),
                'supported_channels'=> $this->faspayService->getSupportedChannels(),
            ],
        ]);
    }

    /**
     * Create a test Faspay invoice — NO minimum amount restriction
     * POST /api/test/faspay/payment
     */
    public function createTestPayment(Request $request): JsonResponse
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'customer_name'  => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|max:20',
            'description'    => 'nullable|string|max:255',
        ]);

        if (!$this->faspayService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Faspay tidak terkonfigurasi. Pastikan FASPAY_MERCHANT_ID, FASPAY_USER_ID, dan FASPAY_PASSWORD sudah diisi di .env',
            ], 422);
        }

        try {
            $billNo      = 'TEST-' . strtoupper(Str::random(8)) . '-' . time();
            $amount      = (float) $request->amount;
            $description = $request->description ?? 'Test Payment Grapadi';

            $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

            $invoiceData = [
                'bill_no'          => $billNo,
                'bill_total'       => $amount,
                'bill_desc'        => $description,
                'cust_name'        => $request->customer_name,
                'cust_email'       => $request->customer_email,
                'cust_phone'       => $request->customer_phone,
                'return_url'       => $frontendUrl . '/payment/success',
                'bill_expired_date'=> now()->addMinutes(config('faspay.invoice_expiration', 30))->toDateTimeString(),
            ];

            $response = $this->faspayService->createInvoice($invoiceData);

            if (!$response['success']) {
                return response()->json([
                    'success'    => false,
                    'message'    => $response['message'] ?? 'Gagal membuat invoice Faspay',
                    'raw'        => $response['data'] ?? [],
                    'debug_sent' => [
                        'return_url' => $invoiceData['return_url'],
                        'frontend_url' => $frontendUrl,
                        'bill_no' => $billNo,
                    ],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice Faspay berhasil dibuat',
                'data'    => [
                    'bill_no'     => $billNo,
                    'amount'      => $amount,
                    'trx_id'      => $response['trx_id'],
                    'payment_url' => $response['payment_url'],
                    'environment' => $this->faspayService->getEnvironment(),
                    'raw_response'=> $response['data'],
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
     * Check payment status by bill_no
     * GET /api/test/faspay/status/{billNo}
     */
    public function checkStatus(string $billNo): JsonResponse
    {
        try {
            $result = $this->faspayService->getPaymentStatus($billNo);

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
