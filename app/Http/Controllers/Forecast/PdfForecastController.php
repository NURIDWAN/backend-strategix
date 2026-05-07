<?php

namespace App\Http\Controllers\Forecast;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Forecast\ForecastData;
use App\Models\Forecast\ForecastResult;
use App\Models\Forecast\ForecastInsight;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfForecastController extends Controller
{
    /**
     * Generate PDF Forecast Report
     */
    public function generatePdf(Request $request)
    {
        try {
            Log::info('Forecast PDF Generation Started', [
                'user_id' => Auth::id(),
                'forecast_data_id' => $request->forecast_data_id,
                'mode' => $request->mode
            ]);

            $userId = Auth::id();
            $forecastDataId = $request->forecast_data_id;
            $mode = $request->mode ?? 'free';

            // Validasi input
            if (!$forecastDataId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Forecast data ID is required'
                ], 422);
            }

            // Check if export all data
            if ($forecastDataId === 'all') {
                return $this->generateAllForecastsPdf($userId, $mode, $request);
            }

            // Ambil forecast data dengan relasi
            $forecastData = $this->getForecastData($userId, $forecastDataId);

            if (!$forecastData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Forecast data not found'
                ], 404);
            }

            // Generate executive summary
            $executiveSummary = $this->createExecutiveSummary($forecastData);

            // Calculate statistics
            $statistics = $this->calculateStatistics($forecastData);

            // Log data yang ditemukan
            Log::info('Forecast Data Found', [
                'year' => $forecastData->year,
                'month' => $forecastData->month,
                'results_count' => $forecastData->forecastResults->count(),
                'insights_count' => $forecastData->insights->count()
            ]);

            // Jika preview mode, return data saja
            if ($request->has('preview') && $request->preview) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'preview_data' => [
                            'forecast_data' => $forecastData,
                            'statistics' => $statistics,
                        ],
                        'filename' => "forecast-report-" . $forecastData->year . "-" . ($forecastData->month ?? 'yearly') . "-" . now()->format('Y-m-d') . ".pdf",
                        'mode' => $mode
                    ],
                    'message' => 'Preview data generated successfully'
                ]);
            }

            // Generate PDF
            // Convert forecast results ke array untuk template
            $forecastResults = $forecastData->forecastResults->map(function($result) {
                return [
                    'month' => $result->month,
                    'forecast_income' => $result->forecast_income,
                    'forecast_expense' => $result->forecast_expense,
                    'forecast_profit' => $result->forecast_profit,
                    'forecast_margin' => $result->forecast_margin,
                    'confidence_level' => $result->confidence_level,
                ];
            })->toArray();

            // Convert insights ke array untuk template
            $insights = $forecastData->insights->map(function($insight) {
                return [
                    'title' => $insight->title,
                    'description' => $insight->description,
                    'severity' => $insight->severity,
                    'value' => $insight->value,
                ];
            })->toArray();

            $pdf = PDF::loadView('pdf.forecast-report', [
                'forecastData' => $forecastData,
                'mode' => $mode,
                'executiveSummary' => $executiveSummary,
                'statistics' => $statistics,
                'forecastResults' => $forecastResults,
                'insights' => $insights,
                'generated_at' => now()->format('d F Y H:i:s')
            ]);

            // Konfigurasi PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'chroot' => [public_path(), storage_path()],
                'enable_php' => true,
            ]);

            $monthLabel = $forecastData->month ? "month-{$forecastData->month}" : 'yearly';
            $filename = "forecast-report-{$forecastData->year}-{$monthLabel}-" . now()->format('Y-m-d') . ".pdf";

            // Encode PDF ke base64
            $pdfContent = $pdf->output();
            $pdfBase64 = base64_encode($pdfContent);

            Log::info('Forecast PDF Generated Successfully', [
                'filename' => $filename,
                'file_size' => strlen($pdfContent),
                'base64_size' => strlen($pdfBase64)
            ]);

            // Return sebagai JSON dengan base64 PDF
            return response()->json([
                'status' => 'success',
                'data' => [
                    'filename' => $filename,
                    'pdf_base64' => $pdfBase64,
                    'file_size' => strlen($pdfContent),
                    'mime_type' => 'application/pdf'
                ],
                'message' => 'Forecast PDF generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating Forecast PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'forecast_data_id' => $request->forecast_data_id ?? 'null'
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get forecast data dengan error handling
     */
    private function getForecastData($userId, $forecastDataId)
    {
        try {
            $forecastData = ForecastData::with([
                'user',
                'financialSimulation',
                'forecastResults' => function($query) {
                    $query->orderBy('month');
                },
                'insights' => function($query) {
                    $query->orderBy('severity', 'desc');
                }
            ])
            ->where('user_id', $userId)
            ->where('id', $forecastDataId)
            ->first();

            return $forecastData;
        } catch (\Exception $e) {
            Log::error('Error getting forecast data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create executive summary from forecast data
     */
    private function createExecutiveSummary($forecastData)
    {
        $monthName = $forecastData->month
            ? $this->getMonthName($forecastData->month)
            : 'Seluruh Tahun';

        $summary = "RINGKASAN EKSEKUTIF FORECAST\n\n";

        if ($forecastData->month) {
            $summary .= "Laporan ini berisi proyeksi keuangan untuk bulan {$monthName} {$forecastData->year}. ";
        } else {
            $summary .= "Laporan ini berisi proyeksi keuangan untuk tahun {$forecastData->year}. ";
        }

        // Data historis
        $totalHistoricalIncome = $forecastData->income_sales + $forecastData->income_other;
        $totalHistoricalExpense = $forecastData->expense_operational + $forecastData->expense_other;
        $historicalProfit = $totalHistoricalIncome - $totalHistoricalExpense;

        $summary .= "Berdasarkan data historis, tercatat pendapatan sebesar Rp " . number_format($totalHistoricalIncome, 0, ',', '.') .
                   " dengan pengeluaran sebesar Rp " . number_format($totalHistoricalExpense, 0, ',', '.') .
                   ", menghasilkan laba sebesar Rp " . number_format($historicalProfit, 0, ',', '.') . ".\n\n";

        // Forecast summary
        if ($forecastData->forecastResults->count() > 0) {
            $totalForecastIncome = $forecastData->forecastResults->sum('forecast_income');
            $totalForecastExpense = $forecastData->forecastResults->sum('forecast_expense');
            $totalForecastProfit = $forecastData->forecastResults->sum('forecast_profit');
            $avgMargin = $forecastData->forecastResults->avg('forecast_margin');
            $avgConfidence = $forecastData->forecastResults->avg('confidence_level');

            $summary .= "Proyeksi menunjukkan total pendapatan diperkirakan mencapai Rp " .
                       number_format($totalForecastIncome, 0, ',', '.') .
                       " dengan pengeluaran Rp " . number_format($totalForecastExpense, 0, ',', '.') .
                       ", menghasilkan laba bersih sebesar Rp " . number_format($totalForecastProfit, 0, ',', '.') . ". ";

            $summary .= "Rata-rata margin keuntungan diproyeksikan sebesar " . number_format($avgMargin, 2) . "% ";
            $summary .= "dengan tingkat kepercayaan prediksi " . number_format($avgConfidence, 2) . "%.\n\n";
        }

        // Insights summary
        $criticalInsights = $forecastData->insights->where('severity', 'critical')->count();
        $warningInsights = $forecastData->insights->where('severity', 'warning')->count();
        $positiveInsights = $forecastData->insights->where('severity', 'positive')->count();

        if ($criticalInsights > 0 || $warningInsights > 0) {
            $summary .= "Analisis sistem mendeteksi ";
            if ($criticalInsights > 0) {
                $summary .= "{$criticalInsights} insight kritis ";
            }
            if ($warningInsights > 0) {
                $summary .= "{$warningInsights} peringatan ";
            }
            $summary .= "yang perlu mendapat perhatian khusus. ";
        }

        if ($positiveInsights > 0) {
            $summary .= "Terdapat {$positiveInsights} peluang positif yang dapat dioptimalkan.";
        }

        $method = $forecastData->forecastResults->first()->method ?? 'ARIMA';
        $summary .= "\n\nLaporan ini dibuat menggunakan metode prediksi {$method} ";
        $summary .= "untuk memberikan proyeksi yang akurat dan dapat diandalkan dalam pengambilan keputusan bisnis.";

        return $summary;
    }

    /**
     * Calculate statistics from forecast data
     */
    private function calculateStatistics($forecastData)
    {
        $results = $forecastData->forecastResults;

        if ($results->isEmpty()) {
            return [
                'total_income' => 0,
                'total_expense' => 0,
                'total_profit' => 0,
                'avg_margin' => 0,
                'avg_confidence' => 0,
                'highest_income_month' => null,
                'lowest_income_month' => null,
                'highest_profit_month' => null,
                'lowest_profit_month' => null,
                'growth_rate' => 0,
            ];
        }

        $totalIncome = $results->sum('forecast_income');
        $totalExpense = $results->sum('forecast_expense');
        $totalProfit = $results->sum('forecast_profit');
        $avgMargin = $results->avg('forecast_margin');
        $avgConfidence = $results->avg('confidence_level');

        // Find peak months
        $highestIncomeResult = $results->sortByDesc('forecast_income')->first();
        $lowestIncomeResult = $results->sortBy('forecast_income')->first();
        $highestProfitResult = $results->sortByDesc('forecast_profit')->first();
        $lowestProfitResult = $results->sortBy('forecast_profit')->first();

        // Calculate growth rate
        $firstMonthIncome = $results->first()->forecast_income ?? 0;
        $lastMonthIncome = $results->last()->forecast_income ?? 0;
        $growthRate = $firstMonthIncome > 0
            ? (($lastMonthIncome - $firstMonthIncome) / $firstMonthIncome) * 100
            : 0;

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_profit' => $totalProfit,
            'avg_margin' => $avgMargin,
            'avg_confidence' => $avgConfidence,
            'highest_income_month' => $highestIncomeResult ? $highestIncomeResult->month : null,
            'highest_income_value' => $highestIncomeResult ? $highestIncomeResult->forecast_income : 0,
            'lowest_income_month' => $lowestIncomeResult ? $lowestIncomeResult->month : null,
            'lowest_income_value' => $lowestIncomeResult ? $lowestIncomeResult->forecast_income : 0,
            'highest_profit_month' => $highestProfitResult ? $highestProfitResult->month : null,
            'highest_profit_value' => $highestProfitResult ? $highestProfitResult->forecast_profit : 0,
            'lowest_profit_month' => $lowestProfitResult ? $lowestProfitResult->month : null,
            'lowest_profit_value' => $lowestProfitResult ? $lowestProfitResult->forecast_profit : 0,
            'growth_rate' => $growthRate,
        ];
    }

    /**
     * Get month name in Indonesian
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[$month] ?? '';
    }

    /**
     * Get PDF statistics
     */
    /**
     * Generate PDF for ALL forecasts (from all years)
     */
    private function generateAllForecastsPdf($userId, $mode, $request)
    {
        try {
            // Ambil semua forecast data dari user
            $allForecasts = ForecastData::where('user_id', $userId)
                ->with(['forecastResults' => function($q) { $q->orderBy('month'); }, 'insights'])
                ->orderBy('year')
                ->get();

            if ($allForecasts->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada forecast data yang tersedia'
                ], 404);
            }

            // Combine semua forecast results dari semua tahun
            $allForecastResults = [];
            $allInsights = [];
            $combinedStatistics = [
                'total_income' => 0,
                'total_expense' => 0,
                'total_profit' => 0,
                'avg_margin' => 0,
                'avg_confidence' => 0,
                'highest_income_month' => null,
                'highest_income_value' => 0,
                'highest_profit_month' => null,
                'highest_profit_value' => 0,
                'growth_rate' => 0,
            ];

            $totalResults = 0;
            $totalMargin = 0;
            $totalConfidence = 0;

            foreach ($allForecasts as $forecastData) {
                // Add forecast results
                foreach ($forecastData->forecastResults as $result) {
                    $allForecastResults[] = [
                        'year' => $forecastData->year,
                        'month' => $result->month,
                        'forecast_income' => $result->forecast_income,
                        'forecast_expense' => $result->forecast_expense,
                        'forecast_profit' => $result->forecast_profit,
                        'forecast_margin' => $result->forecast_margin,
                        'confidence_level' => $result->confidence_level,
                    ];

                    // Calculate statistics
                    $combinedStatistics['total_income'] += $result->forecast_income;
                    $combinedStatistics['total_expense'] += $result->forecast_expense;
                    $combinedStatistics['total_profit'] += $result->forecast_profit;
                    $totalMargin += $result->forecast_margin;
                    $totalConfidence += $result->confidence_level;
                    $totalResults++;

                    // Check highest income
                    if ($result->forecast_income > $combinedStatistics['highest_income_value']) {
                        $combinedStatistics['highest_income_value'] = $result->forecast_income;
                        $combinedStatistics['highest_income_month'] = "Bulan {$result->month} Tahun {$forecastData->year}";
                    }

                    // Check highest profit
                    if ($result->forecast_profit > $combinedStatistics['highest_profit_value']) {
                        $combinedStatistics['highest_profit_value'] = $result->forecast_profit;
                        $combinedStatistics['highest_profit_month'] = "Bulan {$result->month} Tahun {$forecastData->year}";
                    }
                }

                // Add insights
                foreach ($forecastData->insights as $insight) {
                    $allInsights[] = [
                        'title' => $insight->title,
                        'description' => $insight->description,
                        'severity' => $insight->severity,
                        'value' => $insight->value,
                    ];
                }
            }

            // Calculate averages
            if ($totalResults > 0) {
                $combinedStatistics['avg_margin'] = $totalMargin / $totalResults;
                $combinedStatistics['avg_confidence'] = $totalConfidence / $totalResults;
            }

            // Create executive summary for all data
            $executiveSummary = $this->createAllForecastsSummary($allForecasts, $combinedStatistics);

            // Generate PDF dengan semua data
            $pdf = PDF::loadView('pdf.forecast-report', [
                'forecastData' => (object)[
                    'year' => now()->year,
                    'month' => null,
                    'income_sales' => 0,
                    'expense_operational' => 0,
                ],
                'mode' => $mode,
                'executiveSummary' => $executiveSummary,
                'statistics' => $combinedStatistics,
                'forecastResults' => $allForecastResults,
                'insights' => $allInsights,
                'generated_at' => now()->format('d F Y H:i:s'),
                'is_combined' => true,
            ]);

            // Konfigurasi PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'chroot' => [public_path(), storage_path()],
                'enable_php' => true,
            ]);

            $filename = "forecast-report-combined-" . now()->format('Y-m-d-His') . ".pdf";

            // Encode PDF ke base64
            $pdfContent = $pdf->output();
            $pdfBase64 = base64_encode($pdfContent);

            Log::info('Combined Forecast PDF Generated Successfully', [
                'filename' => $filename,
                'total_results' => $totalResults,
                'total_forecasts' => $allForecasts->count(),
                'file_size' => strlen($pdfContent)
            ]);

            // Return sebagai JSON dengan base64 PDF
            return response()->json([
                'status' => 'success',
                'data' => [
                    'filename' => $filename,
                    'pdf_base64' => $pdfBase64,
                    'file_size' => strlen($pdfContent),
                    'mime_type' => 'application/pdf'
                ],
                'message' => 'Combined Forecast PDF generated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating combined Forecast PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate combined PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create executive summary for all forecasts
     */
    private function createAllForecastsSummary($allForecasts, $statistics)
    {
        $yearsCount = $allForecasts->count();
        $years = $allForecasts->pluck('year')->implode(', ');

        $summary = "RINGKASAN EKSEKUTIF FORECAST GABUNGAN\n\n";
        $summary .= "Laporan ini berisi proyeksi keuangan gabungan dari tahun: {$years}. ";
        $summary .= "Data mencakup {$yearsCount} tahun dengan total proyeksi yang komprehensif.\n\n";

        $summary .= "RINGKASAN FINANSIAL:\n";
        $summary .= "Total Pendapatan Proyeksi: Rp " . number_format($statistics['total_income'], 0, ',', '.') . "\n";
        $summary .= "Total Pengeluaran Proyeksi: Rp " . number_format($statistics['total_expense'], 0, ',', '.') . "\n";
        $summary .= "Total Laba Proyeksi: Rp " . number_format($statistics['total_profit'], 0, ',', '.') . "\n";
        $summary .= "Rata-rata Margin: " . number_format($statistics['avg_margin'], 2) . "%\n";
        $summary .= "Rata-rata Confidence Level: " . number_format($statistics['avg_confidence'], 2) . "%\n\n";

        $summary .= "Laporan ini memberikan gambaran komprehensif tentang proyeksi keuangan bisnis Anda dalam jangka panjang.";

        return $summary;
    }

    public function getPdfStatistics(Request $request)
    {
        try {
            $userId = Auth::id();

            $totalForecasts = ForecastData::where('user_id', $userId)->count();
            $recentForecasts = ForecastData::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(['id', 'year', 'month', 'updated_at']);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_forecasts' => $totalForecasts,
                    'recent_forecasts' => $recentForecasts,
                ],
                'message' => 'PDF statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting PDF statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get PDF statistics'
            ], 500);
        }
    }
}
