<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use App\Models\FinancialPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinancialPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = FinancialPlan::with(['businessBackground', 'user'])
                ->where('user_id', Auth::id());

            if ($request->has('business_background_id')) {
                $query->where('business_background_id', $request->business_background_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('feasibility_status')) {
                $query->where('feasibility_status', $request->feasibility_status);
            }

            if ($request->has('plan_name')) {
                $query->where('plan_name', 'like', '%' . $request->plan_name . '%');
            }

            // Sorting
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $data = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'message' => 'Data rencana keuangan berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data rencana keuangan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'plan_name' => 'required|string|max:255',
                'capital_sources' => 'nullable|array',
                'capital_sources.*.source' => 'required|string|max:100',
                'capital_sources.*.amount' => 'required|numeric|min:0',
                'initial_capex' => 'nullable|array',
                'initial_capex.*.category' => 'required|string|max:100',
                'initial_capex.*.amount' => 'required|numeric|min:0',
                'monthly_opex' => 'nullable|array',
                'monthly_opex.*.category' => 'required|string|max:100',
                'monthly_opex.*.amount' => 'required|numeric|min:0',
                'sales_projections' => 'nullable|array',
                'sales_projections.*.product' => 'required|string|max:200',
                'sales_projections.*.price' => 'required|numeric|min:0',
                'sales_projections.*.volume' => 'required|numeric|min:0',
                'cash_flow_simulation' => 'nullable|array',
                'cash_flow_simulation.*.date' => 'required|date',
                'cash_flow_simulation.*.type' => 'required|in:income,expense',
                'cash_flow_simulation.*.category' => 'required|string|max:100',
                'cash_flow_simulation.*.description' => 'required|string|max:500',
                'cash_flow_simulation.*.amount' => 'required|numeric',
                'cash_flow_simulation.*.payment_method' => 'required|string|max:50',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'interest_expense' => 'nullable|numeric|min:0',
                'plan_duration_months' => 'nullable|integer|min:1|max:60',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|in:draft,active,completed'
            ], [
                'user_id.required' => 'User ID wajib diisi',
                'user_id.exists' => 'User tidak ditemukan',
                'business_background_id.required' => 'Bisnis wajib dipilih',
                'business_background_id.exists' => 'Bisnis tidak ditemukan',
                'plan_name.required' => 'Nama rencana wajib diisi',
                'capital_sources.*.source.required' => 'Sumber modal harus diisi',
                'capital_sources.*.amount.required' => 'Nominal sumber modal harus diisi',
                'initial_capex.*.category.required' => 'Kategori CapEx harus diisi',
                'initial_capex.*.amount.required' => 'Nominal CapEx harus diisi',
                'monthly_opex.*.category.required' => 'Kategori biaya operasional harus diisi',
                'monthly_opex.*.amount.required' => 'Nominal biaya operasional harus diisi',
                'sales_projections.*.product.required' => 'Nama produk/jasa harus diisi',
                'sales_projections.*.price.required' => 'Harga jual harus diisi',
                'sales_projections.*.volume.required' => 'Volume penjualan harus diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $planData = $validator->validated();
            $planData['user_id'] = Auth::id();

            // Set default values
            $planData['tax_rate'] = $planData['tax_rate'] ?? 10;
            $planData['interest_expense'] = $planData['interest_expense'] ?? 0;
            $planData['plan_duration_months'] = $planData['plan_duration_months'] ?? 12;
            $planData['status'] = $planData['status'] ?? 'draft';

            // Create financial plan
            $plan = FinancialPlan::create($planData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $plan->load(['businessBackground', 'user']),
                'message' => 'Rencana keuangan berhasil dibuat'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating financial plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat rencana keuangan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $plan = FinancialPlan::with(['businessBackground', 'user'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $plan,
                'message' => 'Data rencana keuangan berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rencana keuangan tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'plan_name' => 'sometimes|required|string|max:255',
                'capital_sources' => 'nullable|array',
                'capital_sources.*.source' => 'required|string|max:100',
                'capital_sources.*.amount' => 'required|numeric|min:0',
                'initial_capex' => 'nullable|array',
                'initial_capex.*.category' => 'required|string|max:100',
                'initial_capex.*.amount' => 'required|numeric|min:0',
                'monthly_opex' => 'nullable|array',
                'monthly_opex.*.category' => 'required|string|max:100',
                'monthly_opex.*.amount' => 'required|numeric|min:0',
                'sales_projections' => 'nullable|array',
                'sales_projections.*.product' => 'required|string|max:200',
                'sales_projections.*.price' => 'required|numeric|min:0',
                'sales_projections.*.volume' => 'required|numeric|min:0',
                'cash_flow_simulation' => 'nullable|array',
                'cash_flow_simulation.*.date' => 'required|date',
                'cash_flow_simulation.*.type' => 'required|in:income,expense',
                'cash_flow_simulation.*.category' => 'required|string|max:100',
                'cash_flow_simulation.*.description' => 'required|string|max:500',
                'cash_flow_simulation.*.amount' => 'required|numeric',
                'cash_flow_simulation.*.payment_method' => 'required|string|max:50',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'interest_expense' => 'nullable|numeric|min:0',
                'plan_duration_months' => 'nullable|integer|min:1|max:60',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|in:draft,active,completed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $plan->update($validator->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $plan->load(['businessBackground', 'user']),
                'message' => 'Rencana keuangan berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating financial plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui rencana keuangan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $plan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Rencana keuangan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting financial plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus rencana keuangan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk mendapatkan summary keuangan
     */
    public function getFinancialSummary(Request $request)
    {
        try {
            $query = FinancialPlan::with('businessBackground')
                ->where('user_id', Auth::id());

            if ($request->has('business_background_id')) {
                $query->where('business_background_id', $request->business_background_id);
            }

            $plans = $query->get();

            $totalPlans = $plans->count();
            $totalInitialCapital = $plans->sum('total_initial_capital');
            $totalCapex = $plans->sum('total_capex');
            $totalMonthlyOpex = $plans->sum('total_monthly_opex');
            $totalMonthlyIncome = $plans->sum('total_monthly_income');
            $totalNetProfit = $plans->sum('net_profit');
            $averageRoi = $plans->avg('roi_percentage');

            $feasibilityDistribution = $plans->groupBy('feasibility_status')->map->count();

            // Hitung metrik tambahan
            $profitablePlans = $plans->where('net_profit', '>', 0)->count();
            $highRoiPlans = $plans->where('roi_percentage', '>=', 20)->count();
            $quickPaybackPlans = $plans->where('payback_period', '<=', 18)->count();

            $summary = [
                'total_plans' => $totalPlans,
                'total_initial_capital' => $totalInitialCapital,
                'total_capex' => $totalCapex,
                'total_monthly_opex' => $totalMonthlyOpex,
                'total_monthly_income' => $totalMonthlyIncome,
                'total_net_profit' => $totalNetProfit,
                'average_roi' => round($averageRoi, 2),
                'average_profit_margin' => $totalMonthlyIncome > 0 ? round(($totalNetProfit / $totalMonthlyIncome) * 100, 2) : 0,
                'feasibility_distribution' => $feasibilityDistribution,
                'performance_metrics' => [
                    'profitable_plans' => $profitablePlans,
                    'high_roi_plans' => $highRoiPlans,
                    'quick_payback_plans' => $quickPaybackPlans,
                    'profitability_rate' => $totalPlans > 0 ? round(($profitablePlans / $totalPlans) * 100, 2) : 0
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $summary,
                'message' => 'Summary keuangan berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting financial summary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil summary keuangan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk simulasi arus kas dengan filter
     */
    public function getCashFlowSimulation($id, Request $request)
    {
        try {
            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $year = $request->get('year', date('Y'));
            $month = $request->get('month');
            $category = $request->get('category');
            $type = $request->get('type');

            $cashFlowData = $plan->cash_flow_simulation ?? [];
            $filteredData = [];

            foreach ($cashFlowData as $flow) {
                $flowDate = Carbon::parse($flow['date']);

                // Filter by year
                if ($flowDate->year != $year) {
                    continue;
                }

                // Filter by month
                if ($month && $flowDate->month != $month) {
                    continue;
                }

                // Filter by category
                if ($category && $flow['category'] != $category) {
                    continue;
                }

                // Filter by type
                if ($type && $flow['type'] != $type) {
                    continue;
                }

                $filteredData[] = $flow;
            }

            // Sort by date
            usort($filteredData, function ($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            // Calculate totals
            $totals = [
                'total_income' => 0,
                'total_expense' => 0,
                'net_cash_flow' => 0
            ];

            foreach ($filteredData as $flow) {
                if ($flow['type'] === 'income') {
                    $totals['total_income'] += $flow['amount'];
                } else {
                    $totals['total_expense'] += $flow['amount'];
                }
            }

            $totals['net_cash_flow'] = $totals['total_income'] - $totals['total_expense'];

            // Calculate monthly summary
            $monthlySummary = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlySummary[$m] = [
                    'income' => 0,
                    'expense' => 0,
                    'net_cash_flow' => 0
                ];
            }

            foreach ($cashFlowData as $flow) {
                $flowDate = Carbon::parse($flow['date']);
                if ($flowDate->year == $year) {
                    $month = $flowDate->month;
                    $amount = floatval($flow['amount']);

                    if ($flow['type'] === 'income') {
                        $monthlySummary[$month]['income'] += $amount;
                    } else {
                        $monthlySummary[$month]['expense'] += $amount;
                    }

                    $monthlySummary[$month]['net_cash_flow'] =
                        $monthlySummary[$month]['income'] - $monthlySummary[$month]['expense'];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cash_flows' => $filteredData,
                    'totals' => $totals,
                    'monthly_summary' => $monthlySummary,
                    'filters' => [
                        'year' => $year,
                        'month' => $month,
                        'category' => $category,
                        'type' => $type
                    ]
                ],
                'message' => 'Data simulasi arus kas berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cash flow simulation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil simulasi arus kas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk analisis kelayakan detail
     */
    public function getFeasibilityAnalysis($id)
    {
        try {
            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Generate recommendations based on metrics
            $recommendations = $this->generateRecommendations($plan);

            // Calculate risk assessment
            $riskAssessment = $this->calculateRiskAssessment($plan);

            $analysis = [
                'roi_percentage' => $plan->roi_percentage,
                'payback_period' => $plan->payback_period,
                'bep_amount' => $plan->bep_amount,
                'profit_margin' => $plan->profit_margin,
                'feasibility_status' => $plan->feasibility_status,
                'feasibility_notes' => $plan->feasibility_notes,
                'recommendations' => $recommendations,
                'risk_assessment' => $riskAssessment,
                'key_metrics' => [
                    'roi_rating' => $this->getMetricRating($plan->roi_percentage, [5, 15, 25]), // Poor, Fair, Good, Excellent
                    'payback_rating' => $this->getMetricRating($plan->payback_period, [36, 24, 12], true), // Reverse scale
                    'margin_rating' => $this->getMetricRating($plan->profit_margin, [5, 10, 20]),
                    'overall_score' => $this->calculateOverallScore($plan)
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $analysis,
                'message' => 'Analisis kelayakan berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting feasibility analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil analisis kelayakan: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Method untuk data chart
     */
    public function getChartData($id, Request $request)
    {
        try {
            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $chartType = $request->get('chart_type', 'profit_loss');
            $timeRange = $request->get('time_range', 'monthly');

            $data = [];

            switch ($chartType) {
                case 'profit_loss':
                    $data = $this->getProfitLossChartData($plan);
                    break;

                case 'capital_structure':
                    $data = $this->getCapitalStructureChartData($plan);
                    break;

                case 'cash_flow':
                    $data = $this->getCashFlowChartData($plan, $timeRange);
                    break;

                case 'revenue_streams':
                    $data = $this->getRevenueStreamsChartData($plan);
                    break;

                case 'expense_breakdown':
                    $data = $this->getExpenseBreakdownChartData($plan);
                    break;

                case 'feasibility':
                    $data = $this->getFeasibilityChartData($plan);
                    break;

                case 'monthly_trends':
                    $data = $this->getMonthlyTrendsChartData($plan);
                    break;

                case 'performance_indicators':
                    $data = $this->getPerformanceIndicatorsChartData($plan);
                    break;
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'message' => 'Data chart berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting chart data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data chart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk dashboard charts (multiple plans)
     */
    public function getDashboardCharts(Request $request)
    {
        try {
            $query = FinancialPlan::with('businessBackground')
                ->where('user_id', Auth::id());

            $plans = $query->get();

            $dashboardData = [
                'profit_trend' => $this->getDashboardProfitTrendData($plans),
                'roi_comparison' => $this->getDashboardRoiComparisonData($plans),
                'feasibility_distribution' => $this->getDashboardFeasibilityDistributionData($plans),
                'capital_efficiency' => $this->getDashboardCapitalEfficiencyData($plans),
                'performance_metrics' => $this->getDashboardPerformanceMetrics($plans)
            ];

            return response()->json([
                'status' => 'success',
                'data' => $dashboardData,
                'message' => 'Data dashboard charts berhasil diambil'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting dashboard charts: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk forecast/proyeksi
     */
    public function getFinancialForecast($id, Request $request)
    {
        try {
            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $period = $request->get('period', 12); // 12 bulan default

            $forecastData = $this->generateFinancialForecast($plan, $period);

            return response()->json([
                'status' => 'success',
                'data' => $forecastData,
                'message' => 'Data forecast berhasil di-generate'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting financial forecast: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal generate forecast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk analisis sensitivitas
     */
    public function getSensitivityAnalysis($id, Request $request)
    {
        try {
            $plan = FinancialPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $scenarios = $request->get('scenarios', ['optimistic', 'pessimistic', 'base']);

            $sensitivityAnalysis = $this->generateSensitivityAnalysis($plan, $scenarios);

            return response()->json([
                'status' => 'success',
                'data' => $sensitivityAnalysis,
                'message' => 'Analisis sensitivitas berhasil di-generate'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sensitivity analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal generate analisis sensitivitas: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Helper method untuk generate rekomendasi
     */
    private function generateRecommendations(FinancialPlan $plan)
    {
        $recommendations = [];

        // ROI recommendations
        if ($plan->roi_percentage < 10) {
            $recommendations[] = [
                'category' => 'ROI',
                'priority' => 'high',
                'message' => 'ROI terlalu rendah. Pertimbangkan untuk meningkatkan efisiensi operasional atau meninjau ulang strategi pricing.',
                'suggestion' => 'Evaluasi biaya operasional dan pertimbangkan peningkatan volume penjualan'
            ];
        } elseif ($plan->roi_percentage < 20) {
            $recommendations[] = [
                'category' => 'ROI',
                'priority' => 'medium',
                'message' => 'ROI cukup baik namun masih ada ruang untuk improvement.',
                'suggestion' => 'Optimasi proses operasional untuk meningkatkan margin'
            ];
        }

        // Payback period recommendations
        if ($plan->payback_period > 36) {
            $recommendations[] = [
                'category' => 'Payback Period',
                'priority' => 'high',
                'message' => 'Periode balik modal terlalu panjang (>3 tahun). Risiko tinggi untuk investasi.',
                'suggestion' => 'Pertimbangkan strategi untuk mempercepat ROI atau kurangi modal awal'
            ];
        } elseif ($plan->payback_period > 24) {
            $recommendations[] = [
                'category' => 'Payback Period',
                'priority' => 'medium',
                'message' => 'Periode balik modal cukup panjang (2-3 tahun).',
                'suggestion' => 'Evaluasi strategi marketing untuk meningkatkan penjualan'
            ];
        }

        // Profit margin recommendations
        if ($plan->profit_margin < 5) {
            $recommendations[] = [
                'category' => 'Profit Margin',
                'priority' => 'high',
                'message' => 'Margin profit sangat rendah. Risiko operasional tinggi.',
                'suggestion' => 'Tinjau struktur biaya dan pertimbangkan penyesuaian harga'
            ];
        } elseif ($plan->profit_margin < 15) {
            $recommendations[] = [
                'category' => 'Profit Margin',
                'priority' => 'medium',
                'message' => 'Margin profit perlu ditingkatkan untuk sustainability jangka panjang.',
                'suggestion' => 'Optimasi biaya variabel dan cari supplier yang lebih kompetitif'
            ];
        }

        // Operational efficiency
        if ($plan->total_monthly_opex > $plan->total_monthly_income * 0.8) {
            $recommendations[] = [
                'category' => 'Efisiensi Operasional',
                'priority' => 'high',
                'message' => 'Biaya operasional terlalu tinggi dibandingkan pendapatan.',
                'suggestion' => 'Lakukan audit biaya dan identifikasi area untuk efisiensi'
            ];
        }

        // Capital structure
        $loanPercentage = 0;
        $capitalSources = $plan->capital_sources ?? [];
        foreach ($capitalSources as $source) {
            if (strpos(strtolower($source['source']), 'pinjaman') !== false) {
                $loanPercentage = $source['percentage'];
                break;
            }
        }

        if ($loanPercentage > 70) {
            $recommendations[] = [
                'category' => 'Struktur Modal',
                'priority' => 'high',
                'message' => 'Ketergantungan pada pinjaman terlalu tinggi. Risiko finansial besar.',
                'suggestion' => 'Pertimbangkan equity financing atau cari investor tambahan'
            ];
        }

        // If no major issues
        if (empty($recommendations)) {
            $recommendations[] = [
                'category' => 'Kinerja Keseluruhan',
                'priority' => 'low',
                'message' => 'Model bisnis menunjukkan kesehatan finansial yang baik.',
                'suggestion' => 'Pertahankan strategi saat ini dan monitor performa secara berkala'
            ];
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
        });

        return $recommendations;
    }

    /**
     * Calculate risk assessment
     */
    private function calculateRiskAssessment(FinancialPlan $plan)
    {
        $riskScore = 0;
        $riskFactors = [];

        // ROI Risk
        if ($plan->roi_percentage < 10) {
            $riskScore += 3;
            $riskFactors[] = 'ROI sangat rendah';
        } elseif ($plan->roi_percentage < 15) {
            $riskScore += 2;
            $riskFactors[] = 'ROI rendah';
        } elseif ($plan->roi_percentage < 20) {
            $riskScore += 1;
            $riskFactors[] = 'ROI moderat';
        }

        // Payback Period Risk
        if ($plan->payback_period > 36) {
            $riskScore += 3;
            $riskFactors[] = 'Periode balik modal sangat panjang';
        } elseif ($plan->payback_period > 24) {
            $riskScore += 2;
            $riskFactors[] = 'Periode balik modal panjang';
        } elseif ($plan->payback_period > 18) {
            $riskScore += 1;
            $riskFactors[] = 'Periode balik modal moderat';
        }

        // Profit Margin Risk
        if ($plan->profit_margin < 5) {
            $riskScore += 3;
            $riskFactors[] = 'Margin profit sangat rendah';
        } elseif ($plan->profit_margin < 10) {
            $riskScore += 2;
            $riskFactors[] = 'Margin profit rendah';
        } elseif ($plan->profit_margin < 15) {
            $riskScore += 1;
            $riskFactors[] = 'Margin profit moderat';
        }

        // Operational Efficiency Risk
        $opexRatio = $plan->total_monthly_income > 0 ? $plan->total_monthly_opex / $plan->total_monthly_income : 1;
        if ($opexRatio > 0.8) {
            $riskScore += 3;
            $riskFactors[] = 'Biaya operasional sangat tinggi';
        } elseif ($opexRatio > 0.7) {
            $riskScore += 2;
            $riskFactors[] = 'Biaya operasional tinggi';
        } elseif ($opexRatio > 0.6) {
            $riskScore += 1;
            $riskFactors[] = 'Biaya operasional moderat';
        }

        // Determine risk level
        if ($riskScore >= 8) {
            $riskLevel = 'Tinggi';
        } elseif ($riskScore >= 5) {
            $riskLevel = 'Sedang';
        } else {
            $riskLevel = 'Rendah';
        }

        return [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'mitigation_suggestions' => $this->generateRiskMitigationSuggestions($riskFactors)
        ];
    }

    /**
     * Generate risk mitigation suggestions
     */
    private function generateRiskMitigationSuggestions(array $riskFactors)
    {
        $suggestions = [];
        $mitigationMap = [
            'ROI sangat rendah' => 'Tingkatkan efisiensi operasional dan evaluasi strategi pricing',
            'ROI rendah' => 'Optimasi biaya dan tingkatkan volume penjualan',
            'Periode balik modal sangat panjang' => 'Pertimbangkan reduksi modal awal atau akselerasi pendapatan',
            'Periode balik modal panjang' => 'Fokus pada strategi marketing untuk meningkatkan penjualan',
            'Margin profit sangat rendah' => 'Lakukan restrukturisasi biaya dan tinjau harga jual',
            'Margin profit rendah' => 'Negosiasi dengan supplier dan optimasi inventory',
            'Biaya operasional sangat tinggi' => 'Lakukan audit biaya komprehensif',
            'Biaya operasional tinggi' => 'Identifikasi dan eliminasi pemborosan'
        ];

        foreach ($riskFactors as $factor) {
            if (isset($mitigationMap[$factor])) {
                $suggestions[] = $mitigationMap[$factor];
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Get metric rating
     */
    private function getMetricRating($value, array $thresholds, $reverse = false)
    {
        if ($reverse) {
            if ($value <= $thresholds[2]) return 'Excellent';
            if ($value <= $thresholds[1]) return 'Good';
            if ($value <= $thresholds[0]) return 'Fair';
            return 'Poor';
        } else {
            if ($value >= $thresholds[2]) return 'Excellent';
            if ($value >= $thresholds[1]) return 'Good';
            if ($value >= $thresholds[0]) return 'Fair';
            return 'Poor';
        }
    }

    /**
     * Calculate overall score
     */
    private function calculateOverallScore(FinancialPlan $plan)
    {
        $score = 0;

        // ROI Score (30%)
        $roiScore = min($plan->roi_percentage / 25 * 30, 30);

        // Payback Score (25%)
        $paybackScore = max(0, 25 - ($plan->payback_period / 48 * 25));

        // Profit Margin Score (25%)
        $marginScore = min($plan->profit_margin / 25 * 25, 25);

        // Operational Efficiency Score (20%)
        $opexRatio = $plan->total_monthly_income > 0 ? $plan->total_monthly_opex / $plan->total_monthly_income : 1;
        $efficiencyScore = max(0, 20 - ($opexRatio * 20));

        $score = $roiScore + $paybackScore + $marginScore + $efficiencyScore;

        return [
            'total_score' => round($score, 1),
            'max_score' => 100,
            'grade' => $this->getScoreGrade($score),
            'breakdown' => [
                'roi_score' => round($roiScore, 1),
                'payback_score' => round($paybackScore, 1),
                'margin_score' => round($marginScore, 1),
                'efficiency_score' => round($efficiencyScore, 1)
            ]
        ];
    }

    private function getScoreGrade($score)
    {
        if ($score >= 85) return 'A';
        if ($score >= 75) return 'B';
        if ($score >= 65) return 'C';
        if ($score >= 50) return 'D';
        return 'E';
    }

    // ==================== CHART DATA METHODS ====================

    private function getProfitLossChartData($plan)
    {
        return [
            'labels' => ['Pendapatan', 'Biaya Operasional', 'Laba Kotor', 'Laba Bersih'],
            'values' => [
                $plan->total_monthly_income,
                $plan->total_monthly_opex,
                $plan->gross_profit,
                $plan->net_profit
            ],
            'colors' => ['#10b981', '#ef4444', '#f59e0b', '#4f46e5']
        ];
    }

    private function getCapitalStructureChartData($plan)
    {
        $capitalSources = $plan->capital_sources ?? [];
        return [
            'labels' => array_column($capitalSources, 'source'),
            'values' => array_column($capitalSources, 'amount'),
            'percentages' => array_column($capitalSources, 'percentage'),
            'colors' => ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4']
        ];
    }

    private function getCashFlowChartData($plan, $timeRange)
    {
        $monthlyData = $plan->getMonthlyCashFlow();

        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'income' => array_column($monthlyData, 'income'),
            'expense' => array_column($monthlyData, 'expense'),
            'net_flow' => array_column($monthlyData, 'net_cash_flow'),
            'colors' => ['#10b981', '#ef4444', '#4f46e5']
        ];
    }

    private function getRevenueStreamsChartData($plan)
    {
        $salesData = $plan->sales_projections ?? [];
        return [
            'labels' => array_column($salesData, 'product'),
            'values' => array_column($salesData, 'monthly_income'),
            'colors' => ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
        ];
    }

    private function getExpenseBreakdownChartData($plan)
    {
        $opexData = $plan->monthly_opex ?? [];
        return [
            'labels' => array_column($opexData, 'category'),
            'values' => array_column($opexData, 'amount'),
            'colors' => ['#ef4444', '#f59e0b', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316']
        ];
    }

    private function getFeasibilityChartData($plan)
    {
        return [
            'labels' => ['ROI', 'Payback Period', 'Profit Margin'],
            'values' => [
                $plan->roi_percentage,
                $plan->payback_period,
                $plan->profit_margin
            ],
            'targets' => [25, 24, 20],
            'colors' => ['#4f46e5', '#f59e0b', '#10b981']
        ];
    }

    private function getMonthlyTrendsChartData($plan)
    {
        // Generate 12 months of trend data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $trendData = [];

        $baseIncome = $plan->total_monthly_income;
        $baseOpex = $plan->total_monthly_opex;

        foreach ($months as $index => $month) {
            // Simulate some variation
            $incomeVariation = $baseIncome * (0.9 + (mt_rand(0, 20) / 100));
            $opexVariation = $baseOpex * (0.95 + (mt_rand(0, 10) / 100));

            $trendData[] = [
                'month' => $month,
                'income' => $incomeVariation,
                'expense' => $opexVariation,
                'profit' => $incomeVariation - $opexVariation
            ];
        }

        return $trendData;
    }

    private function getPerformanceIndicatorsChartData($plan)
    {
        return [
            'labels' => ['ROI', 'Profit Margin', 'Operational Efficiency', 'Liquidity'],
            'current' => [
                $plan->roi_percentage,
                $plan->profit_margin,
                $plan->total_monthly_income > 0 ? (($plan->total_monthly_income - $plan->total_monthly_opex) / $plan->total_monthly_income) * 100 : 0,
                75 // Mock liquidity score
            ],
            'industry_average' => [18, 15, 25, 70],
            'colors' => ['#4f46e5', '#10b981', '#f59e0b', '#06b6d4']
        ];
    }

    // ==================== DASHBOARD CHART METHODS ====================

    private function getDashboardProfitTrendData($plans)
    {
        return $plans->map(function ($plan) {
            return [
                'name' => $plan->plan_name,
                'pendapatan' => $plan->total_monthly_income,
                'laba' => $plan->net_profit,
                'fullName' => $plan->plan_name
            ];
        })->toArray();
    }

    private function getDashboardRoiComparisonData($plans)
    {
        return $plans->map(function ($plan) {
            return [
                'name' => $plan->plan_name,
                'roi' => $plan->roi_percentage,
                'fullName' => $plan->plan_name
            ];
        })->sortByDesc('roi')->values()->toArray();
    }

    private function getDashboardFeasibilityDistributionData($plans)
    {
        $distribution = $plans->groupBy('feasibility_status')->map->count();

        return $distribution->map(function ($count, $status) {
            return [
                'name' => $status,
                'value' => $count
            ];
        })->values()->toArray();
    }

    private function getDashboardCapitalEfficiencyData($plans)
    {
        return $plans->map(function ($plan) {
            $efficiency = $plan->total_initial_capital > 0 ?
                ($plan->net_profit * 12) / $plan->total_initial_capital * 100 : 0;

            return [
                'name' => $plan->plan_name,
                'efficiency' => $efficiency,
                'fullName' => $plan->plan_name
            ];
        })->sortByDesc('efficiency')->values()->toArray();
    }

    private function getDashboardPerformanceMetrics($plans)
    {
        $totalPlans = $plans->count();

        return [
            'total_plans' => $totalPlans,
            'profitable_plans' => $plans->where('net_profit', '>', 0)->count(),
            'high_performance_plans' => $plans->where('roi_percentage', '>=', 20)->count(),
            'average_roi' => $plans->avg('roi_percentage'),
            'average_payback' => $plans->avg('payback_period'),
            'success_rate' => $totalPlans > 0 ?
                ($plans->where('feasibility_status', 'Layak')->count() / $totalPlans) * 100 : 0
        ];
    }

    // ==================== FORECAST & SENSITIVITY METHODS ====================

    private function generateFinancialForecast(FinancialPlan $plan, $period)
    {
        $forecast = [];
        $currentDate = Carbon::now();

        $baseIncome = $plan->total_monthly_income;
        $baseOpex = $plan->total_monthly_opex;
        $growthRate = 0.05; // 5% monthly growth assumption

        for ($i = 0; $i < $period; $i++) {
            $month = $currentDate->copy()->addMonths($i);
            $monthGrowth = pow(1 + $growthRate, $i);

            $projectedIncome = $baseIncome * $monthGrowth;
            $projectedOpex = $baseOpex * (1 + ($growthRate * 0.8 * $i)); // Opex grows slower

            $forecast[] = [
                'month' => $month->format('M Y'),
                'projected_income' => $projectedIncome,
                'projected_opex' => $projectedOpex,
                'projected_profit' => $projectedIncome - $projectedOpex,
                'cumulative_profit' => array_reduce(
                    array_slice($forecast, 0, $i),
                    function ($carry, $item) {
                        return $carry + $item['projected_profit'];
                    },
                    0
                ) + ($projectedIncome - $projectedOpex)
            ];
        }

        return $forecast;
    }

    private function generateSensitivityAnalysis(FinancialPlan $plan, $scenarios)
    {
        $analysis = [];
        $baseRoi = $plan->roi_percentage;

        foreach ($scenarios as $scenario) {
            switch ($scenario) {
                case 'optimistic':
                    $incomeMultiplier = 1.2;
                    $opexMultiplier = 0.9;
                    break;
                case 'pessimistic':
                    $incomeMultiplier = 0.8;
                    $opexMultiplier = 1.2;
                    break;
                default: // base
                    $incomeMultiplier = 1.0;
                    $opexMultiplier = 1.0;
            }

            $adjustedIncome = $plan->total_monthly_income * $incomeMultiplier;
            $adjustedOpex = $plan->total_monthly_opex * $opexMultiplier;
            $adjustedProfit = $adjustedIncome - $adjustedOpex;
            $adjustedRoi = $plan->total_initial_capital > 0 ?
                ($adjustedProfit * 12) / $plan->total_initial_capital * 100 : 0;

            $analysis[$scenario] = [
                'income' => $adjustedIncome,
                'opex' => $adjustedOpex,
                'profit' => $adjustedProfit,
                'roi' => $adjustedRoi,
                'roi_change' => $adjustedRoi - $baseRoi,
                'sensitivity' => abs($adjustedRoi - $baseRoi) / $baseRoi * 100
            ];
        }

        return $analysis;
    }

    // ==================== REPORT GENERATION METHODS ====================

    private function generateAdditionalAnalysis(FinancialPlan $plan)
    {
        return [
            'break_even_analysis' => $this->calculateBreakEvenAnalysis($plan),
            'cash_flow_analysis' => $this->calculateCashFlowAnalysis($plan),
            'investment_analysis' => $this->calculateInvestmentAnalysis($plan)
        ];
    }

    private function generateExecutiveSummary(FinancialPlan $plan)
    {
        $profitability = $plan->net_profit > 0 ? 'Menguntungkan' : 'Tidak Menguntungkan';
        $riskLevel = $this->calculateRiskAssessment($plan)['risk_level'];

        return [
            'overview' => "Rencana keuangan '{$plan->plan_name}' menunjukkan {$profitability} dengan ROI {$plan->roi_percentage}%.",
            'key_strengths' => $this->identifyStrengths($plan),
            'key_concerns' => $this->identifyConcerns($plan),
            'recommendation' => $riskLevel === 'Tinggi' ? 'Perlu evaluasi mendalam' : 'Layak untuk dipertimbangkan',
            'next_steps' => $this->suggestNextSteps($plan)
        ];
    }

    private function calculateBreakEvenAnalysis(FinancialPlan $plan)
    {
        $fixedCosts = $plan->total_monthly_opex;
        $avgPrice = $this->calculateAveragePrice($plan);
        $avgVariableCost = $this->calculateAverageVariableCost($plan);

        $contributionMargin = $avgPrice - $avgVariableCost;
        $breakEvenUnits = $contributionMargin > 0 ? $fixedCosts / $contributionMargin : 0;
        $breakEvenRevenue = $breakEvenUnits * $avgPrice;

        return [
            'break_even_units' => $breakEvenUnits,
            'break_even_revenue' => $breakEvenRevenue,
            'contribution_margin' => $contributionMargin,
            'margin_of_safety' => $plan->total_monthly_income > 0 ?
                (($plan->total_monthly_income - $breakEvenRevenue) / $plan->total_monthly_income) * 100 : 0
        ];
    }

    private function calculateCashFlowAnalysis(FinancialPlan $plan)
    {
        $operatingCashFlow = $plan->net_profit;
        $investingCashFlow = -$plan->total_capex; // Negative because it's outflow
        $financingCashFlow = $plan->total_initial_capital;

        $netCashFlow = $operatingCashFlow + $investingCashFlow + $financingCashFlow;

        return [
            'operating_cash_flow' => $operatingCashFlow,
            'investing_cash_flow' => $investingCashFlow,
            'financing_cash_flow' => $financingCashFlow,
            'net_cash_flow' => $netCashFlow,
            'cash_flow_adequacy' => $operatingCashFlow > 0 ? 'Adekuat' : 'Tidak Adekuat'
        ];
    }

    private function calculateInvestmentAnalysis(FinancialPlan $plan)
    {
        $npv = $this->calculateNPV($plan);
        $irr = $this->calculateIRR($plan);

        return [
            'net_present_value' => $npv,
            'internal_rate_of_return' => $irr,
            'profitability_index' => $plan->total_initial_capital > 0 ? $npv / $plan->total_initial_capital : 0,
            'investment_rating' => $npv > 0 ? 'Layak' : 'Tidak Layak'
        ];
    }

    private function calculateNPV(FinancialPlan $plan)
    {
        // Simplified NPV calculation
        $discountRate = 0.1; // 10%
        $cashFlows = [];

        // Year 0: Initial investment (negative)
        $cashFlows[] = -$plan->total_initial_capital;

        // Years 1-5: Annual profit
        for ($i = 1; $i <= 5; $i++) {
            $cashFlows[] = $plan->net_profit * 12 / pow(1 + $discountRate, $i);
        }

        return array_sum($cashFlows);
    }

    private function calculateIRR(FinancialPlan $plan)
    {
        // Simplified IRR calculation
        $annualProfit = $plan->net_profit * 12;
        if ($plan->total_initial_capital > 0) {
            return ($annualProfit / $plan->total_initial_capital) * 100;
        }
        return 0;
    }

    private function calculateAveragePrice(FinancialPlan $plan)
    {
        $salesItems = $plan->sales_projections ?? [];
        if (empty($salesItems)) return 0;

        $totalPrice = 0;
        foreach ($salesItems as $item) {
            $totalPrice += floatval($item['price'] ?? 0);
        }

        return $totalPrice / count($salesItems);
    }

    private function calculateAverageVariableCost(FinancialPlan $plan)
    {
        // Simplified - assume 60% of opex is variable costs
        return $plan->total_monthly_opex * 0.6 / ($this->getTotalSalesVolume($plan) ?: 1);
    }

    private function getTotalSalesVolume(FinancialPlan $plan)
    {
        $salesItems = $plan->sales_projections ?? [];
        $totalVolume = 0;

        foreach ($salesItems as $item) {
            $totalVolume += floatval($item['volume'] ?? 0);
        }

        return $totalVolume;
    }

    private function identifyStrengths(FinancialPlan $plan)
    {
        $strengths = [];

        if ($plan->roi_percentage >= 20) {
            $strengths[] = 'ROI yang kompetitif';
        }

        if ($plan->profit_margin >= 15) {
            $strengths[] = 'Margin profit yang sehat';
        }

        if ($plan->payback_period <= 18) {
            $strengths[] = 'Periode balik modal yang cepat';
        }

        if ($plan->net_profit > 0) {
            $strengths[] = 'Profitabilitas positif';
        }

        return empty($strengths) ? ['Struktur biaya yang terkendali'] : $strengths;
    }

    private function identifyConcerns(FinancialPlan $plan)
    {
        $concerns = [];

        if ($plan->roi_percentage < 10) {
            $concerns[] = 'ROI yang rendah';
        }

        if ($plan->profit_margin < 5) {
            $concerns[] = 'Margin profit yang tipis';
        }

        if ($plan->payback_period > 36) {
            $concerns[] = 'Periode balik modal yang panjang';
        }

        if ($plan->net_profit <= 0) {
            $concerns[] = 'Belum mencapai break even';
        }

        return $concerns;
    }

    private function suggestNextSteps(FinancialPlan $plan)
    {
        $steps = ['Monitor performa aktual vs proyeksi'];

        if ($plan->roi_percentage < 15) {
            $steps[] = 'Evaluasi strategi pricing dan biaya';
        }

        if ($plan->payback_period > 24) {
            $steps[] = 'Pertimbangkan strategi akselerasi pendapatan';
        }

        if (count($plan->sales_projections ?? []) < 2) {
            $steps[] = 'Diversifikasi sumber pendapatan';
        }

        return $steps;
    }
}
