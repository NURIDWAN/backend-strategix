<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'phone',
        'password',
        'profile_photo',
        'account_status',
        'role',
        'otp_code',
        'otp_expires_at',
        'reset_otp_code',
        'reset_otp_expires_at',
        'pdf_access_expires_at',
        'pdf_access_package',
        'pdf_access_active',
        'referred_by_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'reset_otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'reset_otp_expires_at' => 'datetime',
            'pdf_access_expires_at' => 'datetime',
            'pdf_access_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Generate OTP untuk verifikasi
    public function generateOtp()
    {
        $otp = rand(100000, 999999);
        $this->otp_code = $otp;
        $this->otp_expires_at = now()->addMinutes(10);
        $this->save();

        return $otp;
    }

    // Generate OTP untuk reset password
    public function generateResetOtp()
    {
        $otp = rand(100000, 999999);
        $this->reset_otp_code = $otp;
        $this->reset_otp_expires_at = now()->addMinutes(10);
        $this->save();

        return $otp;
    }

    // Validasi OTP
    public function validateOtp($otp)
    {
        return $this->otp_code === $otp &&
            $this->otp_expires_at &&
            $this->otp_expires_at->isFuture();
    }

    // Validasi Reset OTP
    public function validateResetOtp($otp)
    {
        return $this->reset_otp_code === $otp &&
            $this->reset_otp_expires_at &&
            $this->reset_otp_expires_at->isFuture();
    }

    // Cek apakah nomor sudah diverifikasi
    public function hasVerifiedPhone()
    {
        return !is_null($this->phone_verified_at);
    }

    // Tandai nomor WA sudah diverifikasi
    public function markPhoneAsVerified()
    {
        $this->phone_verified_at = now();
        $this->otp_code = null;
        $this->otp_expires_at = null;
        $this->save();
    }

    // Clear reset OTP setelah berhasil reset password
    public function clearResetOtp()
    {
        $this->reset_otp_code = null;
        $this->reset_otp_expires_at = null;
        $this->save();
    }

    // Cek apakah user adalah admin
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // =====================================
    // SingaPay Payment Relations
    // =====================================

    /**
     * Get all PDF purchases
     */
    public function pdfPurchases()
    {
        return $this->hasMany(\App\Models\Singapay\PdfPurchase::class);
    }

    /**
     * Check if user has active PDF Pro access
     */
    public function hasPdfProAccess(): bool
    {
        return $this->pdf_access_active
            && $this->pdf_access_expires_at
            && $this->pdf_access_expires_at->isFuture();
    }

    // =====================================
    // Affiliate Relations
    // =====================================

    /**
     * Get the user who referred this user (User A who owns the affiliate link)
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * Get all users referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    /**
     * Get affiliate commissions earned by this user
     */
    public function affiliateCommissions()
    {
        return $this->hasMany(\App\Models\Affiliate\AffiliateCommission::class, 'affiliate_user_id');
    }

    /**
     * Get affiliate link for this user
     */
    public function affiliateLink()
    {
        return $this->hasOne(\App\Models\Affiliate\AffiliateLink::class);
    }

    // =====================================
    // Consultation Relations
    // =====================================

    /**
     * Get the consultant profile for this user
     */
    public function consultantProfile()
    {
        return $this->hasOne(\App\Models\Consultation\Consultant::class);
    }

    /**
     * Get consultation sessions as a member
     */
    public function consultationSessions()
    {
        return $this->hasMany(\App\Models\Consultation\ConsultationSession::class, 'member_id');
    }

    /**
     * Get consultation credits
     */
    public function consultationCredits()
    {
        return $this->hasMany(\App\Models\Consultation\ConsultationCredit::class);
    }

    /**
     * Get total remaining consultation credits from active records.
     */
    public function getRemainingConsultationCredits(): int
    {
        return (int) $this->consultationCredits()
            ->active()
            ->sum('remaining_sessions');
    }

    /**
     * Check if user is a consultant
     */
    public function isConsultant(): bool
    {
        return $this->role === 'consultant';
    }

    /**
     * Add consultation credits to user
     */
    public function addConsultationCredits(int $amount, ?int $packageId = null): void
    {
        if ($amount <= 0) return;

        $this->consultationCredits()->create([
            'consultation_package_id' => $packageId,
            'total_sessions' => $amount,
            'used_sessions' => 0,
            'remaining_sessions' => $amount,
            'status' => 'active',
            'expires_at' => null, // Optional: based on business rules
        ]);
    }
}
