<?php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ManagementFinancial\FinancialProjection;
use App\Models\ManagementFinancial\FinancialSimulation;
use Illuminate\Support\Facades\Auth;

class FinancialProjectionController extends Controller
{
    /**
     * Get all financial projections for user
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'scenario_type' => 'nullable|in:optimistic,realistic,pessimistic',
                'base_year' => 'nullable|integer|min:2020|max:2030'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = FinancialProjection::with(['user', 'businessBackground'])
                ->where('user_id', Auth::id())
                ->where('business_background_id', $request->business_background_id);

            if ($request->scenario_type) {
                $query->byScenario($request->scenario_type);
            }

            if ($request->base_year) {
                $query->byBaseYear($request->base_year);
            }

            $projections = $query->orderBy('base_year', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Ensure all projections have calculated metrics
            foreach ($projections as $projection) {
                if (!$projection->npv && !$projection->roi && !$projection->irr) {
                    Log::info("Recalculating metrics for projection ID: {$projection->id} in index");
                    $projection->calculateMetrics();
                }
            }

            // Refresh the collection to get updated values
            $projections = $projections->fresh();

            return response()->json([
                'status' => 'success',
                'data' => $projections,
                'message' => 'Proyeksi keuangan berhasil diambil'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialProjectionController: Error fetching projections - ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data proyeksi'
            ], 500);
        }
    }

    /**
     * Get baseline data from latest simulations
     */
    public function getBaselineData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'base_year' => 'nullable|integer|min:2020|max:2030'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $baseYear = $request->base_year ?? date('Y');

            // Get ALL completed simulations to calculate current cash balance
            $allSimulations = FinancialSimulation::where('user_id', Auth::id())
                ->where('business_background_id', $request->business_background_id)
                ->where('status', 'completed')
                ->get();

            // Calculate accumulated totals from ALL simulations
            $accumulatedIncome = $allSimulations->where('type', 'income')->sum('amount');
            $accumulatedExpense = $allSimulations->where('type', 'expense')->sum('amount');

            // Get initial investment from business background
            $businessBackground = \App\Models\BusinessBackground::where('id', $request->business_background_id)
                ->where('user_id', Auth::id())
                ->first();
            $initialInvestment = $businessBackground ? $businessBackground->initial_capital : 0;

            // Current cash balance = Initial Investment + Total Income - Total Expense
            $currentCashBalance = $initialInvestment + $accumulatedIncome - $accumulatedExpense;

            // Get simulations for base year only (for baseline calculation)
            $simulations = FinancialSimulation::with('category')
                ->where('user_id', Auth::id())
                ->where('business_background_id', $request->business_background_id)
                ->where('year', $baseYear)
                ->where('status', 'completed')
                ->get();

