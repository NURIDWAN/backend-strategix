<?php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementFinancial\FinancialCategory;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialSummary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ManagementFinancialController extends Controller
{
    // ===============================
    // FINANCIAL CATEGORY METHODS
    // ===============================

    /**
     * Get all financial categories
     */
    public function indexCategories(Request $request)
    {
        try {
            $query = FinancialCategory::query()
                ->where('user_id', Auth::id());

            // Filter by business if provided
            if ($request->has('business_background_id')) {
                $query->where('business_background_id', $request->business_background_id);
            }

            $categories = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching financial categories: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kategori.'
            ], 500);
        }
    }

    /**
     * Get specific financial category
     */
    public function showCategory($id)
    {
        try {
            $category = FinancialCategory::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak ditemukan atau unauthorized'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching financial category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kategori.'
            ], 500);
        }
    }

    /**
     * Create new financial category
     */
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'category_subtype' => 'nullable|in:operating_revenue,non_operating_revenue,cogs,operating_expense,interest_expense,tax_expense,other',
            'color' => 'nullable|string|max:7',
            'status' => 'required|in:actual,plan',
            'description' => 'nullable|string'
        ], [
            'user_id.required' => 'User ID wajib diisi.',
            'business_background_id.required' => 'Business ID wajib diisi.',
            'business_background_id.exists' => 'Business tidak ditemukan.',
            'name.required' => 'Nama kategori wajib diisi.',
            'type.required' => 'Jenis kategori wajib dipilih.',
            'type.in' => 'Jenis kategori harus Income atau Expense.',
            'category_subtype.in' => 'Sub-tipe kategori tidak valid.',
            'status.required' => 'Status kategori wajib dipilih.',
            'status.in' => 'Status harus Actual atau Plan.'
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal pada storeFinancialCategory', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check unique constraint: name must be unique within user_id + business_background_id
        $exists = FinancialCategory::where('user_id', Auth::id())
            ->where('business_background_id', $request->business_background_id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nama kategori sudah digunakan untuk bisnis ini.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = FinancialCategory::create([
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'name' => $request->name,
                'type' => $request->type,
                'category_subtype' => $request->category_subtype ?? $this->getDefaultSubtype($request->type),
                'color' => $request->color ?? $this->generateDefaultColor($request->type),
                'status' => $request->status,
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori keuangan berhasil dibuat.',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing financial category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat kategori.'
            ], 500);
        }
    }

    /**
     * Update financial category
     */
    public function updateCategory(Request $request, $id)
    {
        $category = FinancialCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan'
            ], 404);
        }

        if (!$category || $category->user_id != Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or Not Found: Anda tidak memiliki akses untuk mengubah data ini.'
            ], 404);
        }

        // Check business ownership
        if ($request->has('business_background_id') && $request->business_background_id != $category->business_background_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Kategori ini milik bisnis lain.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'category_subtype' => 'nullable|in:operating_revenue,non_operating_revenue,cogs,operating_expense,interest_expense,tax_expense,other',
            'color' => 'nullable|string|max:7',
            'status' => 'required|in:actual,plan',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'type.required' => 'Jenis kategori wajib dipilih.',
            'type.in' => 'Jenis kategori harus Income atau Expense.',
            'category_subtype.in' => 'Sub-tipe kategori tidak valid.',
            'status.required' => 'Status kategori wajib dipilih.',
            'status.in' => 'Status harus Actual atau Plan.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check unique constraint if name is changed
        if ($request->name !== $category->name) {
            $exists = FinancialCategory::where('user_id', $category->user_id)
                ->where('business_background_id', $category->business_background_id)
                ->where('name', $request->name)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nama kategori sudah digunakan untuk bisnis ini.'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $category->update([
                'name' => $request->name,
                'type' => $request->type,
                'category_subtype' => $request->category_subtype ?? $category->category_subtype,
                'color' => $request->color ?? $category->color,
                'status' => $request->status,
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori keuangan berhasil diperbarui.',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating financial category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui kategori.'
            ], 500);
        }
    }

    /**
     * Delete financial category
     */
    public function destroyCategory(Request $request, $id)
    {
        $category = FinancialCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan'
            ], 404);
        }

        if (!$category || $category->user_id != Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or Not Found: Anda tidak memiliki akses untuk menghapus data ini.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Check if category has transactions (you can add this later)
            // if ($category->transactions()->exists()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Tidak dapat menghapus kategori yang sudah memiliki transaksi.'
            //     ], 422);
            // }

            $category->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori keuangan berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting financial category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus kategori.'
            ], 500);
        }
    }

    /**
     * Get categories summary with auto-total
     */
    public function getCategoriesSummary(Request $request)
    {
        try {
            $user_id = Auth::id();

            $summary = FinancialCategory::where('user_id', $user_id)
                ->selectRaw('
                    type,
                    status,
                    COUNT(*) as total_categories,
                    GROUP_CONCAT(name) as category_names
                ')
                ->groupBy('type', 'status')
                ->get();

            $totalIncomeCategories = FinancialCategory::where('user_id', $user_id)
                ->where('type', 'income')
                ->count();

            $totalExpenseCategories = FinancialCategory::where('user_id', $user_id)
                ->where('type', 'expense')
                ->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => $summary,
                    'totals' => [
                        'income_categories' => $totalIncomeCategories,
                        'expense_categories' => $totalExpenseCategories,
                        'all_categories' => $totalIncomeCategories + $totalExpenseCategories
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching categories summary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil ringkasan kategori.'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $user_id = Auth::id();

            if (!$user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID diperlukan'
                ], 400);
            }

            // Validate business_background_id
            if (!$request->has('business_background_id')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business Background ID diperlukan'
                ], 400);
            }

            $business_background_id = $request->business_background_id;

            // Total Categories
            $totalCategories = FinancialCategory::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->count();

            // Total Simulations
            $totalSimulations = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->count();

            // Total Income (completed only)
            $totalIncome = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->where('type', 'income')
                ->where('status', 'completed')
                ->sum('amount');

            // Total Expense (completed only)
            $totalExpense = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->sum('amount');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_categories' => (int) $totalCategories,
                    'total_simulations' => (int) $totalSimulations,
                    'total_income' => (float) $totalIncome,
                    'total_expense' => (float) $totalExpense,
                    'net_cash_flow' => (float) ($totalIncome - $totalExpense)
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard stats: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik dashboard.'
            ], 500);
        }
    }

    /**
     * Generate default color based on category type
     */
    private function generateDefaultColor($type)
    {
        $colors = [
            'income' => '#10B981', // green-500
            'expense' => '#EF4444' // red-500
        ];

        return $colors[$type] ?? '#6B7280'; // gray-500 as fallback
    }

    /**
     * Get default subtype based on category type
     */
    private function getDefaultSubtype($type)
    {
        $defaults = [
            'income' => 'operating_revenue',
            'expense' => 'operating_expense'
        ];

        return $defaults[$type] ?? 'other';
    }
}
