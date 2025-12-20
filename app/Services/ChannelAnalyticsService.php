<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Channel Analytics Service
 *
 * Advanced analytics for all messaging channels
 */
class ChannelAnalyticsService
{
    /**
     * Get comprehensive channel analytics
     */
    public function getChannelAnalytics(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'overview' => $this->getOverview($business, $channel, $startDate, $endDate),
            'message_volume' => $this->getMessageVolume($business, $channel, $startDate, $endDate),
            'response_metrics' => $this->getResponseMetrics($business, $channel, $startDate, $endDate),
            'engagement_metrics' => $this->getEngagementMetrics($business, $channel, $startDate, $endDate),
            'conversion_metrics' => $this->getConversionMetrics($business, $channel, $startDate, $endDate),
            'hourly_distribution' => $this->getHourlyDistribution($business, $channel, $startDate, $endDate),
        ];
    }

    /**
     * Get overview metrics
     */
    protected function getOverview(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $messages = ChatbotMessage::whereIn('conversation_id', $conversations->pluck('id'))
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_conversations' => $conversations->count(),
            'total_messages' => $messages->count(),
            'unique_customers' => $conversations->distinct('customer_id')->count(),
            'avg_messages_per_conversation' => $conversations->count() > 0
                ? round($messages->count() / $conversations->count(), 2)
                : 0,
        ];
    }

    /**
     * Get message volume over time
     */
    protected function getMessageVolume(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        $daily = ChatbotMessage::whereIn('conversation_id', $conversations)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(CASE WHEN direction = "incoming" THEN 1 ELSE 0 END) as incoming, SUM(CASE WHEN direction = "outgoing" THEN 1 ELSE 0 END) as outgoing')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'daily' => $daily->map(fn($d) => [
                'date' => $d->date,
                'total' => $d->count,
                'incoming' => $d->incoming,
                'outgoing' => $d->outgoing,
            ])->toArray(),
        ];
    }

    /**
     * Get response time metrics
     */
    protected function getResponseMetrics(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        $messages = ChatbotMessage::whereIn('conversation_id', $conversations)
            ->where('direction', 'incoming')
            ->whereNotNull('response_time_seconds')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $avgResponseTime = $messages->avg('response_time_seconds');

        return [
            'avg_response_time_seconds' => round($avgResponseTime ?? 0, 2),
            'avg_response_time_minutes' => round(($avgResponseTime ?? 0) / 60, 2),
            'response_rate' => $this->calculateResponseRate($business, $channel, $startDate, $endDate),
        ];
    }

    /**
     * Get engagement metrics
     */
    protected function getEngagementMetrics(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'active_conversations' => $conversations->where('status', 'open')->count(),
            'closed_conversations' => $conversations->where('status', 'closed')->count(),
            'avg_conversation_duration_minutes' => $this->calculateAvgDuration($conversations->get()),
            'returning_customers' => $this->countReturningCustomers($business, $channel, $startDate, $endDate),
        ];
    }

    /**
     * Get conversion metrics
     */
    protected function getConversionMetrics(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalConversations = $conversations->count();
        $convertedConversations = $conversations->where('current_stage', 'PURCHASE')->count();

        return [
            'total_conversations' => $totalConversations,
            'converted_conversations' => $convertedConversations,
            'conversion_rate' => $totalConversations > 0
                ? round(($convertedConversations / $totalConversations) * 100, 2)
                : 0,
            'stages_distribution' => $this->getStagesDistribution($conversations->get()),
        ];
    }

    /**
     * Get hourly message distribution
     */
    protected function getHourlyDistribution(Business $business, string $channel, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        $hourly = ChatbotMessage::whereIn('conversation_id', $conversations)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return $hourly->map(fn($h) => [
            'hour' => $h->hour,
            'count' => $h->count,
        ])->toArray();
    }

    /**
     * Compare channels
     */
    public function compareChannels(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $channels = ['whatsapp', 'instagram', 'telegram', 'facebook'];
        $comparison = [];

        foreach ($channels as $channel) {
            $conversations = ChatbotConversation::where('business_id', $business->id)
                ->where('channel', $channel)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $messages = ChatbotMessage::whereIn('conversation_id', $conversations->pluck('id'))
                ->whereBetween('created_at', [$startDate, $endDate]);

            $comparison[$channel] = [
                'conversations' => $conversations->count(),
                'messages' => $messages->count(),
                'conversion_rate' => $this->calculateConversionRate($conversations->get()),
            ];
        }

        return $comparison;
    }

    /**
     * Calculate response rate
     */
    protected function calculateResponseRate(Business $business, string $channel, Carbon $startDate, Carbon $endDate): float
    {
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        $incomingMessages = ChatbotMessage::whereIn('conversation_id', $conversations)
            ->where('direction', 'incoming')
            ->count();

        $respondedMessages = ChatbotMessage::whereIn('conversation_id', $conversations)
            ->where('direction', 'incoming')
            ->whereNotNull('response_time_seconds')
            ->count();

        return $incomingMessages > 0 ? round(($respondedMessages / $incomingMessages) * 100, 2) : 0;
    }

    /**
     * Calculate average conversation duration
     */
    protected function calculateAvgDuration($conversations): float
    {
        if ($conversations->isEmpty()) return 0;

        $durations = $conversations->map(function ($conv) {
            if ($conv->last_message_at && $conv->created_at) {
                return $conv->created_at->diffInMinutes($conv->last_message_at);
            }
            return 0;
        })->filter(fn($d) => $d > 0);

        return $durations->isNotEmpty() ? round($durations->avg(), 2) : 0;
    }

    /**
     * Count returning customers
     */
    protected function countReturningCustomers(Business $business, string $channel, Carbon $startDate, Carbon $endDate): int
    {
        return ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    /**
     * Get stages distribution
     */
    protected function getStagesDistribution($conversations): array
    {
        return $conversations->groupBy('current_stage')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Calculate conversion rate
     */
    protected function calculateConversionRate($conversations): float
    {
        $total = $conversations->count();
        $converted = $conversations->where('current_stage', 'PURCHASE')->count();

        return $total > 0 ? round(($converted / $total) * 100, 2) : 0;
    }
}
