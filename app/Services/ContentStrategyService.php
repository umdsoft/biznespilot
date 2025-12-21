<?php

namespace App\Services;

use App\Models\ContentCalendar;
use App\Models\MonthlyPlan;
use App\Models\WeeklyPlan;
use App\Models\Business;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ContentStrategyService
{
    public function __construct(
        private AIStrategyService $aiService
    ) {}

    /**
     * Generate content calendar for a month
     */
    public function generateMonthlyCalendar(MonthlyPlan $monthlyPlan): Collection
    {
        $items = collect();

        $startDate = $monthlyPlan->getStartDate();
        $endDate = $monthlyPlan->getEndDate();

        $contentThemes = $monthlyPlan->content_themes ?? ['educational', 'promotional', 'engagement', 'behind_scenes'];
        $contentTypes = $monthlyPlan->content_types ?? ['post', 'story', 'reel'];
        $channels = $monthlyPlan->channel_focus ?? ['instagram', 'telegram'];

        // Get posting schedule
        $postsPerDay = ceil(($monthlyPlan->posts_target ?? 30) / $endDate->day);

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            // Skip if Sunday (optional rest day)
            if ($date->isSunday()) {
                continue;
            }

            // Determine theme for the day
            $dayOfWeek = $date->dayOfWeek;
            $theme = $this->getThemeForDay($dayOfWeek, $contentThemes);

            // Determine content type
            $contentType = $this->getContentTypeForDay($dayOfWeek, $contentTypes);

            foreach ($channels as $channel) {
                $item = ContentCalendar::create([
                    'uuid' => Str::uuid(),
                    'business_id' => $monthlyPlan->business_id,
                    'monthly_plan_id' => $monthlyPlan->id,
                    'title' => $this->generateContentTitle($theme, $contentType, $date),
                    'content_type' => $contentType,
                    'channel' => $channel,
                    'scheduled_date' => $date,
                    'scheduled_time' => $this->getBestPostingTime($channel, $contentType),
                    'status' => 'idea',
                    'theme' => $theme,
                    'goal' => $this->getGoalForTheme($theme),
                    'is_ai_generated' => false,
                    'priority' => $this->getPriorityForDay($dayOfWeek),
                ]);

                $items->push($item);
            }
        }

        return $items;
    }

    /**
     * Generate content calendar for a week
     */
    public function generateWeeklyCalendar(WeeklyPlan $weeklyPlan): Collection
    {
        $items = collect();

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $dayIndex => $day) {
            $dayPlan = $weeklyPlan->getDayPlan($day);
            $date = $weeklyPlan->start_date->copy()->addDays($dayIndex);

            if (!$dayPlan || empty($dayPlan['content'])) {
                continue;
            }

            $content = $dayPlan['content'];
            $channels = $weeklyPlan->monthlyPlan?->channel_focus ?? ['instagram'];

            foreach ($channels as $channel) {
                $item = ContentCalendar::create([
                    'uuid' => Str::uuid(),
                    'business_id' => $weeklyPlan->business_id,
                    'weekly_plan_id' => $weeklyPlan->id,
                    'monthly_plan_id' => $weeklyPlan->monthly_plan_id,
                    'title' => $content['topic'] ?? "Kontent - {$day}",
                    'content_type' => $content['type'] ?? 'post',
                    'channel' => $channel,
                    'scheduled_date' => $date,
                    'scheduled_time' => $this->getBestPostingTime($channel, $content['type'] ?? 'post'),
                    'status' => 'idea',
                    'theme' => $content['theme'] ?? null,
                    'goal' => $content['goal'] ?? 'engagement',
                    'is_ai_generated' => false,
                    'priority' => $this->getPriorityForDay($dayIndex),
                ]);

                $items->push($item);
            }
        }

        return $items;
    }

    /**
     * Generate AI content suggestions for an item
     */
    public function generateAIContent(ContentCalendar $item): ContentCalendar
    {
        $business = $item->business;

        $suggestions = $this->aiService->generateContentIdeas($business, [
            'channel' => $item->channel,
            'theme' => $item->theme,
            'type' => $item->content_type,
        ], 1);

        if (!empty($suggestions)) {
            $suggestion = $suggestions[0];

            $item->update([
                'ai_suggestions' => $suggestions,
                'ai_caption_suggestion' => $suggestion['caption'] ?? null,
                'hashtags' => $suggestion['hashtags'] ?? [],
                'is_ai_generated' => true,
            ]);
        }

        return $item->fresh();
    }

    /**
     * Move content item to a different date
     */
    public function moveContent(ContentCalendar $item, string $newDate, ?string $newTime = null): ContentCalendar
    {
        $item->update([
            'scheduled_date' => Carbon::parse($newDate),
            'scheduled_time' => $newTime ?? $item->scheduled_time,
        ]);

        return $item->fresh();
    }

    /**
     * Duplicate content item
     */
    public function duplicateContent(ContentCalendar $item, ?string $newDate = null): ContentCalendar
    {
        return $item->duplicate($newDate);
    }

    /**
     * Get content calendar for date range
     */
    public function getCalendar(Business $business, string $startDate, string $endDate, ?string $channel = null): Collection
    {
        $query = ContentCalendar::where('business_id', $business->id)
            ->forDateRange($startDate, $endDate)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');

        if ($channel) {
            $query->forChannel($channel);
        }

        return $query->get();
    }

    /**
     * Get content analytics summary
     */
    public function getContentAnalytics(Business $business, string $startDate, string $endDate): array
    {
        $items = ContentCalendar::where('business_id', $business->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        $published = $items->where('status', 'published');

        return [
            'total_planned' => $items->count(),
            'total_published' => $published->count(),
            'completion_rate' => $items->count() > 0 ? round(($published->count() / $items->count()) * 100, 1) : 0,
            'total_reach' => $published->sum('reach'),
            'total_engagement' => $published->sum(function ($item) {
                return $item->getTotalEngagements();
            }),
            'avg_engagement_rate' => round($published->avg('engagement_rate') ?? 0, 2),
            'by_channel' => $published->groupBy('channel')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'reach' => $group->sum('reach'),
                    'engagement' => $group->sum(function ($item) {
                        return $item->getTotalEngagements();
                    }),
                    'avg_engagement_rate' => round($group->avg('engagement_rate') ?? 0, 2),
                ];
            }),
            'by_type' => $published->groupBy('content_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg_engagement_rate' => round($group->avg('engagement_rate') ?? 0, 2),
                ];
            }),
            'top_performing' => $published->sortByDesc('engagement_rate')->take(5)->values(),
        ];
    }

    /**
     * Get upcoming content items
     */
    public function getUpcomingContent(Business $business, int $days = 7): Collection
    {
        return ContentCalendar::where('business_id', $business->id)
            ->upcoming()
            ->forDateRange(now()->toDateString(), now()->addDays($days)->toDateString())
            ->get();
    }

    /**
     * Get today's content
     */
    public function getTodaysContent(Business $business): Collection
    {
        return ContentCalendar::where('business_id', $business->id)
            ->today()
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Bulk update content status
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return ContentCalendar::whereIn('id', $ids)->update(['status' => $status]);
    }

    // Helper methods
    private function getThemeForDay(int $dayOfWeek, array $themes): string
    {
        $themeMap = [
            1 => 0, // Monday - Theme 1
            2 => 1, // Tuesday - Theme 2
            3 => 2, // Wednesday - Theme 3
            4 => 3, // Thursday - Theme 4
            5 => 0, // Friday - Theme 1
            6 => 1, // Saturday - Theme 2
            0 => 2, // Sunday - Theme 3
        ];

        $index = $themeMap[$dayOfWeek] % count($themes);
        return $themes[$index];
    }

    private function getContentTypeForDay(int $dayOfWeek, array $types): string
    {
        // Reels on Wednesday, Stories on weekend, Posts otherwise
        if ($dayOfWeek === 3 && in_array('reel', $types)) {
            return 'reel';
        }

        if (in_array($dayOfWeek, [0, 6]) && in_array('story', $types)) {
            return 'story';
        }

        return 'post';
    }

    private function generateContentTitle(string $theme, string $type, Carbon $date): string
    {
        $typeLabels = [
            'post' => 'Post',
            'story' => 'Story',
            'reel' => 'Reel',
            'carousel' => 'Carousel',
        ];

        $typeName = $typeLabels[$type] ?? $type;
        return "{$typeName} - {$theme} ({$date->format('d.m')})";
    }

    private function getBestPostingTime(string $channel, string $type): string
    {
        // Best posting times for Uzbekistan audience
        $times = [
            'instagram' => [
                'post' => '18:00',
                'story' => '09:00',
                'reel' => '20:00',
            ],
            'telegram' => [
                'post' => '13:00',
                'story' => '09:00',
            ],
            'facebook' => [
                'post' => '19:00',
            ],
            'tiktok' => [
                'video' => '21:00',
                'reel' => '21:00',
            ],
        ];

        return $times[$channel][$type] ?? $times[$channel]['post'] ?? '12:00';
    }

    private function getGoalForTheme(string $theme): string
    {
        $goals = [
            'educational' => 'education',
            'promotional' => 'conversion',
            'engagement' => 'engagement',
            'behind_scenes' => 'awareness',
            'testimonial' => 'conversion',
            'product' => 'conversion',
        ];

        return $goals[$theme] ?? 'engagement';
    }

    private function getPriorityForDay(int $dayOfWeek): int
    {
        // Higher priority for weekdays
        if (in_array($dayOfWeek, [1, 2, 3, 4, 5])) {
            return 1;
        }
        return 0;
    }
}
