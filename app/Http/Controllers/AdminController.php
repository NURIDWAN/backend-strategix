<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * GET /api/admin/users
     * List all users with pagination, search, and filters.
     */
    public function users(Request $request)
    {
        $query = User::query()->withSum(['consultationCredits as consultation_credits_sum_remaining_sessions' => function ($q) {
            $q->active();
        }], 'remaining_sessions');

        // Search by name, username, or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by account_status
        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        // Filter by phone verification
        if ($request->filled('verified')) {
            if ($request->verified === 'true' || $request->verified === '1') {
                $query->whereNotNull('phone_verified_at');
            } else {
                $query->whereNull('phone_verified_at');
            }
        }

        // Filter by PDF Pro access
        if ($request->filled('pro')) {
            if ($request->pro === 'true' || $request->pro === '1') {
                $query->where('pdf_access_active', true)
                      ->where('pdf_access_expires_at', '>', now());
            } else {
                $query->where(function ($q) {
                    $q->where('pdf_access_active', false)
                      ->orWhereNull('pdf_access_expires_at')
                      ->orWhere('pdf_access_expires_at', '<=', now());
                });
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['name', 'username', 'phone', 'role', 'account_status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * GET /api/admin/dashboard-stats
     * Admin dashboard overview statistics.
     */
    public function dashboardStats()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('account_status', 'active')->count();
        $bannedUsers = User::where('account_status', 'banned')->count();
        $inactiveUsers = User::where('account_status', 'inactive')->count();
        $verifiedUsers = User::whereNotNull('phone_verified_at')->count();
        $unverifiedUsers = User::whereNull('phone_verified_at')->count();
        $adminUsers = User::where('role', 'admin')->count();

        // Pro subscription stats
        $proActiveUsers = User::where('pdf_access_active', true)
            ->where('pdf_access_expires_at', '>', now())
            ->count();

        // Users registered in the last 30 days
        $newUsersLast30Days = User::where('created_at', '>=', now()->subDays(30))->count();

        // Users registered in the last 7 days
        $newUsersLast7Days = User::where('created_at', '>=', now()->subDays(7))->count();

        // Registration trend (last 12 months)
        $registrationTrend = User::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->get();

        // Affiliate stats
        $totalReferrals = User::whereNotNull('referred_by_user_id')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'banned' => $bannedUsers,
                    'inactive' => $inactiveUsers,
                    'verified' => $verifiedUsers,
                    'unverified' => $unverifiedUsers,
                    'admins' => $adminUsers,
                ],
                'subscriptions' => [
                    'pro_active' => $proActiveUsers,
                    'free' => $totalUsers - $proActiveUsers,
                ],
                'growth' => [
                    'last_7_days' => $newUsersLast7Days,
                    'last_30_days' => $newUsersLast30Days,
                    'trend' => $registrationTrend,
                ],
                'affiliates' => [
                    'total_referrals' => $totalReferrals,
                ],
            ],
        ]);
    }

    /**
     * POST /api/admin/users
     * Create a new user (Admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,admin'],
            'account_status' => ['required', 'in:active,inactive,banned'],
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'phone.unique' => 'Nomor telepon sudah digunakan.',
            'password.min' => 'Password minimal :min karakter.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'account_status' => $validated['account_status'],
            'phone_verified_at' => now(), // Admin creation bypasses OTP
        ]);

        \App\Models\ActivityLog::logAction(
            'user.created',
            "Pengguna baru {$user->name} (@{$user->username}) dibuat oleh Admin",
            $user,
            ['role' => $user->role, 'status' => $user->account_status],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Pengguna baru berhasil dibuat.',
        ], 201);
    }

    /**
     * PUT /api/admin/users/{id}/role
     * Update a user's role.
     */
    public function updateRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        $user = User::findOrFail($id);

        // Prevent demoting self
        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah role Anda sendiri.',
            ], 422);
        }

        $oldRole = $user->role;
        $user->role = $validated['role'];
        $user->save();

        \App\Models\ActivityLog::logAction(
            'user.role_changed',
            "Role pengguna {$user->name} diubah dari {$oldRole} ke {$validated['role']}",
            $user,
            ['old_role' => $oldRole, 'new_role' => $validated['role']],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Role pengguna berhasil diperbarui.',
        ]);
    }

    /**
     * PUT /api/admin/users/{id}/grant-pro
     * Manually grant PRO access to a user.
     */
    public function grantProAccess(Request $request, $id)
    {
        $validated = $request->validate([
            'package' => ['required', 'in:monthly,yearly'],
        ]);

        $user = User::findOrFail($id);

        $days = $validated['package'] === 'monthly' ? 30 : 365;
        $expiresAt = now()->addDays($days);

        $oldActive = $user->pdf_access_active;
        $oldExpires = $user->pdf_access_expires_at;

        $user->pdf_access_active = true;
        $user->pdf_access_package = $validated['package'];
        $user->pdf_access_expires_at = $expiresAt;
        $user->save();

        \App\Models\ActivityLog::logAction(
            'user.pro_granted_manually',
            "Akses PRO diberikan manual ke {$user->name} ({$validated['package']}) oleh Admin",
            $user,
            [
                'package' => $validated['package'],
                'expires_at' => $expiresAt->toDateTimeString(),
                'old_active' => $oldActive,
                'old_expires' => $oldExpires ? $oldExpires->toDateTimeString() : null
            ],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => "Akses PRO ({$validated['package']}) berhasil diberikan secara manual.",
        ]);
    }
    /**
     * PUT /api/admin/users/{id}/grant-consultation
     * Manually grant consultation credits to a user.
     */
    public function grantConsultationCredit(Request $request, $id)
    {
        $validated = $request->validate([
            'credits' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::findOrFail($id);

        $user->addConsultationCredits($validated['credits'], null);

        \App\Models\ActivityLog::logAction(
            'user.consultation_granted_manually',
            "Kredit Konsultasi diberikan manual ({$validated['credits']} sesi) ke {$user->name} oleh Admin",
            $user,
            [
                'credits' => $validated['credits'],
            ],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => "{$validated['credits']} Sesi kredit konsultasi berhasil diberikan secara manual.",
        ]);
    }
}
