<?php

namespace App\Services;

use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateWithdrawal;
use App\Models\Singapay\PdfPurchase;
use App\Models\User;
use App\Services\Singapay\SingaPayPayoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateCommissionService
{
    /**
     * Calculate and create commission for a purchase
     * Called from WebhookService when payment is successful
     */
    public function calculateCommission(PdfPurchase $purchase): ?AffiliateCommission
    {
        try {
            // Check if commission can be earned
            if (!$this->canEarnCommission($purchase)) {
                Log::info('[AffiliateCommission] Commission not applicable', [
                    'purchase_id' => $purchase->id,
                    'user_id' => $purchase->user_id,
                ]);
                return null;
            }

            // Get the referred user
            $referredUser = $purchase->user;
            $affiliateUserId = $referredUser->referred_by_user_id;

            // Calculate commission
            $subscriptionAmount = $purchase->amount_paid;
            $commissionPercentage = AffiliateCommission::COMMISSION_PERCENTAGE;
            $commissionAmount = ($subscriptionAmount * $commissionPercentage) / 100;

            // Create commission record
            $commission = AffiliateCommission::create([
                'affiliate_user_id' => $affiliateUserId,
                'referred_user_id' => $referredUser->id,
                'purchase_id' => $purchase->id,
                'subscription_amount' => $subscriptionAmount,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'status' => AffiliateCommission::STATUS_APPROVED, // Auto-approve
                'notes' => "Commission from {$referredUser->name} subscription ({$purchase->package_type})",
            ]);

            Log::info('[AffiliateCommission] Commission created', [
                'commission_id' => $commission->id,
                'affiliate_user_id' => $affiliateUserId,
                'referred_user_id' => $referredUser->id,
                'purchase_id' => $purchase->id,
                'commission_amount' => $commissionAmount,
            ]);

            return $commission;
        } catch (\Exception $e) {
            Log::error('[AffiliateCommission] Failed to calculate commission', [
                'purchase_id' => $purchase->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Check if purchase is eligible for commission
     */
    public function canEarnCommission(PdfPurchase $purchase): bool
    {
        // 1. Purchase must be paid
        if ($purchase->status !== 'paid') {
            return false;
        }

        // 2. User must be referred by someone
        $user = $purchase->user;
        if (!$user || !$user->referred_by_user_id) {
            return false;
        }

        // 3. Must be first purchase (not renewal)
        if (!$this->isFirstPurchase($purchase)) {
            Log::info('[AffiliateCommission] Not first purchase, commission skipped', [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
            ]);
            return false;
        }

        // 4. Commission must not already exist for this purchase
        if (AffiliateCommission::where('purchase_id', $purchase->id)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is user's first purchase (not renewal)
     */
    public function isFirstPurchase(PdfPurchase $purchase): bool
    {
        return PdfPurchase::where('user_id', $purchase->user_id)
            ->where('status', 'paid')
            ->where('id', '!=', $purchase->id)
            ->doesntExist();
    }

    /**
     * Get total earnings for a user
     */
    public function getTotalEarnings(int $userId, ?string $status = null): float
    {
        $query = AffiliateCommission::where('affiliate_user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        } else {
            // Default: approved + paid
            $query->whereIn('status', [
                AffiliateCommission::STATUS_APPROVED,
                AffiliateCommission::STATUS_PAID,
            ]);
        }

        return (float) $query->sum('commission_amount');
    }

    /**
     * Get withdrawable balance (Total Approved - Total Withdrawn)
     */
    public function getWithdrawableBalance(int $userId): float
    {
        $totalApproved = (float) AffiliateCommission::where('affiliate_user_id', $userId)
            ->where('status', AffiliateCommission::STATUS_APPROVED)
            ->sum('commission_amount');

        $totalWithdrawn = (float) AffiliateWithdrawal::where('user_id', $userId)
            ->whereIn('status', [AffiliateWithdrawal::STATUS_PENDING, AffiliateWithdrawal::STATUS_PROCESSED])
            ->sum('amount');

        return max(0, $totalApproved - $totalWithdrawn);
    }

    /**
     * Request a withdrawal
     */
    public function withdraw(int $userId, float $amount, array $bankDetails): array
    {
        // 1. Validate Balance
        $balance = $this->getWithdrawableBalance($userId);
        if ($amount > $balance) {
            throw new \Exception('Saldo tidak mencukupi untuk melakukan penarikan.');
        }

        if ($amount < AffiliateCommission::MINIMUM_WITHDRAWAL) {
            throw new \Exception('Jumlah penarikan di bawah minimum (' . number_format(AffiliateCommission::MINIMUM_WITHDRAWAL) . ').');
        }

        DB::beginTransaction();
        try {
            // 2. Create Withdrawal Record
            $withdrawal = AffiliateWithdrawal::create([
                'user_id' => $userId,
                'amount' => $amount,
                'status' => AffiliateWithdrawal::STATUS_PENDING,
                'bank_name' => $bankDetails['bank_name'],
                'bank_code' => $bankDetails['bank_code'] ?? null,
                'account_number' => $bankDetails['account_number'],
                'account_name' => $bankDetails['account_name'],
                'notes' => $bankDetails['notes'] ?? null,
            ]);

            // 3. Process Payout with SingaPay (or Mock)
            $payoutService = app(SingaPayPayoutService::class);
            $result = $payoutService->requestPayout($withdrawal);

            DB::commit();

            return [
                'success' => true,
                'message' => $result['message'],
                'data' => $withdrawal->refresh()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Affiliate Withdrawal] Failed', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if user can withdraw (balance >= minimum)
     */
    public function canWithdraw(int $userId): bool
    {
        $balance = $this->getWithdrawableBalance($userId);
        return $balance >= AffiliateCommission::MINIMUM_WITHDRAWAL;
    }

    /**
     * Get commission statistics for a user
     */
    public function getStatistics(int $userId): array
    {
        $totalEarnings = $this->getTotalEarnings($userId);
        $approvedBalance = $this->getTotalEarnings($userId, AffiliateCommission::STATUS_APPROVED);
        $paidTotal = $this->getTotalEarnings($userId, AffiliateCommission::STATUS_PAID);
        $pendingTotal = $this->getTotalEarnings($userId, AffiliateCommission::STATUS_PENDING);

        // Count referrals who made purchases
        $totalReferrals = AffiliateCommission::where('affiliate_user_id', $userId)
            ->distinct('referred_user_id')
            ->count('referred_user_id');

        // Count total commissions
        $totalCommissions = AffiliateCommission::where('affiliate_user_id', $userId)->count();

        return [
            'total_earnings' => $totalEarnings,
            'approved_balance' => $approvedBalance,
            'paid_total' => $paidTotal,
            'pending_total' => $pendingTotal,
            'total_referrals' => $totalReferrals,
            'total_commissions' => $totalCommissions,
            'can_withdraw' => $this->canWithdraw($userId),
            'minimum_withdrawal' => AffiliateCommission::MINIMUM_WITHDRAWAL,
        ];
    }

    /**
     * Get commission history with pagination
     */
    public function getCommissionHistory(int $userId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return AffiliateCommission::where('affiliate_user_id', $userId)
            ->with(['referredUser', 'purchase.premiumPdf'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
