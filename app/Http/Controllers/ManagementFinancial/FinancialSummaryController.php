<?php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementFinancial\FinancialSummary;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\BusinessBackground;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinancialSummaryController extends Controller
{
    /**
     * Get all financial summaries for user
     */
    public function index(Request $request)
    {
        try {
            Log::info('FinancialSummaryController: Fetching financial summaries', ['request' => $request->all()]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'business_background_id' => 'nullable|exists:business_backgrounds,id',
                'year' => 'nullable|integer|min:2020|max:2030',
                'month' => 'nullable|integer|between:1,12'
            ]);

            if ($validator->fails()) {
                Log::warning('FinancialSummaryController: Validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user_id = Auth::id();
            $business_id = $request->business_background_id;
            $year = $request->year ?? date('Y');
            $month = $request->month ?? null;

            Log::info('FinancialSummaryController: Building query', [
                'user_id' => $user_id,
                'year' => $year,
                'month' => $month,
                'business_id' => $business_id
            ]);

            $query = FinancialSummary::with(['businessBackground'])
                ->where('user_id', $user_id)
                ->where('year', $year);

            if ($business_id) {
                $query->where('business_background_id', $business_id);
            }

            // Only filter by month if specified
            if ($month) {
                $query->where('month', $month);
            }

            $summaries = $query->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            Log::info('FinancialSummaryController: Successfully fetched summaries', [
                'count' => $summaries->count(),
                'user_id' => $user_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $summaries,
                'count' => $summaries->count(),
                'message' => 'Data ringkasan keuangan berhasil diambil'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialSummaryController: Error fetching financial summaries - ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific financial summary
     */
    public function show($id)
    {
        try {
            $summary = FinancialSummary::with(['businessBackground', 'user'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$summary) {
                Log::warning('FinancialSummaryController: Summary not found or unauthorized', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ringkasan keuangan tidak ditemukan atau unauthorized'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $summary,
                'message' => 'Data ringkasan berhasil diambil'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialSummaryController: Error fetching financial summary - ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data ringkasan.'
            ], 500);
        }
    }

    /**
     * Create new financial summary
     */
    public function store(Request $request)
    {
        Log::info('FinancialSummaryController: Creating new financial summary', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2030',
            'total_income' => 'required|numeric|min:0',
            'total_expense' => 'required|numeric|min:0',
            'gross_profit' => 'required|numeric',
            'net_profit' => 'required|numeric',
            'cash_position' => 'required|numeric',
            'income_breakdown' => 'nullable|array',
            'expense_breakdown' => 'nullable|array',
            'notes' => 'nullable|string|max:1000'
        ], [
            'month.required' => 'Bulan wajib diisi.',
            'month.between' => 'Bulan harus antara 1-12.',
            'year.required' => 'Tahun wajib diisi.',
            'total_income.required' => 'Total pendapatan wajib diisi.',
            'total_expense.required' => 'Total pengeluaran wajib diisi.',
            'gross_profit.required' => 'Laba kotor wajib diisi.',
            'net_profit.required' => 'Laba bersih wajib diisi.',
            'cash_position.required' => 'Posisi kas wajib diisi.'
        ]);

        if ($validator->fails()) {
            Log::warning('FinancialSummaryController: Store validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if summary already exists for this period
            $existingSummary = FinancialSummary::where('user_id', $request->user_id)
                ->where('business_background_id', $request->business_background_id)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->first();

            if ($existingSummary) {
                Log::warning('FinancialSummaryController: Duplicate summary found', [
                    'user_id' => $request->user_id,
                    'business_id' => $request->business_background_id,
                    'month' => $request->month,
                    'year' => $request->year
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ringkasan keuangan untuk periode ini sudah ada.'
                ], 422);
            }

            $summaryData = [
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'month' => $request->month,
                'year' => $request->year,
                'total_income' => $request->total_income,
                'total_expense' => $request->total_expense,
                'gross_profit' => $request->gross_profit,
                'net_profit' => $request->net_profit,
                'cash_position' => $request->cash_position,
                'notes' => $request->notes
            ];

            // Add breakdowns if provided
            if ($request->has('income_breakdown')) {
                $summaryData['income_breakdown'] = $request->income_breakdown;
            }

            if ($request->has('expense_breakdown')) {
                $summaryData['expense_breakdown'] = $request->expense_breakdown;
            }

            $summary = FinancialSummary::create($summaryData);

            DB::commit();

            Log::info('FinancialSummaryController: Successfully created financial summary', [
                'id' => $summary->id,
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ringkasan keuangan berhasil dibuat.',
                'data' => $summary
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinancialSummaryController: Error storing financial summary - ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat ringkasan keuangan.'
            ], 500);
        }
    }

    /**
     * Update financial summary
     */
    public function update(Request $request, $id)
    {
        Log::info('FinancialSummaryController: Updating financial summary', ['id' => $id, 'request' => $request->all()]);

        $summary = FinancialSummary::find($id);

        if (!$summary) {
            Log::warning('FinancialSummaryController: Summary not found for update', ['id' => $id]);
            return response()->json([
                'status' => 'error',
                'message' => 'Ringkasan keuangan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if (!$summary || $summary->user_id != Auth::id()) {
            Log::warning('FinancialSummaryController: Unauthorized update attempt or not found', [
                'request_user_id' => Auth::id(),
                'summary_user_id' => $summary ? $summary->user_id : null
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or Not Found: Anda tidak memiliki akses untuk mengubah data ini.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'total_income' => 'required|numeric|min:0',
            'total_expense' => 'required|numeric|min:0',
            'gross_profit' => 'required|numeric',
            'net_profit' => 'required|numeric',
            'cash_position' => 'required|numeric',
            'income_breakdown' => 'nullable|array',
            'expense_breakdown' => 'nullable|array',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            Log::warning('FinancialSummaryController: Update validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = [
                'total_income' => $request->total_income,
                'total_expense' => $request->total_expense,
                'gross_profit' => $request->gross_profit,
                'net_profit' => $request->net_profit,
                'cash_position' => $request->cash_position,
                'notes' => $request->notes
            ];

            // Update breakdowns if provided
            if ($request->has('income_breakdown')) {
                $updateData['income_breakdown'] = $request->income_breakdown;
            }

            if ($request->has('expense_breakdown')) {
                $updateData['expense_breakdown'] = $request->expense_breakdown;
            }

            $summary->update($updateData);

            DB::commit();

            Log::info('FinancialSummaryController: Successfully updated financial summary', ['id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ringkasan keuangan berhasil diperbarui.',
                'data' => $summary
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinancialSummaryController: Error updating financial summary - ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui ringkasan keuangan.'
            ], 500);
        }
    }

    /**
     * Delete financial summary
     */
    public function destroy(Request $request, $id)
    {
        Log::info('FinancialSummaryController: Deleting financial summary', ['id' => $id, 'user_id' => $request->user_id]);

        $summary = FinancialSummary::find($id);

        if (!$summary) {
            Log::warning('FinancialSummaryController: Summary not found for deletion', ['id' => $id]);
            return response()->json([
                'status' => 'error',
                'message' => 'Ringkasan keuangan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if (!$summary || $summary->user_id != Auth::id()) {
            Log::warning('FinancialSummaryController: Unauthorized deletion attempt or not found', [
                'request_user_id' => Auth::id(),
                'summary_user_id' => $summary ? $summary->user_id : null
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or Not Found: Anda tidak memiliki akses untuk menghapus data ini.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $summary->delete();

            DB::commit();

            Log::info('FinancialSummaryController: Successfully deleted financial summary', ['id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ringkasan keuangan berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinancialSummaryController: Error deleting financial summary - ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus ringkasan keuangan.'
            ], 500);
        }
    }

    /**
     * Get financial summary statistics (auto-calculated from simulations)
     */
    public function getStatistics(Request $request)
    {
        try {
            Log::info('FinancialSummaryController: Fetching statistics from summaries', ['request' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'business_id' => 'nullable|exists:business_backgrounds,id',
                'business_background_id' => 'nullable|exists:business_backgrounds,id',
                'year' => 'nullable|integer|min:2020|max:2030'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user_id = Auth::id();
            $business_id = $request->business_id ?? $request->business_background_id;
            $year = $request->year ?? date('Y');

            $query = FinancialSummary::where('user_id', $user_id)
                ->where('year', $year);

            if ($business_id) {
                $query->where('business_background_id', $business_id);
            }

            $summaries = $query->get();

            // Calculate annual totals
            $totalIncome = $summaries->sum('total_income');
            $totalExpense = $summaries->sum('total_expense');
            $totalGrossProfit = $summaries->sum('gross_profit');
            $totalNetProfit = $summaries->sum('net_profit');

            // Calculate monthly averages
            $monthsWithData = $summaries->count();

            $avgMonthlyIncome = $monthsWithData > 0 ? $totalIncome / $monthsWithData : 0;
            $avgMonthlyExpense = $monthsWithData > 0 ? $totalExpense / $monthsWithData : 0;
            $avgMonthlyGrossProfit = $monthsWithData > 0 ? $totalGrossProfit / $monthsWithData : 0;

            // Calculate max/min monthly values
            $monthlyIncomes = $summaries->pluck('total_income')->toArray();
            $monthlyGrossProfits = $summaries->pluck('gross_profit')->toArray();

            $maxMonthlyIncome = count($monthlyIncomes) > 0 ? max($monthlyIncomes) : 0;
            $minMonthlyIncome = count($monthlyIncomes) > 0 ? min($monthlyIncomes) : 0;
            $maxMonthlyProfit = count($monthlyGrossProfits) > 0 ? max($monthlyGrossProfits) : 0;
            $minMonthlyProfit = count($monthlyGrossProfits) > 0 ? min($monthlyGrossProfits) : 0;

            // Get the latest month cash position
            $latestSummary = $summaries->sortByDesc('month')->first();
            $cashPosition = $latestSummary ? $latestSummary->cash_position : 0;
            $latestMonth = $latestSummary ? $latestSummary->month : null;

            $statsArray = [
                'total_months' => $monthsWithData,
                'total_annual_income' => (float) $totalIncome,
                'total_annual_expense' => (float) $totalExpense,
                'total_annual_gross_profit' => (float) $totalGrossProfit,
                'total_annual_net_profit' => (float) $totalNetProfit,
                'avg_monthly_income' => (float) $avgMonthlyIncome,
                'avg_monthly_expense' => (float) $avgMonthlyExpense,
                'avg_monthly_gross_profit' => (float) $avgMonthlyGrossProfit,
                'max_monthly_income' => (float) $maxMonthlyIncome,
                'min_monthly_income' => (float) $minMonthlyIncome,
                'max_monthly_profit' => (float) $maxMonthlyProfit,
                'min_monthly_profit' => (float) $minMonthlyProfit,
                'current_cash_position' => (float) $cashPosition,
                'latest_month' => $latestMonth,
                'latest_year' => $year,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $statsArray,
                'message' => 'Statistik berhasil diambil dari ringkasan'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialSummaryController: Error fetching statistics - ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik keuangan.'
            ], 500);
        }
    }

    /**
     * Get monthly comparison data for charts (auto-calculated from simulations)
     */
    public function getMonthlyComparison(Request $request)
    {
        try {
            Log::info('FinancialSummaryController: Fetching monthly comparison from summaries', ['request' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'business_id' => 'nullable|exists:business_backgrounds,id',
                'business_background_id' => 'nullable|exists:business_backgrounds,id',
                'year' => 'nullable|integer|min:2020|max:2030'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user_id = Auth::id();
            $business_id = $request->business_id ?? $request->business_background_id;
            $year = $request->year ?? date('Y');

            $query = FinancialSummary::where('user_id', $user_id)
                ->where('year', $year);

            if ($business_id) {
                $query->where('business_background_id', $business_id);
            }

            $summaries = $query->get();

            $months = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];

            $completeData = [];

            for ($month = 1; $month <= 12; $month++) {
                $monthSummary = $summaries->firstWhere('month', $month);

                $totalIncome = $monthSummary ? $monthSummary->total_income : 0;
                $totalExpense = $monthSummary ? $monthSummary->total_expense : 0;
                $grossProfit = $monthSummary ? $monthSummary->gross_profit : 0;
                $netProfit = $monthSummary ? $monthSummary->net_profit : 0;

                // Calculate profit margin
                $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

                $completeData[] = [
                    'month' => $month,
                    'month_name' => $months[$month],
                    'total_income' => (float) $totalIncome,
                    'total_expense' => (float) $totalExpense,
                    'gross_profit' => (float) $grossProfit,
                    'net_profit' => (float) $netProfit,
                    'profit_margin' => round($profitMargin, 2)
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $completeData,
                'message' => 'Data perbandingan bulanan berhasil diambil dari ringkasan'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialSummaryController: Error fetching monthly comparison - ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data perbandingan bulanan.'
            ], 500);
        }
    }

    /**
     * Auto-generate financial summary from simulations
     * This will calculate summary from all completed financial simulations
     */
    public function generateFromSimulations(Request $request)
    {
        try {
            Log::info('FinancialSummaryController: Auto-generating summary from simulations', ['request' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'year' => 'required|integer|min:2020|max:2030',
                'month' => 'nullable|integer|between:1,12', // Optional: generate specific month or all months
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user_id = Auth::id();
            $business_id = $request->business_background_id;
            $year = $request->year;
            $specificMonth = $request->month;

            DB::beginTransaction();

            $generatedSummaries = [];
            $monthsToGenerate = $specificMonth ? [$specificMonth] : range(1, 12);

            foreach ($monthsToGenerate as $month) {
                // Query completed simulations for this month
                $simulations = FinancialSimulation::where('user_id', $user_id)
                    ->where('status', 'completed')
                    ->whereYear('simulation_date', $year)
                    ->whereMonth('simulation_date', $month)
                    ->get();

                // Calculate totals
                $totalIncome = $simulations->where('type', 'income')->sum('amount');
                $totalExpense = $simulations->where('type', 'expense')->sum('amount');
                $grossProfit = $totalIncome - $totalExpense;

                // Net profit (simplified: same as gross profit, can add tax/other deductions later)
                $netProfit = $grossProfit;

                // Calculate cash position (accumulative from previous month)
                $previousMonth = $month - 1;
                $previousYear = $year;
                if ($previousMonth < 1) {
                    $previousMonth = 12;
                    $previousYear = $year - 1;
                }

                $previousSummary = FinancialSummary::where('user_id', $user_id)
                    ->where('business_background_id', $business_id)
                    ->where('year', $previousYear)
                    ->where('month', $previousMonth)
                    ->first();

                $cashPosition = ($previousSummary ? $previousSummary->cash_position : 0) + $netProfit;

                // Generate breakdown by category
                $incomeByCategory = [];
                $expenseByCategory = [];

                foreach ($simulations as $simulation) {
                    $categoryName = $simulation->category ? $simulation->category->name : 'Uncategorized';

                    if ($simulation->type === 'income') {
                        if (!isset($incomeByCategory[$categoryName])) {
                            $incomeByCategory[$categoryName] = 0;
                        }
                        $incomeByCategory[$categoryName] += (float) $simulation->amount;
                    } else {
                        if (!isset($expenseByCategory[$categoryName])) {
                            $expenseByCategory[$categoryName] = 0;
                        }
                        $expenseByCategory[$categoryName] += (float) $simulation->amount;
                    }
                }

                // Skip if no transactions in this month
                if ($totalIncome == 0 && $totalExpense == 0) {
                    Log::info("Skipping month $month - no transactions");
                    continue;
                }

                // Create or update summary
                $summary = FinancialSummary::updateOrCreate(
                    [
                        'user_id' => $user_id,
                        'business_background_id' => $business_id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'total_income' => $totalIncome,
                        'total_expense' => $totalExpense,
                        'gross_profit' => $grossProfit,
                        'net_profit' => $netProfit,
                        'cash_position' => $cashPosition,
                        'income_breakdown' => $incomeByCategory,
                        'expense_breakdown' => $expenseByCategory,
                        'notes' => 'Auto-generated from financial simulations on ' . now()->format('Y-m-d H:i:s'),
                    ]
                );

                $generatedSummaries[] = $summary;

                Log::info("Generated summary for month $month", [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'net_profit' => $netProfit
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ringkasan keuangan berhasil di-generate dari simulasi',
                'data' => [
                    'generated_count' => count($generatedSummaries),
                    'summaries' => $generatedSummaries,
                    'year' => $year,
                    'month' => $specificMonth
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinancialSummaryController: Error generating summary from simulations - ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat generate ringkasan keuangan: ' . $e->getMessage()
            ], 500);
        }
    }
}
