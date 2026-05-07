<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Singapay\PdfPurchase;
use App\Models\Singapay\PaymentTransaction;
use App\Models\Singapay\PremiumPdf;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    /**
     * GET /api/admin/payments
     * List all payment transactions with filters.
     */
    public function index(Request $request)
    {
        $query = PdfPurchase::with(['user:id,name,username,phone', 'premiumPdf:id,name,package_type,price', 'paymentTransaction']);

        // Search by transaction code or user name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by package type
        if ($request->filled('package_type')) {
            $query->where('package_type', $request->package_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'amount_paid', 'status', 'paid_at'];
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
     * GET /api/admin/payments/{id}
     * Get detailed payment info including Singapay raw data.
     */
    public function show($id)
    {
        $purchase = PdfPurchase::with([
            'user:id,name,username,phone,account_status,role',
            'premiumPdf',
            'paymentTransaction',
            'affiliateCommission.affiliateUser:id,name',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $purchase,
        ]);
    }

    /**
     * GET /api/admin/payments/stats
     * Payment statistics and revenue summary.
     */
    public function stats(Request $request)
    {
        try {
            // Total revenue (paid only)
        $totalRevenue = PdfPurchase::where('status', 'paid')->sum('amount_paid');

        // Transaction counts by status
        $statusCounts = PdfPurchase::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Revenue by payment method
        $revenueByMethod = PdfPurchase::where('status', 'paid')
            ->select('payment_method', DB::raw('SUM(amount_paid) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        // Revenue by package type
        $revenueByPackage = PdfPurchase::where('status', 'paid')
            ->select('package_type', DB::raw('SUM(amount_paid) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('package_type')
            ->get();

        // Monthly revenue trend (last 12 months)
        $monthlyRevenue = PdfPurchase::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        // Today's revenue
        $todayRevenue = PdfPurchase::where('status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('amount_paid');

        // This month's revenue
        $monthRevenue = PdfPurchase::where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount_paid');

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => (int) $totalRevenue,
                'today_revenue' => (int) $todayRevenue,
                'month_revenue' => (int) $monthRevenue,
                'status_counts' => $statusCounts,
                'revenue_by_method' => $revenueByMethod,
                'revenue_by_package' => $revenueByPackage,
                'monthly_revenue' => $monthlyRevenue,
                'total_transactions' => PdfPurchase::count(),
            ],
        ]);
    } catch (\Exception $e) {
        \Log::error('Admin payment stats error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal memuat statistik pembayaran: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * PUT /api/admin/payments/{id}/status
     * Manually override payment status (edge case: webhook failure).
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,expired,failed'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $purchase = PdfPurchase::with('paymentTransaction')->findOrFail($id);
        $oldStatus = $purchase->status;

        $purchase->status = $validated['status'];

        // If marking as paid, activate the subscription
        if ($validated['status'] === 'paid' && $oldStatus !== 'paid') {
            $purchase->paid_at = now();
            $purchase->started_at = now();

            if ($purchase->premiumPdf) {
                $durationDays = (int) $purchase->premiumPdf->duration_days;
                $purchase->expires_at = now()->addDays($durationDays);
            }

            $purchase->save();

            // Activate user's Pro access
            $user = $purchase->user;
            if ($user) {
                $user->pdf_access_active = true;
                $user->pdf_access_expires_at = $purchase->expires_at;
                $user->pdf_access_package = $purchase->package_type;
                $user->save();
            }

            // Update payment transaction status too
            if ($purchase->paymentTransaction) {
                $purchase->paymentTransaction->status = 'paid';
                $purchase->paymentTransaction->paid_at = now();
                $purchase->paymentTransaction->save();
            }
        } else {
            $purchase->save();
        }

        // Log the action
        ActivityLog::logAction(
            'payment.status_changed',
            "Status pembayaran #{$purchase->transaction_code} diubah dari {$oldStatus} ke {$validated['status']}" . ($validated['notes'] ?? ''),
            $purchase,
            ['old_status' => $oldStatus, 'new_status' => $validated['status'], 'notes' => $validated['notes'] ?? null],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $purchase->fresh(['user', 'premiumPdf', 'paymentTransaction']),
            'message' => 'Status pembayaran berhasil diperbarui.',
        ]);
    }

    /**
     * GET /api/admin/packages
     * List all subscription packages.
     */
    public function packages()
    {
        $packages = PremiumPdf::withCount('purchases')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * PUT /api/admin/packages/{id}
     * Update a subscription package.
     */
    public function updatePackage(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'package_type' => ['sometimes', 'in:monthly,yearly,lifetime,consultation'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'duration_days' => ['sometimes', 'integer', 'min:0'],
            'consultation_credits' => ['sometimes', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $package = PremiumPdf::findOrFail($id);
        $package->update($validated);

        ActivityLog::logAction(
            'package.updated',
            "Paket {$package->name} diperbarui",
            $package,
            $validated,
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $package->fresh(),
            'message' => 'Paket berhasil diperbarui.',
        ]);
    }
    /**
     * POST /api/admin/packages
     * Create a new subscription package.
     */
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'package_type' => ['required', 'in:monthly,yearly,lifetime,consultation'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'consultation_credits' => ['sometimes', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $package = PremiumPdf::create($validated);

        ActivityLog::logAction(
            'package.created',
            "Paket baru {$package->name} dibuat",
            $package,
            $validated,
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $package,
            'message' => 'Paket baru berhasil dibuat.',
        ], 201);
    }

    /**
     * DELETE /api/admin/packages/{id}
     * Delete a subscription package.
     */
    public function destroyPackage(Request $request, $id)
    {
        $package = PremiumPdf::withCount('purchases')->findOrFail($id);

        // Security check: Don't allow deleting packages with transaction history
        if ($package->purchases_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Paket tidak dapat dihapus karena sudah memiliki riwayat transaksi. Silakan nonaktifkan paket ini sebagai gantinya.',
            ], 422);
        }

        $packageName = $package->name;
        $package->delete();

        ActivityLog::logAction(
            'package.deleted',
            "Paket {$packageName} dihapus",
            null,
            ['package_id' => $id, 'package_name' => $packageName],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil dihapus.',
        ]);
    }
}
