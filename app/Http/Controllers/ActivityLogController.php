<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Display activity logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by description
        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        $logs = $query->paginate(50)->withQueryString();

        // Get unique actions for filter dropdown
        $actions = ActivityLog::select('action')
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => $request->only(['action', 'user_id', 'from_date', 'to_date', 'search']),
        ]);
    }

    /**
     * Display a specific activity log
     */
    public function show(ActivityLog $log)
    {
        $log->load(['user', 'subject']);

        return Inertia::render('ActivityLogs/Show', [
            'log' => $log,
        ]);
    }

    /**
     * Get activity stats
     */
    public function stats(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total_activities' => ActivityLog::where('created_at', '>=', $startDate)->count(),
            'unique_users' => ActivityLog::where('created_at', '>=', $startDate)->distinct('user_id')->count('user_id'),
            'by_action' => ActivityLog::where('created_at', '>=', $startDate)
                ->selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->get()
                ->pluck('count', 'action'),
            'by_day' => ActivityLog::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Export activity logs
     *
     * PERFORMANCE: Uses chunking to prevent memory overflow with large datasets
     */
    public function export(Request $request)
    {
        $query = ActivityLog::with(['user'])->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Get total count for logging
        $totalCount = $query->count();

        // Create CSV with streaming and chunking
        $filename = 'activity-logs-'.now()->format('Y-m-d-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        // PERFORMANCE: Stream data in chunks to prevent memory overflow
        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Date', 'User', 'Action', 'Description', 'IP Address']);

            // PERFORMANCE: Process in chunks of 1000 records
            $query->chunk(1000, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user?->name ?? 'System',
                        $log->action,
                        $log->description,
                        $log->ip_address,
                    ]);
                }
                // Flush output buffer to prevent memory buildup
                flush();
            });

            fclose($file);
        };

        // Log the export
        ActivityLogger::exported('Activity Logs', $totalCount);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clean old activity logs
     */
    public function clean(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:30|max:365',
        ]);

        $deleted = ActivityLogger::cleanOldLogs($request->days);

        // Log the cleanup
        ActivityLogger::log('logs_cleaned', null, "{$deleted} ta eski log o'chirildi (>{$request->days} kun)");

        return response()->json([
            'success' => true,
            'message' => "{$deleted} ta eski log o'chirildi",
            'deleted' => $deleted,
        ]);
    }
}
