<?php

namespace App\Http\Controllers\Singapay;

use App\Http\Controllers\Controller;
use App\Services\Singapay\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle payment webhook from SingaPay
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handlePayment(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            Log::info('[SingaPay Webhook] Received payment webhook', [
                'payload' => $payload,
                'headers' => [
                    'X-Signature' => $request->header('X-Signature'),
                    'X-Partner-ID' => $request->header('X-Partner-ID'),
                    'Content-Type' => $request->header('Content-Type'),
                ],
                'ip' => $request->ip(),
            ]);

            // Process webhook - PERBAIKAN: Pass Request object untuk signature validation
            $result = $this->webhookService->processPaymentWebhook($payload, $request);

            $statusCode = $result['success'] ? 200 : 400;

            Log::info('[SingaPay Webhook] Response sent', [
                'success' => $result['success'],
                'message' => $result['message'],
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $statusCode);
        } catch (\Exception $e) {
            Log::error('[SingaPay Webhook] Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle Virtual Account webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleVirtualAccount(Request $request): JsonResponse
    {
        Log::info('[SingaPay Webhook] Virtual Account webhook received');
        return $this->handlePayment($request);
    }

    /**
     * Handle QRIS webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleQris(Request $request): JsonResponse
    {
        Log::info('[SingaPay Webhook] QRIS webhook received');
        return $this->handlePayment($request);
    }

    /**
     * Handle Disbursement webhook from SingaPay
     * Called when withdrawal/payout is completed (success/failed)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleDisbursement(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            Log::info('[SingaPay Webhook] Disbursement webhook received', [
                'payload' => $payload,
                'headers' => [
                    'X-Signature' => $request->header('X-Signature'),
                    'X-Timestamp' => $request->header('X-Timestamp'),
                ],
            ]);

            // Process disbursement webhook
            $result = $this->webhookService->processDisbursementWebhook($payload);

            $statusCode = $result['success'] ? 200 : 400;

            Log::info('[SingaPay Webhook] Disbursement response sent', [
                'success' => $result['success'],
                'message' => $result['message'],
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $statusCode);
        } catch (\Exception $e) {
            Log::error('[SingaPay Webhook] Disbursement exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Test webhook (mock mode only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function test(Request $request): JsonResponse
    {
        if (config('singapay.mode') !== 'mock') {
            return response()->json([
                'success' => false,
                'message' => 'Test webhook only available in mock mode',
            ], 403);
        }

        $validated = $request->validate([
            'transaction_code' => 'required|string',
        ]);

        $transaction = \App\Models\Singapay\PaymentTransaction::where('transaction_code', $validated['transaction_code'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        Log::info('[SingaPay Webhook] Test webhook triggered', [
            'transaction_code' => $validated['transaction_code'],
            'transaction_id' => $transaction->id,
        ]);

        $result = $this->webhookService->processMockWebhook($transaction);

        return response()->json($result);
    }
}