            if ($simulations->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data simulasi untuk tahun ' . $baseYear
                ], 404);
            }

            // Calculate baseline totals (for projection baseline)
            $totalRevenue = $simulations->where('type', 'income')->sum('amount');
            $totalCost = $simulations->where('type', 'expense')->sum('amount');
            $netProfit = $totalRevenue - $totalCost;

            // Get breakdown by category subtype
            $breakdown = [
                'operating_revenue' => $simulations->filter(function ($sim) {
                    return $sim->type === 'income' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'operating_revenue';
                })->sum('amount'),
                'non_operating_revenue' => $simulations->filter(function ($sim) {
                    return $sim->type === 'income' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'non_operating_revenue';
                })->sum('amount'),
                'cogs' => $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'cogs';
                })->sum('amount'),
                'operating_expense' => $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'operating_expense';
                })->sum('amount'),
                'interest_expense' => $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'interest_expense';
                })->sum('amount'),
                'tax_expense' => $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'tax_expense';
                })->sum('amount'),
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'base_year' => $baseYear,
                    'initial_investment' => $initialInvestment,
                    'accumulated_income' => $accumulatedIncome,
                    'accumulated_expense' => $accumulatedExpense,
                    'current_cash_balance' => $currentCashBalance,
                    'total_revenue' => $totalRevenue,
                    'total_cost' => $totalCost,
                    'net_profit' => $netProfit,
                    'breakdown' => $breakdown,
                    'simulation_count' => $simulations->count(),
                    'all_simulation_count' => $allSimulations->count()
                ],
                'message' => 'Data baseline berhasil diambil'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialProjectionController: Error fetching baseline - ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data baseline'
            ], 500);
        }
    }

    /**
     * Create new financial projection
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'projection_name' => 'required|string|max:255',
                'base_year' => 'required|integer|min:2020|max:2030',
                'growth_rate' => 'required|numeric|min:-100|max:500',
                'inflation_rate' => 'required|numeric|min:-100|max:500',
                'discount_rate' => 'required|numeric|min:0|max:100',
                'initial_investment' => 'required|numeric|min:0'
            ], [
                'projection_name.required' => 'Nama proyeksi wajib diisi',
                'base_year.required' => 'Tahun dasar wajib diisi',
                'growth_rate.required' => 'Tingkat pertumbuhan wajib diisi',
                'inflation_rate.required' => 'Tingkat inflasi wajib diisi',
                'discount_rate.required' => 'Discount rate wajib diisi',
                'initial_investment.required' => 'Investasi awal wajib diisi'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Get baseline data first
            $baselineResponse = $this->getBaselineData($request);
            if ($baselineResponse->status() !== 200) {
                DB::rollBack();
                return $baselineResponse;
            }

            $baseline = $baselineResponse->getData()->data;

            // Create projections for 3 scenarios
            $scenarios = [
                'optimistic' => [
                    'growth_rate' => $request->growth_rate + 5, // +5% more optimistic
                    'inflation_rate' => max(0, $request->inflation_rate - 2), // -2% less cost increase
                ],
                'realistic' => [
                    'growth_rate' => $request->growth_rate,
                    'inflation_rate' => $request->inflation_rate,
                ],
                'pessimistic' => [
                    'growth_rate' => max(0, $request->growth_rate - 7), // -7% more conservative
                    'inflation_rate' => $request->inflation_rate + 3, // +3% more cost increase
                ]
            ];

            $createdProjections = [];

            foreach ($scenarios as $scenarioType => $scenarioRates) {
                // Calculate 5-year projections
                $yearlyProjections = [];
                $currentRevenue = $baseline->total_revenue;
                $currentCost = $baseline->total_cost;

                for ($year = 1; $year <= 5; $year++) {
                    $currentRevenue *= (1 + $scenarioRates['growth_rate'] / 100);
                    $currentCost *= (1 + $scenarioRates['inflation_rate'] / 100);
                    $netProfit = $currentRevenue - $currentCost;

                    $yearlyProjections[] = [
                        'year' => $year,
                        'revenue' => round($currentRevenue, 2),
                        'cost' => round($currentCost, 2),
                        'net_profit' => round($netProfit, 2)
                    ];
                }

                // Create projection record
                $projection = FinancialProjection::create([
                    'user_id' => Auth::id(),
                    'business_background_id' => $request->business_background_id,
                    'projection_name' => $request->projection_name . ' - ' . ucfirst($scenarioType),
                    'base_year' => $request->base_year,
                    'scenario_type' => $scenarioType,
                    'growth_rate' => $scenarioRates['growth_rate'],
                    'inflation_rate' => $scenarioRates['inflation_rate'],
                    'discount_rate' => $request->discount_rate,
                    'initial_investment' => $baseline->initial_investment ?? $request->initial_investment,
                    'current_cash_balance' => $baseline->current_cash_balance ?? $request->initial_investment,
                    'accumulated_income' => $baseline->accumulated_income ?? 0,
                    'accumulated_expense' => $baseline->accumulated_expense ?? 0,
                    'base_revenue' => $baseline->total_revenue,
                    'base_cost' => $baseline->total_cost,
                    'base_net_profit' => $baseline->net_profit,
                    'yearly_projections' => $yearlyProjections
                ]);

                // Calculate financial metrics
                $projection->calculateMetrics();

                $createdProjections[] = $projection->fresh();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $createdProjections,
                'message' => 'Proyeksi keuangan berhasil dibuat'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinancialProjectionController: Error creating projection - ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat proyeksi'
            ], 500);
        }
    }

    /**
     * Get specific financial projection
     */
    public function show($id)
    {
        try {
            $projection = FinancialProjection::with(['user', 'businessBackground'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$projection) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proyeksi tidak ditemukan atau unauthorized'
                ], 404);
            }

            // Ensure metrics are calculated (refresh calculation if needed)
            if (!$projection->npv && !$projection->roi && !$projection->irr) {
                Log::info("Recalculating metrics for projection ID: {$projection->id}");
                $projection->calculateMetrics();
                $projection->refresh(); // Reload from database
            }

            return response()->json([
                'status' => 'success',
                'data' => $projection,
                'message' => 'Data proyeksi berhasil diambil'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialProjectionController: Error fetching projection - ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data proyeksi'
            ], 500);
        }
    }

    /**
     * Delete financial projection
     */
    public function destroy(Request $request, $id)
    {
        try {
            $projection = FinancialProjection::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$projection) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proyeksi tidak ditemukan atau unauthorized'
                ], 404);
            }

            $projection->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Proyeksi berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            Log::error('FinancialProjectionController: Error deleting projection - ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus proyeksi'
            ], 500);
        }
    }
}
