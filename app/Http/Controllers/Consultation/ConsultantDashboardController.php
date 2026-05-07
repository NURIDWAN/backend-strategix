<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation\Consultant;
use App\Models\Consultation\ConsultationSession;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ConsultantDashboardController extends Controller
{
    private function resolveConsultant()
    {
        $user = Auth::user();
        $consultant = $user?->consultantProfile;

        if (!$consultant) {
            abort(response()->json(['success' => false, 'message' => 'Bukan profil konsultan'], 403));
        }

        return $consultant;
    }

    /**
     * Get dashboard stats for consultant
     */
    public function dashboardStats()
    {
        $consultant = $this->resolveConsultant();

        $upcomingCount = ConsultationSession::forConsultant($consultant->id)->upcoming()->count();
        $totalCompleted = $consultant->total_completed_sessions;
        $avgRating = round($consultant->average_rating, 1);

        return response()->json([
            'success' => true,
            'data' => [
                'upcoming_sessions' => $upcomingCount,
                'total_completed' => $totalCompleted,
                'average_rating' => $avgRating,
                'is_available' => $consultant->is_available
            ]
        ]);
    }

    /**
     * Get upcoming sessions for consultant
     */
    public function upcomingSessions()
    {
        $consultant = $this->resolveConsultant();
        
        $sessions = ConsultationSession::with('member:id,name,email')
            ->forConsultant($consultant->id)
            ->upcoming()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get full sessions list for consultant with filters
     */
    public function sessions(Request $request)
    {
        $consultant = $this->resolveConsultant();

        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled,no_show',
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'sort' => 'nullable|in:newest,oldest',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $query = ConsultationSession::with('member:id,name,email')
            ->forConsultant($consultant->id);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('topic', 'like', '%' . $search . '%')
                    ->orWhereHas('member', function ($memberQ) use ($search) {
                        $memberQ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('session_date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('session_date', '<=', $validated['date_to']);
        }

        $sort = $validated['sort'] ?? 'newest';
        if ($sort === 'oldest') {
            $query->orderBy('session_date')->orderBy('start_time');
        } else {
            $query->orderByDesc('session_date')->orderByDesc('start_time');
        }

        $perPage = $validated['per_page'] ?? 10;
        $sessions = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ]
        ]);
    }

    /**
     * Get rich dashboard overview for consultant
     */
    public function dashboardOverview()
    {
        $consultant = $this->resolveConsultant();
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $baseQuery = ConsultationSession::forConsultant($consultant->id);

        $todayCount = (clone $baseQuery)
            ->whereDate('session_date', $today->toDateString())
            ->count();

        $weekCount = (clone $baseQuery)
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->count();

        $totalSessions = (clone $baseQuery)->count();
        $completedSessions = (clone $baseQuery)->where('status', 'completed')->count();
        $upcomingSessions = (clone $baseQuery)->whereDate('session_date', '>=', $today->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
        $needsMeetingLink = (clone $baseQuery)
            ->whereDate('session_date', '>=', $today->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) {
                $q->whereNull('meeting_link')->orWhere('meeting_link', '');
            })
            ->count();

        $todayAgenda = ConsultationSession::with('member:id,name,email')
            ->forConsultant($consultant->id)
            ->whereDate('session_date', $today->toDateString())
            ->orderBy('start_time')
            ->get();

        $latestSessions = ConsultationSession::with('member:id,name,email')
            ->forConsultant($consultant->id)
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->limit(8)
            ->get();

        $trendStart = Carbon::today()->subDays(6);
        $trendRaw = ConsultationSession::forConsultant($consultant->id)
            ->whereBetween('session_date', [$trendStart->toDateString(), $today->toDateString()])
            ->selectRaw('session_date, count(*) as total, sum(case when status = "completed" then 1 else 0 end) as completed')
            ->groupBy('session_date')
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->session_date)->toDateString());

        $weeklyTrend = collect(range(0, 6))->map(function ($offset) use ($trendStart, $trendRaw) {
            $date = $trendStart->copy()->addDays($offset);
            $key = $date->toDateString();
            $row = $trendRaw->get($key);

            return [
                'date' => $key,
                'label' => $date->format('D'),
                'total' => $row ? (int) $row->total : 0,
                'completed' => $row ? (int) $row->completed : 0,
            ];
        })->values();

        $completionRate = $totalSessions > 0
            ? round(($completedSessions / $totalSessions) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'today_count' => $todayCount,
                    'week_count' => $weekCount,
                    'upcoming_count' => $upcomingSessions,
                    'total_completed' => $completedSessions,
                    'average_rating' => round($consultant->average_rating, 1),
                    'completion_rate' => $completionRate,
                    'needs_meeting_link_count' => $needsMeetingLink,
                    'is_available' => (bool) $consultant->is_available,
                ],
                'weekly_trend' => $weeklyTrend,
                'today_agenda' => $todayAgenda,
                'latest_sessions' => $latestSessions,
            ]
        ]);
    }

    /**
     * Get consultant profile data
     */
    public function profile()
    {
        $consultant = $this->resolveConsultant();
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'specialization' => $consultant->specialization,
                'bio' => $consultant->bio,
                'google_calendar_id' => $consultant->google_calendar_id,
                'working_hours_start' => $consultant->working_hours_start,
                'working_hours_end' => $consultant->working_hours_end,
                'working_days' => $consultant->working_days ?? [1, 2, 3, 4, 5],
                'is_available' => (bool) $consultant->is_available,
                'average_rating' => round($consultant->average_rating, 1),
                'total_completed_sessions' => $consultant->total_completed_sessions,
            ]
        ]);
    }

    /**
     * Update session (add notes, mark as completed)
     */
    public function updateSession(Request $request, $id)
    {
        $consultant = $this->resolveConsultant();
        $session = ConsultationSession::where('consultant_id', $consultant->id)->findOrFail($id);

        $request->validate([
            'status' => 'nullable|in:confirmed,in_progress,completed,cancelled,no_show',
            'notes_consultant' => 'nullable|string',
            'meeting_link' => 'nullable|string|url'
        ]);

        $payload = $request->only(['status', 'notes_consultant', 'meeting_link']);
        if (array_key_exists('meeting_link', $payload) && $payload['meeting_link'] === '') {
            $payload['meeting_link'] = null;
        }

        $session->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Data sesi berhasil diperbarui',
            'data' => $session
        ]);
    }

    /**
     * Update availability settings
     */
    public function updateAvailability(Request $request)
    {
        $consultant = $this->resolveConsultant();

        $request->validate([
            'is_available' => 'sometimes|boolean',
            'working_hours_start' => 'sometimes|string',
            'working_hours_end' => 'sometimes|string',
            'working_days' => 'sometimes|array',
            'specialization' => 'sometimes|string',
            'bio' => 'sometimes|string',
            'google_calendar_id' => 'sometimes|nullable|email',
        ]);

        $updatable = [
            'is_available',
            'working_hours_start',
            'working_hours_end',
            'working_days',
            'specialization',
            'bio',
            'google_calendar_id',
        ];

        $payload = $request->only($updatable);

        if (array_key_exists('specialization', $payload)) {
            $payload['specialization'] = Str::of($payload['specialization'])->trim()->value();
        }

        if (array_key_exists('bio', $payload)) {
            $payload['bio'] = Str::of($payload['bio'])->trim()->value();
        }

        $consultant->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan ketersediaan berhasil diperbarui'
        ]);
    }
}
