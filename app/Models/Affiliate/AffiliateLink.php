<?php

namespace App\Models\Affiliate;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class AffiliateLink extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'original_slug',
        'is_custom',
        'change_count',
        'max_changes',
        'is_active',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
        'change_count' => 'integer',
        'max_changes' => 'integer',
    ];

    /**
     * Get the user that owns the affiliate link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }



    /**
     * Generate the full affiliate URL
     */
    public function getFullUrlAttribute(): string
    {
        // Use frontend URL for shareable affiliate link
        $frontendUrl = config('app.frontend_url');
        return "{$frontendUrl}/affiliate/{$this->slug}";
    }
}
