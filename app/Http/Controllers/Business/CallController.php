<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\CallLog;
use App\Models\CallDailyStat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CallController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $period = $request->get('period', 'daily');
        $selectedDay = $request->get('day', now()->day);
        $tab = $request->get('tab', 'all');

        // Get date range based on period
        $dateRange = $this->getDateRange($period, $selectedDay);

        // Get calls based on tab
        $calls = $this->getCalls($businessId, $dateRange, $tab);

        // Get stats for the period
        $stats = $this->getCallStats($businessId, $dateRange);

        // Get daily breakdown for the month
        $dailyBreakdown = $this->getDailyBreakdown($businessId);

        // Get operator performance
        $operatorStats = $this->getOperatorStats($businessId, $dateRange);

        // Get audit count for badge
        $auditCount = CallLog::where('business_id', $businessId)
            ->recommended()
            ->count();

        // Date info
        $dateInfo = [
            'current_month' => now()->translatedFormat('F Y'),
            'current_day' => now()->day,
            'days_in_month' => now()->daysInMonth,
            'selected_day' => (int) $selectedDay,
        ];

        return Inertia::render('Business/Calls/Index', [
            'calls' => $calls,
            'stats' => $stats,
            'dailyBreakdown' => $dailyBreakdown,
            'operatorStats' => $operatorStats,
            'period' => $period,
            'dateInfo' => $dateInfo,
            'tab' => $tab,
            'auditCount' => $auditCount,
            'canViewAudit' => $request->user()->hasAnyRole(['admin', 'sales_head']),
        ]);
    }

    public function show($call)
    {
        return Inertia::render('Business/Calls/Show');
    }

    /**
     * Get calls based on tab and filters
     */
    private function getCalls($businessId, $dateRange, $tab)
    {
        $query = CallLog::where('business_id', $businessId);

        if ($tab === 'audit') {
            // Smart Audit: Get recommended calls without date filter
            $query->recommended();
        } else {
            // Overview: Get all calls for the period
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        return $query->with(['lead:id,name,phone,status', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($call) use ($tab) {
                $data = [
                    'id' => $call->id,
                    'type' => $call->direction === 'inbound' ? 'incoming' : 'outgoing',
                    'status' => $call->status,
                    'phone' => $call->from_number ?: $call->to_number,
                    'duration' => $call->duration,
                    'formatted_duration' => $call->formatted_duration,
                    'lead' => $call->lead ? [
                        'id' => $call->lead->id,
                        'name' => $call->lead->name,
                        'status' => $call->lead->status,
                    ] : null,
                    'operator' => $call->user ? [
                        'id' => $call->user->id,
                        'name' => $call->user->name,
                    ] : null,
                    'created_at' => $call->created_at,
                    'recording_url' => $call->recording_url,
                    'analysis_status' => $call->analysis_status,
                ];

                // Add recommended reason for audit tab
                if ($tab === 'audit') {
                    $data['recommended_reason'] = $call->recommended_reason;
                }

                return $data;
            });
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period, $selectedDay): array
    {
        if ($period === 'daily') {
            $date = Carbon::create(now()->year, now()->month, $selectedDay);
            return [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        } elseif ($period === 'weekly') {
            return [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ];
        } else {
            return [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ];
        }
    }

    /**
     * Get call statistics for the period
     */
    private function getCallStats($businessId, $dateRange): array
    {
        $query = CallLog::where('business_id', $businessId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        $total = (clone $query)->count();
        $incoming = (clone $query)->where('direction', 'inbound')->count();
        $outgoing = (clone $query)->where('direction', 'outbound')->count();
        $answered = (clone $query)->whereIn('status', ['answered', 'completed'])->count();
        $missed = (clone $query)->whereIn('status', ['missed', 'no_answer'])->count();
        $totalDuration = (clone $query)->whereIn('status', ['answered', 'completed'])->sum('duration');
        $avgDuration = $answered > 0 ? round($totalDuration / $answered) : 0;

        // Answer rate
        $answerRate = $total > 0 ? round(($answered / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'answered' => $answered,
            'missed' => $missed,
            'avg_duration' => $avgDuration,
            'total_duration' => $totalDuration,
            'answer_rate' => $answerRate,
        ];
    }

    /**
     * Get daily breakdown for the current month
     */
    private function getDailyBreakdown($businessId): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $dailyStats = CallLog::where('business_id', $businessId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN direction = "inbound" THEN 1 ELSE 0 END) as incoming'),
                DB::raw('SUM(CASE WHEN direction = "outbound" THEN 1 ELSE 0 END) as outgoing'),
                DB::raw('SUM(CASE WHEN status IN ("answered", "completed") THEN 1 ELSE 0 END) as answered'),
                DB::raw('SUM(CASE WHEN status IN ("missed", "no_answer") THEN 1 ELSE 0 END) as missed'),
                DB::raw('AVG(CASE WHEN status IN ("answered", "completed") THEN duration ELSE NULL END) as avg_duration')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $breakdown = [];
        $current = $startOfMonth->copy();

        while ($current <= now() && $current <= $endOfMonth) {
            $dateKey = $current->format('Y-m-d');
            $stat = $dailyStats->get($dateKey);

            $breakdown[$current->day] = [
                'date' => $dateKey,
                'day' => $current->day,
                'total' => $stat->total ?? 0,
                'incoming' => $stat->incoming ?? 0,
                'outgoing' => $stat->outgoing ?? 0,
                'answered' => $stat->answered ?? 0,
                'missed' => $stat->missed ?? 0,
                'avg_duration' => round($stat->avg_duration ?? 0),
            ];

            $current->addDay();
        }

        return $breakdown;
    }

    /**
     * Get operator statistics
     */
    private function getOperatorStats($businessId, $dateRange): array
    {
        return DB::table('call_logs')
            ->join('users', 'call_logs.user_id', '=', 'users.id')
            ->where('call_logs.business_id', $businessId)
            ->whereBetween('call_logs.created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN call_logs.direction = "inbound" THEN 1 ELSE 0 END) as incoming'),
                DB::raw('SUM(CASE WHEN call_logs.direction = "outbound" THEN 1 ELSE 0 END) as outgoing'),
                DB::raw('SUM(CASE WHEN call_logs.status IN ("answered", "completed") THEN 1 ELSE 0 END) as answered'),
                DB::raw('SUM(CASE WHEN call_logs.status IN ("missed", "no_answer") THEN 1 ELSE 0 END) as missed'),
                DB::raw('AVG(CASE WHEN call_logs.status IN ("answered", "completed") THEN call_logs.duration ELSE NULL END) as avg_duration'),
                DB::raw('SUM(CASE WHEN call_logs.status IN ("answered", "completed") THEN call_logs.duration ELSE 0 END) as total_duration')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_calls')
            ->get()
            ->map(function ($row) {
                $answerRate = $row->total_calls > 0 ? round(($row->answered / $row->total_calls) * 100, 1) : 0;

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'avatar' => strtoupper(substr($row->name, 0, 1)),
                    'total_calls' => (int) $row->total_calls,
                    'incoming' => (int) $row->incoming,
                    'outgoing' => (int) $row->outgoing,
                    'answered' => (int) $row->answered,
                    'missed' => (int) $row->missed,
                    'answer_rate' => $answerRate,
                    'avg_duration' => round($row->avg_duration ?? 0),
                    'total_duration' => (int) $row->total_duration,
                ];
            })
            ->toArray();
    }
}
