<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Lead;
use App\Models\Task;
use App\Models\BusinessUser;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get current business ID
     */
    protected function getBusinessId()
    {
        return session('current_business_id');
    }

    /**
     * Show sales head dashboard
     */
    public function index()
    {
        $businessId = $this->getBusinessId();

        // Get sales team members (operators)
        $teamMembers = BusinessUser::where('business_id', $businessId)
            ->where('department', 'sales_operator')
            ->with('user:id,name,phone')
            ->get();

        // Today's stats
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Lead stats
        $leadStats = [
            'new_today' => Lead::where('business_id', $businessId)
                ->whereDate('created_at', $today)
                ->count(),
            'total_active' => Lead::where('business_id', $businessId)
                ->whereNotIn('status', ['won', 'lost', 'cancelled'])
                ->count(),
            'won_this_month' => Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->where('updated_at', '>=', $startOfMonth)
                ->count(),
            'conversion_rate' => $this->calculateConversionRate($businessId, $startOfMonth),
        ];

        // Revenue stats
        $revenueStats = [
            'today' => Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereDate('updated_at', $today)
                ->sum('estimated_value') ?? 0,
            'this_month' => Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->where('updated_at', '>=', $startOfMonth)
                ->sum('estimated_value') ?? 0,
        ];

        // Pipeline summary
        $pipeline = Lead::where('business_id', $businessId)
            ->whereNotIn('status', ['won', 'lost', 'cancelled'])
            ->selectRaw('status, COUNT(*) as count, SUM(estimated_value) as total_value')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Team performance (top operators)
        $teamPerformance = $this->getTeamPerformance($businessId, $startOfMonth);

        // Recent leads
        $recentLeads = Lead::where('business_id', $businessId)
            ->with(['assignedTo:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Overdue tasks
        $overdueTasks = Task::where('business_id', $businessId)
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->with(['assignedUser:id,name'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        return Inertia::render('SalesHead/Dashboard', [
            'teamMembers' => $teamMembers,
            'leadStats' => $leadStats,
            'revenueStats' => $revenueStats,
            'pipeline' => $pipeline,
            'teamPerformance' => $teamPerformance,
            'recentLeads' => $recentLeads,
            'overdueTasks' => $overdueTasks,
        ]);
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate($businessId, $startOfMonth): float
    {
        $totalClosed = Lead::where('business_id', $businessId)
            ->whereIn('status', ['won', 'lost'])
            ->where('updated_at', '>=', $startOfMonth)
            ->count();

        if ($totalClosed === 0) {
            return 0;
        }

        $won = Lead::where('business_id', $businessId)
            ->where('status', 'won')
            ->where('updated_at', '>=', $startOfMonth)
            ->count();

        return round(($won / $totalClosed) * 100, 1);
    }

    /**
     * Get team performance stats
     */
    private function getTeamPerformance($businessId, $startOfMonth): array
    {
        $operators = BusinessUser::where('business_id', $businessId)
            ->where('department', 'sales_operator')
            ->with('user:id,name')
            ->get();

        $performance = [];

        foreach ($operators as $operator) {
            $userId = $operator->user_id;

            $leadsHandled = Lead::where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('updated_at', '>=', $startOfMonth)
                ->count();

            $leadsWon = Lead::where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('status', 'won')
                ->where('updated_at', '>=', $startOfMonth)
                ->count();

            $revenue = Lead::where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('status', 'won')
                ->where('updated_at', '>=', $startOfMonth)
                ->sum('estimated_value') ?? 0;

            $performance[] = [
                'id' => $operator->id,
                'user_id' => $userId,
                'name' => $operator->user->name ?? 'Noma\'lum',
                'leads_handled' => $leadsHandled,
                'leads_won' => $leadsWon,
                'conversion_rate' => $leadsHandled > 0 ? round(($leadsWon / $leadsHandled) * 100, 1) : 0,
                'revenue' => $revenue,
            ];
        }

        // Sort by revenue
        usort($performance, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        return $performance;
    }

    /**
     * API endpoint for stats (used by layout polling)
     */
    public function apiStats()
    {
        $businessId = $this->getBusinessId();
        $today = Carbon::today();

        return response()->json([
            'leads' => [
                'new' => Lead::where('business_id', $businessId)
                    ->whereDate('created_at', $today)
                    ->count(),
                'total' => Lead::where('business_id', $businessId)
                    ->whereNotIn('status', ['won', 'lost', 'cancelled'])
                    ->count(),
            ],
            'tasks' => [
                'total' => Task::where('business_id', $businessId)->count(),
                'overdue' => Task::where('business_id', $businessId)
                    ->where('due_date', '<', now())
                    ->where('status', '!=', 'completed')
                    ->count(),
            ],
            'calls' => [
                'missed' => 0, // TODO: Implement when call tracking is ready
                'total' => 0,
            ],
            'messages' => [
                'unread' => 0, // TODO: Implement when messaging is ready
                'total' => 0,
            ],
            'inbox' => [
                'unread' => 0, // TODO: Implement from UnifiedInboxService
                'total' => 0,
            ],
            'today' => [
                'deals' => Lead::where('business_id', $businessId)
                    ->where('status', 'won')
                    ->whereDate('updated_at', $today)
                    ->count(),
                'revenue' => Lead::where('business_id', $businessId)
                    ->where('status', 'won')
                    ->whereDate('updated_at', $today)
                    ->sum('estimated_value') ?? 0,
            ],
        ]);
    }
}
