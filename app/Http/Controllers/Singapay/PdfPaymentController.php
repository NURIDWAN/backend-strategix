<?php

namespace App\Http\Controllers\Singapay;

use App\Http\Controllers\Controller;
use App\Services\Singapay\PdfPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PdfPaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PdfPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get available packages
     *
     * @return JsonResponse
     */
    public function packages(): JsonResponse
    {
        $result = $this->paymentService->getAvailablePackages();

        return response()->json($result);
    }

    /**
     * Check user Pro access status
     *
     * @return JsonResponse
     */
    public function checkAccess(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $result = $this->paymentService->checkAccess($user);

        return response()->json($result);
    }

    /**
     * Get user subscription
     *
     * @return JsonResponse
     */
    public function subscription(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $result = $this->paymentService->getUserSubscription($user);

        return response()->json($result);
    }

    /**
     * Create new purchase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function purchase(Request $request): JsonResponse
    {
        if (!config('singapay.payment_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur pembayaran sedang tidak tersedia. Silakan coba lagi nanti.',
            ], 503);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'package_id' => 'required|integer|exists:premium_pdfs,id',
            'payment_method' => 'required|in:virtual_account,qris',
            'bank_code' => 'required_if:payment_method,virtual_account|string|in:BRI,BNI,MANDIRI,PERMATA,DANAMON,CIMB,BSI',
        ]);

        $result = $this->paymentService->createPurchase(
            $user,
            $validated['package_id'],
            $validated['payment_method'],
            $validated['bank_code'] ?? null
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Check payment status
     *
     * @param string $transactionCode
     * @return JsonResponse
     */
    public function checkStatus(string $transactionCode): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $result = $this->paymentService->checkPaymentStatus($transactionCode);

        return response()->json($result);
    }

    /**
     * Cancel purchase
     *
     * @param string $transactionCode
     * @return JsonResponse
     */
    public function cancel(string $transactionCode): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $result = $this->paymentService->cancelPurchase($user, $transactionCode);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Get transaction history
     *
     * @return JsonResponse
     */
    public function history(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $result = $this->paymentService->getTransactionHistory($user);

        return response()->json($result);
    }
}
