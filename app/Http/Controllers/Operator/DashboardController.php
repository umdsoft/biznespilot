<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (!$business) {
            return Inertia::render('Operator/Dashboard', [
                'stats' => null,
                'myLeads' => [],
                'myTasks' => [],
            ]);
        }

        // My assigned leads
        $myLeads = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'source' => $lead->source,
                'created_at' => $lead->created_at->format('Y-m-d H:i'),
            ]);

        // My tasks
        $myTasks = Task::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'is_overdue' => $task->due_date && $task->due_date->isPast(),
            ]);

        // Stats
        $stats = [
            'my_leads' => [
                'total' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->count(),
                'new' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'new')->count(),
                'in_progress' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'in_progress')->count(),
                'converted' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'converted')->count(),
            ],
            'my_tasks' => [
                'total' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->count(),
                'pending' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'pending')->count(),
                'overdue' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', '!=', 'completed')->where('due_date', '<', now())->count(),
                'completed_today' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'completed')->whereDate('completed_at', today())->count(),
            ],
            'kpi' => [
                'calls_today' => 12, // Sample data
                'calls_target' => 20,
                'conversion_rate' => 15.5,
                'response_time_avg' => 5, // minutes
            ],
        ];

        return Inertia::render('Operator/Dashboard', [
            'stats' => $stats,
            'recentLeads' => $myLeads,
            'todayTasks' => $myTasks,
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }

    public function apiStats()
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $tasksCount = $business ? Task::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count() : 0;

        return response()->json([
            'tasks_count' => $tasksCount,
            'unread_count' => 0,
        ]);
    }
}
