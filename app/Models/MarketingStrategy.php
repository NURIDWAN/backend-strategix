<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_background_id',
        'promotion_strategy',
        'media_used',
        'pricing_strategy',
        'monthly_target',
        'collaboration_plan',
        'status'
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke business background
    public function businessBackground()
    {
        return $this->belongsTo(BusinessBackground::class, 'business_background_id');
    }
}
