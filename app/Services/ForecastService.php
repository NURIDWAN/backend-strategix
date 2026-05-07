<?php

namespace App\Services;

use App\Models\Forecast\ForecastData;
use App\Models\Forecast\ForecastResult;
use App\Models\Forecast\ForecastInsight;

class ForecastService
{
    /**
     * Generate forecast menggunakan metode ARIMA atau exponential smoothing
     * $forecastMonths default 12 (1 tahun), bisa diset 120 untuk 10 tahun
     */
    public function generateForecast(ForecastData $forecastData, string $method = 'auto', int $forecastMonths = 12)
    {
        // Limit forecast months (max 120 untuk 10 tahun)
        $forecastMonths = min($forecastMonths, 120);

        $historicalData = $forecastData->toArray();

        // Pilih metode berdasarkan parameter
        if ($method === 'auto' || $method === 'arima') {
            $results = $this->arimaForecast($forecastData, $forecastMonths);
        } else {
            $results = $this->exponentialSmoothing($forecastData, $forecastMonths);
        }

        return $results;
    }

    /**
     * ARIMA-like forecast menggunakan exponential smoothing & trend analysis
     */
    private function arimaForecast(ForecastData $forecastData, int $forecastMonths = 12)
    {
        $results = [];

        // Ambil historical data (12 bulan terakhir atau lebih)
        $historicalIncomes = [];
        $historicalExpenses = [];

        // Simulasi: ambil data dari forecast_data (jika ada historical)
        // Untuk MVP, kita gunakan seasonal factor dan trend

        $baseIncome = $forecastData->income_sales + $forecastData->income_other;
        $baseExpense = $forecastData->expense_operational + $forecastData->expense_other;
        $seasonalFactor = $forecastData->seasonal_factor ?? 1.0;

        // Generate forecast untuk 12 bulan ke depan
        for ($i = 1; $i <= $forecastMonths; $i++) {
            // Simulasi trend (bisa dari analisis historical data)
            $trendFactor = 1 + (($i - 1) * 0.02); // 2% growth per bulan
            $seasonalAdjustment = sin(($i / 12) * pi()) * 0.3 + 1; // Seasonal variation

            $forecastedIncome = $baseIncome * $trendFactor * $seasonalAdjustment * $seasonalFactor;
            $forecastedExpense = $baseExpense * $trendFactor * ($seasonalAdjustment * 0.8 + 0.2);
            $forecastedProfit = $forecastedIncome - $forecastedExpense;
            $margin = ($forecastedIncome > 0) ? ($forecastedProfit / $forecastedIncome) * 100 : 0;

            $results[] = [
                'month' => $i,
                'year' => $forecastData->year,
                'forecast_income' => round($forecastedIncome, 2),
                'forecast_expense' => round($forecastedExpense, 2),
                'forecast_profit' => round($forecastedProfit, 2),
                'forecast_margin' => round($margin, 2),
                'confidence_level' => round(85 - ($i * 0.5), 2), // Confidence decreases over time
                'method' => 'arima',
            ];
        }

        return $results;
    }

    /**
     * Exponential Smoothing Forecast
     */
    private function exponentialSmoothing(ForecastData $forecastData, int $forecastMonths = 12)
    {
        $results = [];
        $alpha = 0.3; // Smoothing factor

        $baseIncome = $forecastData->income_sales + $forecastData->income_other;
        $baseExpense = $forecastData->expense_operational + $forecastData->expense_other;

        $smoothedIncome = $baseIncome;
        $smoothedExpense = $baseExpense;

        for ($i = 1; $i <= $forecastMonths; $i++) {
            // Simple exponential smoothing dengan seasonal adjustment
            $seasonalFactor = 1 + (sin(($i / 12) * pi()) * 0.2);

            $smoothedIncome = ($alpha * $baseIncome * $seasonalFactor) + ((1 - $alpha) * $smoothedIncome);
            $smoothedExpense = ($alpha * $baseExpense * $seasonalFactor) + ((1 - $alpha) * $smoothedExpense);

            $forecastedProfit = $smoothedIncome - $smoothedExpense;
            $margin = ($smoothedIncome > 0) ? ($forecastedProfit / $smoothedIncome) * 100 : 0;

            $results[] = [
                'month' => $i,
                'year' => $forecastData->year,
                'forecast_income' => round($smoothedIncome, 2),
                'forecast_expense' => round($smoothedExpense, 2),
                'forecast_profit' => round($forecastedProfit, 2),
                'forecast_margin' => round($margin, 2),
                'confidence_level' => round(90 - ($i * 0.3), 2),
                'method' => 'exponential_smoothing',
            ];
        }

        return $results;
    }

