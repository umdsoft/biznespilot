<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\ChatbotConversation;
use App\Models\ChatbotDailyStats;
use App\Models\ChatbotMessage;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateChatbotStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Carbon $date;
    protected ?int $businessId;

    /**
     * Create a new job instance.
     */
    public function __construct(?Carbon $date = null, ?int $businessId = null)
    {
        $this->date = $date ?? Carbon::yesterday();
        $this->businessId = $businessId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $businesses = $this->businessId
                ? Business::where('id', $this->businessId)->get()
                : Business::all();

            foreach ($businesses as $business) {
                $this->aggregateStatsForBusiness($business);
            }

            Log::info('Chatbot stats aggregation completed', [
                'date' => $this->date->toDateString(),
                'business_count' => $businesses->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot stats aggregation failed', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Aggregate stats for a specific business
     */
    protected function aggregateStatsForBusiness(Business $business): void
    {
        $stats = [
            'business_id' => $business->id,
            'date' => $this->date,
        ];

        // Conversation metrics
        $conversationMetrics = $this->getConversationMetrics($business);
        $stats = array_merge($stats, $conversationMetrics);

        // Message metrics
        $messageMetrics = $this->getMessageMetrics($business);
        $stats = array_merge($stats, $messageMetrics);

        // Channel breakdown
        $channelMetrics = $this->getChannelMetrics($business);
        $stats = array_merge($stats, $channelMetrics);

        // Funnel metrics
        $funnelMetrics = $this->getFunnelMetrics($business);
        $stats = array_merge($stats, $funnelMetrics);

        // Lead metrics
        $leadMetrics = $this->getLeadMetrics($business);
        $stats = array_merge($stats, $leadMetrics);

        // Intent and sentiment breakdown
        $intentMetrics = $this->getIntentMetrics($business);
        $stats = array_merge($stats, $intentMetrics);

        // Performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($business);
        $stats = array_merge($stats, $performanceMetrics);

        // Customer satisfaction
        $satisfactionMetrics = $this->getSatisfactionMetrics($business);
        $stats = array_merge($stats, $satisfactionMetrics);

        // Upsert the stats
        ChatbotDailyStats::updateOrCreate(
            [
                'business_id' => $business->id,
                'date' => $this->date,
            ],
            $stats
        );
    }

    /**
     * Get conversation metrics
     */
    protected function getConversationMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        // Total conversations that had any activity on this date
        $totalConversations = ChatbotConversation::where('business_id', $business->id)
            ->where(function ($q) use ($startOfDay, $endOfDay) {
                $q->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->orWhereBetween('updated_at', [$startOfDay, $endOfDay]);
            })
            ->count();

        // New conversations created on this date
        $newConversations = ChatbotConversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Active conversations (still active at end of day)
        $activeConversations = ChatbotConversation::where('business_id', $business->id)
            ->where('status', 'active')
            ->where('created_at', '<=', $endOfDay)
            ->count();

        // Conversations closed on this date
        $closedConversations = ChatbotConversation::where('business_id', $business->id)
            ->whereBetween('closed_at', [$startOfDay, $endOfDay])
            ->count();

        // Conversations handed off on this date
        $handedOffConversations = ChatbotConversation::where('business_id', $business->id)
            ->whereBetween('handed_off_at', [$startOfDay, $endOfDay])
            ->count();

        return [
            'total_conversations' => $totalConversations,
            'new_conversations' => $newConversations,
            'active_conversations' => $activeConversations,
            'closed_conversations' => $closedConversations,
            'handed_off_conversations' => $handedOffConversations,
        ];
    }

    /**
     * Get message metrics
     */
    protected function getMessageMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        $messages = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN role = "bot" THEN 1 ELSE 0 END) as bot_count,
                SUM(CASE WHEN role = "user" THEN 1 ELSE 0 END) as user_count
            ')
            ->first();

        // Calculate average response time
        $avgResponseTime = $this->calculateAverageResponseTime($business, $startOfDay, $endOfDay);

        return [
            'total_messages' => $messages->total ?? 0,
            'bot_messages' => $messages->bot_count ?? 0,
            'user_messages' => $messages->user_count ?? 0,
            'avg_response_time_seconds' => $avgResponseTime,
        ];
    }

    /**
     * Calculate average response time in seconds
     */
    protected function calculateAverageResponseTime(Business $business, Carbon $start, Carbon $end): ?float
    {
        // Get all conversations with messages on this date
        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->with(['messages' => function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                    ->orderBy('created_at', 'asc');
            }])
            ->get();

        $responseTimes = [];

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages;
            $previousMessage = null;

            foreach ($messages as $message) {
                // If current message is from bot and previous was from user, calculate response time
                if ($message->role === 'bot' && $previousMessage && $previousMessage->role === 'user') {
                    $responseTime = $message->created_at->diffInSeconds($previousMessage->created_at);
                    $responseTimes[] = $responseTime;
                }

                $previousMessage = $message;
            }
        }

        if (empty($responseTimes)) {
            return null;
        }

        return round(array_sum($responseTimes) / count($responseTimes), 2);
    }

    /**
     * Get channel metrics
     */
    protected function getChannelMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        $channelCounts = ChatbotConversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->selectRaw('
                channel,
                COUNT(*) as count
            ')
            ->groupBy('channel')
            ->get()
            ->pluck('count', 'channel');

        return [
            'telegram_conversations' => $channelCounts['telegram'] ?? 0,
            'instagram_conversations' => $channelCounts['instagram'] ?? 0,
            'facebook_conversations' => $channelCounts['facebook'] ?? 0,
        ];
    }

    /**
     * Get funnel stage metrics
     */
    protected function getFunnelMetrics(Business $business): array
    {
        $endOfDay = $this->date->copy()->endOfDay();

        $stageCounts = ChatbotConversation::where('business_id', $business->id)
            ->where('created_at', '<=', $endOfDay)
            ->selectRaw('
                current_stage,
                COUNT(*) as count
            ')
            ->groupBy('current_stage')
            ->get()
            ->pluck('count', 'current_stage');

        return [
            'awareness_stage' => $stageCounts['AWARENESS'] ?? 0,
            'interest_stage' => $stageCounts['INTEREST'] ?? 0,
            'consideration_stage' => $stageCounts['CONSIDERATION'] ?? 0,
            'intent_stage' => $stageCounts['INTENT'] ?? 0,
            'purchase_stage' => $stageCounts['PURCHASE'] ?? 0,
            'post_purchase_stage' => $stageCounts['POST_PURCHASE'] ?? 0,
        ];
    }

    /**
     * Get lead metrics
     */
    protected function getLeadMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        // Leads created from chatbot on this date
        $leadsCreated = Lead::where('business_id', $business->id)
            ->where('source', 'like', 'chatbot_%')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Leads converted (status changed to 'won' or 'converted')
        $leadsConverted = Lead::where('business_id', $business->id)
            ->where('source', 'like', 'chatbot_%')
            ->whereIn('status', ['won', 'converted'])
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->count();

        // Calculate conversion rate
        $conversionRate = $leadsCreated > 0 ? round(($leadsConverted / $leadsCreated) * 100, 2) : 0;

        // Total conversion value
        $conversionValue = Lead::where('business_id', $business->id)
            ->where('source', 'like', 'chatbot_%')
            ->whereIn('status', ['won', 'converted'])
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->sum('value') ?? 0;

        return [
            'leads_created' => $leadsCreated,
            'leads_converted' => $leadsConverted,
            'conversion_rate' => $conversionRate,
            'total_conversion_value' => $conversionValue,
        ];
    }

    /**
     * Get intent and sentiment metrics
     */
    protected function getIntentMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        $messages = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereNotNull('detected_intent')
            ->get();

        // Aggregate intent breakdown
        $intentBreakdown = [];
        foreach ($messages as $message) {
            $intent = $message->detected_intent;
            $intentBreakdown[$intent] = ($intentBreakdown[$intent] ?? 0) + 1;
        }

        // Aggregate sentiment breakdown
        $sentimentBreakdown = [];
        foreach ($messages as $message) {
            $sentiment = $message->sentiment ?? 'neutral';
            $sentimentBreakdown[$sentiment] = ($sentimentBreakdown[$sentiment] ?? 0) + 1;
        }

        return [
            'intent_breakdown' => empty($intentBreakdown) ? null : $intentBreakdown,
            'sentiment_breakdown' => empty($sentimentBreakdown) ? null : $sentimentBreakdown,
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        // Get conversations closed on this date
        $closedConversations = ChatbotConversation::where('business_id', $business->id)
            ->whereBetween('closed_at', [$startOfDay, $endOfDay])
            ->get();

        if ($closedConversations->isEmpty()) {
            return [
                'avg_conversation_duration_minutes' => null,
                'avg_messages_per_conversation' => null,
            ];
        }

        // Calculate average conversation duration
        $totalDuration = 0;
        $totalMessages = 0;

        foreach ($closedConversations as $conversation) {
            if ($conversation->created_at && $conversation->closed_at) {
                $duration = $conversation->created_at->diffInMinutes($conversation->closed_at);
                $totalDuration += $duration;
            }

            $totalMessages += $conversation->messages()->count();
        }

        $avgDuration = $closedConversations->count() > 0
            ? round($totalDuration / $closedConversations->count(), 2)
            : null;

        $avgMessages = $closedConversations->count() > 0
            ? round($totalMessages / $closedConversations->count(), 2)
            : null;

        return [
            'avg_conversation_duration_minutes' => $avgDuration,
            'avg_messages_per_conversation' => $avgMessages,
        ];
    }

    /**
     * Get customer satisfaction metrics
     */
    protected function getSatisfactionMetrics(Business $business): array
    {
        $startOfDay = $this->date->copy()->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        $ratings = ChatbotConversation::where('business_id', $business->id)
            ->whereNotNull('rating')
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->selectRaw('
                AVG(rating) as avg_rating,
                COUNT(*) as total_ratings
            ')
            ->first();

        return [
            'avg_rating' => $ratings->avg_rating ? round($ratings->avg_rating, 2) : null,
            'total_ratings' => $ratings->total_ratings ?? 0,
        ];
    }
}
