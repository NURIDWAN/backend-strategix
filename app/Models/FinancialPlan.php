<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_background_id',
        'plan_name',
        'capital_sources',
        'total_initial_capital',
        'initial_capex',
        'total_capex',
        'monthly_opex',
        'total_monthly_opex',
        'sales_projections',
        'total_monthly_income',
        'total_yearly_income',
        'gross_profit',
        'tax_rate',
        'tax_amount',
        'interest_expense',
        'net_profit',
        'cash_flow_simulation',
        'financial_summary',
        'roi_percentage',
        'payback_period',
        'bep_amount',
        'profit_margin',
        'feasibility_status',
        'feasibility_notes',
        'notes',
        'status',
        'plan_duration_months'
    ];

    protected $casts = [
        'capital_sources' => 'array',
        'initial_capex' => 'array',
        'monthly_opex' => 'array',
        'sales_projections' => 'array',
        'cash_flow_simulation' => 'array',
        'financial_summary' => 'array',
        'total_initial_capital' => 'decimal:2',
        'total_capex' => 'decimal:2',
        'total_monthly_opex' => 'decimal:2',
        'total_monthly_income' => 'decimal:2',
        'total_yearly_income' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'interest_expense' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'roi_percentage' => 'decimal:2',
        'payback_period' => 'decimal:2',
        'bep_amount' => 'decimal:2',
        'profit_margin' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($plan) {
            $plan->calculateFinancials();
        });
    }

    /**
     * Hitung semua metrik keuangan secara otomatis
     */
    public function calculateFinancials()
    {
        // 7.1 Hitung total modal awal
        $this->calculateTotalCapital();

        // 7.2 Hitung total CapEx
        $this->calculateTotalCapex();

        // 7.3 Hitung total OpEx bulanan
        $this->calculateTotalOpex();

        // 7.4 Hitung proyeksi penjualan
        $this->calculateSalesProjections();

        // 7.5 Hitung laba rugi
        $this->calculateProfitLoss();

        // 7.7 Buat ringkasan keuangan
        $this->createFinancialSummary();

        // 7.8 Analisis kelayakan
        $this->calculateFeasibility();
    }

    /**
     * 7.1 Hitung total modal awal dan persentase
     */
    private function calculateTotalCapital()
    {
        $capitalSources = $this->capital_sources ?? [];
        $total = 0;

        foreach ($capitalSources as &$source) {
            $amount = floatval($source['amount'] ?? 0);
            $total += $amount;
        }

        // Hitung persentase untuk setiap sumber
        foreach ($capitalSources as &$source) {
            $amount = floatval($source['amount'] ?? 0);
            $source['percentage'] = $total > 0 ? round(($amount / $total) * 100, 2) : 0;
        }

        $this->capital_sources = $capitalSources;
        $this->total_initial_capital = $total;
    }

    /**
     * 7.2 Hitung total CapEx
     */
    private function calculateTotalCapex()
    {
        $capexItems = $this->initial_capex ?? [];
        $total = 0;

        foreach ($capexItems as $item) {
            $total += floatval($item['amount'] ?? 0);
        }

        $this->total_capex = $total;
    }

    /**
     * 7.3 Hitung total OpEx bulanan
     */
    private function calculateTotalOpex()
    {
        $opexItems = $this->monthly_opex ?? [];
        $total = 0;

        foreach ($opexItems as $item) {
            $total += floatval($item['amount'] ?? 0);
        }

        $this->total_monthly_opex = $total;
    }

    /**
     * 7.4 Hitung proyeksi penjualan
     */
    private function calculateSalesProjections()
    {
        $salesItems = $this->sales_projections ?? [];
        $monthlyTotal = 0;

        foreach ($salesItems as &$item) {
            $price = floatval($item['price'] ?? 0);
            $volume = floatval($item['volume'] ?? 0);
            $monthlyIncome = $price * $volume;

            $item['monthly_income'] = $monthlyIncome;
            $monthlyTotal += $monthlyIncome;
        }

        $this->sales_projections = $salesItems;
        $this->total_monthly_income = $monthlyTotal;
        $this->total_yearly_income = $monthlyTotal * 12;
    }

    /**
     * 7.5 Hitung laba rugi
     */
    private function calculateProfitLoss()
    {
        // Laba kotor = Pendapatan bulanan - Biaya operasional bulanan
        $this->gross_profit = $this->total_monthly_income - $this->total_monthly_opex;

        // Pajak (asumsi 10% dari laba kotor jika positif)
        $this->tax_amount = $this->gross_profit > 0 ? $this->gross_profit * ($this->tax_rate / 100) : 0;

        // Laba bersih = Laba kotor - Pajak - Bunga
        $this->net_profit = $this->gross_profit - $this->tax_amount - $this->interest_expense;
    }

    /**
     * 7.7 Buat ringkasan keuangan
     */
    private function createFinancialSummary()
    {
        $this->financial_summary = [
            'total_initial_capital' => $this->total_initial_capital,
            'total_capex' => $this->total_capex,
            'total_monthly_opex' => $this->total_monthly_opex,
            'total_monthly_income' => $this->total_monthly_income,
            'total_yearly_income' => $this->total_yearly_income,
            'gross_profit' => $this->gross_profit,
            'net_profit' => $this->net_profit,
            'profit_margin' => $this->total_monthly_income > 0 ?
                round(($this->net_profit / $this->total_monthly_income) * 100, 2) : 0
        ];
    }

    /**
     * 7.8 Analisis kelayakan finansial
     */
    private function calculateFeasibility()
    {
        // ROI (Return on Investment)
        $this->roi_percentage = $this->total_initial_capital > 0 ?
            round(($this->net_profit * 12) / $this->total_initial_capital * 100, 2) : 0;

        // Payback Period (dalam bulan)
        $this->payback_period = $this->net_profit > 0 ?
            round($this->total_initial_capital / ($this->net_profit), 2) : 0;

        // Break Even Point (dalam unit, asumsi harga rata-rata)
        $averagePrice = $this->calculateAveragePrice();
        $this->bep_amount = $this->total_monthly_income > 0 ?
            round($this->total_monthly_opex / ($this->total_monthly_income / ($this->getTotalSalesVolume() ?: 1)), 2) : 0;

        // Profit Margin
        $this->profit_margin = $this->total_monthly_income > 0 ?
            round(($this->net_profit / $this->total_monthly_income) * 100, 2) : 0;

        // Tentukan status kelayakan
        $this->determineFeasibilityStatus();
    }

    /**
     * Hitung harga rata-rata produk
     */
    private function calculateAveragePrice()
    {
        $salesItems = $this->sales_projections ?? [];
        if (empty($salesItems)) return 0;

        $totalPrice = 0;
        foreach ($salesItems as $item) {
            $totalPrice += floatval($item['price'] ?? 0);
        }

        return $totalPrice / count($salesItems);
    }

    /**
     * Hitung total volume penjualan
     */
    private function getTotalSalesVolume()
    {
        $salesItems = $this->sales_projections ?? [];
        $totalVolume = 0;

        foreach ($salesItems as $item) {
            $totalVolume += floatval($item['volume'] ?? 0);
        }

        return $totalVolume;
    }

    /**
     * Tentukan status kelayakan berdasarkan metrik
     */
    private function determineFeasibilityStatus()
    {
        $score = 0;

        // Skor berdasarkan ROI
        if ($this->roi_percentage >= 25) $score += 3;
        elseif ($this->roi_percentage >= 15) $score += 2;
        elseif ($this->roi_percentage >= 5) $score += 1;

        // Skor berdasarkan Payback Period
        if ($this->payback_period <= 12) $score += 3;
        elseif ($this->payback_period <= 24) $score += 2;
        elseif ($this->payback_period <= 36) $score += 1;

        // Skor berdasarkan Profit Margin
        if ($this->profit_margin >= 20) $score += 3;
        elseif ($this->profit_margin >= 10) $score += 2;
        elseif ($this->profit_margin >= 5) $score += 1;

        // Tentukan status berdasarkan total skor
        if ($score >= 7) {
            $this->feasibility_status = 'Layak';
            $this->feasibility_notes = 'Usaha menunjukkan potensi profitabilitas yang baik dengan ROI dan margin yang sehat.';
        } elseif ($score >= 4) {
            $this->feasibility_status = 'Cukup Layak';
            $this->feasibility_notes = 'Usaha memiliki potensi tetapi perlu optimasi dalam biaya operasional atau strategi penjualan.';
        } else {
            $this->feasibility_status = 'Tidak Layak';
            $this->feasibility_notes = 'Perlu evaluasi ulang model bisnis, pertimbangkan untuk mengurangi biaya atau meningkatkan pendapatan.';
        }
    }

    /**
     * Get monthly cash flow data for charts
     */
    public function getMonthlyCashFlow($year = null)
    {
        $cashFlowData = $this->cash_flow_simulation ?? [];
        $year = $year ?? date('Y');

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = [
                'income' => 0,
                'expense' => 0,
                'net_cash_flow' => 0
            ];
        }

        foreach ($cashFlowData as $flow) {
            $flowDate = \Carbon\Carbon::parse($flow['date']);
            if ($flowDate->year == $year) {
                $month = $flowDate->month;
                $amount = floatval($flow['amount']);

                if ($flow['type'] === 'income') {
                    $monthlyData[$month]['income'] += $amount;
                } else {
                    $monthlyData[$month]['expense'] += $amount;
                }

                $monthlyData[$month]['net_cash_flow'] =
                    $monthlyData[$month]['income'] - $monthlyData[$month]['expense'];
            }
        }

        return $monthlyData;
    }

    /**
     * Generate financial reports data
     */
    public function generateReportData($reportType, $period = 'yearly')
    {
        $data = [
            'plan_info' => [
                'plan_name' => $this->plan_name,
                'business_name' => $this->businessBackground->name ?? 'N/A',
                'period' => $period,
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]
        ];

        switch ($reportType) {
            case 'sales':
                $data['sales_data'] = $this->sales_projections ?? [];
                $data['totals'] = [
                    'monthly_income' => $this->total_monthly_income,
                    'yearly_income' => $this->total_yearly_income
                ];
                break;

            case 'profit_loss':
                $data['profit_loss'] = [
                    'revenue' => $this->total_monthly_income,
                    'operational_costs' => $this->total_monthly_opex,
                    'gross_profit' => $this->gross_profit,
                    'tax' => $this->tax_amount,
                    'interest' => $this->interest_expense,
                    'net_profit' => $this->net_profit
                ];
                break;

            case 'cash_flow':
                $data['cash_flow'] = $this->cash_flow_simulation ?? [];
                $data['monthly_summary'] = $this->getMonthlyCashFlow();
                break;

            case 'feasibility':
                $data['feasibility'] = [
                    'roi' => $this->roi_percentage,
                    'payback_period' => $this->payback_period,
                    'break_even_point' => $this->bep_amount,
                    'profit_margin' => $this->profit_margin,
                    'status' => $this->feasibility_status,
                    'notes' => $this->feasibility_notes
                ];
                break;

            case 'summary':
            default:
                $data['summary'] = $this->financial_summary ?? [];
                $data['feasibility'] = [
                    'roi' => $this->roi_percentage,
                    'payback_period' => $this->payback_period,
                    'profit_margin' => $this->profit_margin,
                    'status' => $this->feasibility_status
                ];
                break;
        }

        return $data;
    }

    // Relationships
    public function businessBackground()
    {
        return $this->belongsTo(BusinessBackground::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
