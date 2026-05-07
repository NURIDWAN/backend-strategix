<?php

namespace App\Models\Singapay;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PdfPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'premium_pdf_id',
        'transaction_code',
        'package_type',
        'amount_paid',
        'payment_method',
        'status',
        'started_at',
        'expires_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount_paid' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the premium PDF package
     */
    public function premiumPdf(): BelongsTo
    {
        return $this->belongsTo(PremiumPdf::class);
    }

    /**
     * Get the payment transaction
     */
    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }

    /**
     * Get the affiliate commission (if exists)
     */
    public function affiliateCommission(): HasOne
    {
        return $this->hasOne(\App\Models\Affiliate\AffiliateCommission::class, 'purchase_id');
    }

    /**
     * Scope active purchases
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'paid')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope pending purchases
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope expired purchases
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'paid')
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if purchase is active
     */
    public function isActive(): bool
    {
        return $this->status === 'paid' &&
            $this->expires_at &&
            $this->expires_at->isFuture();
    }

    /**
     * Check if purchase is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'paid' &&
            $this->expires_at &&
            $this->expires_at->isPast();
    }

    /**
     * Get remaining days
     */
    public function getRemainingDaysAttribute(): int
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->expires_at);
    }

    /**
     * Activate purchase
     * 🔧 FIXED: Type casting untuk duration_days
     */
    public function activate(): bool
    {
        $this->status = 'paid';
        $this->started_at = now();
        $this->paid_at = now();

        // 🔧 FIX: Ensure duration_days is integer
        if ($this->premiumPdf) {
            $durationDays = (int) $this->premiumPdf->duration_days;
            $this->expires_at = now()->addDays($durationDays);

            Log::info('[PdfPurchase] Activating purchase', [
                'purchase_id' => $this->id,
                'duration_days' => $durationDays,
                'expires_at' => $this->expires_at->toDateTimeString(),
            ]);
        }

        return $this->save();
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): bool
    {
        $this->status = 'expired';
        return $this->save();
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): bool
    {
        $this->status = 'failed';
        return $this->save();
    }

    /**
     * Generate unique transaction code with configurable prefix
     */
    public static function generateTransactionCode(): string
    {
        $prefix = config('singapay.invoice_prefix', 'GRPD');
        return $prefix . date('YmdHis') . rand(1000, 9999);
    }
}
