<?php

namespace App\Models\Singapay;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'pdf_purchase_id',
        'consultation_purchase_id',
        'transaction_code',
        'reference_no',
        'payment_method',
        'bank_code',
        'va_number',
        'qris_content',
        'qris_url',
        'payment_url',
        'amount',
        'currency',
        'status',
        'mode',
        'expired_at',
        'paid_at',
        'singapay_request',
        'singapay_response',
        'webhook_data',
    ];

    protected $casts = [
        'amount' => 'integer',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'singapay_request' => 'array',
        'singapay_response' => 'array',
        'webhook_data' => 'array',
    ];

    /**
     * Get the PDF purchase
     */
    public function pdfPurchase(): BelongsTo
    {
        return $this->belongsTo(PdfPurchase::class);
    }

    /**
     * Get the Consultation purchase
     */
    public function consultationPurchase(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Consultation\ConsultationPurchase::class, 'consultation_purchase_id');
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by payment method
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Check if paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expired_at && $this->expired_at->isPast());
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark as paid
     */
    public function markAsPaid($paidAt = null, array $webhookData = []): bool
    {
        $this->status = 'paid';
        $this->paid_at = $paidAt ?? now();

        if (!empty($webhookData)) {
            $this->webhook_data = $webhookData;
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
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Generate unique transaction code with configurable prefix
     */
    public static function generateTransactionCode(): string
    {
        $prefix = config('singapay.invoice_prefix', 'GRPD');
        return $prefix . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Generate unique reference number
     */
    public static function generateReferenceNo(): string
    {
        return 'REF' . date('YmdHis') . rand(100000, 999999);
    }
}
