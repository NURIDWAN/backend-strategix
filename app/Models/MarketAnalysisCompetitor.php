<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketAnalysisCompetitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_analysis_id',
        'competitor_name',
        'type',
        'code',
        'address',
        'annual_sales_estimate',
        'selling_price',
        'strengths',
        'weaknesses',
        'sort_order',
    ];

    protected $casts = [
        'annual_sales_estimate' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    // Relasi ke market analysis
    public function marketAnalysis()
    {
        return $this->belongsTo(MarketAnalysis::class, 'market_analysis_id');
    }

    // Accessor untuk type
    public function getTypeLabelAttribute()
    {
        return $this->type === 'ownshop' ? 'Own Shop' : 'Kompetitor';
    }

    // Accessor untuk format currency
    public function getAnnualSalesEstimateFormattedAttribute()
    {
        return $this->annual_sales_estimate ? 'Rp ' . number_format($this->annual_sales_estimate, 0, ',', '.') : '-';
    }

    public function getSellingPriceFormattedAttribute()
    {
        return $this->selling_price ? 'Rp ' . number_format($this->selling_price, 0, ',', '.') : '-';
    }
}
