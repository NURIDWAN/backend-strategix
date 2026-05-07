<?php

namespace App\Models\Consultation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Consultant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialization',
        'bio',
        'google_calendar_id',
        'hourly_rate',
        'is_available',
        'max_daily_sessions',
        'working_hours_start',
        'working_hours_end',
        'working_days',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'working_days' => 'array',
    ];

    // =====================================
    // Relationships
    // =====================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(ConsultationSession::class);
    }

    // =====================================
    // Scopes
    // =====================================

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    // =====================================
    // Helpers
    // =====================================

    public function getAverageRatingAttribute()
    {
        return $this->sessions()
            ->whereNotNull('rating')
            ->avg('rating') ?? 0;
    }

    public function getTotalCompletedSessionsAttribute()
    {
        return $this->sessions()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Check if the consultant works on a given day (1=Mon, 7=Sun)
     */
    public function worksOnDay(int $dayOfWeek): bool
    {
        $days = $this->working_days ?? [1, 2, 3, 4, 5]; // Default: Mon-Fri
        return in_array($dayOfWeek, $days);
    }

    /**
     * Get session count for a specific date
     */
    public function sessionCountOnDate(string $date): int
    {
        return $this->sessions()
            ->where('session_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->count();
    }
}
