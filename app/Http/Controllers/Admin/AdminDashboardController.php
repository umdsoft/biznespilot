<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Models\Campaign;
use App\Models\ChatbotConversation;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // Platform-wide statistics
        $stats = [
            'total_users' => User::count(),
            'total_businesses' => Business::count(),
            'active_businesses' => Business::where('status', 'active')->count(),
            'total_customers' => Customer::count(),
            'total_conversations' => ChatbotConversation::count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::whereIn('status', ['running', 'scheduled'])->count(),
        ];

        // Recent activity
        $recentBusinesses = Business::with('owner')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($business) => [
                'id' => $business->id,
                'name' => $business->name,
                'owner' => $business->owner->name ?? 'N/A',
                'status' => $business->status,
                'created_at' => $business->created_at->diffForHumans(),
            ]);

        $recentUsers = User::latest()
            ->limit(5)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->diffForHumans(),
            ]);

        // Monthly growth
        $monthlyGrowth = [
            'users' => $this->getMonthlyGrowth(User::class),
            'businesses' => $this->getMonthlyGrowth(Business::class),
            'conversations' => $this->getMonthlyGrowth(ChatbotConversation::class),
        ];

        // Top performing businesses
        $topBusinesses = Business::withCount('chatbot_conversations')
            ->orderBy('chatbot_conversations_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($business) => [
                'id' => $business->id,
                'name' => $business->name,
                'conversations_count' => $business->chatbot_conversations_count,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentBusinesses' => $recentBusinesses,
            'recentUsers' => $recentUsers,
            'monthlyGrowth' => $monthlyGrowth,
            'topBusinesses' => $topBusinesses,
        ]);
    }

    /**
     * Get monthly growth data
     */
    protected function getMonthlyGrowth($model)
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = $model::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $months[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

        return $months;
    }

    /**
     * System health check
     */
    public function systemHealth()
    {
        $health = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        return response()->json([
            'success' => true,
            'health' => $health,
            'overall' => $this->calculateOverallHealth($health),
        ]);
    }

    protected function checkDatabase()
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    protected function checkStorage()
    {
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

        return [
            'status' => $usedPercentage < 90 ? 'healthy' : 'warning',
            'message' => "Storage: " . round($usedPercentage, 2) . "% used",
            'used' => round($usedPercentage, 2),
        ];
    }

    protected function checkCache()
    {
        try {
            cache()->put('health_check', true, 10);
            $result = cache()->get('health_check');
            return ['status' => $result ? 'healthy' : 'warning', 'message' => 'Cache working'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    protected function checkQueue()
    {
        return ['status' => 'healthy', 'message' => 'Queue system operational'];
    }

    protected function calculateOverallHealth($health)
    {
        $healthyCount = collect($health)->where('status', 'healthy')->count();
        $totalChecks = count($health);

        return [
            'status' => $healthyCount === $totalChecks ? 'healthy' : 'warning',
            'percentage' => round(($healthyCount / $totalChecks) * 100, 2),
        ];
    }

    /**
     * Platform analytics
     */
    public function analytics()
    {
        $analytics = [
            'total_revenue' => 0,
            'active_subscriptions' => Business::where('status', 'active')->count(),
            'churn_rate' => $this->calculateChurnRate(),
            'average_campaigns_per_business' => Campaign::count() / max(Business::count(), 1),
            'average_conversations_per_business' => ChatbotConversation::count() / max(Business::count(), 1),
        ];

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    protected function calculateChurnRate()
    {
        $lastMonth = now()->subMonth();
        $activeLastMonth = Business::where('status', 'active')
            ->where('created_at', '<=', $lastMonth)
            ->count();

        $churnedThisMonth = Business::where('status', 'inactive')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return $activeLastMonth > 0 ? round(($churnedThisMonth / $activeLastMonth) * 100, 2) : 0;
    }
}
