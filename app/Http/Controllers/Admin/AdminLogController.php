<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLogController extends Controller
{
    /**
     * GET /api/admin/logs
     * List all activity logs with filters.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,username,phone,role');

        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by action prefix (e.g., "payment." for all payment actions)
        if ($request->filled('action_group')) {
            $query->where('action', 'like', $request->action_group . '.%');
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search in description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort (newest first by default)
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy('created_at', $sortDir === 'asc' ? 'asc' : 'desc');

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * GET /api/admin/logs/stats
     * Log summary statistics.
     */
    public function stats()
    {
        // Total logs
        $totalLogs = ActivityLog::count();

        // Logs today
        $todayLogs = ActivityLog::whereDate('created_at', today())->count();

        // Action type breakdown
        $actionBreakdown = ActivityLog::select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Action group breakdown (e.g., "login" from "login.success")
        $groupBreakdown = ActivityLog::select(
                DB::raw("SUBSTRING_INDEX(action, '.', 1) as action_group"),
                DB::raw('COUNT(*) as count')
            )
            ->groupByRaw("SUBSTRING_INDEX(action, '.', 1)")
            ->orderByDesc('count')
            ->get();

        // Most active users (last 30 days)
        $activeUsers = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('user:id,name,username')
            ->get();

        // Daily log count (last 30 days)
        $dailyActivity = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as count')
            )
            ->groupByRaw("DATE(created_at)")
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_logs' => $totalLogs,
                'today_logs' => $todayLogs,
                'action_breakdown' => $actionBreakdown,
                'group_breakdown' => $groupBreakdown,
                'active_users' => $activeUsers,
                'daily_activity' => $dailyActivity,
            ],
        ]);
    }
}
