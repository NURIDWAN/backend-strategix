<?php

namespace App\Models\Consultation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Consultation\ConsultationPackage;

class ConsultationCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consultation_package_id',
        'total_sessions',
        'used_sessions',
        'remaining_sessions',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // =====================================
    // Relationships
    // =====================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(ConsultationPackage::class, 'consultation_package_id');
    }

    // =====================================
    // Scopes
    // =====================================

    public function scopeActive($query)
    {
        return $query->where('remaining_sessions', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // =====================================
    // Helpers
    // =====================================

    public function hasRemainingSessions(): bool
    {
        return $this->remaining_sessions > 0
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function consumeSession(): bool
    {
        if (!$this->hasRemainingSessions()) {
            return false;
        }

        $this->increment('used_sessions');
        $this->decrement('remaining_sessions');

        $this->refresh();
        if ($this->remaining_sessions <= 0 && $this->status !== 'used') {
            $this->update(['status' => 'used']);
        }

        return true;
    }
}
