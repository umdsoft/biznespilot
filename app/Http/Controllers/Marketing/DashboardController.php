<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\ContentPost;
use App\Models\Lead;
use App\Models\Task;
use App\Services\ContentStatisticsService;
use App\Services\LeadStatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HasCurrentBusiness;

    protected LeadStatisticsService $leadStats;

    protected ContentStatisticsService $contentStats;

    public function __construct(LeadStatisticsService $leadStats, ContentStatisticsService $contentStats)
    {
        $this->leadStats = $leadStats;
        $this->contentStats = $contentStats;
    }

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (! $business) {
            return redirect()->route('login');
        }

        // Real campaign statistics
        $campaignStats = $this->getCampaignStats($business->id);

        // Real lead statistics (using centralized service)
        $leadStats = $this->leadStats->getLeadStats($business->id);

        // Real content statistics (using centralized service)
        $contentStats = $this->contentStats->getContentStats($business->id);

        // Budget (from campaigns)
        $budgetStats = $this->getBudgetStats($business->id);

        // Social stats (using centralized service)
        $socialStats = $this->contentStats->getSocialStats($business->id);

        // Upcoming content
        $upcomingContent = $this->getUpcomingContent($business->id);

        // Task stats
        $taskStats = $this->getTaskStats($business->id);

        return Inertia::render('Marketing/Dashboard', [
            'stats' => [
                'campaigns' => $campaignStats,
                'leads' => $leadStats,
                'content' => $contentStats,
                'budget' => $budgetStats,
                'social' => $socialStats,
                'tasks' => $taskStats,
            ],
            'upcomingContent' => $upcomingContent,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    private function getCampaignStats(string $businessId): array
    {
        $total = Campaign::where('business_id', $businessId)->count();
        $active = Campaign::where('business_id', $businessId)->where('status', 'active')->count();
        $completed = Campaign::where('business_id', $businessId)->where('status', 'completed')->count();
        $draft = Campaign::where('business_id', $businessId)->where('status', 'draft')->count();
        $paused = Campaign::where('business_id', $businessId)->where('status', 'paused')->count();

        return [
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'draft' => $draft,
            'paused' => $paused,
        ];
    }

    private function getBudgetStats($businessId): array
    {
        // Get budget data from campaigns
        $campaigns = Campaign::where('business_id', $businessId)
            ->whereNotNull('settings')
            ->get();

        $totalBudget = 0;
        $spentBudget = 0;

        foreach ($campaigns as $campaign) {
            $settings = $campaign->settings ?? [];
            $totalBudget += $settings['budget'] ?? 0;
            $spentBudget += $settings['spent'] ?? 0;
        }

        // Haqiqiy ma'lumot — kampaniyalar yo'q bo'lsa 0 ko'rsatiladi
        // (avval 5,000,000 so'm hardcoded default qo'yilgan, foydalanuvchi
        //  uni qayerdan kelganini bilolmasdi — olib tashlandi)
        return [
            'total' => $totalBudget,
            'spent' => $spentBudget,
            'remaining' => $totalBudget - $spentBudget,
        ];
    }

    private function getUpcomingContent($businessId): array
    {
        return ContentPost::where('business_id', $businessId)
            ->whereIn('status', ['scheduled', 'approved', 'pending_review'])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(function ($content) {
                return [
                    'id' => $content->id,
                    'uuid' => $content->uuid,
                    'title' => $content->title,
                    'platform' => $content->platform,
                    'content_type' => $content->content_type,
                    'status' => $content->status,
                    'scheduled_at' => $content->scheduled_at?->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
    }

    private function getTaskStats($businessId): array
    {
        $userId = auth()->id();

        // Get tasks assigned to current marketing user
        $total = Task::where('business_id', $businessId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })->count();

        $pending = Task::where('business_id', $businessId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })->where('status', 'pending')->count();

        $inProgress = Task::where('business_id', $businessId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })->where('status', 'in_progress')->count();

        $completed = Task::where('business_id', $businessId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })->where('status', 'completed')->count();

        $overdue = Task::where('business_id', $businessId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '<', now())
            ->count();

        // Today's tasks
        $today = Task::where('business_id', $businessId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->whereDate('due_date', now()->toDateString())
            ->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'overdue' => $overdue,
            'today' => $today,
        ];
    }

    public function marketingHub()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get marketing channels
        $channels = \App\Models\MarketingChannel::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($channel) {
                return [
                    'id' => $channel->id,
                    'uuid' => $channel->uuid ?? null,
                    'name' => $channel->name,
                    'type' => $channel->type,
                    'status' => $channel->is_active ? 'active' : 'inactive',
                    'followers' => $channel->followers_count ?? 0,
                    'engagement_rate' => $channel->engagement_rate ?? 0,
                    'last_synced' => $channel->last_synced_at?->diffForHumans(),
                ];
            });

        // Recent posts from content posts
        $recentPosts = ContentPost::where('business_id', $business->id)
            ->whereIn('status', ['published', 'scheduled', 'draft'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($content) {
                $metrics = is_array($content->metrics) ? $content->metrics : [];

                return [
                    'id' => $content->id,
                    'uuid' => $content->uuid ?? null,
                    'title' => $content->title,
                    'platform' => $content->platform,
                    'status' => $content->status,
                    'engagement' => $metrics['engagement_rate'] ?? 0,
                    'reach' => $metrics['reach'] ?? 0,
                    'scheduled_date' => $content->scheduled_at?->format('Y-m-d'),
                    'published_at' => $content->published_at?->format('Y-m-d'),
                    'created_at' => $content->created_at?->format('Y-m-d'),
                ];
            });

        // Stats for Marketing Hub - same structure as Business panel
        $stats = [
            'dream_buyers' => \App\Models\DreamBuyer::where('business_id', $business->id)->count(),
            'competitors' => \App\Models\Competitor::where('business_id', $business->id)->count(),
            'offers' => \App\Models\Offer::where('business_id', $business->id)->count(),
            'total_posts' => ContentPost::where('business_id', $business->id)->count(),
            'published_posts' => ContentPost::where('business_id', $business->id)->where('status', 'published')->count(),
            'scheduled_posts' => ContentPost::where('business_id', $business->id)->where('status', 'scheduled')->count(),
            'total_channels' => \App\Models\MarketingChannel::where('business_id', $business->id)->count(),
            'campaigns' => Campaign::where('business_id', $business->id)->count(),
            'total_spend' => Campaign::where('business_id', $business->id)->sum('budget') ?? 0,
        ];

        return Inertia::render('Marketing/Index', [
            'channels' => $channels,
            'recentPosts' => $recentPosts,
            'stats' => $stats,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function apiStats()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $userId = auth()->id();

        // Tasks count
        $tasksCount = Task::where('business_id', $business->id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        // Unread notifications count
        $unreadCount = auth()->user()->unreadNotifications()->count();

        // Overdue tasks
        $overdueCount = Task::where('business_id', $business->id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '<', now())
            ->count();

        return response()->json([
            'tasks_count' => $tasksCount,
            'unread_count' => $unreadCount,
            'overdue_count' => $overdueCount,
        ]);
    }
}
