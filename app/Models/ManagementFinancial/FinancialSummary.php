<?php

namespace App\Models\ManagementFinancial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialSummary extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_summaries';

    protected $fillable = [
        'user_id',
        'business_background_id',
        'month',
        'year',
        // Original fields
        'total_income',
        'total_expense',
        'gross_profit',
        'net_profit',
        'cash_position',
        'income_breakdown',
        'expense_breakdown',
        'notes',
        // Cash Flow fields
        'cash_beginning',
        'cash_in',
        'cash_out',
        'net_cash_flow',
        'cash_ending',
        // Income Statement details
        'operating_revenue',
        'non_operating_revenue',
        'cogs',
        'operating_expense',
        'interest_expense',
        'tax_expense',
        'operating_income',
        // Balance Sheet - Assets
        'fixed_assets',
        'receivables',
        'total_assets',
        // Balance Sheet - Liabilities
        'debt',
        'other_liabilities',
        'total_liabilities',
        // Balance Sheet - Equity
        'equity',
        'retained_earnings',
    ];

    protected $casts = [
        // Original fields
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'cash_position' => 'decimal:2',
        'income_breakdown' => 'array',
        'expense_breakdown' => 'array',
        // Cash Flow fields
        'cash_beginning' => 'decimal:2',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'net_cash_flow' => 'decimal:2',
        'cash_ending' => 'decimal:2',
        // Income Statement details
        'operating_revenue' => 'decimal:2',
        'non_operating_revenue' => 'decimal:2',
        'cogs' => 'decimal:2',
        'operating_expense' => 'decimal:2',
        'interest_expense' => 'decimal:2',
        'tax_expense' => 'decimal:2',
        'operating_income' => 'decimal:2',
        // Balance Sheet - Assets
        'fixed_assets' => 'decimal:2',
        'receivables' => 'decimal:2',
        'total_assets' => 'decimal:2',
        // Balance Sheet - Liabilities
        'debt' => 'decimal:2',
        'other_liabilities' => 'decimal:2',
        'total_liabilities' => 'decimal:2',
        // Balance Sheet - Equity
        'equity' => 'decimal:2',
        'retained_earnings' => 'decimal:2',
        // Timestamps
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relationship with Business Background
     */
    public function businessBackground()
    {
        return $this->belongsTo(\App\Models\BusinessBackground::class);
    }

    /**
     * Get month name attribute
     */
    public function getMonthNameAttribute()
    {
        try {
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
            return $months[$this->month] ?? 'Bulan ' . $this->month;
        } catch (\Exception $e) {
            return 'Bulan ' . $this->month;
        }
    }

    /**
     * Get period attribute (Month Year)
     */
    public function getPeriodAttribute()
    {
        return $this->monthName . ' ' . $this->year;
    }

    /**
     * Calculate profit margin percentage
     */
    public function getProfitMarginAttribute()
    {
        if ($this->total_income > 0) {
            return ($this->gross_profit / $this->total_income) * 100;
        }
        return 0;
    }

    /**
     * Check if summary is for current month
     */
    public function getIsCurrentMonthAttribute()
    {
        return $this->month == now()->month && $this->year == now()->year;
    }

    /**
     * Scope for specific month and year
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope for specific year
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope for specific business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_background_id', $businessId);
    }
}
