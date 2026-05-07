<?php

namespace App\Models\Affiliate;

use App\Models\User;
use App\Models\Singapay\PdfPurchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_user_id',
        'referred_user_id',
        'purchase_id',
        'subscription_amount',
        'commission_percentage',
        'commission_amount',
        'status',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'subscription_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_percentage' => 'integer',
        'paid_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';

    const COMMISSION_PERCENTAGE = 17; // 17%
    const MINIMUM_WITHDRAWAL = 50000; // Rp 100,000

    /**
     * Get the affiliate user (User A who owns the link)
     */
    public function affiliateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    /**
     * Get the referred user (User B who made purchase)
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Get the purchase that generated this commission
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PdfPurchase::class, 'purchase_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: Filter paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope: Filter pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Filter by affiliate user
     */
    public function scopeForAffiliateUser($query, $userId)
    {
        return $query->where('affiliate_user_id', $userId);
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(): bool
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        return $this->save();
    }

    /**
     * Check if commission is withdrawable
     */
    public function isWithdrawable(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
