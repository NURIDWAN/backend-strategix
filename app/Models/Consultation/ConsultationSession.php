<?php

namespace App\Models\Consultation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ConsultationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'consultant_id',
        'google_event_id',
        'session_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'meeting_link',
        'topic',
        'notes_member',
        'notes_consultant',
        'report_type',
        'related_report_id',
        'rating',
        'review',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'session_date' => 'date',
        'rating' => 'integer',
        'duration_minutes' => 'integer',
    ];

    // =====================================
    // Relationships
    // =====================================

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    // =====================================
    // Scopes
    // =====================================

    public function scopeUpcoming($query)
    {
        return $query->where('session_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('session_date')
            ->orderBy('start_time');
    }

    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('session_date', '<', now()->toDateString())
                ->orWhere('status', 'completed');
        })->orderByDesc('session_date');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForConsultant($query, int $consultantId)
    {
        return $query->where('consultant_id', $consultantId);
    }

    public function scopeForMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeOnDate($query, string $date)
    {
        return $query->where('session_date', $date);
    }

    // =====================================
    // Helpers
    // =====================================

    public function isUpcoming(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->session_date >= now()->toDateString();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->session_date > now()->toDateString();
    }
}
