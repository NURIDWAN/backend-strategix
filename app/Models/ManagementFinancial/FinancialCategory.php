<?php

namespace App\Models\ManagementFinancial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_categories';

    protected $fillable = [
        'user_id',
        'business_background_id',
        'name',
        'type',
        'category_subtype',
        'color',
        'status',
        'description'
    ];

    protected $casts = [
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
     * Relationship with BusinessBackground
     */
    public function businessBackground()
    {
        return $this->belongsTo(\App\Models\BusinessBackground::class);
    }

    /**
     * Scope for income categories
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope for expense categories
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope for actual categories
     */
    public function scopeActual($query)
    {
        return $query->where('status', 'actual');
    }

    /**
     * Scope for plan categories
     */
    public function scopePlan($query)
    {
        return $query->where('status', 'plan');
    }

    /**
     * Scope by category subtype
     */
    public function scopeBySubtype($query, $subtype)
    {
        return $query->where('category_subtype', $subtype);
    }

    /**
     * Scope for COGS categories
     */
    public function scopeCogs($query)
    {
        return $query->where('category_subtype', 'cogs');
    }

    /**
     * Scope for operating expense categories
     */
    public function scopeOperatingExpense($query)
    {
        return $query->where('category_subtype', 'operating_expense');
    }

    /**
     * Scope for interest expense categories
     */
    public function scopeInterestExpense($query)
    {
        return $query->where('category_subtype', 'interest_expense');
    }

    /**
     * Scope for tax expense categories
     */
    public function scopeTaxExpense($query)
    {
        return $query->where('category_subtype', 'tax_expense');
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
        return $this->status === 'actual' ? 'Aktual' : 'Rencana';
    }

    /**
     * Get display category subtype attribute
     */
    public function getDisplaySubtypeAttribute()
    {
        $subtypes = [
            'operating_revenue' => 'Pendapatan Operasional',
            'non_operating_revenue' => 'Pendapatan Lain-lain',
            'cogs' => 'HPP / COGS',
            'operating_expense' => 'Beban Operasional',
            'interest_expense' => 'Beban Bunga',
            'tax_expense' => 'Pajak Penghasilan',
            'other' => 'Lainnya'
        ];

        return $subtypes[$this->category_subtype] ?? 'Lainnya';
    }

    /**
     * Check if category can be deleted
     */
    public function getCanDeleteAttribute()
    {
        // Add logic later to check if category has transactions
        return true;
    }

    public function simulations()
    {
        return $this->hasMany(FinancialSimulation::class, 'financial_category_id');
    }
}
