<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateWithdrawal;
use App\Services\AffiliateCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AffiliateWithdrawalController extends Controller
{
    protected $commissionService;

    public function __construct(AffiliateCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Submit a withdrawal request
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:50000',
            'bank_name' => 'required|string',
            'bank_code' => 'nullable|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $amount = $request->amount;
            $bankDetails = $request->only(['bank_name', 'bank_code', 'account_number', 'account_name', 'notes']);

            // Rate Limiting: Check withdrawal count in last 24 hours
            $withdrawalsToday = AffiliateWithdrawal::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($withdrawalsToday >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda telah mencapai batas maksimal penarikan (5x per hari). Silakan coba lagi besok.'
                ], 429); // HTTP 429 Too Many Requests
            }

            $result = $this->commissionService->withdraw($user->id, $amount, $bankDetails);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get withdrawal history
     */
    public function history(Request $request)
    {
        $withdrawals = AffiliateWithdrawal::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }
}