    /**
     * Generate Auto Insights dari forecast results
     */
    public function generateInsights(ForecastData $forecastData, array $forecastResults)
    {
        $insights = [];

        // 1. Bulan dengan income tertinggi
        $maxIncomeMonth = collect($forecastResults)->max('forecast_income');
        $maxIncomeResult = collect($forecastResults)->firstWhere('forecast_income', $maxIncomeMonth);

        if ($maxIncomeResult) {
            $insights[] = [
                'insight_type' => 'peak_income',
                'title' => 'Bulan Pendapatan Tertinggi',
                'description' => 'Bulan ' . $maxIncomeResult['month'] . ' diproyeksikan memiliki pendapatan tertinggi',
                'value' => $maxIncomeMonth,
                'month' => $maxIncomeResult['month'],
                'year' => $maxIncomeResult['year'],
                'severity' => 'positive',
            ];
        }

        // 2. Bulan dengan expense tertinggi
        $maxExpenseMonth = collect($forecastResults)->max('forecast_expense');
        $maxExpenseResult = collect($forecastResults)->firstWhere('forecast_expense', $maxExpenseMonth);

        if ($maxExpenseResult) {
            $insights[] = [
                'insight_type' => 'peak_expense',
                'title' => 'Bulan Pengeluaran Tertinggi',
                'description' => 'Bulan ' . $maxExpenseResult['month'] . ' diproyeksikan memiliki pengeluaran tertinggi',
                'value' => $maxExpenseMonth,
                'month' => $maxExpenseResult['month'],
                'year' => $maxExpenseResult['year'],
                'severity' => 'warning',
            ];
        }

        // 3. Bulan dengan risiko kerugian
        $riskMonths = collect($forecastResults)->filter(function ($result) {
            return $result['forecast_profit'] < 0;
        });

        if ($riskMonths->isNotEmpty()) {
            $insights[] = [
                'insight_type' => 'loss_risk',
                'title' => 'Risiko Kerugian Terdeteksi',
                'description' => 'Terdapat ' . $riskMonths->count() . ' bulan dengan proyeksi kerugian',
                'value' => $riskMonths->count(),
                'severity' => 'critical',
            ];
        }

        // 4. Break-even point
        $breakEvenMonth = collect($forecastResults)
            ->filter(function ($result) {
                return $result['forecast_profit'] >= 0;
            })
            ->first();

        if ($breakEvenMonth) {
            $insights[] = [
                'insight_type' => 'break_even',
                'title' => 'Estimasi Break-Even',
                'description' => 'Break-even diproyeksikan pada bulan ' . $breakEvenMonth['month'],
                'value' => $breakEvenMonth['month'],
                'month' => $breakEvenMonth['month'],
                'year' => $breakEvenMonth['year'],
                'severity' => 'positive',
            ];
        }

        // 5. Margin Profit tertinggi
        $maxMargin = collect($forecastResults)->max('forecast_margin');
        $maxMarginResult = collect($forecastResults)->firstWhere('forecast_margin', $maxMargin);

        if ($maxMarginResult) {
            $insights[] = [
                'insight_type' => 'max_margin',
                'title' => 'Margin Profit Tertinggi',
                'description' => 'Bulan ' . $maxMarginResult['month'] . ' memiliki margin profit ' . round($maxMargin, 2) . '%',
                'value' => round($maxMargin, 2),
                'month' => $maxMarginResult['month'],
                'year' => $maxMarginResult['year'],
                'severity' => 'positive',
            ];
        }

        // 6. Growth rate average
        $avgProfit = collect($forecastResults)->avg('forecast_profit');
        $avgIncome = collect($forecastResults)->avg('forecast_income');
        $growthRate = ($avgIncome > 0) ? (($avgProfit / $avgIncome) * 100) : 0;

        $insights[] = [
            'insight_type' => 'growth_rate',
            'title' => 'Growth Rate Proyeksi',
            'description' => 'Rata-rata growth rate profit adalah ' . round($growthRate, 2) . '%',
            'value' => round($growthRate, 2),
            'severity' => $growthRate > 0 ? 'positive' : 'warning',
        ];

        return $insights;
    }

