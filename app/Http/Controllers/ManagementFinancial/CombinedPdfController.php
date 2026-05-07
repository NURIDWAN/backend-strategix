<?php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessBackground;
use App\Models\MarketAnalysis;
use App\Models\ProductService;
use App\Models\MarketingStrategy;
use App\Models\OperationalPlan;
use App\Models\TeamStructure;
use App\Models\FinancialPlan;
use App\Models\ManagementFinancial\FinancialCategory;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialProjection;
use App\Models\Forecast\ForecastData;
use App\Models\Forecast\ForecastResult;
use App\Models\Forecast\ForecastInsight;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CombinedPdfController extends Controller
{
    /**
     * Generate Combined PDF (Business Plan + Financial Report)
     */
    public function generateCombinedPdf(Request $request)
    {
        try {
            Log::info('Combined PDF Generation Started', [
                'user_id' => $request->user_id,
                'business_background_id' => $request->business_background_id,
                'period_type' => $request->period_type,
                'period_value' => $request->period_value,
                'mode' => $request->mode
            ]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'period_type' => 'required|in:year,month',
                'period_value' => 'required',
                'mode' => 'required|in:free,pro'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $businessBackgroundId = $request->business_background_id;
            $periodType = $request->period_type;
            $periodValue = $request->period_value;
            $mode = $request->mode;

            // 1. Get Business Plan Data (similar to PdfBusinessPlanController)
            Log::info('📊 Step 1: Fetching Business Plan Data...');
            $businessPlanData = $this->getBusinessPlanData($userId, $businessBackgroundId);

            if (!$businessPlanData['business_background']) {
                Log::error('❌ Business background not found', [
                    'user_id' => $userId,
                    'business_background_id' => $businessBackgroundId
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background not found'
                ], 404);
            }

            Log::info('✅ Business Plan Data Retrieved', [
                'business_name' => $businessPlanData['business_background']->name,
                'business_id' => $businessPlanData['business_background']->id,
                'category' => $businessPlanData['business_background']->category,
                'market_analysis' => $businessPlanData['market_analysis'] ? 'YES' : 'NO',
                'products_services_count' => $businessPlanData['products_services']->count(),
                'marketing_strategies_count' => $businessPlanData['marketing_strategies']->count(),
                'operational_plans_count' => $businessPlanData['operational_plans']->count(),
                'team_structures_count' => $businessPlanData['team_structures']->count(),
                'financial_plans_count' => $businessPlanData['financial_plans']->count(),
            ]);

            // Log detail market analysis
            if ($businessPlanData['market_analysis']) {
                Log::info('📈 Market Analysis Details', [
                    'has_target_market' => !empty($businessPlanData['market_analysis']->target_market),
                    'has_swot' => !empty($businessPlanData['market_analysis']->strengths),
                    'competitors_count' => $businessPlanData['market_analysis']->competitors->count()
                ]);
            }

            // 2. Get Financial Report Data (similar to PdfFinancialReportController)
            Log::info('💰 Step 2: Fetching Financial Data...');
            $financialData = $this->getFinancialData($userId, $businessBackgroundId, $periodType, $periodValue);

            Log::info('✅ Financial Data Retrieved', [
                'period_type' => $periodType,
                'period_value' => $periodValue,
                'categories_count' => isset($financialData['categories']) ? count($financialData['categories']) : 0,
                'simulations_count' => isset($financialData['simulations']) ? count($financialData['simulations']) : 0,
                'projections_count' => isset($financialData['projections']) ? count($financialData['projections']) : 0,
                'has_summary' => isset($financialData['summary'])
            ]);

            // 3. Generate summaries
            Log::info('📝 Step 3: Generating Executive Summaries...');
            $businessExecutiveSummary = $this->createBusinessExecutiveSummary($businessPlanData);
            $financialExecutiveSummary = $this->createFinancialExecutiveSummary($financialData);

            Log::info('✅ Executive Summaries Generated', [
                'business_summary_length' => strlen($businessExecutiveSummary),
                'has_financial_summary' => !empty($financialExecutiveSummary)
            ]);

            // 4. Generate Business Plan Workflows
            Log::info('🔄 Step 4: Generating Workflow Diagrams...');
            $workflows = $this->generateWorkflowDiagrams($businessPlanData['operational_plans']);

            Log::info('✅ Workflow Diagrams Generated', [
                'workflows_count' => count($workflows)
            ]);

            // 4a. Convert workflow images to data URLs (for faster PDF generation)
            Log::info('🖼️ Step 4a: Converting Workflow Images to Data URLs...');
            $workflowImages = $this->convertWorkflowImagesToDataUrl($businessPlanData['operational_plans']);

            Log::info('✅ Workflow Images Converted', [
                'workflow_images_count' => count($workflowImages)
            ]);

            // 4b. Generate Organization Charts (QuickChart Mermaid)
            Log::info('📊 Step 4b: Generating Organization Charts...');
            $orgCharts = $this->generateOrganizationCharts($businessPlanData['team_structures']);

            Log::info('✅ Organization Charts Generated', [
                'org_charts_count' => count($orgCharts)
            ]);

            // 5. Generate Business Plan Charts (6 charts untuk Financial Plans)
            Log::info('📊 Step 5: Generating Business Plan Charts...');
            $businessPlanCharts = [];
            if ($businessPlanData['financial_plans']->count() > 0) {
                $businessPlanCharts = $this->generateBusinessPlanCharts($businessPlanData['financial_plans']);
            }

            Log::info('✅ Business Plan Charts Generated', [
                'charts_count' => count($businessPlanCharts),
                'chart_keys' => array_keys($businessPlanCharts)
            ]);

            // 5b. Generate Market Analysis Chart (TAM/SAM/SOM Pie Chart)
            Log::info('📊 Step 5b: Generating Market Analysis Charts...');
            $marketAnalysisCharts = [];
            if ($businessPlanData['market_analysis']) {
                $marketAnalysisCharts = $this->generateMarketAnalysisCharts($businessPlanData['market_analysis']);
            }

            Log::info('✅ Market Analysis Charts Generated', [
                'charts_count' => count($marketAnalysisCharts),
                'chart_keys' => array_keys($marketAnalysisCharts)
            ]);

            // 6. Generate Financial Report Charts (4 charts)
            Log::info('📊 Step 6: Generating Financial Report Charts...');
            $financialCharts = $this->generateCharts($financialData, $periodType);

            Log::info('✅ Financial Report Charts Generated', [
                'charts_count' => count($financialCharts),
                'chart_keys' => array_keys($financialCharts)
            ]);

            // 7. Get Forecast Data
            Log::info('📈 Step 7: Fetching Forecast Data...');
            $forecastData = $this->getForecastData($userId, $periodType, $periodValue);

            $hasForecast = !empty($forecastData['forecast_data']);
            $forecastExecutiveSummary = $hasForecast ? $this->generateForecastExecutiveSummary($forecastData) : null;
            $forecastStatistics = $hasForecast ? $this->calculateForecastStatistics($forecastData) : null;

            Log::info('✅ Forecast Data Retrieved', [
                'has_forecast' => $hasForecast,
                'forecast_results_count' => $hasForecast ? count($forecastData['results']) : 0,
                'forecast_insights_count' => $hasForecast ? count($forecastData['insights']) : 0
            ]);

            // 8. Generate Combined PDF
            Log::info('📄 Step 8: Generating PDF Document...');

            // Log all data being passed to view
            Log::info('🔍 Data being passed to PDF view:', [
                'business_background_exists' => isset($businessPlanData['business_background']),
                'market_analysis_exists' => isset($businessPlanData['market_analysis']),
                'products_services_count' => $businessPlanData['products_services']->count(),
                'marketing_strategies_count' => $businessPlanData['marketing_strategies']->count(),
                'operational_plans_count' => $businessPlanData['operational_plans']->count(),
                'team_structures_count' => $businessPlanData['team_structures']->count(),
                'financial_plans_count' => $businessPlanData['financial_plans']->count(),
                'workflows_count' => count($workflows),
                'businessPlanCharts_count' => count($businessPlanCharts),
                'financial_data_keys' => array_keys($financialData),
                'executiveSummary_length' => strlen($businessExecutiveSummary),
                'financial_summary_type' => gettype($financialExecutiveSummary),
                'financialCharts_count' => count($financialCharts),
                'mode' => $mode,
                'period_type' => $periodType,
                'period_label' => $this->getPeriodLabel($periodType, $periodValue)
            ]);
            // Prepare watermark logo (convert to base64 for PDF)
            $watermarkLogoPath = config('app.watermark_logo', '/images/watermark-logo.png');
            $watermarkLogoDataUrl = null;

            if ($watermarkLogoPath) {
                // Try to get the watermark logo path
                $logoPath = public_path(ltrim($watermarkLogoPath, '/'));
                if (file_exists($logoPath)) {
                    $watermarkLogoDataUrl = $this->convertLogoToDataUrl($logoPath);
                }
            }

            // Generate chart analyses
            $chartAnalyses = [
                'income_vs_expense' => $this->generateIncomeVsExpenseAnalysis($financialData['summary'] ?? null),
                'category_income' => $this->generateCategoryIncomeAnalysis($financialData['category_summary'] ?? []),
                'category_expense' => $this->generateCategoryExpenseAnalysis($financialData['category_summary'] ?? []),
                'monthly_trend' => $this->generateMonthlyTrendAnalysis($financialData['monthly_summary'] ?? [])
            ];

            // 8a. Calculate Yearly Projections (5 Years) from Forecast Results
            Log::info('📊 Step 8a: Calculating Yearly Projections from Forecast...');
            // Get all forecast results for 5 years (not just current period)
            $allForecastResults = $this->getAllForecastResults($userId, $businessBackgroundId);
            $yearlyProjections = $this->calculateYearlyProjections($allForecastResults);
            $yearlyProjectionChart = $this->generateYearlyProjectionChart($yearlyProjections);

            // Generate Detailed Forecast Chart Grids (4 charts each)
            Log::info('📊 Step 8b: Generating Detailed Forecast Chart Grids...');
            $monthlyForecastCharts = $this->generateForecastChartGrid($forecastData['results'] ?? [], 'Proyeksi Bulanan');
            $yearlyForecastCharts = $this->generateForecastChartGrid($yearlyProjections, 'Proyeksi Tahunan');

            Log::info('✅ Yearly Projections Calculated', [
                'forecast_results_count' => count($allForecastResults),
                'years_count' => count($yearlyProjections),
                'has_chart' => !empty($yearlyProjectionChart),
                'has_monthly_grid' => !empty($monthlyForecastCharts),
                'has_yearly_grid' => !empty($yearlyForecastCharts),
                'yearly_projections_data' => $yearlyProjections,
                'chart_url_length' => $yearlyProjectionChart ? strlen($yearlyProjectionChart) : 0
            ]);

            // Debug: Log data before passing to view
            Log::info('🔍 Data being passed to PDF view for yearly projections:', [
                'yearly_projections_empty' => empty($yearlyProjections),
                'yearly_projections_count' => count($yearlyProjections),
                'yearly_projection_chart_empty' => empty($yearlyProjectionChart),
                'monthly_grid_count' => count($monthlyForecastCharts),
                'yearly_grid_count' => count($yearlyForecastCharts)
            ]);

            $pdf = PDF::loadView('pdf.combined-report', [
                'data' => $businessPlanData,  // Business plan data
                'financial_data' => $financialData,  // Financial data
                'executiveSummary' => $businessExecutiveSummary,  // Business executive summary
                'financial_summary' => $financialExecutiveSummary,  // Financial summary
                'charts' => $businessPlanCharts,  // Business Plan charts (6 charts) - untuk Section 8
                'marketAnalysisCharts' => $marketAnalysisCharts,  // Market Analysis charts (TAM/SAM/SOM pie) - untuk Section 3
                'financialCharts' => $financialCharts,  // Financial Report charts (4 charts) - untuk BAGIAN 2
                'chartAnalyses' => $chartAnalyses,  // Chart analyses
                'workflows' => $workflows,  // Workflow diagrams for operational plans - untuk Section 6
                'workflowImages' => $workflowImages,  // Workflow uploaded images as data URLs (converted for PDF)
                'orgCharts' => $orgCharts,  // Organization charts for team structures - untuk Section 7
                // Yearly Projections (5 Years Summary)
                'yearly_projections' => $yearlyProjections,
                'yearly_projection_chart' => $yearlyProjectionChart,
                'projectedYears' => count($yearlyProjections),
                // Forecast Charts Grids
                'monthlyForecastCharts' => $monthlyForecastCharts,
                'yearlyForecastCharts' => $yearlyForecastCharts,
                // Forecast data - untuk BAGIAN 3
                'forecast_data' => $forecastData['forecast_data'] ?? null,
                'forecast_results' => $forecastData['results'] ?? [],
                'forecast_insights' => $forecastData['insights'] ?? [],
                'forecast_summary' => $forecastExecutiveSummary,
                'forecast_statistics' => $forecastStatistics,
                'has_forecast' => $hasForecast,
                // Common data
                'mode' => $mode,
                'period_type' => $periodType,
                'period_label' => $this->getPeriodLabel($periodType, $periodValue),
                'generated_at' => now()->format('d F Y'),
                'watermark_logo' => $watermarkLogoDataUrl  // Watermark logo as data URL
            ]);

            // Konfigurasi PDF (Portrait)
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'enable_php' => false
            ]);

            // Generate filename
            $businessName = Str::slug($businessPlanData['business_background']->name);
            $filename = "laporan-lengkap-{$businessName}-{$periodValue}.pdf";

            Log::info('Combined PDF Generated Successfully', [
                'filename' => $filename,
                'user_id' => $userId
            ]);

            // Convert PDF to base64 dan return sebagai JSON
            $pdfContent = $pdf->output();
            $pdfBase64 = base64_encode($pdfContent);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'filename' => $filename,
                    'pdf_base64' => $pdfBase64
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Combined PDF Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Business Plan Data
     */
    private function getBusinessPlanData($userId, $businessBackgroundId)
    {
        Log::info('🔍 getBusinessPlanData - Start fetching...', [
            'user_id' => $userId,
            'business_background_id' => $businessBackgroundId
        ]);

        $businessBackground = BusinessBackground::with('user')
            ->where('user_id', $userId)
            ->where('id', $businessBackgroundId)
            ->first();

        if (!$businessBackground) {
            Log::warning('⚠️ Business Background NOT FOUND');
            return ['business_background' => null];
        }

        Log::info('✅ Business Background Found', [
            'name' => $businessBackground->name,
            'category' => $businessBackground->category,
            'has_description' => !empty($businessBackground->description),
            'has_vision' => !empty($businessBackground->vision),
            'has_mission' => !empty($businessBackground->mission)
        ]);

        $marketAnalysis = MarketAnalysis::with(['businessBackground', 'competitors'])
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->first();

        Log::info('📊 Market Analysis', [
            'found' => $marketAnalysis ? 'YES' : 'NO',
            'competitors_count' => $marketAnalysis ? $marketAnalysis->competitors->count() : 0
        ]);

        $products = ProductService::with(['businessBackground', 'user'])
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->get();

        Log::info('📦 Products & Services', ['count' => $products->count()]);

        $marketing = MarketingStrategy::with('businessBackground')
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->get();

        Log::info('📢 Marketing Strategies', ['count' => $marketing->count()]);

        $operational = OperationalPlan::with(['businessBackground', 'user'])
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->get();

        Log::info('⚙️ Operational Plans', ['count' => $operational->count()]);

        $team = TeamStructure::with(['businessBackground', 'user', 'operationalPlan'])
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->orderBy('sort_order')
            ->get();

        Log::info('👥 Team Structures', ['count' => $team->count()]);

        $financialPlans = FinancialPlan::with(['businessBackground', 'user'])
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->get();

        Log::info('💼 Financial Plans', ['count' => $financialPlans->count()]);

        // Convert logo to base64 data URL if it exists
        $logoDataUrl = null;
        if ($businessBackground->logo) {
            $logoDataUrl = $this->convertLogoToDataUrl($businessBackground->logo);
        }

        // Update business background logo to data URL for PDF rendering
        if ($logoDataUrl) {
            $businessBackground->logo = $logoDataUrl;
        }

        // Convert org_chart_image to base64 data URL if it exists
        $orgChartDataUrl = null;
        if ($businessBackground->org_chart_image) {
            $orgChartDataUrl = $this->convertLogoToDataUrl($businessBackground->org_chart_image);
        }

        // Update business background org_chart_image to data URL for PDF rendering
        if ($orgChartDataUrl) {
            $businessBackground->org_chart_image = $orgChartDataUrl;
        }

        return [
            'business_background' => $businessBackground,
            'market_analysis' => $marketAnalysis,
            'products_services' => $products,
            'marketing_strategies' => $marketing,
            'operational_plans' => $operational,
            'team_structures' => $team,
            'financial_plans' => $financialPlans
        ];
    }

    /**
     * Get Financial Data
     */
    private function getFinancialData($userId, $businessBackgroundId, $periodType, $periodValue)
    {
        Log::info('🔍 getFinancialData - Start fetching...', [
            'user_id' => $userId,
            'business_background_id' => $businessBackgroundId,
            'period_type' => $periodType,
            'period_value' => $periodValue
        ]);

        $periodData = $this->parsePeriod($periodType, $periodValue);

        Log::info('📅 Period Parsed', [
            'year' => $periodData['year'],
            'month' => $periodData['month'] ?? 'N/A'
        ]);

        $businessBackground = BusinessBackground::where('id', $businessBackgroundId)
            ->where('user_id', $userId)
            ->first();

        Log::info('🏢 Business Background for Financial', [
            'found' => $businessBackground ? 'YES' : 'NO',
            'name' => $businessBackground->name ?? 'N/A'
        ]);

        $categories = FinancialCategory::where('user_id', $userId)
            ->where('status', 'actual')
            ->get();

        Log::info('📁 Financial Categories', ['count' => $categories->count()]);

        $simulationsQuery = FinancialSimulation::with('category')
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->where('status', 'completed');

        if ($periodType === 'year') {
            $simulationsQuery->where('year', $periodData['year']);
        } else {
            $simulationsQuery->where('year', $periodData['year'])
                ->where('month', $periodData['month']);
        }

        $simulations = $simulationsQuery->orderBy('simulation_date', 'asc')->get();

        Log::info('💰 Financial Simulations (Period)', [
            'count' => $simulations->count(),
            'total_income' => $simulations->where('transaction_type', 'income')->sum('amount'),
            'total_expense' => $simulations->where('transaction_type', 'expense')->sum('amount')
        ]);

        $allSimulations = FinancialSimulation::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->where('status', 'completed')
            ->get();

        Log::info('💰 Financial Simulations (All)', ['count' => $allSimulations->count()]);

        $summary = $this->calculateSummary($simulations, $allSimulations, $businessBackground);
        Log::info('📊 Summary Calculated', [
            'has_summary' => !empty($summary),
            'summary_keys' => array_keys($summary)
        ]);

        $categorySummary = $this->getCategorySummary($simulations, $categories);
        Log::info('📁 Category Summary', [
            'top_income_count' => isset($categorySummary['top_income']) ? count($categorySummary['top_income']) : 0,
            'top_expense_count' => isset($categorySummary['top_expense']) ? count($categorySummary['top_expense']) : 0
        ]);

        $monthlySummary = [];
        if ($periodType === 'year') {
            $monthlySummary = $this->getMonthlySummary($simulations, $periodData['year']);
            Log::info('📅 Monthly Summary', ['months_count' => count($monthlySummary)]);
        }

        $projections = FinancialProjection::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->whereIn('scenario_type', ['optimistic', 'realistic', 'pessimistic'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('scenario_type')
            ->map(function ($group) {
                return $group->first();
            })
            ->values();

        Log::info('📈 Financial Projections', [
            'count' => $projections->count(),
            'scenarios' => $projections->pluck('scenario_type')->toArray()
        ]);

        $financialData = [
            'business_background' => $businessBackground,
            'categories' => $categories,
            'simulations' => $simulations,
            'summary' => $summary,
            'category_summary' => $categorySummary,
            'monthly_summary' => $monthlySummary,
            'projections' => $projections,
            'period' => $periodData
        ];

        Log::info('✅ getFinancialData - Complete', [
            'data_keys' => array_keys($financialData)
        ]);

        return $financialData;
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($simulations, $allSimulations, $businessBackground)
    {
        $totalIncome = $simulations->where('type', 'income')->sum('amount');
        $totalExpense = $simulations->where('type', 'expense')->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        $accumulatedIncome = $allSimulations->where('type', 'income')->sum('amount');
        $accumulatedExpense = $allSimulations->where('type', 'expense')->sum('amount');

        $initialCapital = 0;
        if ($businessBackground) {
            $projection = FinancialProjection::where('business_background_id', $businessBackground->id)
                ->orderByRaw("CASE WHEN scenario_type = 'realistic' THEN 1 WHEN scenario_type = 'optimistic' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'desc')
                ->first();

            $initialCapital = $projection ? $projection->initial_investment : $businessBackground->initial_capital;
        }

        $currentCashBalance = $initialCapital + $accumulatedIncome - $accumulatedExpense;

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
            'transaction_count' => $simulations->count(),
            'income_count' => $simulations->where('type', 'income')->count(),
            'expense_count' => $simulations->where('type', 'expense')->count(),
            'current_cash_balance' => $currentCashBalance,
            'accumulated_income' => $accumulatedIncome,
            'accumulated_expense' => $accumulatedExpense,
            'initial_capital' => $initialCapital
        ];
    }

    /**
     * Get summary per category
     */
    private function getCategorySummary($simulations, $categories)
    {
        $summary = [];

        foreach ($categories as $category) {
            $categorySimulations = $simulations->where('financial_category_id', $category->id);
            $total = $categorySimulations->sum('amount');
            $count = $categorySimulations->count();

            if ($count > 0) {
                $summary[] = [
                    'category' => $category,
                    'total' => $total,
                    'count' => $count,
                    'average' => $total / $count,
                    'type' => $category->type
                ];
            }
        }

        usort($summary, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return [
            'all' => $summary,
            'income' => array_filter($summary, fn($s) => $s['type'] === 'income'),
            'expense' => array_filter($summary, fn($s) => $s['type'] === 'expense'),
            'top_income' => array_slice(array_filter($summary, fn($s) => $s['type'] === 'income'), 0, 5),
            'top_expense' => array_slice(array_filter($summary, fn($s) => $s['type'] === 'expense'), 0, 5)
        ];
    }

    /**
     * Get monthly summary
     */
    private function getMonthlySummary($simulations, $year)
    {
        $summary = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthSimulations = $simulations->filter(function ($sim) use ($month) {
                return Carbon::parse($sim->simulation_date)->month == $month;
            });

            $income = $monthSimulations->where('type', 'income')->sum('amount');
            $expense = $monthSimulations->where('type', 'expense')->sum('amount');

            $summary[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->isoFormat('MMMM'),
                'income' => $income,
                'expense' => $expense,
                'net_profit' => $income - $expense,
                'transaction_count' => $monthSimulations->count()
            ];
        }

        return $summary;
    }

    /**
     * Generate charts for Financial Report
     */
    private function generateCharts($data, $periodType)
    {
        $charts = [];

        try {
            // Income vs Expense Chart
            $incomeVsExpenseUrl = $this->generateIncomeVsExpenseChart($data);
            if ($incomeVsExpenseUrl) {
                $charts['income_vs_expense'] = $incomeVsExpenseUrl;
            }

            // Monthly Trend Chart
            if ($periodType === 'year' && !empty($data['monthly_summary'])) {
                $monthlyTrendUrl = $this->generateMonthlyTrendChart($data['monthly_summary']);
                if ($monthlyTrendUrl) {
                    $charts['monthly_trend'] = $monthlyTrendUrl;
                }
            }

            // Category Pie Charts
            if (!empty($data['category_summary']['top_income'])) {
                $categoryIncomeUrl = $this->generateCategoryPieChart($data['category_summary']['top_income'], 'income');
                if ($categoryIncomeUrl) {
                    $charts['category_income_pie'] = $categoryIncomeUrl;
                }
            }

            if (!empty($data['category_summary']['top_expense'])) {
                $categoryExpenseUrl = $this->generateCategoryPieChart($data['category_summary']['top_expense'], 'expense');
                if ($categoryExpenseUrl) {
                    $charts['category_expense_pie'] = $categoryExpenseUrl;
                }
            }
        } catch (\Exception $e) {
            Log::error('Chart generation error: ' . $e->getMessage());
        }

        return $charts;
    }

    /**
     * Generate Income vs Expense bar chart
     */
    private function generateIncomeVsExpenseChart($data)
    {
        $summary = $data['summary'];

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => ['Pendapatan', 'Pengeluaran', 'Laba/Rugi'],
                'datasets' => [[
                    'label' => 'Ringkasan Keuangan',
                    'data' => [
                        $summary['total_income'],
                        $summary['total_expense'],
                        $summary['net_profit']
                    ],
                    'backgroundColor' => ['#10b981', '#ef4444', '#3b82f6']
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Perbandingan Pendapatan vs Pengeluaran'
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 800, 400);
    }

    /**
     * Generate Monthly Trend line chart
     */
    private function generateMonthlyTrendChart($monthlySummary)
    {
        $labels = array_map(fn($m) => $m['month_name'], $monthlySummary);
        $incomeData = array_map(fn($m) => $m['income'], $monthlySummary);
        $expenseData = array_map(fn($m) => $m['expense'], $monthlySummary);

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $incomeData,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'data' => $expenseData,
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'fill' => true
                    ]
                ]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Tren Bulanan'
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 300);
    }

    /**
     * Generate Category Pie chart
     */
    private function generateCategoryPieChart($categories, $type = 'income')
    {
        $labels = [];
        $data = [];
        $colors = $type === 'income'
            ? ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5']
            : ['#ef4444', '#f87171', '#fca5a5', '#fecaca', '#fee2e2'];

        foreach ($categories as $index => $cat) {
            $labels[] = $cat['category']->name;
            $data[] = $cat['total'];
        }

        $chartConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colors
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => $type === 'income' ? 'Distribusi Pendapatan' : 'Distribusi Pengeluaran'
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 500, 300);
    }

    /**
     * Generate QuickChart URL
     */
    private function getQuickChartUrl($config, $width = 600, $height = 300)
    {
        try {
            $baseUrl = 'https://quickchart.io/chart';
            $params = [
                'c' => json_encode($config),
                'width' => $width,
                'height' => $height,
                'backgroundColor' => 'white',
                'devicePixelRatio' => 2.0
            ];

            $fullUrl = $baseUrl . '?' . http_build_query($params);
            return $fullUrl;
        } catch (\Exception $e) {
            Log::error('QuickChart URL generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse period value
     */
    private function parsePeriod($periodType, $periodValue)
    {
        if ($periodType === 'year') {
            return [
                'year' => (int) $periodValue,
                'month' => null
            ];
        } else {
            $parts = explode('-', $periodValue);
            return [
                'year' => (int) $parts[0],
                'month' => (int) $parts[1]
            ];
        }
    }

    /**
     * Get period label
     */
    private function getPeriodLabel($periodType, $periodValue)
    {
        if ($periodType === 'year') {
            return "Tahun " . $periodValue;
        } else {
            $date = Carbon::createFromFormat('Y-m', $periodValue);
            return $date->isoFormat('MMMM YYYY');
        }
    }

    /**
     * Create Business Executive Summary
     */
    private function createBusinessExecutiveSummary($businessData)
    {
        $business = $businessData['business_background'];
        $marketAnalysis = $businessData['market_analysis'];
        $products = $businessData['products_services'];
        $marketing = $businessData['marketing_strategies'];
        $team = $businessData['team_structures'];
        $financialPlan = $businessData['financial_plans']->first();

        $summary = "{$business->name} adalah perusahaan bisnis yang bergerak di kategori {$business->category}. ";
        $summary .= "Dengan fokus utama pada " . strtolower($business->description) . ", ";
        $summary .= "perusahaan ini dirancang untuk memberikan solusi terbaik bagi pelanggannya. ";

        // Visi dan Misi
        if ($business->vision) {
            $summary .= "Visi perusahaan adalah " . strtolower($business->vision) . ". ";
        }
        if ($business->mission) {
            $summary .= "Sementara misi utamanya mencakup " . strtolower($business->mission) . ". ";
        }

        // Pasar Target
        if ($marketAnalysis && $marketAnalysis->target_market) {
            $summary .= "Pasar target kami adalah: " . $marketAnalysis->target_market . ". ";
        }

        // Produk/Layanan
        if ($products->count() > 0) {
            $summary .= "Kami menawarkan " . $products->count() . " produk/layanan utama yang dirancang untuk memenuhi kebutuhan pasar. ";
        }

        // Strategi Marketing
        if ($marketing->count() > 0) {
            $summary .= "Strategi pemasaran kami mencakup " . $marketing->count() . " pendekatan yang terintegrasi untuk mencapai target audience. ";
        }

        // Tim
        if ($team->count() > 0) {
            $summary .= "Tim kami terdiri dari " . $team->count() . " profesional berpengalaman yang siap mendukung pertumbuhan bisnis. ";
        }

        // Proyeksi Finansial
        if ($financialPlan) {
            $summary .= "Dengan proyeksi pendapatan bulanan sebesar Rp " .
                number_format($financialPlan->total_monthly_income ?? 0, 0, ',', '.') . ", ";
            $summary .= "kami memperkirakan pertumbuhan yang konsisten dan berkelanjutan dalam periode mendatang. ";
        }

        $summary .= "Rencana bisnis komprehensif ini dirancang untuk memberikan panduan strategis dalam mencapai target bisnis dan pertumbuhan jangka panjang.";

        return $summary;
    }

    /**
     * Create Financial Executive Summary
     */
    private function createFinancialExecutiveSummary($data)
    {
        $summary = $data['summary'];
        $categorySummary = $data['category_summary'];
        $monthlySummary = $data['monthly_summary'] ?? [];
        $periodInfo = $data['period'] ?? [];

        $profitStatus = $summary['net_profit'] >= 0 ? 'profit' : 'loss';
        $profitPercentage = $summary['total_income'] > 0
            ? ($summary['net_profit'] / $summary['total_income']) * 100
            : 0;

        $topIncomeCategory = !empty($categorySummary['top_income'])
            ? $categorySummary['top_income'][0]['category']->name
            : '-';

        $topExpenseCategory = !empty($categorySummary['top_expense'])
            ? $categorySummary['top_expense'][0]['category']->name
            : '-';

        // Generate executive summary text
        $executiveSummaryText = $this->generateFinancialSummaryText($summary, $categorySummary, $periodInfo);

        // Return struktur lengkap untuk template
        return [
            // Original fields untuk backward compatibility
            'profit_status' => $profitStatus,
            'profit_percentage' => round($profitPercentage, 2),
            'top_income_category' => $topIncomeCategory,
            'top_expense_category' => $topExpenseCategory,
            'cash_health' => $summary['current_cash_balance'] > 0 ? 'healthy' : 'critical',

            // Fields lengkap untuk template BAGIAN 2
            'executive_summary' => $executiveSummaryText,
            'summary_cards' => [
                'total_income' => $summary['total_income'],
                'total_expense' => $summary['total_expense'],
                'net_profit' => $summary['net_profit'],
                'cash_balance' => $summary['current_cash_balance'],
                'income_count' => $summary['income_count'],
                'expense_count' => $summary['expense_count'],
                'transaction_count' => $summary['transaction_count']
            ],
            'category_summary' => $categorySummary,
            'monthly_summary' => $monthlySummary,
            'year' => $periodInfo['year'] ?? date('Y'),
            'month' => $periodInfo['month'] ?? null
        ];
    }

    /**
     * Generate executive summary text untuk financial report
     */
    private function generateFinancialSummaryText($summary, $categorySummary, $periodInfo)
    {
        $year = $periodInfo['year'] ?? date('Y');
        $businessName = "bisnis";

        $profitStatus = $summary['net_profit'] >= 0 ? 'mengalami keuntungan' : 'mengalami kerugian';
        $profitAmount = number_format(abs($summary['net_profit']), 0, ',', '.');

        $topIncome = !empty($categorySummary['top_income'])
            ? $categorySummary['top_income'][0]['category']->name
            : 'tidak ada';

        $topExpense = !empty($categorySummary['top_expense'])
            ? $categorySummary['top_expense'][0]['category']->name
            : 'tidak ada';

        return "Ringkasan keuangan untuk periode {$year} menunjukkan bahwa {$businessName} {$profitStatus} sebesar Rp {$profitAmount}. "
            . "Total pendapatan mencapai Rp " . number_format($summary['total_income'], 0, ',', '.') . " "
            . "dengan total pengeluaran sebesar Rp " . number_format($summary['total_expense'], 0, ',', '.') . ". "
            . "Kategori pendapatan tertinggi berasal dari {$topIncome}, "
            . "sedangkan pengeluaran terbesar untuk {$topExpense}. "
            . "Saldo kas saat ini adalah Rp " . number_format($summary['current_cash_balance'], 0, ',', '.') . ".";
    }

    /**
     * Generate Market Analysis Charts (TAM/SAM/SOM Pie Chart)
     */
    private function generateMarketAnalysisCharts($marketAnalysis)
    {
        $charts = [];

        try {
            // Generate TAM/SAM/SOM Pie Chart
            $tamSomChart = $this->generateTamSamSomChart($marketAnalysis);
            if ($tamSomChart) {
                $charts['tam_sam_som'] = $tamSomChart;
                Log::info('✅ TAM/SAM/SOM chart generated');
            }
        } catch (\Exception $e) {
            Log::error('Market Analysis Chart Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $charts;
    }

    /**
     * Generate TAM/SAM/SOM Pie Chart
     */
    private function generateTamSamSomChart($marketAnalysis)
    {
        $tam = floatval($marketAnalysis->tam_total ?? 0);
        $sam = floatval($marketAnalysis->sam_total ?? 0);
        $som = floatval($marketAnalysis->som_total ?? 0);

        if ($tam <= 0 && $sam <= 0 && $som <= 0) {
            Log::warning('⚠️ No valid TAM/SAM/SOM data for chart');
            return null;
        }

        $chartConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => ['TAM (Total Addressable Market)', 'SAM (Serviceable Available Market)', 'SOM (Serviceable Obtainable Market)'],
                'datasets' => [[
                    'data' => [$tam, $sam, $som],
                    'backgroundColor' => ['#3b82f6', '#10b981', '#8b5cf6']
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Analisis Ukuran Pasar (TAM/SAM/SOM)'
                    ],
                    'legend' => [
                        'position' => 'bottom'
                    ],
                    'datalabels' => [
                        'display' => false
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 400);
    }

    /**
     * Generate Business Plan Charts (6 charts untuk Financial Plan section)
     */
    private function generateBusinessPlanCharts($financialPlans)
    {
        $charts = [];

        // Ambil financial plan pertama saja untuk combined PDF
        $plan = $financialPlans->first();

        if (!$plan) {
            return $charts;
        }

        try {
            // 1. Chart Sumber Modal → key: 'capital_structure'
            if ($plan->capital_sources && count($plan->capital_sources) > 0) {
                $charts['capital_structure'] = $this->generateCapitalSourcesChart($plan);
            }

            // 2. Chart Proyeksi Penjualan → key: 'revenue_streams'
            // Field yang benar adalah 'sales_projections' (plural)
            if ($plan->sales_projections && count($plan->sales_projections) > 0) {
                $charts['revenue_streams'] = $this->generateSalesProjectionChart($plan);
            }

            // 3. Chart Breakdown Biaya Operasional → key: 'expense_breakdown'
            // Field yang benar adalah 'monthly_opex'
            if ($plan->monthly_opex && count($plan->monthly_opex) > 0) {
                $charts['expense_breakdown'] = $this->generateOperationalCostsChart($plan);
            }

            // 4. Chart Ringkasan Laba Rugi Bulanan → key: 'profit_loss'
            // Generate dari data yang ada (tidak ada field monthly_profit_loss)
            $charts['profit_loss'] = $this->generateMonthlyProfitLossChart($plan);

            // 5. Chart Analisis Kelayakan → key: 'feasibility'
            $charts['feasibility'] = $this->generateFeasibilityChart($plan);

            // 6. Chart Proyeksi Keuangan Masa Depan → key: 'forecast'
            // Generate dari cash_flow_simulation atau proyeksi tahunan
            $charts['forecast'] = $this->generateFutureProjectionChart($plan);
        } catch (\Exception $e) {
            Log::error('Business Plan Chart Generation Error: ' . $e->getMessage(), [
                'financial_plan_id' => $plan->id,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $charts;
    }
    /**
     * Generate Capital Sources Chart
     */
    private function generateCapitalSourcesChart($plan)
    {
        $sources = is_string($plan->capital_sources)
            ? json_decode($plan->capital_sources, true)
            : $plan->capital_sources;

        if (empty($sources)) return null;

        $labels = [];
        $data = [];
        foreach ($sources as $source) {
            $labels[] = $source['source'] ?? 'Unknown';
            $data[] = $source['amount'] ?? 0;
        }

        $chartConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Sumber Modal'
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 500, 300);
    }

    /**
     * Generate Sales Projection Chart
     */
    private function generateSalesProjectionChart($plan)
    {
        // Field yang benar adalah sales_projections (plural)
        $projections = $plan->sales_projections;

        if (empty($projections)) return null;

        $labels = [];
        $data = [];
        foreach ($projections as $item) {
            $labels[] = $item['product'] ?? 'Unknown';
            $data[] = $item['monthly_income'] ?? 0;
        }
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Proyeksi Penjualan',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Proyeksi Penjualan'
                    ]
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 300);
    }

    /**
     * Generate Operational Costs Chart
     */
    private function generateOperationalCostsChart($plan)
    {
        // Field yang benar adalah monthly_opex
        $opexItems = $plan->monthly_opex;

        if (empty($opexItems)) return null;

        $labels = [];
        $data = [];
        foreach ($opexItems as $item) {
            $labels[] = $item['category'] ?? 'Unknown';
            $data[] = $item['amount'] ?? 0;
        }
        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Biaya Operasional',
                    'data' => $data,
                    'backgroundColor' => '#ef4444'
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Breakdown Biaya Operasional'
                    ]
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 300);
    }

    /**
     * Generate Monthly Profit Loss Chart
     */
    private function generateMonthlyProfitLossChart($plan)
    {
        // Generate dari data yang ada (12 bulan proyeksi)
        $labels = [];
        $revenue = [];
        $expense = [];
        $profit = [];

        $monthlyRevenue = $plan->total_monthly_income ?? 0;
        $monthlyExpense = $plan->total_monthly_opex ?? 0;
        $monthlyProfit = $plan->net_profit ?? 0;

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = 'Bulan ' . $i;
            $revenue[] = $monthlyRevenue;
            $expense[] = $monthlyExpense;
            $profit[] = $monthlyProfit;
        }
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $revenue,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'transparent'
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'data' => $expense,
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'transparent'
                    ],
                    [
                        'label' => 'Laba/Rugi',
                        'data' => $profit,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'transparent'
                    ]
                ]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Ringkasan Laba Rugi Bulanan'
                    ]
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 300);
    }

    /**
     * Generate Feasibility Chart
     */
    private function generateFeasibilityChart($plan)
    {
        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => ['ROI', 'Payback Period', 'Break Even Point'],
                'datasets' => [[
                    'label' => 'Analisis Kelayakan',
                    'data' => [
                        $plan->roi_percentage ?? 0,
                        $plan->payback_period_months ?? 0,
                        $plan->break_even_point ?? 0
                    ],
                    'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b']
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Analisis Kelayakan'
                    ]
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 500, 300);
    }

    /**
     * Generate Future Projection Chart
     */
    private function generateFutureProjectionChart($plan)
    {
        // Generate proyeksi 5 tahun dari data yang ada
        $labels = [];
        $data = [];

        $yearlyIncome = $plan->total_yearly_income ?? 0;
        $currentYear = date('Y');

        // Asumsi growth rate 10% per tahun
        $growthRate = 1.10;

        for ($i = 0; $i < 5; $i++) {
            $labels[] = 'Tahun ' . ($currentYear + $i);
            $data[] = $yearlyIncome * pow($growthRate, $i);
        }
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Proyeksi Pendapatan',
                    'data' => $data,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Proyeksi Keuangan Masa Depan (5 Tahun)'
                    ]
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 300);
    }

    /**
     * Generate workflow diagrams for operational plans
     */
    private function generateWorkflowDiagrams($operationalPlans)
    {
        $workflows = [];

        foreach ($operationalPlans as $plan) {
            if ($plan->workflow_diagram) {
                try {
                    // Untuk sementara return null, nanti bisa integrasikan dengan WorkflowDiagramService
                    // $diagram = is_string($plan->workflow_diagram)
                    //     ? json_decode($plan->workflow_diagram, true)
                    //     : $plan->workflow_diagram;
                    // $workflows[$plan->id] = $diagram;
                } catch (\Exception $e) {
                    Log::error('Workflow generation error: ' . $e->getMessage());
                }
            }
        }

        return $workflows;
    }

    /**
     * Calculate Yearly Projections (5 Years) from Forecast Results
     * Aggregates monthly forecast data and groups by year
     */
    private function calculateYearlyProjections($forecastResults)
    {
        if (empty($forecastResults)) {
            Log::info('📊 No forecast results available for yearly projections');
            return [];
        }

        $yearlyData = [];

        // Group forecast results by year and sum monthly values
        foreach ($forecastResults as $result) {
            $year = $result['year'] ?? date('Y');

            if (!isset($yearlyData[$year])) {
                $yearlyData[$year] = [
                    'year' => $year,
                    'income' => 0,
                    'expense' => 0,
                    'profit' => 0
                ];
            }

            // Accumulate monthly values into yearly totals
            // Field names: forecast_income, forecast_expense, forecast_profit
            $yearlyData[$year]['income'] += floatval($result['forecast_income'] ?? 0);
            $yearlyData[$year]['expense'] += floatval($result['forecast_expense'] ?? 0);
            $yearlyData[$year]['profit'] += floatval($result['forecast_profit'] ?? 0);
        }

        // Sort by year and convert to indexed array
        ksort($yearlyData);
        $yearlyData = array_values($yearlyData);

        // Calculate margin for each year
        foreach ($yearlyData as &$data) {
            if ($data['income'] > 0) {
                $data['margin'] = ($data['profit'] / $data['income']) * 100;
            } else {
                $data['margin'] = 0;
            }
        }

        // --- NEW: Extrapolate based on the maximum year found in results ---
        $count = count($yearlyData);
        if ($count > 0) {
            // Find the maximum year in the original results set (to know what duration the user intended)
            $lastActualYearData = end($yearlyData);
            $lastActualYear = (int)$lastActualYearData['year'];

            // We want to projected up to the endYear calculated in getAllForecastResults (or at least 3 years total)
            // But since we don't have endYear here, we'll rely on the count or just leave it as is if it's already multi-year.
            // Actually, the user wants it to be dynamic. If the count is 1, let's at least show 3 years.
            // If the count is 3, 5, or 10, keep it as is.

            $targetYears = max(3, $count); // Minimum 3 years for visual trend

            if ($count < $targetYears) {
                $lastYearData = end($yearlyData);
                $lastYear = (int)$lastYearData['year'];
                $growthRate = 1.10; // Default 10% annual growth for projections

                for ($i = 1; $i <= ($targetYears - $count); $i++) {
                    $newYear = $lastYear + $i;
                    $projectedIncome = floatval($lastYearData['income']) * pow($growthRate, $i);
                    $projectedExpense = floatval($lastYearData['expense']) * pow($growthRate, $i);
                    $projectedProfit = $projectedIncome - $projectedExpense;

                    $yearlyData[] = [
                        'year' => $newYear,
                        'income' => $projectedIncome,
                        'expense' => $projectedExpense,
                        'profit' => $projectedProfit,
                        'margin' => $projectedIncome > 0 ? ($projectedProfit / $projectedIncome) * 100 : 0,
                        'is_projected' => true // Flag to indicate this is extrapolated
                    ];
                }
            }
        }

        Log::info('📊 Yearly Projections Calculated and Extrapolated', [
            'original_count' => $count,
            'final_count' => count($yearlyData),
            'years' => array_column($yearlyData, 'year')
        ]);

        return $yearlyData;
    }

    /**
     * Generate Yearly Projection Chart (Line Chart with 3 lines: Income, Expense, Profit)
     * Uses forecast results aggregated by year
     */
    private function generateYearlyProjectionChart($yearlyProjections)
    {
        if (empty($yearlyProjections)) {
            Log::warning('⚠️ No yearly projections data for chart');
            return null;
        }

        $labels = array_map(fn($item) => 'Tahun ' . $item['year'], $yearlyProjections);
        $incomeData = array_map(fn($item) => $item['income'], $yearlyProjections);
        $expenseData = array_map(fn($item) => $item['expense'], $yearlyProjections);
        $profitData = array_map(fn($item) => $item['profit'], $yearlyProjections);

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $incomeData,
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'borderWidth' => 3,
                        'tension' => 0.4,
                        'fill' => false
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'data' => $expenseData,
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'borderWidth' => 3,
                        'tension' => 0.4,
                        'fill' => false
                    ],
                    [
                        'label' => 'Laba',
                        'data' => $profitData,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderWidth' => 3,
                        'tension' => 0.4,
                        'fill' => false
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Proyeksi Keuangan Tahunan (5 Tahun Kedepan)',
                        'font' => ['size' => 16, 'weight' => 'bold']
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom'
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }'
                        ]
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 350);
    }

    /**
     * Generate 4-chart grid for Forecast Data (Income, Expense, Profit, Margin)
     */
    private function generateForecastChartGrid($data, $titlePrefix = '')
    {
        if (empty($data)) {
            return [];
        }

        $labels = [];
        $income = [];
        $expense = [];
        $profit = [];
        $margin = [];

        foreach ($data as $item) {
            // Label depends on data type (monthly result or yearly projection)
            if (isset($item['month']) && isset($item['year'])) {
                $labels[] = date('M y', mktime(0, 0, 0, $item['month'], 1, $item['year']));
            } else {
                $labels[] = 'Thn ' . ($item['year'] ?? '');
            }

            $income[] = floatval($item['forecast_income'] ?? $item['income'] ?? 0);
            $expense[] = floatval($item['forecast_expense'] ?? $item['expense'] ?? 0);
            $profit[] = floatval($item['forecast_profit'] ?? $item['profit'] ?? 0);
            $margin[] = floatval($item['forecast_margin'] ?? $item['margin'] ?? 0);
        }

        $chartConfigs = [
            'income' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Pendapatan',
                        'data' => $income,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ]]
                ],
                'options' => [
                    'plugins' => ['title' => ['display' => true, 'text' => "{$titlePrefix} - Pendapatan"]]
                ]
            ],
            'expense' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Pengeluaran',
                        'data' => $expense,
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'rgba(239, 104, 68, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ]]
                ],
                'options' => [
                    'plugins' => ['title' => ['display' => true, 'text' => "{$titlePrefix} - Pengeluaran"]]
                ]
            ],
            'profit' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Laba Bersih',
                        'data' => $profit,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ]]
                ],
                'options' => [
                    'plugins' => ['title' => ['display' => true, 'text' => "{$titlePrefix} - Laba Bersih"]]
                ]
            ],
            'margin' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Margin (%)',
                        'data' => $margin,
                        'borderColor' => '#8b5cf6',
                        'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ]]
                ],
                'options' => [
                    'plugins' => ['title' => ['display' => true, 'text' => "{$titlePrefix} - Margin (%)"]]
                ]
            ]
        ];

        $urls = [];
        foreach ($chartConfigs as $key => $config) {
            $urls[$key] = $this->getQuickChartUrl($config, 400, 250);
        }

        return $urls;
    }

    /**
     * Generate Organization Charts using QuickChart with Mermaid
     */
    private function generateOrganizationCharts($teamStructures)
    {
        $orgCharts = [];

        if ($teamStructures->isEmpty()) {
            return $orgCharts;
        }

        try {
            // Group by team category
            $groupedTeams = $teamStructures->groupBy('team_category');

            foreach ($groupedTeams as $category => $members) {
                // Helper function to determine hierarchy level
                $getHierarchyLevel = function ($position) {
                    $pos = strtolower($position);

                    // Level 1: Top Management
                    if (
                        stripos($pos, 'ceo') !== false ||
                        stripos($pos, 'direktur') !== false ||
                        stripos($pos, 'owner') !== false ||
                        stripos($pos, 'founder') !== false ||
                        stripos($pos, 'presiden') !== false ||
                        stripos($pos, 'pimpinan') !== false
                    ) {
                        return 1;
                    }

                    // Level 2: Middle Management
                    if (
                        stripos($pos, 'manager') !== false ||
                        stripos($pos, 'head') !== false ||
                        stripos($pos, 'lead') !== false ||
                        stripos($pos, 'supervisor') !== false ||
                        stripos($pos, 'koordinator') !== false ||
                        stripos($pos, 'kepala') !== false
                    ) {
                        return 2;
                    }

                    // Level 3: Staff/Team Members
                    return 3;
                };

                // Sort members by hierarchy level
                $hierarchyLevels = collect($members)->groupBy(function ($member) use ($getHierarchyLevel) {
                    return $getHierarchyLevel($member->position);
                })->sortKeys();

                // Build Mermaid diagram
                $mermaid = "graph TD\n";
                $nodeId = 0;
                $previousLevelNodes = [];

                // Process each level
                foreach ($hierarchyLevels as $level => $levelMembers) {
                    $currentLevelNodes = [];

                    foreach ($levelMembers as $member) {
                        $nodeId++;
                        $nodeName = "node" . $nodeId;
                        $currentLevelNodes[] = $nodeName;

                        // Clean and truncate name for display
                        $displayName = str_replace(['"', "'"], '', $member->member_name);
                        $displayPosition = str_replace(['"', "'"], '', $member->position);

                        // Style based on level
                        if ($level == 1) {
                            $mermaid .= "    {$nodeName}[\"👑 {$displayName}<br/>{$displayPosition}\"]:::level1\n";
                        } elseif ($level == 2) {
                            $mermaid .= "    {$nodeName}[\"⭐ {$displayName}<br/>{$displayPosition}\"]:::level2\n";
                        } else {
                            $mermaid .= "    {$nodeName}[\"👤 {$displayName}<br/>{$displayPosition}\"]:::level3\n";
                        }
                    }

                    // Connect to previous level
                    if (!empty($previousLevelNodes)) {
                        foreach ($previousLevelNodes as $parentNode) {
                            foreach ($currentLevelNodes as $childNode) {
                                $mermaid .= "    {$parentNode} --> {$childNode}\n";
                            }
                        }
                    }

                    $previousLevelNodes = $currentLevelNodes;
                }

                // Add styling
                $mermaid .= "    classDef level1 fill:#1e40af,stroke:#1e3a8a,stroke-width:3px,color:#fff\n";
                $mermaid .= "    classDef level2 fill:#7c3aed,stroke:#6d28d9,stroke-width:2px,color:#fff\n";
                $mermaid .= "    classDef level3 fill:#10b981,stroke:#059669,stroke-width:2px,color:#fff\n";

                // Generate chart URL using QuickChart
                $chartUrl = $this->getMermaidChartUrl($mermaid, 800, 600);

                if ($chartUrl) {
                    $orgCharts[$category] = $chartUrl;
                    Log::info('✅ Organization chart generated for category: ' . $category);
                } else {
                    Log::warning('⚠️ Failed to generate organization chart for category: ' . $category);
                }
            }
        } catch (\Exception $e) {
            Log::error('Organization chart generation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $orgCharts;
    }

    /**
     * Generate Mermaid Chart URL using QuickChart
     */
    private function getMermaidChartUrl($mermaid, $width = 800, $height = 600)
    {
        try {
            $baseUrl = 'https://quickchart.io/chart';

            $params = [
                'chart' => $mermaid,
                'width' => $width,
                'height' => $height,
                'format' => 'png',
                'backgroundColor' => 'white'
            ];

            $queryString = http_build_query($params);
            return $baseUrl . '?' . $queryString;
        } catch (\Exception $e) {
            Log::error('Mermaid Chart URL generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all Forecast Results for 5 years projection
     * Fetches all forecast results regardless of period filter
     */
    private function getAllForecastResults($userId, $businessBackgroundId)
    {
        try {
            // 1. Dapatkan tahun maksimal yang ada di database untuk user dan bisnis ini
            $maxYearInDb = ForecastResult::whereHas('forecastData', function ($query) use ($userId, $businessBackgroundId) {
                $query->where('user_id', $userId)
                    ->whereHas('financialSimulation', function ($simQuery) use ($businessBackgroundId) {
                        $simQuery->where('business_background_id', $businessBackgroundId);
                    });
            })->max('year');

            $currentYear = date('Y');

            // 2. Tentukan endYear secara dinamis
            // Jika ada data di masa depan, ambil tahun maksimal. Jika tidak, minimal currentYear + 2 (total 3 tahun)
            $endYear = $maxYearInDb ? max($maxYearInDb, $currentYear + 2) : ($currentYear + 2);

            Log::info('📊 Dynamic Forecast Range Determined', [
                'current_year' => $currentYear,
                'max_year_in_db' => $maxYearInDb,
                'end_year' => $endYear,
            ]);

            // Query forecast results through forecast_data relation
            // Join with financial_simulations to filter by business_background_id
            $results = ForecastResult::whereHas('forecastData', function ($query) use ($userId, $businessBackgroundId) {
                $query->where('user_id', $userId)
                    ->whereHas('financialSimulation', function ($simQuery) use ($businessBackgroundId) {
                        $simQuery->where('business_background_id', $businessBackgroundId);
                    });
            })
                ->whereBetween('year', [$currentYear, $endYear])
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get()
                ->toArray();

            Log::info('📊 All Forecast Results Retrieved', [
                'user_id' => $userId,
                'business_background_id' => $businessBackgroundId,
                'year_range' => [$currentYear, $endYear],
                'results_count' => count($results),
                'years_found' => collect($results)->pluck('year')->unique()->values()->toArray()
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Error fetching all forecast results: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Forecast Data based on user and period
     */
    private function getForecastData($userId, $periodType, $periodValue)
    {
        try {
            $periodInfo = $this->parsePeriod($periodType, $periodValue);
            $year = $periodInfo['year'];
            $month = $periodInfo['month'];

            // Fetch ForecastData untuk user dan periode tertentu
            $forecastDataQuery = ForecastData::where('user_id', $userId)
                ->where('year', $year);

            if ($month) {
                $forecastDataQuery->where('month', $month);
            }

            $forecastData = $forecastDataQuery->with(['forecastResults', 'insights'])
                ->latest()
                ->first();

            if (!$forecastData) {
                Log::info('⚠️ No forecast data found for period', [
                    'user_id' => $userId,
                    'year' => $year,
                    'month' => $month
                ]);

                return [
                    'forecast_data' => null,
                    'results' => [],
                    'insights' => []
                ];
            }

            // Get forecast results (hasil prediksi bulanan)
            $results = $forecastData->forecastResults()
                ->orderBy('month', 'asc')
                ->get()
                ->toArray();

            // Get forecast insights
            $insights = $forecastData->insights()
                ->orderBy('severity', 'desc') // Critical first
                ->get()
                ->toArray();

            Log::info('✅ Forecast data found', [
                'forecast_data_id' => $forecastData->id,
                'year' => $forecastData->year,
                'month' => $forecastData->month,
                'results_count' => count($results),
                'insights_count' => count($insights)
            ]);

            return [
                'forecast_data' => $forecastData,
                'results' => $results,
                'insights' => $insights
            ];
        } catch (\Exception $e) {
            Log::error('Forecast data fetch error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'period_type' => $periodType,
                'period_value' => $periodValue,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'forecast_data' => null,
                'results' => [],
                'insights' => []
            ];
        }
    }

    /**
     * Generate executive summary untuk forecast
     */
    private function generateForecastExecutiveSummary($forecastData)
    {
        if (empty($forecastData['forecast_data']) || empty($forecastData['results'])) {
            return 'Data forecast tidak tersedia untuk periode ini.';
        }

        $data = $forecastData['forecast_data'];
        $results = $forecastData['results'];

        $stats = $this->calculateForecastStatistics($forecastData);

        $year = $data->year;
        $monthText = $data->month ? "bulan {$data->month} tahun {$year}" : "tahun {$year}";

        $totalIncome = number_format($stats['total_income'], 0, ',', '.');
        $totalExpense = number_format($stats['total_expense'], 0, ',', '.');
        $totalProfit = number_format(abs($stats['total_profit']), 0, ',', '.');
        $profitStatus = $stats['total_profit'] >= 0 ? 'keuntungan' : 'kerugian';

        $avgMargin = number_format($stats['avg_margin'], 2);
        $avgConfidence = number_format($stats['avg_confidence'], 2);
        $growthRate = number_format($stats['growth_rate'], 2);

        $highestIncomeMonth = $stats['highest_income_month'];
        $highestProfitMonth = $stats['highest_profit_month'];

        $summary = "Laporan proyeksi keuangan untuk {$monthText} menunjukkan prediksi total pendapatan sebesar Rp {$totalIncome} dengan total pengeluaran Rp {$totalExpense}, menghasilkan proyeksi {$profitStatus} sebesar Rp {$totalProfit}.\n\n";

        $summary .= "Margin keuntungan rata-rata diproyeksikan sebesar {$avgMargin}% dengan tingkat kepercayaan prediksi rata-rata {$avgConfidence}%. ";
        $summary .= "Tingkat pertumbuhan diperkirakan mencapai {$growthRate}%.\n\n";

        $summary .= "Pendapatan tertinggi diprediksi terjadi pada bulan {$highestIncomeMonth}, ";
        $summary .= "sedangkan laba tertinggi diperkirakan pada bulan {$highestProfitMonth}. ";

        $summary .= "Proyeksi ini dibuat menggunakan metode " . (strtoupper($results[0]['method'] ?? 'ARIMA')) . " dengan mempertimbangkan data historis dan tren pasar.";

        return $summary;
    }

    /**
     * Calculate statistics dari forecast results
     */
    private function calculateForecastStatistics($forecastData)
    {
        if (empty($forecastData['results'])) {
            return [
                'total_income' => 0,
                'total_expense' => 0,
                'total_profit' => 0,
                'avg_margin' => 0,
                'avg_confidence' => 0,
                'growth_rate' => 0,
                'highest_income_month' => '-',
                'highest_income_value' => 0,
                'highest_profit_month' => '-',
                'highest_profit_value' => 0
            ];
        }

        $results = $forecastData['results'];

        $totalIncome = 0;
        $totalExpense = 0;
        $totalProfit = 0;
        $totalMargin = 0;
        $totalConfidence = 0;
        $count = count($results);

        $highestIncome = ['month' => null, 'value' => 0];
        $highestProfit = ['month' => null, 'value' => PHP_FLOAT_MIN];

        foreach ($results as $result) {
            $income = floatval($result['forecast_income'] ?? 0);
            $expense = floatval($result['forecast_expense'] ?? 0);
            $profit = floatval($result['forecast_profit'] ?? 0);
            $margin = floatval($result['forecast_margin'] ?? 0);
            $confidence = floatval($result['confidence_level'] ?? 0);

            $totalIncome += $income;
            $totalExpense += $expense;
            $totalProfit += $profit;
            $totalMargin += $margin;
            $totalConfidence += $confidence;

            // Track highest income
            if ($income > $highestIncome['value']) {
                $highestIncome = [
                    'month' => $result['month'] ?? '-',
                    'value' => $income
                ];
            }

            // Track highest profit
            if ($profit > $highestProfit['value']) {
                $highestProfit = [
                    'month' => $result['month'] ?? '-',
                    'value' => $profit
                ];
            }
        }

        // Calculate growth rate (simple: compare last vs first month)
        $growthRate = 0;
        if ($count >= 2) {
            $firstProfit = floatval($results[0]['forecast_profit'] ?? 0);
            $lastProfit = floatval($results[$count - 1]['forecast_profit'] ?? 0);

            if ($firstProfit != 0) {
                $growthRate = (($lastProfit - $firstProfit) / abs($firstProfit)) * 100;
            }
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_profit' => $totalProfit,
            'avg_margin' => $count > 0 ? ($totalMargin / $count) : 0,
            'avg_confidence' => $count > 0 ? ($totalConfidence / $count) : 0,
            'growth_rate' => $growthRate,
            'highest_income_month' => $highestIncome['month'] ?? '-',
            'highest_income_value' => $highestIncome['value'],
            'highest_profit_month' => $highestProfit['month'] ?? '-',
            'highest_profit_value' => $highestProfit['value']
        ];
    }

    /**
     * Convert workflow images to data URLs for PDF embedding
     * Prevents DOMPDF from fetching images over network during PDF generation
     */
    private function convertWorkflowImagesToDataUrl($operationalPlans)
    {
        $workflowImages = [];

        foreach ($operationalPlans as $plan) {
            try {
                // Check if plan has workflow image path in database
                if (!empty($plan->workflow_image_path)) {
                    // Build the direct file system path
                    $filePath = storage_path('app/public/' . $plan->workflow_image_path);

                    // Check if file actually exists
                    if (file_exists($filePath)) {
                        // Read the file content
                        $imageContent = file_get_contents($filePath);

                        if ($imageContent) {
                            // Determine MIME type
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_buffer($finfo, $imageContent);
                            finfo_close($finfo);

                            // Create data URL
                            $base64 = base64_encode($imageContent);
                            $dataUrl = 'data:' . $mimeType . ';base64,' . $base64;

                            $workflowImages[$plan->id] = $dataUrl;

                            Log::info('✅ Workflow image converted to data URL', [
                                'plan_id' => $plan->id,
                                'file_path' => $filePath,
                                'mime_type' => $mimeType,
                                'data_url_length' => strlen($dataUrl)
                            ]);
                        }
                    } else {
                        Log::warning('⚠️ Workflow image file not found', [
                            'plan_id' => $plan->id,
                            'expected_path' => $filePath,
                            'database_path' => $plan->workflow_image_path
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('❌ Error converting workflow image', [
                    'plan_id' => $plan->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('📊 Workflow Images Conversion Complete', [
            'total_plans' => count($operationalPlans),
            'converted_count' => count($workflowImages),
            'converted_plan_ids' => array_keys($workflowImages)
        ]);

        return $workflowImages;
    }
    /**
     * Convert logo path/URL to base64 data URL for PDF embedding
     */
    private function convertLogoToDataUrl($logoPath)
    {
        try {
            $imageContent = null;

            // Check if it's already a data URL
            if (is_string($logoPath) && strpos($logoPath, 'data:') === 0) {
                return $logoPath;
            }

            // Check if it's a direct file path that exists
            if (is_string($logoPath) && file_exists($logoPath)) {
                $imageContent = file_get_contents($logoPath);
            }
            // Check if it's a full URL
            elseif (is_string($logoPath) && filter_var($logoPath, FILTER_VALIDATE_URL)) {
                // For URLs, try to fetch content
                $imageContent = @file_get_contents($logoPath);
                if ($imageContent === false) {
                    Log::warning('⚠️ Could not fetch logo from URL: ' . $logoPath);
                    return null;
                }
            }
            // Try as relative path in storage
            else {
                $filePath = $logoPath;

                // Try public storage first
                if (file_exists(public_path($filePath))) {
                    $imageContent = file_get_contents(public_path($filePath));
                } elseif (Storage::disk('public')->exists(str_replace('storage/', '', $filePath))) {
                    // Try via Storage facade
                    $imageContent = Storage::disk('public')->get(str_replace('storage/', '', $filePath));
                } else {
                    Log::warning('⚠️ Logo file not found: ' . $filePath);
                    return null;
                }
            }

            if (!$imageContent) {
                Log::warning('⚠️ Could not read logo content from: ' . $logoPath);
                return null;
            }

            // Determine MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $imageContent);
            finfo_close($finfo);

            // Create data URL
            $base64 = base64_encode($imageContent);
            $dataUrl = 'data:' . $mimeType . ';base64,' . $base64;

            Log::info('✅ Logo converted to data URL', ['mime_type' => $mimeType, 'size' => strlen($base64)]);

            return $dataUrl;
        } catch (\Exception $e) {
            Log::error('❌ Error converting logo to data URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate analysis for Income vs Expense chart
     */
    private function generateIncomeVsExpenseAnalysis($summary)
    {
        if (!$summary) {
            return "Data analisis tidak tersedia.";
        }

        $totalIncome = $summary['total_income'] ?? 0;
        $totalExpense = $summary['total_expense'] ?? 0;
        $netProfit = $summary['net_profit'] ?? 0;
        $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        $analysis = "Grafik menunjukkan perbandingan antara total pendapatan Rp " . number_format($totalIncome, 0, ',', '.') .
            " dan pengeluaran Rp " . number_format($totalExpense, 0, ',', '.');

        if ($netProfit > 0) {
            $analysis .= " Bisnis mencapai keuntungan bersih sebesar Rp " . number_format($netProfit, 0, ',', '.') .
                " dengan margin keuntungan " . number_format($profitMargin, 1, ',', '.') . "%.";
        } elseif ($netProfit < 0) {
            $analysis .= " Bisnis mengalami kerugian sebesar Rp " . number_format(abs($netProfit), 0, ',', '.') .
                ". Perhatian diperlukan untuk mengurangi pengeluaran atau meningkatkan pendapatan.";
        } else {
            $analysis .= " Pendapatan dan pengeluaran seimbang tanpa keuntungan atau kerugian.";
        }

        return $analysis;
    }

    /**
     * Generate analysis for Category Income Pie chart
     */
    private function generateCategoryIncomeAnalysis($categorySummary)
    {
        if (!isset($categorySummary['top_income']) || empty($categorySummary['top_income'])) {
            return "Data kategori pendapatan tidak tersedia.";
        }

        $topIncome = $categorySummary['top_income'];
        $topCategory = $topIncome[0] ?? null;

        if (!$topCategory) {
            return "Data kategori pendapatan tidak tersedia.";
        }

        $categoryName = $topCategory['category']->name ?? 'Tidak diketahui';
        $totalAmount = $topCategory['total'] ?? 0;
        $percentageOfTotal = 0;

        // Calculate percentage if we have total income
        $allIncome = collect($topIncome)->sum('total');
        if ($allIncome > 0) {
            $percentageOfTotal = ($totalAmount / $allIncome) * 100;
        }

        $analysis = "Kategori " . strtoupper($categoryName) . " merupakan sumber pendapatan utama dengan kontribusi Rp " .
            number_format($totalAmount, 0, ',', '.') . " (" . number_format($percentageOfTotal, 1, ',', '.') . "%).";

        if (count($topIncome) > 1) {
            $analysis .= " Terdapat " . count($topIncome) . " kategori pendapatan, menunjukkan diversifikasi pendapatan yang baik.";
        } else {
            $analysis .= " Perhatikan bahwa pendapatan terpusat pada satu kategori, risiko konsentrasi tinggi.";
        }

        return $analysis;
    }

    /**
     * Generate analysis for Category Expense Pie chart
     */
    private function generateCategoryExpenseAnalysis($categorySummary)
    {
        if (!isset($categorySummary['top_expense']) || empty($categorySummary['top_expense'])) {
            return "Data kategori pengeluaran tidak tersedia.";
        }

        $topExpense = $categorySummary['top_expense'];
        $topCategory = $topExpense[0] ?? null;

        if (!$topCategory) {
            return "Data kategori pengeluaran tidak tersedia.";
        }

        $categoryName = $topCategory['category']->name ?? 'Tidak diketahui';
        $totalAmount = $topCategory['total'] ?? 0;
        $percentageOfTotal = 0;

        // Calculate percentage if we have total expense
        $allExpense = collect($topExpense)->sum('total');
        if ($allExpense > 0) {
            $percentageOfTotal = ($totalAmount / $allExpense) * 100;
        }

        $analysis = "Kategori " . strtoupper($categoryName) . " adalah pengeluaran terbesar dengan nilai Rp " .
            number_format($totalAmount, 0, ',', '.') . " (" . number_format($percentageOfTotal, 1, ',', '.') . "%).";

        $analysis .= " Terdapat " . count($topExpense) . " kategori pengeluaran.";

        if ($percentageOfTotal > 60) {
            $analysis .= " Pengeluaran terkonsentrasi pada kategori ini, pertimbangkan optimisasi untuk meningkatkan efisiensi.";
        } else {
            $analysis .= " Distribusi pengeluaran cukup merata, menunjukkan pengelolaan biaya yang seimbang.";
        }

        return $analysis;
    }

    /**
     * Generate analysis for Monthly Trend chart
     */
    private function generateMonthlyTrendAnalysis($monthlySummary)
    {
        if (empty($monthlySummary)) {
            return "Data tren bulanan tidak tersedia.";
        }

        $months = collect($monthlySummary);
        $incomes = $months->pluck('total_income');
        $expenses = $months->pluck('total_expense');

        $avgIncome = $incomes->avg();
        $avgExpense = $expenses->avg();
        $maxIncomeMonth = $months->sortByDesc('total_income')->first();
        $minIncomeMonth = $months->sortBy('total_income')->first();

        $analysis = "Tren bulanan menunjukkan rata-rata pendapatan Rp " . number_format($avgIncome, 0, ',', '.') .
            " dan rata-rata pengeluaran Rp " . number_format($avgExpense, 0, ',', '.') . ".";

        if ($maxIncomeMonth) {
            $maxMonth = $maxIncomeMonth['month'] ?? 'Tidak diketahui';
            $maxAmount = $maxIncomeMonth['total_income'] ?? 0;
            $analysis .= " Pendapatan tertinggi terjadi pada " . $maxMonth . " sebesar Rp " .
                number_format($maxAmount, 0, ',', '.') . ".";
        }

        if ($minIncomeMonth) {
            $minMonth = $minIncomeMonth['month'] ?? 'Tidak diketahui';
            $minAmount = $minIncomeMonth['total_income'] ?? 0;
            $analysis .= " Sementara pendapatan terendah pada " . $minMonth . " sebesar Rp " .
                number_format($minAmount, 0, ',', '.') . ".";
        }

        $analysis .= " Pola ini membantu identifikasi musim ramai dan sepi untuk perencanaan cash flow yang lebih baik.";

        return $analysis;
    }
}
