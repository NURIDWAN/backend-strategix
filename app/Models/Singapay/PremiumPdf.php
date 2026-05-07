<?php

namespace App\Models\Singapay;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PremiumPdf extends Model
{
    protected $fillable = [
        'package_type',
        'name',
        'description',
        'price',
        'duration_days',
        'features',
        'is_active',
        'sort_order',
        'consultation_credits',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'integer',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
        'consultation_credits' => 'integer',
    ];

    /**
     * Get purchases for this package
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(PdfPurchase::class);
    }

    /**
     * Scope active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get duration text
     */
    public function getDurationTextAttribute(): string
    {
        if ($this->duration_days >= 365) {
            return floor($this->duration_days / 365) . ' Tahun';
        }
        if ($this->duration_days >= 30) {
            return floor($this->duration_days / 30) . ' Bulan';
        }
        return $this->duration_days . ' Hari';
    }
}
