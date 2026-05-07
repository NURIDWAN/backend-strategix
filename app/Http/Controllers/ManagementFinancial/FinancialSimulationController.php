<?php
// app/Http/Controllers/ManagementFinancial/FinancialSimulationController.php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinancialSimulationController extends Controller
{
    // ===============================
    // FINANCIAL SIMULATION METHODS
    // ===============================

    /**
     * Get all financial simulations with filters
     */
    public function index(Request $request)
    {
        try {
            // Get user ID from authenticated user or request parameter
            $user_id = Auth::id();

            if (!$user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID diperlukan'
                ], 400);
            }

            $query = FinancialSimulation::with(['category' => function ($query) {
                $query->select('id', 'name', 'type', 'color');
            }])
                ->where('user_id', $user_id);

            // Filter by business_background_id if provided
            if ($request->has('business_background_id')) {
                $query->where('business_background_id', $request->business_background_id);
            }

            // Apply filters
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('category_id') && $request->category_id) {
                $query->where('financial_category_id', $request->category_id);
            }

            // Filter by year
            if ($request->has('year') && $request->year) {
                $query->where('year', $request->year);
            }

            if ($request->has('month') && $request->month) {
                $year = $request->year ?? now()->year;
                $query->whereYear('simulation_date', $year)
                    ->whereMonth('simulation_date', $request->month);
            }

            if ($request->has('start_date') && $request->start_date) {
                $query->where('simulation_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->where('simulation_date', '<=', $request->end_date);
            }

            $simulations = $query->orderBy('simulation_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $simulations
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching financial simulations: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data simulasi.'
            ], 500);
        }
    }

    /**
     * Get specific financial simulation
     */
    public function show($id)
    {
        try {
            $simulation = FinancialSimulation::with(['category', 'user'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$simulation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data simulasi tidak ditemukan atau unauthorized'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $simulation
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching financial simulation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data simulasi.'
            ], 500);
        }
    }

    /**
     * Create new financial simulation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'financial_category_id' => 'required|exists:financial_categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'simulation_date' => 'required|date',
            'year' => 'nullable|integer|min:2020|max:2100',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,digital_wallet,other',
            'status' => 'required|in:planned,completed,cancelled',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurring_end_date' => 'nullable|date|after:simulation_date',
            'notes' => 'nullable|string'
        ], [
            'user_id.required' => 'User ID diperlukan',
            'business_background_id.required' => 'Business ID diperlukan',
            'business_background_id.exists' => 'Business tidak ditemukan.',
            'financial_category_id.required' => 'Kategori wajib dipilih.',
            'financial_category_id.exists' => 'Kategori tidak valid.',
            'type.required' => 'Jenis simulasi wajib dipilih.',
            'type.in' => 'Jenis simulasi harus Income atau Expense.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal tidak boleh kurang dari 0.',
            'simulation_date.required' => 'Tanggal simulasi wajib diisi.',
            'simulation_date.date' => 'Format tanggal tidak valid.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal 2020.',
            'year.max' => 'Tahun maksimal 2100.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'status.required' => 'Status simulasi wajib dipilih.'
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal pada storeFinancialSimulation', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validate category ownership and type consistency
            $category = FinancialCategory::find($request->financial_category_id);
            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak ditemukan.'
                ], 422);
            }

            if ($category->user_id != $request->user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak valid untuk user ini.'
                ], 422);
            }

            if ($category->business_background_id != $request->business_background_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak valid untuk bisnis ini.'
                ], 422);
            }

            if ($category->type != $request->type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jenis kategori tidak sesuai dengan jenis simulasi.'
                ], 422);
            }

            // Get year from request or extract from simulation_date
            $year = $request->year ?? Carbon::parse($request->simulation_date)->year;

            $simulationData = [
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'financial_category_id' => $request->financial_category_id,
                'simulation_code' => FinancialSimulation::generateSimulationCode(),
                'type' => $request->type,
                'amount' => $request->amount,
                'simulation_date' => $request->simulation_date,
                'year' => $year,
                'description' => $request->description,
                'payment_method' => $request->payment_method,
                'status' => $request->status,
                'is_recurring' => $request->is_recurring ?? false,
                'notes' => $request->notes
            ];

            // Only add recurring data if is_recurring is true
            if ($request->is_recurring) {
                $simulationData['recurring_frequency'] = $request->recurring_frequency;
                $simulationData['recurring_end_date'] = $request->recurring_end_date;
            }

            $simulation = FinancialSimulation::create($simulationData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Simulasi keuangan berhasil dibuat.',
                'data' => $simulation->load('category')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing financial simulation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat simulasi.'
            ], 500);
        }
    }

    /**
     * Update financial simulation
     */
    public function update(Request $request, $id)
    {
        $simulation = FinancialSimulation::find($id);

        if (!$simulation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data simulasi tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if (Auth::id() != $simulation->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Anda tidak memiliki akses untuk mengubah data ini.'
            ], 403);
        }

        // Check business ownership
        if ($request->has('business_background_id') && $request->business_background_id != $simulation->business_background_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Simulasi ini milik bisnis lain.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'financial_category_id' => 'required|exists:financial_categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'simulation_date' => 'required|date',
            'year' => 'nullable|integer|min:2020|max:2100',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,digital_wallet,other',
            'status' => 'required|in:planned,completed,cancelled',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurring_end_date' => 'nullable|date|after:simulation_date',
            'notes' => 'nullable|string'
        ], [
            'financial_category_id.required' => 'Kategori wajib dipilih.',
            'financial_category_id.exists' => 'Kategori tidak valid.',
            'type.required' => 'Jenis simulasi wajib dipilih.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal tidak boleh kurang dari 0.',
            'simulation_date.required' => 'Tanggal simulasi wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal 2020.',
            'year.max' => 'Tahun maksimal 2100.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'status.required' => 'Status simulasi wajib dipilih.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validate category ownership and type consistency
            $category = FinancialCategory::find($request->financial_category_id);
            if (!$category || $category->user_id != $request->user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak valid atau tidak ditemukan.'
                ], 422);
            }

            // Validate category belongs to same business
            if ($category->business_background_id != $simulation->business_background_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak valid untuk bisnis ini.'
                ], 422);
            }

            if ($category->type != $request->type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jenis kategori tidak sesuai dengan jenis simulasi.'
                ], 422);
            }

            $updateData = [
                'financial_category_id' => $request->financial_category_id,
                'type' => $request->type,
                'amount' => $request->amount,
                'simulation_date' => $request->simulation_date,
                'year' => $request->year ?? Carbon::parse($request->simulation_date)->year,
                'description' => $request->description,
                'payment_method' => $request->payment_method,
                'status' => $request->status,
                'is_recurring' => $request->is_recurring ?? false,
                'notes' => $request->notes
            ];

            // Handle recurring data
            if ($request->is_recurring) {
                $updateData['recurring_frequency'] = $request->recurring_frequency;
                $updateData['recurring_end_date'] = $request->recurring_end_date;
            } else {
                $updateData['recurring_frequency'] = null;
                $updateData['recurring_end_date'] = null;
            }

            $simulation->update($updateData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Simulasi keuangan berhasil diperbarui.',
                'data' => $simulation->load('category')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating financial simulation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui simulasi.'
            ], 500);
        }
    }

    /**
     * Delete financial simulation
     */
    public function destroy(Request $request, $id)
    {
        $simulation = FinancialSimulation::find($id);

        if (!$simulation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data simulasi tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if (Auth::id() != $simulation->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Anda tidak memiliki akses untuk menghapus data ini.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $simulation->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Simulasi keuangan berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting financial simulation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus simulasi.'
            ], 500);
        }
    }

    /**
     * Get cash flow summary
     */
    public function getCashFlowSummary(Request $request)
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
            $year = (int) ($request->year ?? now()->year);
            $month = $request->has('month') && $request->month ? (int) $request->month : null;

            // Validate month if provided
            if ($month !== null && ($month < 1 || $month > 12)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bulan tidak valid'
                ], 422);
            }

            // Total Income (completed)
            $totalIncomeQuery = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->where('type', 'income')
                ->where('status', 'completed')
                ->whereYear('simulation_date', $year);
            if ($month) {
                $totalIncomeQuery->whereMonth('simulation_date', $month);
            }
            $totalIncome = $totalIncomeQuery->sum('amount');

            // Total Expense (completed)
            $totalExpenseQuery = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->whereYear('simulation_date', $year);
            if ($month) {
                $totalExpenseQuery->whereMonth('simulation_date', $month);
            }
            $totalExpense = $totalExpenseQuery->sum('amount');

            // Planned Income
            $plannedIncomeQuery = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->where('type', 'income')
                ->where('status', 'planned')
                ->whereYear('simulation_date', $year);
            if ($month) {
                $plannedIncomeQuery->whereMonth('simulation_date', $month);
            }
            $plannedIncome = $plannedIncomeQuery->sum('amount');

            // Planned Expense
            $plannedExpenseQuery = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->where('type', 'expense')
                ->where('status', 'planned')
                ->whereYear('simulation_date', $year);
            if ($month) {
                $plannedExpenseQuery->whereMonth('simulation_date', $month);
            }
            $plannedExpense = $plannedExpenseQuery->sum('amount');

            // Net Cash Flow
            $netCashFlow = $totalIncome - $totalExpense;
            $projectedNetCashFlow = ($totalIncome + $plannedIncome) - ($totalExpense + $plannedExpense);

            // Category breakdown
            $categoryBreakdownQuery = FinancialSimulation::with(['category' => function ($query) {
                $query->select('id', 'name', 'type', 'color');
            }])
                ->where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->whereYear('simulation_date', $year);
            if ($month) {
                $categoryBreakdownQuery->whereMonth('simulation_date', $month);
            }
            $categoryBreakdown = $categoryBreakdownQuery
                ->selectRaw('type, financial_category_id, SUM(amount) as total_amount')
                ->groupBy('type', 'financial_category_id')
                ->get()
                ->groupBy('type');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_income' => (float) $totalIncome,
                        'total_expense' => (float) $totalExpense,
                        'net_cash_flow' => (float) $netCashFlow,
                        'planned_income' => (float) $plannedIncome,
                        'planned_expense' => (float) $plannedExpense,
                        'projected_net_cash_flow' => (float) $projectedNetCashFlow,
                    ],
                    'category_breakdown' => $categoryBreakdown,
                    'period' => [
                        'year' => (int) $year,
                        'month' => $month,
                        'month_name' => $month ? Carbon::create()->month($month)->monthName : 'Tahunan'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching cash flow summary: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil ringkasan arus kas.'
            ], 500);
        }
    }

    /**
     * Get monthly comparison
     */
    public function getMonthlyComparison(Request $request)
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
            $year = (int) ($request->year ?? now()->year);

            $monthlyData = FinancialSimulation::where('user_id', $user_id)
                ->where('business_background_id', $business_background_id)
                ->whereYear('simulation_date', $year)
                ->selectRaw('
                    MONTH(simulation_date) as month,
                    SUM(CASE WHEN type = "income" AND status = "completed" THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = "expense" AND status = "completed" THEN amount ELSE 0 END) as total_expense,
                    SUM(CASE WHEN type = "income" AND status = "planned" THEN amount ELSE 0 END) as planned_income,
                    SUM(CASE WHEN type = "expense" AND status = "planned" THEN amount ELSE 0 END) as planned_expense
                ')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $monthlyData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching monthly comparison: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil perbandingan bulanan.'
            ], 500);
        }
    }

    /**
     * Get available years for business
     */
    public function getAvailableYears(Request $request)
    {
        try {
            $user_id = Auth::id();

            if (!$user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID diperlukan'
                ], 400);
            }

            if (!$request->has('business_background_id')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business Background ID diperlukan'
                ], 400);
            }

            $years = FinancialSimulation::getAvailableYears(
                $request->business_background_id,
                $user_id
            );

            // Tambahkan tahun saat ini jika belum ada
            $currentYear = now()->year;
            if (!in_array($currentYear, $years)) {
                $years[] = $currentYear;
                sort($years);
                rsort($years); // Sort descending
            }

            return response()->json([
                'status' => 'success',
                'data' => $years,
                'current_year' => $currentYear
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching available years: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil daftar tahun.'
            ], 500);
        }
    }
}
