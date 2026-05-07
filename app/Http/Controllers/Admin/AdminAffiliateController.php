<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate\AffiliateLink;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateWithdrawal;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAffiliateController extends Controller
{
    /**
     * GET /api/admin/affiliates
     * List all affiliate users with their stats.
     */
    public function index(Request $request)
    {
        $query = User::whereHas('affiliateLink')
            ->with('affiliateLink')
            ->withCount('referrals')
            ->withSum(['affiliateCommissions as total_commission' => function ($q) {
                $q->whereIn('status', ['approved', 'paid']);
            }], 'commission_amount')
            ->withSum(['affiliateCommissions as pending_commission' => function ($q) {
                $q->where('status', 'pending');
            }], 'commission_amount');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by link status
        if ($request->filled('link_active')) {
            $isActive = $request->link_active === 'true' || $request->link_active === '1';
            $query->whereHas('affiliateLink', function ($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['name', 'created_at', 'referrals_count'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * GET /api/admin/affiliates/{userId}
     * Get detailed affiliate info for a specific user.
     */
    public function show($userId)
    {
        $user = User::with([
            'affiliateLink',
            'referrals:id,name,username,phone,created_at,referred_by_user_id',
            'affiliateCommissions' => function ($q) {
                $q->with(['referredUser:id,name', 'purchase:id,transaction_code,amount_paid,package_type'])
                  ->orderBy('created_at', 'desc');
            },
        ])->findOrFail($userId);

        // Withdrawal history
        $withdrawals = AffiliateWithdrawal::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Stats
        $totalEarned = $user->affiliateCommissions->whereIn('status', ['approved', 'paid'])->sum('commission_amount');
        $totalPaid = $user->affiliateCommissions->where('status', 'paid')->sum('commission_amount');
        $totalPending = $user->affiliateCommissions->where('status', 'pending')->sum('commission_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'withdrawals' => $withdrawals,
                'summary' => [
                    'total_referrals' => $user->referrals->count(),
                    'total_earned' => $totalEarned,
                    'total_paid' => $totalPaid,
                    'total_pending' => $totalPending,
                    'balance' => $totalEarned - $totalPaid,
                ],
            ],
        ]);
    }

    /**
     * GET /api/admin/affiliates/commissions
     * List all commissions across all users.
     */
    public function commissions(Request $request)
    {
        $query = AffiliateCommission::with([
            'affiliateUser:id,name,username,phone',
            'referredUser:id,name,username',
            'purchase:id,transaction_code,amount_paid,package_type',
        ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by affiliate user name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('affiliateUser', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'commission_amount', 'status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * PUT /api/admin/affiliates/commissions/{id}/status
     * Approve or reject a commission.
     */
    public function updateCommissionStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,paid'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $commission = AffiliateCommission::with('affiliateUser:id,name')->findOrFail($id);
        $oldStatus = $commission->status;

        $commission->status = $validated['status'];
        if ($validated['notes']) {
            $commission->notes = $validated['notes'];
        }
        if ($validated['status'] === 'paid') {
            $commission->paid_at = now();
        }
        $commission->save();

        ActivityLog::logAction(
            'commission.status_changed',
            "Status komisi #{$id} diubah dari {$oldStatus} ke {$validated['status']}",
            $commission,
            ['old_status' => $oldStatus, 'new_status' => $validated['status']],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $commission->fresh(['affiliateUser', 'referredUser', 'purchase']),
            'message' => 'Status komisi berhasil diperbarui.',
        ]);
    }

    /**
     * GET /api/admin/affiliates/withdrawals
     * List all withdrawal requests.
     */
    public function withdrawals(Request $request)
    {
        $query = AffiliateWithdrawal::with('user:id,name,username,phone');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * PUT /api/admin/affiliates/withdrawals/{id}/status
     * Approve/reject/process a withdrawal request.
     */
    public function updateWithdrawalStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processed,failed,rejected'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $withdrawal = AffiliateWithdrawal::with('user:id,name')->findOrFail($id);
        $oldStatus = $withdrawal->status;

        $withdrawal->status = $validated['status'];
        if ($validated['notes']) {
            $withdrawal->notes = $validated['notes'];
        }
        $withdrawal->save();

        // If rejected, we could potentially restore the balance to commissions
        // For now, just update the status

        ActivityLog::logAction(
            'withdrawal.status_changed',
            "Status penarikan #{$id} oleh {$withdrawal->user->name} diubah dari {$oldStatus} ke {$validated['status']}",
            $withdrawal,
            ['old_status' => $oldStatus, 'new_status' => $validated['status'], 'amount' => $withdrawal->amount],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $withdrawal->fresh('user'),
            'message' => 'Status penarikan berhasil diperbarui.',
        ]);
    }

    /**
     * GET /api/admin/affiliates/stats
     * Affiliate program summary statistics.
     */
    public function stats()
    {
        $totalAffiliates = AffiliateLink::count();
        $activeLinks = AffiliateLink::where('is_active', true)->count();
        $totalReferrals = User::whereNotNull('referred_by_user_id')->count();

        // Commission stats
        $totalCommissionPending = AffiliateCommission::where('status', 'pending')->sum('commission_amount');
        $totalCommissionApproved = AffiliateCommission::where('status', 'approved')->sum('commission_amount');
        $totalCommissionPaid = AffiliateCommission::where('status', 'paid')->sum('commission_amount');

        // Withdrawal stats
        $totalWithdrawalPending = AffiliateWithdrawal::where('status', 'pending')->sum('amount');
        $totalWithdrawalProcessed = AffiliateWithdrawal::where('status', 'processed')->sum('amount');

        // Monthly commission trend (last 12 months)
        $monthlyCommissions = AffiliateCommission::whereIn('status', ['approved', 'paid'])
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(commission_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        // Top affiliates (by total earned)
        $topAffiliates = User::whereHas('affiliateCommissions')
            ->withSum(['affiliateCommissions as total_earned' => function ($q) {
                $q->whereIn('status', ['approved', 'paid']);
            }], 'commission_amount')
            ->withCount('referrals')
            ->orderByDesc('total_earned')
            ->limit(10)
            ->get(['id', 'name', 'username', 'phone']);

        return response()->json([
            'success' => true,
            'data' => [
                'total_affiliates' => $totalAffiliates,
                'active_links' => $activeLinks,
                'total_referrals' => $totalReferrals,
                'commissions' => [
                    'pending' => (float) $totalCommissionPending,
                    'approved' => (float) $totalCommissionApproved,
                    'paid' => (float) $totalCommissionPaid,
                    'total' => (float) ($totalCommissionPending + $totalCommissionApproved + $totalCommissionPaid),
                ],
                'withdrawals' => [
                    'pending' => (float) $totalWithdrawalPending,
                    'processed' => (float) $totalWithdrawalProcessed,
                ],
                'monthly_commissions' => $monthlyCommissions,
                'top_affiliates' => $topAffiliates,
            ],
        ]);
    }
}
