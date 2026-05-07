<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_background_id',
        'target_market',
        'market_size',
        'market_trends',
        'main_competitors',
        'competitor_strengths',
        'competitor_weaknesses',
        'competitive_advantage',
        // REVISI: Tambahan field baru
        'tam_total',
        'sam_percentage',
        'sam_total',
        'som_percentage',
        'som_total',
        'strengths',
        'weaknesses',
        'opportunities',
        'threats',
    ];

    protected $casts = [
        'tam_total' => 'decimal:2',
        'sam_percentage' => 'decimal:2',
        'sam_total' => 'decimal:2',
        'som_percentage' => 'decimal:2',
        'som_total' => 'decimal:2',
    ];

    // Relasi - pastikan nama model dan foreign key benar
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessBackground()
    {
        return $this->belongsTo(BusinessBackground::class, 'business_background_id', 'id');
    }

    // REVISI: Relasi ke tabel kompetitor detail
    public function competitors()
    {
        return $this->hasMany(MarketAnalysisCompetitor::class, 'market_analysis_id')->orderBy('sort_order');
    }
}
