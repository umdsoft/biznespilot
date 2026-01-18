<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\ContentCalendar;
use App\Models\Lead;
use App\Models\Task;
use App\Services\LeadStatisticsService;
use App\Services\ContentStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if (!$business) {
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

        // Recent active campaigns
        $recentCampaigns = $this->getRecentCampaigns($business->id);

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
            'recentCampaigns' => $recentCampaigns,
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

        // If no campaign budget, use default
        if ($totalBudget === 0) {
            $totalBudget = 5000000; // Default 5M so'm
            $spentBudget = 0;
        }

        return [
            'total' => $totalBudget,
            'spent' => $spentBudget,
            'remaining' => $totalBudget - $spentBudget,
        ];
    }


    private function getRecentCampaigns($businessId): array
    {
        return Campaign::where('business_id', $businessId)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                $settings = $campaign->settings ?? [];
                $budget = $settings['budget'] ?? 0;
                $spent = $settings['spent'] ?? 0;

                return [
                    'id' => $campaign->id,
                    'uuid' => $campaign->uuid,
                    'name' => $campaign->name,
                    'type' => $campaign->type,
                    'channel' => $campaign->channel,
                    'status' => $campaign->status,
                    'budget' => $budget,
                    'spent' => $spent,
                    'leads' => $campaign->sent_count ?? 0,
                    'progress' => $budget > 0 ? round(($spent / $budget) * 100, 1) : 0,
                    'created_at' => $campaign->created_at->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    private function getUpcomingContent($businessId): array
    {
        return ContentCalendar::where('business_id', $businessId)
            ->whereIn('status', ['scheduled', 'approved', 'pending_review'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get()
            ->map(function ($content) {
                return [
                    'id' => $content->id,
                    'uuid' => $content->uuid,
                    'title' => $content->title,
                    'platform' => $content->channel,
                    'content_type' => $content->content_type,
                    'status' => $content->status,
                    'scheduled_at' => $content->scheduled_date
                        ? $content->scheduled_date->format('Y-m-d') . ' ' . ($content->scheduled_time ?? '12:00')
                        : null,
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

        if (!$business) {
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

        // Recent posts from content calendar
        $recentPosts = ContentCalendar::where('business_id', $business->id)
            ->whereIn('status', ['published', 'scheduled', 'draft'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($content) {
                return [
                    'id' => $content->id,
                    'uuid' => $content->uuid ?? null,
                    'title' => $content->title,
                    'platform' => $content->channel,
                    'status' => $content->status,
                    'engagement' => $content->engagement_rate ?? 0,
                    'reach' => $content->reach ?? 0,
                    'scheduled_date' => $content->scheduled_date?->format('Y-m-d'),
                    'published_at' => $content->published_at?->format('Y-m-d'),
                    'created_at' => $content->created_at?->format('Y-m-d'),
                ];
            });

        // Stats for Marketing Hub - same structure as Business panel
        $stats = [
            'dream_buyers' => \App\Models\DreamBuyer::where('business_id', $business->id)->count(),
            'competitors' => \App\Models\Competitor::where('business_id', $business->id)->count(),
            'offers' => \App\Models\Offer::where('business_id', $business->id)->count(),
            'total_posts' => ContentCalendar::where('business_id', $business->id)->count(),
            'published_posts' => ContentCalendar::where('business_id', $business->id)->where('status', 'published')->count(),
            'scheduled_posts' => ContentCalendar::where('business_id', $business->id)->where('status', 'scheduled')->count(),
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

        if (!$business) {
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
