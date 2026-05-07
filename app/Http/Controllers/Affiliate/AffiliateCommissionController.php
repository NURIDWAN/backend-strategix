<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Services\AffiliateCommissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AffiliateCommissionController extends Controller
{
    protected $commissionService;

    public function __construct(AffiliateCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Get user's commission statistics
     */
    public function getStatistics(): JsonResponse
    {
        $user = Auth::user();

        $statistics = $this->commissionService->getStatistics($user->id);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get commission history with pagination
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);

        $history = $this->commissionService->getCommissionHistory($user->id, $perPage);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get withdrawable balance
     */
    public function getWithdrawableBalance(): JsonResponse
    {
        $user = Auth::user();

        $balance = $this->commissionService->getWithdrawableBalance($user->id);
        $canWithdraw = $this->commissionService->canWithdraw($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'can_withdraw' => $canWithdraw,
                'minimum_withdrawal' => \App\Models\Affiliate\AffiliateCommission::MINIMUM_WITHDRAWAL,
            ],
        ]);
    }

    /**
     * Request withdrawal (placeholder for future implementation)
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:100000',
        ]);

        $balance = $this->commissionService->getWithdrawableBalance($user->id);

        if ($validated['amount'] > $balance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Your current balance: Rp ' . number_format($balance, 0, ',', '.'),
            ], 400);
        }

        if (!$this->commissionService->canWithdraw($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum withdrawal amount is Rp 100,000',
            ], 400);
        }

        // TODO: Implement withdrawal logic here
        // For now, just return success message
        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request received. This feature is coming soon!',
            'data' => [
                'requested_amount' => $validated['amount'],
                'current_balance' => $balance,
            ],
        ]);
    }
}
