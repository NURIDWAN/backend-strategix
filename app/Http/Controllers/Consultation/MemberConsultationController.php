<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Consultation\Consultant;
use App\Models\Consultation\ConsultationSession;
use App\Models\Consultation\ConsultationCredit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MemberConsultationController extends Controller
{
    public function __construct()
    {
        // Internal manual scheduling
    }

    /**
     * Get list of available consultants
     */
    public function getConsultants()
    {
        $consultants = Consultant::with('user:id,name,profile_photo')
            ->available()
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->user->name,
                    'photo' => $c->user->profile_photo,
                    'specialization' => $c->specialization,
                    'bio' => $c->bio,
                    'rating' => round($c->average_rating, 1),
                    'completed_sessions' => $c->total_completed_sessions,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $consultants
        ]);
    }

    /**
     * Get available slots for a consultant on a specific date
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'consultant_id' => 'required|exists:consultants,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $consultant = Consultant::findOrFail($request->consultant_id);
        
        // Check if consultant works on this day
        $date = Carbon::parse($request->date);
        if (!$consultant->worksOnDay($date->dayOfWeekIso)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Konsultan tidak bekerja pada hari tersebut.'
            ]);
        }

        // Generate slots manually
        $slots = [];
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $consultant->working_hours_start);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $consultant->working_hours_end);

        // Fetch booked sessions for the day
        $bookedSessions = ConsultationSession::where('consultant_id', $consultant->id)
            ->where('session_date', $request->date)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get();

        while ($startTime->copy()->addHour()->lte($endTime)) {
            $slotStartStr = $startTime->format('H:i:s');
            $slotEndStr = $startTime->copy()->addHour()->format('H:i:s');

            $isBooked = $bookedSessions->contains(function ($session) use ($slotStartStr, $slotEndStr) {
                // Check if our generated slot overlaps with any booked session
                return ($slotStartStr < $session->end_time && $slotEndStr > $session->start_time);
            });

            if (!$isBooked) {
                $slots[] = [
                    'start' => substr($slotStartStr, 0, 5),
                    'end' => substr($slotEndStr, 0, 5)
                ];
            }

            $startTime->addHour();
        }

        return response()->json([
            'success' => true,
            'data' => $slots
        ]);
    }

    /**
     * Request a new consultation session
     */
    public function requestSession(Request $request)
    {
        $request->validate([
            'consultant_id' => 'required|exists:consultants,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'topic' => 'required|string|max:255',
            'report_type' => 'nullable|string',
            'related_report_id' => 'nullable|integer',
        ]);

        $user = auth()->user();

        // 1. Check if user has credits
        $credit = ConsultationCredit::where('user_id', $user->id)
            ->active()
            ->first();

        if (!$credit) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki kredit konsultasi aktif. Silakan beli paket terlebih dahulu.'
            ], 403);
        }

        $consultant = Consultant::findOrFail($request->consultant_id);
        $startTime = Carbon::parse($request->date . ' ' . $request->start_time);
        $endTime = $startTime->copy()->addHour(); // Default 1 hour

        // 2. Double check availability via Google Calendar (re-verify)
        // In real world, we should check if it's still free
        
        return DB::transaction(function() use ($request, $user, $consultant, $startTime, $endTime, $credit) {
            // 3. Consume credit
            $credit->consumeSession();

            // 4. Create local session record
            $session = ConsultationSession::create([
                'member_id' => $user->id,
                'consultant_id' => $consultant->id,
                'session_date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $endTime->format('H:i'),
                'topic' => $request->topic,
                'report_type' => $request->report_type,
                'related_report_id' => $request->related_report_id,
                'status' => 'confirmed' // Or pending if manual approval needed
            ]);

            // No Google Calendar integration needed anymore
            // Link akan diisi manual oleh konsultan/admin nanti

            return response()->json([
                'success' => true,
                'message' => 'Sesi konsultasi berhasil dijadwalkan.',
                'data' => $session
            ]);
        });
    }

    /**
     * Get member's consultation history
     */
    public function mySessions()
    {
        $sessions = ConsultationSession::with('consultant.user:id,name')
            ->where('member_id', auth()->id())
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get credits status
     */
    public function getCredits()
    {
        $credits = ConsultationCredit::with('package:id,name')
            ->where('user_id', auth()->id())
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_remaining' => auth()->user()->getRemainingConsultationCredits(),
                'details' => $credits
            ]
        ]);
    }
}
