<?php

namespace App\Models\ManagementFinancial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class FinancialProjection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_projections';

    protected $fillable = [
        'user_id',
        'business_background_id',
        'projection_name',
        'base_year',
        'scenario_type',
        'growth_rate',
        'inflation_rate',
        'discount_rate',
        'initial_investment',
        'current_cash_balance',
        'accumulated_income',
        'accumulated_expense',
        'base_revenue',
        'base_cost',
        'base_net_profit',
        'yearly_projections',
        'roi',
        'npv',
        'irr',
        'payback_period',
        'notes'
    ];

    protected $casts = [
        'yearly_projections' => 'array',
        'growth_rate' => 'decimal:2',
        'inflation_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'initial_investment' => 'decimal:2',
        'current_cash_balance' => 'decimal:2',
        'accumulated_income' => 'decimal:2',
        'accumulated_expense' => 'decimal:2',
        'base_revenue' => 'decimal:2',
        'base_cost' => 'decimal:2',
        'base_net_profit' => 'decimal:2',
        'roi' => 'decimal:2',
        'npv' => 'decimal:2',
        'irr' => 'decimal:2',
        'payback_period' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Append formatted attributes to JSON
     */
    protected $appends = [
        'display_scenario',
        'formatted_roi',
        'formatted_npv',
        'formatted_irr',
        'formatted_payback'
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relationship with BusinessBackground
     */
    public function businessBackground()
    {
        return $this->belongsTo(\App\Models\BusinessBackground::class);
    }

    /**
     * Scope for scenario type
     */
    public function scopeByScenario($query, $scenario)
    {
        return $query->where('scenario_type', $scenario);
    }

    /**
     * Scope for base year
     */
    public function scopeByBaseYear($query, $year)
    {
        return $query->where('base_year', $year);
    }

    /**
     * Get display scenario type attribute
     */
    public function getDisplayScenarioAttribute()
    {
        $scenarios = [
            'optimistic' => 'Optimis',
            'realistic' => 'Realistis',
            'pessimistic' => 'Pesimis'
        ];

        return $scenarios[$this->scenario_type] ?? 'Unknown';
    }

    /**
     * Get formatted ROI attribute
     */
    public function getFormattedRoiAttribute()
    {
        return $this->roi ? number_format($this->roi, 2) . '%' : '-';
    }

    /**
     * Get formatted NPV attribute
     */
    public function getFormattedNpvAttribute()
    {
        return $this->npv ? 'Rp ' . number_format($this->npv, 0, ',', '.') : '-';
    }

    /**
     * Get formatted IRR attribute
     */
    public function getFormattedIrrAttribute()
    {
        return $this->irr ? number_format($this->irr, 2) . '%' : '-';
    }

    /**
     * Get formatted Payback Period attribute
     */
    public function getFormattedPaybackAttribute()
    {
        return $this->payback_period ? number_format($this->payback_period, 1) . ' tahun' : '-';
    }

    /**
     * Calculate financial metrics
     */
    public function calculateMetrics()
    {
        if (!$this->yearly_projections) {
            return false;
        }

        try {
            $projections = $this->yearly_projections;
            $discountRate = $this->discount_rate / 100;

            // PERBAIKAN: Gunakan current_cash_balance sebagai starting point, bukan initial_investment
            // NPV = PV of future cash flows - Current cash position
            $currentCashPosition = $this->current_cash_balance ?? $this->initial_investment;

            // Calculate NPV
            // NPV mengukur nilai sekarang dari proyeksi dikurangi posisi kas saat ini
            $npv = 0; // Mulai dari 0
            foreach ($projections as $projection) {
                $year = $projection['year'];
                $cashFlow = $projection['net_profit'];
                $npv += $cashFlow / pow(1 + $discountRate, $year);
            }
            // NPV final = PV of projected cash flows - starting cash position
            $npv = $npv - $currentCashPosition;

            // Calculate ROI
            // ROI = (Total Projected Profit) / Current Cash Position * 100
            $totalNetProfit = array_sum(array_column($projections, 'net_profit'));
            $roi = $currentCashPosition > 0 ? ($totalNetProfit / $currentCashPosition) * 100 : 0;

            // Calculate Payback Period
            // Berapa tahun untuk ROI mencapai break-even dari posisi kas saat ini
            $cumulativeCashFlow = 0; // Mulai dari 0, bukan dari -investment
            $paybackPeriod = null;
            foreach ($projections as $projection) {
                $cumulativeCashFlow += $projection['net_profit'];
                if ($cumulativeCashFlow >= $currentCashPosition && !$paybackPeriod) {
                    $paybackPeriod = $projection['year'];
                    break;
                }
            }

            // If not paid back in projection period
            if (!$paybackPeriod && count($projections) > 0) {
                $paybackPeriod = count($projections) + 1; // Beyond projection period
            }

            // Calculate IRR
            $irr = $this->calculateIRR($projections, $currentCashPosition);

            $this->update([
                'npv' => $npv,
                'roi' => $roi,
                'payback_period' => $paybackPeriod,
                'irr' => $irr
            ]);

            Log::info("Calculated metrics for projection ID {$this->id}: NPV={$npv}, ROI={$roi}, IRR={$irr}, Payback={$paybackPeriod}");

            return true;
        } catch (\Exception $e) {
            Log::error("Error calculating metrics for projection ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate IRR using Newton-Raphson method (simplified)
     */
    private function calculateIRR($projections, $currentCashPosition)
    {
        // Skip IRR calculation if no investment
        if ($currentCashPosition <= 0) {
            return 0;
        }

        $rate = 0.1; // Initial guess 10%
        $maxIterations = 100;
        $tolerance = 0.0001;

        for ($i = 0; $i < $maxIterations; $i++) {
            $npv = -$currentCashPosition;
            $derivative = 0;

            foreach ($projections as $projection) {
                $year = $projection['year'];
                $cashFlow = $projection['net_profit'];
                $npv += $cashFlow / pow(1 + $rate, $year);
                $derivative -= ($year * $cashFlow) / pow(1 + $rate, $year + 1);
            }

            if (abs($npv) < $tolerance) {
                return $rate * 100; // Return as percentage

            }

            if ($derivative == 0) {
                break;
            }

            $rate = $rate - $npv / $derivative;
        }

        return $rate * 100; // Return as percentage
    }
}
