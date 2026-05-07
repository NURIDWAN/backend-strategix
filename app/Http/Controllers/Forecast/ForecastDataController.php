<?php

namespace App\Http\Controllers\Forecast;

use App\Http\Controllers\Controller;
use App\Models\Forecast\ForecastData;
use App\Models\Forecast\ForecastResult;
use App\Models\Forecast\ForecastInsight;
use App\Services\ForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForecastDataController extends Controller
{
    protected $forecastService;

    public function __construct(ForecastService $forecastService)
    {
        $this->forecastService = $forecastService;
    }

    /**
     * Display a listing of forecast data
     */
    public function index(Request $request)
    {
        $userId = Auth::user()?->id;
        $query = ForecastData::where('user_id', $userId)
            ->with(['forecastResults', 'insights', 'financialSimulation']);

        // Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Filter by month
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        $forecastData = $query->latest()->paginate(15);

        // Transform collection to include calculated summaries only for Pro users
        $user = Auth::user();
        $isPro = $user->hasPdfProAccess();

        $forecastData->getCollection()->transform(function ($forecast) use ($isPro) {
            if ($isPro) {
            $results = $forecast->forecastResults->sortBy('month')->values()->toArray();

            // Calculate summaries
            $annualSummary = $this->forecastService->calculateAnnualSummary($results);

            // Calculate yearly summary if needed (for yearly forecasts)
            $yearlySummary = [];
            // If it's a yearly forecast (month is null or logic implies) or just always calculate it
            // Based on frontend logic: (!forecast.month || forecast.month === null) implies yearly forecast logic
            // But we can calculate it regardless if it has multiple years data
            if (count($results) > 0) {
                $yearlySummary = $this->forecastService->calculateYearlySummary(
                    $results,
                    $forecast->year,
                    $forecast->month ?? 1
                );
            }

                // Structure data to match frontend expectation (results_with_insights)
                $forecast->results_with_insights = [
                    'results' => $forecast->forecastResults, // usage in frontend: forecast.results_with_insights.results
                    'insights' => $forecast->insights,
                    'annual_summary' => $annualSummary,
                    'yearly_summary' => $yearlySummary,
                ];
            } else {
                // Return flag to frontend
                $forecast->results_with_insights = null;
                $forecast->is_pro_required = true;
            }

            return $forecast;
        });

        return response()->json([
            'success' => true,
            'data' => $forecastData,
        ]);
    }

    /**
     * Store a newly created forecast data
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'financial_simulation_id' => 'nullable|exists:financial_simulations,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'income_sales' => 'required|numeric|min:0',
            'income_other' => 'nullable|numeric|min:0',
            'expense_operational' => 'required|numeric|min:0',
            'expense_other' => 'nullable|numeric|min:0',
            'seasonal_factor' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::user()?->id;
        $validated['seasonal_factor'] = $validated['seasonal_factor'] ?? 1.0;
        $validated['income_other'] = $validated['income_other'] ?? 0;
        $validated['expense_other'] = $validated['expense_other'] ?? 0;

        $forecastData = ForecastData::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Forecast data created successfully',
            'data' => $forecastData->load(['forecastResults', 'insights']),
        ], 201);
    }

    /**
     * Display the specified forecast data
     */
    public function show(ForecastData $forecastData)
    {
        $user = Auth::user();
        if (!$user->hasPdfProAccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Detail Hasil Forecast hanya tersedia untuk pengguna Paket Pro.',
                'is_pro_required' => true
            ], 403);
        }

        $results = $forecastData->forecastResults->sortBy('month')->values()->toArray();

        $annualSummary = $this->forecastService->calculateAnnualSummary($results);

        $yearlySummary = [];
        if (count($results) > 0) {
            $yearlySummary = $this->forecastService->calculateYearlySummary(
                $results,
                $forecastData->year,
                $forecastData->month ?? 1
            );
        }

        $forecastData->results_with_insights = [
            'results' => $forecastData->forecastResults,
            'insights' => $forecastData->insights,
            'annual_summary' => $annualSummary,
            'yearly_summary' => $yearlySummary,
        ];

        return response()->json([
            'success' => true,
            'data' => $forecastData,
        ]);
    }

    /**
     * Update the specified forecast data
     */
    public function update(Request $request, ForecastData $forecastData)
    {
        // Authorize user
        $userId = Auth::user()?->id;
        if ($forecastData->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'month' => 'sometimes|integer|min:1|max:12',
            'year' => 'sometimes|integer|min:2020',
            'income_sales' => 'sometimes|numeric|min:0',
            'income_other' => 'sometimes|numeric|min:0',
            'expense_operational' => 'sometimes|numeric|min:0',
            'expense_other' => 'sometimes|numeric|min:0',
            'seasonal_factor' => 'sometimes|numeric|min:0',
            'notes' => 'sometimes|string',
        ]);

        $forecastData->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Forecast data updated successfully',
            'data' => $forecastData->load(['forecastResults', 'insights']),
        ]);
    }

    /**
     * Delete the specified forecast data
     */
    public function destroy(ForecastData $forecastData)
    {
        // Authorize user
        $userId = Auth::user()?->id;
        if ($forecastData->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete related results and insights
        ForecastResult::where('forecast_data_id', $forecastData->id)->delete();
        ForecastInsight::where('forecast_data_id', $forecastData->id)->delete();
        $forecastData->delete();

        return response()->json([
            'success' => true,
            'message' => 'Forecast data deleted successfully',
        ]);
    }

    /**
     * Auto-import & summarize data dari FinancialSimulation berdasarkan tahun
     */
    public function importFromFinancialSimulation(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020',
            'month' => 'sometimes|integer|min:1|max:12',
        ]);

        $userId = Auth::user()?->id;
        $year = $validated['year'];
        $month = $validated['month'] ?? now()->month;

        try {
            // Get financial simulations untuk user dan tahun ini
            $simulations = \App\Models\ManagementFinancial\FinancialSimulation::where('user_id', $userId)
                ->where('year', $year)
                ->get();

            if ($simulations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No financial simulation data found for the selected year',
                ], 404);
            }

            // Summarize income dan expense
            $incomeSimulations = $simulations->where('type', 'income');
            $expenseSimulations = $simulations->where('type', 'expense');

            $totalIncome = $incomeSimulations->sum('amount');
            $totalExpense = $expenseSimulations->sum('amount');

            // Create forecast data from summarized simulations
            $forecastData = ForecastData::create([
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
                'income_sales' => round($totalIncome * 0.8, 2), // 80% assumed dari penjualan
                'income_other' => round($totalIncome * 0.2, 2), // 20% dari sumber lain
                'expense_operational' => round($totalExpense * 0.7, 2), // 70% operational
                'expense_other' => round($totalExpense * 0.3, 2), // 30% lainnya
                'seasonal_factor' => 1.0,
                'notes' => 'Auto-imported from Financial Simulation for year ' . $year . ' (Income: ' . $totalIncome . ', Expense: ' . $totalExpense . ')',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data imported successfully from Financial Simulations',
                'data' => $forecastData->load(['forecastResults', 'insights']),
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'simulation_count' => $simulations->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available years dari FinancialSimulation
     */
    public function getAvailableSimulationYears()
    {
        $userId = Auth::user()?->id;

        try {
            $years = \App\Models\ManagementFinancial\FinancialSimulation::where('user_id', $userId)
                ->distinct()
                ->pluck('year')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $years,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching years: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate forecast directly from financial simulation
     */
    public function generateFromSimulation(Request $request)
    {
        $validated = $request->validate([
            'financial_simulation_id' => 'required|exists:financial_simulations,id',
            'forecast_months' => 'nullable|integer|min:1|max:120',
            'method' => 'nullable|in:auto,arima,exponential_smoothing',
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $userId = Auth::user()?->id;
        $simulationId = $validated['financial_simulation_id'];
        $forecastMonths = $validated['forecast_months'] ?? 12;
        $method = $validated['method'] ?? 'auto';

        try {
            // Get the simulation
            $simulation = \App\Models\ManagementFinancial\FinancialSimulation::find($simulationId);

            if (!$simulation || $simulation->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Simulation not found or unauthorized',
                ], 404);
            }

            // Extract year from simulation
            $baseYear = $validated['year'] ?? $simulation->year;
            // If month is explicitly null (forecast per tahun), keep it null. Otherwise use provided value or default to 1
            $baseMonth = array_key_exists('month', $validated) ? $validated['month'] : 1;

            // Aggregate all financial simulations by month for the user and year
            $allSimulations = \App\Models\ManagementFinancial\FinancialSimulation::where('user_id', $userId)
                ->where('year', $baseYear)
                ->where('status', 'completed')
                ->get();

            // Group by month and sum income/expense
            $monthlyData = [];
            foreach ($allSimulations as $sim) {
                $month = $sim->simulation_date ? $sim->simulation_date->month : 1;
                if (!isset($monthlyData[$month])) {
                    $monthlyData[$month] = [
                        'income' => 0,
                        'expense' => 0,
                    ];
                }
                if ($sim->type === 'income') {
                    $monthlyData[$month]['income'] += $sim->amount;
                } else {
                    $monthlyData[$month]['expense'] += $sim->amount;
                }
            }

            // Calculate average monthly values from aggregated data
            $totalIncome = array_sum(array_column($monthlyData, 'income')) ?: 0;
            $totalExpense = array_sum(array_column($monthlyData, 'expense')) ?: 0;
            $monthCount = count($monthlyData) ?: 1;

            $avgMonthlyIncome = $totalIncome / $monthCount;
            $avgMonthlyExpense = $totalExpense / $monthCount;

            // Create or update forecast data record using aggregated values
            // Use updateOrCreate to prevent duplicate key constraint violations
            $forecastData = ForecastData::updateOrCreate(
                [
                    'user_id' => $userId,
                    'financial_simulation_id' => $simulationId,
                    'year' => $baseYear,
                    'month' => $baseMonth,
                ],
                [
                    'income_sales' => $avgMonthlyIncome,
                    'income_other' => 0,
                    'expense_operational' => $avgMonthlyExpense,
                    'expense_other' => 0,
                    'seasonal_factor' => 1.0,
                    'notes' => 'Generated from Financial Simulation ID: ' . $simulationId . ' (Aggregated from ' . count($allSimulations) . ' transactions)',
                ]
            );

            // Generate forecast using ForecastService
            $results = $this->forecastService->generateForecast(
                $forecastData,
                $method,
                $forecastMonths
            );

            // Save results to database
            ForecastResult::where('forecast_data_id', $forecastData->id)->delete();
            foreach ($results as $result) {
                ForecastResult::create([
                    'forecast_data_id' => $forecastData->id,
                    'month' => $result['month'],
                    'year' => $result['year'],
                    'forecast_income' => $result['forecast_income'],
                    'forecast_expense' => $result['forecast_expense'],
                    'forecast_profit' => $result['forecast_profit'],
                    'forecast_margin' => $result['forecast_margin'],
                    'confidence_level' => $result['confidence_level'],
                    'method' => $result['method'],
                ]);
            }

            // Generate insights
            $insights = $this->forecastService->generateInsights($forecastData, $results);

            ForecastInsight::where('forecast_data_id', $forecastData->id)->delete();
            foreach ($insights as $insight) {
                ForecastInsight::create([
                    'forecast_data_id' => $forecastData->id,
                    'insight_type' => $insight['insight_type'],
                    'title' => $insight['title'],
                    'description' => $insight['description'],
                    'value' => $insight['value'],
                    'month' => $insight['month'] ?? null,
                    'year' => $insight['year'] ?? null,
                    'severity' => $insight['severity'],
                ]);
            }

            // Calculate yearly summary for multi-year forecasts
            $yearlySummary = $this->forecastService->calculateYearlySummary(
                $results,
                $baseYear,
                $baseMonth ?? 1
            );

            $isPro = $user->hasPdfProAccess();

            return response()->json([
                'success' => true,
                'message' => 'Forecast record created successfully',
                'is_pro' => $isPro,
                'data' => [
                    'results' => $isPro ? $results : [],
                    'insights' => $isPro ? $insights : [],
                    'yearly_summary' => $isPro ? $yearlySummary : [],
                    'forecast_data_id' => $forecastData->id,
                    'message' => $isPro ? null : 'Upgrade ke Paket Pro untuk melihat hasil dan insight.'
                ],
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating forecast: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating forecast: ' . $e->getMessage(),
            ], 500);
        }
    }
}
