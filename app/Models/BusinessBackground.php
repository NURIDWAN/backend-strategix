<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'logo',
        'background_image',
        'org_chart_image',
        'name',
        'category',
        'description',
        'business_overview',
        'business_legality',
        'business_objectives',
        'purpose',
        'location',
        'business_type',
        'start_date',
        'values',
        'vision',
        'mission',
        'contact',
    ];

    // Relasi ke market analyses
    public function marketAnalyses()
    {
        return $this->hasMany(MarketAnalysis::class, 'business_background_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
