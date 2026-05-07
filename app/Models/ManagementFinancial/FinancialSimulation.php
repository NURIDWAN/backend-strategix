<?php
// app/Models/ManagementFinancial/FinancialSimulation.php

namespace App\Models\ManagementFinancial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialSimulation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_simulations';

    protected $fillable = [
        'user_id',
        'business_background_id',
        'financial_category_id',
        'simulation_code',
        'type',
        'amount',
        'simulation_date',
        'year',
        'description',
        'payment_method',
        'status',
        'is_recurring',
        'recurring_frequency',
        'recurring_end_date',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'simulation_date' => 'date',
        'recurring_end_date' => 'date',
        'is_recurring' => 'boolean',
        'year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relationship dengan User
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relationship dengan BusinessBackground
     */
    public function businessBackground()
    {
        return $this->belongsTo(\App\Models\BusinessBackground::class);
    }

    /**
     * Relationship dengan FinancialCategory
     */
    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'financial_category_id');
    }

    /**
     * Scope untuk simulasi pendapatan
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope untuk simulasi pengeluaran
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope untuk simulasi completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope untuk simulasi planned
     */
    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    /**
     * Scope untuk simulasi berdasarkan bulan dan tahun
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('simulation_date', $year)
            ->whereMonth('simulation_date', $month);
    }

    /**
     * Scope untuk simulasi berdasarkan tahun tertentu
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope untuk simulasi tahun terbaru
     */
    public function scopeLatestYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Generate simulation code
     */
    public static function generateSimulationCode()
    {
        $prefix = 'SIM';
        $date = now()->format('Ymd');
        $lastSimulation = self::where('simulation_code', 'like', "{$prefix}{$date}%")->latest()->first();

        if ($lastSimulation) {
            $lastNumber = intval(substr($lastSimulation->simulation_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }

    /**
     * Set default year saat create
     */
    public function setYearAttribute($value)
    {
        $this->attributes['year'] = $value ?? now()->year;
    }

    /**
     * Auto-increment tahun ketika tanggal simulasi berubah
     */
    public function setSimulationDateAttribute($value)
    {
        $this->attributes['simulation_date'] = $value;

        // Jika tahun belum diset atau kosong, ambil dari simulation_date
        if (!isset($this->attributes['year']) || $this->attributes['year'] === null) {
            $this->attributes['year'] = \Carbon\Carbon::parse($value)->year;
        }
    }

    /**
     * Method untuk auto-increment tahun ke tahun berikutnya
     */
    public function incrementToNextYear()
    {
        $this->year = $this->year + 1;
        return $this;
    }

    /**
     * Method untuk get tahun available (untuk dropdown/filter)
     */
    public static function getAvailableYears($businessBackgroundId, $userId)
    {
        return self::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    /**
     * Get display type attribute
     */
    public function getDisplayTypeAttribute()
    {
        return $this->type === 'income' ? 'Pendapatan' : 'Pengeluaran';
    }

    /**
     * Get display status attribute
     */
    public function getDisplayStatusAttribute()
    {
        $statuses = [
            'planned' => 'Rencana',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get display payment method attribute
     */
    public function getDisplayPaymentMethodAttribute()
    {
        $methods = [
            'cash' => 'Tunai',
            'bank_transfer' => 'Transfer Bank',
            'credit_card' => 'Kartu Kredit',
            'digital_wallet' => 'Dompet Digital',
            'other' => 'Lainnya'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get formatted amount attribute
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get month from simulation_date attribute
     */
    public function getMonthAttribute()
    {
        return $this->simulation_date ? $this->simulation_date->month : null;
    }

    /**
     * Check if simulation can be edited
     */
    public function getCanEditAttribute()
    {
        return $this->status !== 'cancelled';
    }

    /**
     * Check if simulation can be deleted
     */
    public function getCanDeleteAttribute()
    {
        return $this->status !== 'completed';
    }
}
