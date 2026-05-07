<?php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\BusinessBackground;
use App\Models\ManagementFinancial\FinancialCategory;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialSummary;
use App\Models\ManagementFinancial\FinancialProjection;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PdfFinancialReportController extends Controller
{
    /**
     * Generate PDF Laporan Keuangan
     */
    public function generatePdf(Request $request)
    {
        try {
            Log::info('Financial PDF Generation Started', [
                'user_id' => $request->user_id,
                'business_background_id' => $request->business_background_id,
                'period_type' => $request->period_type,
                'period_value' => $request->period_value
            ]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'period_type' => 'required|in:year,month',
                'period_value' => 'required', // Format: 2025 atau 2025-01
                'charts' => 'nullable|array'
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

            // Ambil semua data keuangan
            $financialData = $this->getFinancialData($userId, $businessBackgroundId, $periodType, $periodValue);

            if (!$financialData['business_background']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background not found'
                ], 404);
            }

            // Generate Executive Summary
            $executiveSummary = $this->createExecutiveSummary($financialData);

            // Generate Charts
            $charts = $this->generateCharts($financialData, $periodType);

            Log::info('Financial Data Collected', [
                'business_name' => $financialData['business_background']->name,
                'categories_count' => count($financialData['categories']),
                'simulations_count' => count($financialData['simulations']),
                'projections_count' => count($financialData['projections'])
            ]);

            // Generate PDF
            $pdf = PDF::loadView('pdf.financial-report', [
                'data' => $financialData,
                'executiveSummary' => $executiveSummary,
                'charts' => $charts,
                'period_type' => $periodType,
                'period_value' => $periodValue,
                'period_label' => $this->getPeriodLabel($periodType, $periodValue),
                'generated_at' => now()->format('d F Y H:i:s')
            ]);

            // Konfigurasi PDF
            $pdf->setPaper('A4', 'landscape'); // Landscape untuk tabel yang lebar
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'enable_php' => false
            ]);

            // Generate filename
            $filename = "laporan-keuangan-" .
                Str::slug($financialData['business_background']->name) . "-" .
                str_replace(['/', '-'], '', $periodValue) . "-" .
                now()->format('Ymd-His') . ".pdf";

            // Log success
            Log::info('Financial PDF Generated Successfully', [
                'filename' => $filename,
                'user_id' => $userId
            ]);

            // Return PDF sebagai download
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Financial PDF Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all financial data for PDF
     */
    private function getFinancialData($userId, $businessBackgroundId, $periodType, $periodValue)
    {
        // Parse period
        $periodData = $this->parsePeriod($periodType, $periodValue);

        // Get business background
        $businessBackground = BusinessBackground::where('id', $businessBackgroundId)
            ->where('user_id', $userId)
            ->first();

        // Get categories
        $categories = FinancialCategory::where('user_id', $userId)
            ->where('status', 'actual')
            ->get();

        // Get simulations for period
        $simulationsQuery = FinancialSimulation::with('category')
            ->where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->where('status', 'completed');

        if ($periodType === 'year') {
            $simulationsQuery->where('year', $periodData['year']);
        } else {
            $simulationsQuery->where('year', $periodData['year'])
                ->whereMonth('simulation_date', $periodData['month']);
        }

        $simulations = $simulationsQuery->orderBy('simulation_date', 'asc')->get();

        // Get ALL simulations for current cash calculation
        $allSimulations = FinancialSimulation::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->where('status', 'completed')
            ->get();

        // Calculate summary
        $summary = $this->calculateSummary($simulations, $allSimulations, $businessBackground);

        // Get category summary
        $categorySummary = $this->getCategorySummary($simulations, $categories);

        // Get monthly summary (for year view)
        $monthlySummary = [];
        if ($periodType === 'year') {
            $monthlySummary = $this->getMonthlySummary($simulations, $periodData['year']);
        }

        // Get latest projections (one for each scenario)
        $projections = FinancialProjection::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->whereIn('scenario_type', ['optimistic', 'realistic', 'pessimistic'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('scenario_type')
            ->map(function ($group) {
                return $group->first(); // Get latest for each scenario
            })
            ->values();

        Log::info('Projections Data', [
            'count' => $projections->count(),
            'projections' => $projections->map(function ($p) {
                return [
                    'id' => $p->id,
                    'scenario_type' => $p->scenario_type,
                    'npv' => $p->npv,
                    'roi' => $p->roi,
                    'yearly_projections_count' => is_array($p->yearly_projections) ? count($p->yearly_projections) : 0
                ];
            })
        ]);

        return [
            'business_background' => $businessBackground,
            'categories' => $categories,
            'simulations' => $simulations,
            'summary' => $summary,
            'category_summary' => $categorySummary,
            'monthly_summary' => $monthlySummary,
            'projections' => $projections,
            'period' => $periodData
        ];
    }

    /**
     * Parse period value
     */
    private function parsePeriod($periodType, $periodValue)
    {
        if ($periodType === 'year') {
            return [
                'year' => (int)$periodValue,
                'month' => null
            ];
        } else {
            // Format: 2025-01
            $parts = explode('-', $periodValue);
            return [
                'year' => (int)$parts[0],
                'month' => (int)$parts[1]
            ];
        }
    }

    /**
     * Get period label for display
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
     * Calculate summary statistics
     */
    private function calculateSummary($simulations, $allSimulations, $businessBackground)
    {
        $totalIncome = $simulations->where('type', 'income')->sum('amount');
        $totalExpense = $simulations->where('type', 'expense')->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        // Calculate current cash balance
        $accumulatedIncome = $allSimulations->where('type', 'income')->sum('amount');
        $accumulatedExpense = $allSimulations->where('type', 'expense')->sum('amount');

        // Get initial investment from latest financial projection (preferably realistic scenario)
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

        // Sort by total descending
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
     * Get monthly summary for year view
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
     * Create executive summary
     */
    private function createExecutiveSummary($data)
    {
        $summary = $data['summary'];
        $categorySummary = $data['category_summary'];

        $profitStatus = $summary['net_profit'] >= 0 ? 'profit' : 'loss';
        $profitPercentage = $summary['total_income'] > 0
            ? ($summary['net_profit'] / $summary['total_income']) * 100
            : 0;

        // Top performers
        $topIncomeCategory = !empty($categorySummary['top_income'])
            ? $categorySummary['top_income'][0]['category']->name
            : '-';

        $topExpenseCategory = !empty($categorySummary['top_expense'])
            ? $categorySummary['top_expense'][0]['category']->name
            : '-';

        return [
            'profit_status' => $profitStatus,
            'profit_percentage' => round($profitPercentage, 2),
            'top_income_category' => $topIncomeCategory,
            'top_expense_category' => $topExpenseCategory,
            'cash_health' => $summary['current_cash_balance'] > 0 ? 'healthy' : 'critical'
        ];
    }

    /**
     * Generate charts for PDF using QuickChart API
     */
    private function generateCharts($data, $periodType)
    {
        $charts = [];

        try {
            // 1. Income vs Expense Chart (Bar)
            $incomeVsExpenseUrl = $this->generateIncomeVsExpenseChart($data);
            if ($incomeVsExpenseUrl) {
                $charts['income_vs_expense'] = $incomeVsExpenseUrl;
            }

            // 2. Monthly Trend Chart (Line) - Only for year view
            if ($periodType === 'year' && !empty($data['monthly_summary'])) {
                $monthlyTrendUrl = $this->generateMonthlyTrendChart($data['monthly_summary']);
                if ($monthlyTrendUrl) {
                    $charts['monthly_trend'] = $monthlyTrendUrl;
                }
            }

            // 3. Category Distribution (Pie Charts)
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
            // Return empty charts if generation fails
        }

        Log::info('Charts Generated', [
            'income_vs_expense' => isset($charts['income_vs_expense']) ? 'YES' : 'NO',
            'monthly_trend' => isset($charts['monthly_trend']) ? 'YES' : 'NO',
            'projection_comparison' => isset($charts['projection_comparison']) ? 'YES' : 'NO',
            'category_income_pie' => isset($charts['category_income_pie']) ? 'YES' : 'NO',
            'category_expense_pie' => isset($charts['category_expense_pie']) ? 'YES' : 'NO',
        ]);

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
                'labels' => ['Pendapatan', 'Pengeluaran', 'Laba Bersih'],
                'datasets' => [[
                    'label' => 'Jumlah (Rp)',
                    'data' => [
                        $summary['total_income'],
                        $summary['total_expense'],
                        $summary['net_profit']
                    ],
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)', // Green
                        'rgba(239, 68, 68, 0.8)',  // Red
                        $summary['net_profit'] >= 0 ? 'rgba(59, 130, 246, 0.8)' : 'rgba(239, 68, 68, 0.8)' // Blue or Red
                    ]
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Pendapatan vs Pengeluaran',
                        'font' => ['size' => 16]
                    ],
                    'legend' => ['display' => false]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => '(value) => "Rp " + value.toLocaleString("id-ID")'
                        ]
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
        $profitData = array_map(fn($m) => $m['net_profit'], $monthlySummary);

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $incomeData,
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'data' => $expenseData,
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'fill' => true
                    ],
                    [
                        'label' => 'Laba Bersih',
                        'data' => $profitData,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true
                    ]
                ]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Tren Bulanan',
                        'font' => ['size' => 16]
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
            if ($index >= 5) break; // Top 5 only
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
                        'text' => 'Top 5 Kategori ' . ($type === 'income' ? 'Pendapatan' : 'Pengeluaran'),
                        'font' => ['size' => 16]
                    ],
                    'legend' => [
                        'position' => 'bottom'
                    ]
                ]
            ]
        ];

        return $this->getQuickChartUrl($chartConfig, 600, 400);
    }

    /**
     * Generate QuickChart URL
     */
    private function getQuickChartUrl($config, $width = 800, $height = 400)
    {
        try {
            $chartUrl = 'https://quickchart.io/chart';
            $chartConfig = [
                'chart' => $config,
                'width' => $width,
                'height' => $height,
                'backgroundColor' => 'white',
                'format' => 'png'
            ];

            // Use QuickChart short URL API to avoid long URLs
            $response = @file_get_contents('https://quickchart.io/chart/create', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($chartConfig),
                    'timeout' => 10
                ]
            ]));

            if ($response) {
                $result = json_decode($response, true);
                if (isset($result['url'])) {
                    Log::info('QuickChart Short URL Created', ['short_url' => $result['url']]);
                    return $result['url'];
                }
            }

            // Fallback to direct URL if short URL fails
            $baseUrl = 'https://quickchart.io/chart';
            $params = [
                'c' => json_encode($config),
                'w' => $width,
                'h' => $height,
                'bkg' => 'white',
                'f' => 'png'
            ];

            $fullUrl = $baseUrl . '?' . http_build_query($params);
            Log::warning('Using fallback QuickChart URL', ['url_length' => strlen($fullUrl)]);
            return $fullUrl;
        } catch (\Exception $e) {
            Log::error('QuickChart URL generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get statistics (untuk tracking)
     */
    public function getStatistics(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // 'user_id' removed as we use Auth::id()
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Count PDF generations (bisa track via logs atau buat tabel terpisah)
            $stats = [
                'total_generated' => 0, // TODO: Implement tracking if needed
                'last_generated' => null
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