    /**
     * Save forecast results ke database
     */
    public function saveForecastResults(ForecastData $forecastData, array $results)
    {
        // Hapus hasil forecast sebelumnya untuk forecast_data ini
        ForecastResult::where('forecast_data_id', $forecastData->id)->delete();

        // Simpan hasil forecast baru
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
    }

    /**
     * Save insights ke database
     */
    public function saveInsights(ForecastData $forecastData, array $insights)
    {
        // Hapus insights sebelumnya
        ForecastInsight::where('forecast_data_id', $forecastData->id)->delete();

        // Simpan insights baru
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
    }

    /**
     * Calculate annual/period summary dari forecast results
     * Bisa untuk 1 tahun, 2 tahun, hingga 5 tahun
     */
    public function calculateAnnualSummary(array $forecastResults)
    {
        // Group by year jika ada multiple tahun
        $groupedByYear = collect($forecastResults)->groupBy('year');

        $yearlyData = [];
        foreach ($groupedByYear as $year => $yearResults) {
            $yearlyData[$year] = [
                'total_income' => round($yearResults->sum('forecast_income'), 2),
                'total_expense' => round($yearResults->sum('forecast_expense'), 2),
                'total_profit' => round($yearResults->sum('forecast_profit'), 2),
                'avg_margin' => round($yearResults->avg('forecast_margin'), 2),
                'avg_confidence' => round($yearResults->avg('confidence_level'), 2),
            ];
        }

        return [
            'by_year' => $yearlyData,
            'total_income' => round(collect($forecastResults)->sum('forecast_income'), 2),
            'total_expense' => round(collect($forecastResults)->sum('forecast_expense'), 2),
            'total_profit' => round(collect($forecastResults)->sum('forecast_profit'), 2),
            'avg_margin' => round(collect($forecastResults)->avg('forecast_margin'), 2),
            'avg_confidence' => round(collect($forecastResults)->avg('confidence_level'), 2),
        ];
    }

    /**
     * Calculate yearly summary - groups monthly data into 12-month periods
     * For easier analysis of multi-year forecasts (3, 5, 10 years)
     */
    public function calculateYearlySummary(array $forecastResults, int $startYear, int $startMonth = 1)
    {
        $yearlyData = [];

        foreach ($forecastResults as $result) {
            // Calculate which forecast year this month belongs to
            // Month 1-12 = Year 1, Month 13-24 = Year 2, etc.
            $monthsFromStart = $result['month'] - 1;
            $yearOffset = floor(($startMonth + $monthsFromStart - 1) / 12);
            $forecastYear = $startYear + $yearOffset;

            if (!isset($yearlyData[$forecastYear])) {
                $yearlyData[$forecastYear] = [
                    'year' => $forecastYear,
                    'total_income' => 0,
                    'total_expense' => 0,
                    'total_profit' => 0,
                    'months_count' => 0,
                    'margins' => [],
                    'confidences' => [],
                ];
            }

            $yearlyData[$forecastYear]['total_income'] += $result['forecast_income'];
            $yearlyData[$forecastYear]['total_expense'] += $result['forecast_expense'];
            $yearlyData[$forecastYear]['total_profit'] += $result['forecast_profit'];
            $yearlyData[$forecastYear]['months_count']++;
            $yearlyData[$forecastYear]['margins'][] = $result['forecast_margin'];
            $yearlyData[$forecastYear]['confidences'][] = $result['confidence_level'];
        }

        // Calculate averages and round values
        foreach ($yearlyData as $year => &$data) {
            $data['total_income'] = round($data['total_income'], 2);
            $data['total_expense'] = round($data['total_expense'], 2);
            $data['total_profit'] = round($data['total_profit'], 2);
            $data['avg_margin'] = round(array_sum($data['margins']) / count($data['margins']), 2);
            $data['avg_confidence'] = round(array_sum($data['confidences']) / count($data['confidences']), 2);
            unset($data['margins'], $data['confidences']);
        }

        return array_values($yearlyData);
    }
}
