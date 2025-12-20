<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\Business;
use App\Models\MarketingChannel;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AIInsightsService
{
    /**
     * Claude AI Service instance
     */
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Generate insights for a business
     *
     * @param Business $business
     * @param array $types Insight types to generate
     * @return array Generated insights
     */
    public function generateInsightsForBusiness(Business $business, array $types = []): array
    {
        if (empty($types)) {
            $types = ['marketing', 'sales', 'content', 'customer'];
        }

        $insights = [];

        foreach ($types as $type) {
            try {
                $insight = $this->generateInsight($business, $type);
                if ($insight) {
                    $insights[] = $insight;
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate {$type} insight for business {$business->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $insights;
    }

    /**
     * Generate a specific type of insight
     *
     * @param Business $business
     * @param string $type
     * @return AiInsight|null
     */
    public function generateInsight(Business $business, string $type): ?AiInsight
    {
        // Gather relevant data for this insight type
        $data = $this->gatherDataForInsightType($business, $type);

        if (empty($data)) {
            Log::info("No data available for {$type} insight", ['business_id' => $business->id]);
            return null;
        }

        // Generate insight using Claude AI
        $insightData = $this->claudeAI->generateInsight($data, $type);

        // Create insight record
        return AiInsight::create([
            'business_id' => $business->id,
            'type' => $type,
            'title' => $insightData['title'],
            'content' => $insightData['content'],
            'priority' => $insightData['priority'],
            'sentiment' => $insightData['sentiment'],
            'is_actionable' => $insightData['is_actionable'],
            'data' => $data,
            'generated_at' => now(),
        ]);
    }

    /**
     * Gather data for specific insight type
     *
     * @param Business $business
     * @param string $type
     * @return array
     */
    private function gatherDataForInsightType(Business $business, string $type): array
    {
        return match($type) {
            'marketing' => $this->gatherMarketingData($business),
            'sales' => $this->gatherSalesData($business),
            'content' => $this->gatherContentData($business),
            'customer' => $this->gatherCustomerData($business),
            'competitor' => $this->gatherCompetitorData($business),
            default => [],
        };
    }

    /**
     * Gather marketing channel data
     */
    private function gatherMarketingData(Business $business): array
    {
        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        if ($channels->isEmpty()) {
            return [];
        }

        $data = [
            'period' => '30 days',
            'channels' => [],
        ];

        foreach ($channels as $channel) {
            $metrics = $channel->latestMetrics();

            if (!$metrics) {
                continue;
            }

            $channelData = [
                'name' => $channel->name,
                'type' => $channel->type,
                'platform' => $channel->platform,
            ];

            // Add platform-specific metrics
            if ($channel->type === 'instagram') {
                $channelData['followers'] = $metrics->followers_count;
                $channelData['reach'] = $metrics->reach;
                $channelData['engagement_rate'] = $metrics->engagement_rate;
                $channelData['likes'] = $metrics->likes;
                $channelData['comments'] = $metrics->comments;
            } elseif ($channel->type === 'telegram') {
                $channelData['members'] = $metrics->members_count;
                $channelData['views'] = $metrics->total_views;
                $channelData['engagement_rate'] = $metrics->engagement_rate;
            } elseif ($channel->type === 'facebook') {
                $channelData['followers'] = $metrics->page_followers;
                $channelData['reach'] = $metrics->reach;
                $channelData['engagement_rate'] = $metrics->engagement_rate;
            } elseif ($channel->type === 'google_ads') {
                $channelData['impressions'] = $metrics->impressions;
                $channelData['clicks'] = $metrics->clicks;
                $channelData['conversions'] = $metrics->conversions;
                $channelData['cost'] = $metrics->cost / 100; // Convert from kopeks
                $channelData['ctr'] = $metrics->ctr;
                $channelData['roas'] = $metrics->roas;
            }

            $data['channels'][] = $channelData;
        }

        return $data;
    }

    /**
     * Gather sales data
     */
    private function gatherSalesData(Business $business): array
    {
        $period = Carbon::now()->subDays(30);

        $orders = Order::where('business_id', $business->id)
            ->where('created_at', '>=', $period)
            ->get();

        $leads = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', $period)
            ->get();

        if ($orders->isEmpty() && $leads->isEmpty()) {
            return [];
        }

        return [
            'period' => '30 days',
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'total_leads' => $leads->count(),
            'conversion_rate' => $leads->count() > 0
                ? round(($orders->count() / $leads->count()) * 100, 2)
                : 0,
            'orders_by_status' => $orders->groupBy('status')->map->count(),
            'leads_by_status' => $leads->groupBy('status')->map->count(),
        ];
    }

    /**
     * Gather content performance data
     */
    private function gatherContentData(Business $business): array
    {
        // This would analyze content posts if ContentPost model exists
        // For now, we'll use marketing channel data as proxy

        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        $contentData = [
            'period' => '30 days',
            'channels_analyzed' => $channels->count(),
            'engagement_trends' => [],
        ];

        foreach ($channels as $channel) {
            $metrics = $channel->latestMetrics();
            if ($metrics && isset($metrics->engagement_rate)) {
                $contentData['engagement_trends'][$channel->type] = $metrics->engagement_rate;
            }
        }

        return $contentData;
    }

    /**
     * Gather customer data
     */
    private function gatherCustomerData(Business $business): array
    {
        $period = Carbon::now()->subDays(30);

        $customers = Customer::where('business_id', $business->id)->get();
        $recentCustomers = Customer::where('business_id', $business->id)
            ->where('created_at', '>=', $period)
            ->get();

        if ($customers->isEmpty()) {
            return [];
        }

        $orders = Order::where('business_id', $business->id)
            ->where('created_at', '>=', $period)
            ->get();

        return [
            'period' => '30 days',
            'total_customers' => $customers->count(),
            'new_customers' => $recentCustomers->count(),
            'repeat_customers' => $orders->groupBy('customer_id')
                ->filter(fn($orders) => $orders->count() > 1)
                ->count(),
            'average_customer_value' => $orders->isNotEmpty()
                ? $orders->sum('total_amount') / $orders->unique('customer_id')->count()
                : 0,
            'churn_indicators' => $this->calculateChurnIndicators($business, $customers),
        ];
    }

    /**
     * Calculate churn indicators
     */
    private function calculateChurnIndicators(Business $business, $customers): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sixtyDaysAgo = Carbon::now()->subDays(60);

        $inactiveCustomers = $customers->filter(function ($customer) use ($thirtyDaysAgo) {
            $lastOrder = Order::where('customer_id', $customer->id)
                ->latest('created_at')
                ->first();

            return $lastOrder && $lastOrder->created_at < $thirtyDaysAgo;
        });

        return [
            'inactive_customers_30d' => $inactiveCustomers->count(),
            'at_risk_percentage' => $customers->count() > 0
                ? round(($inactiveCustomers->count() / $customers->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Gather competitor data (placeholder)
     */
    private function gatherCompetitorData(Business $business): array
    {
        // This would be implemented when Competitor model exists
        return [];
    }

    /**
     * Generate monthly insights summary
     *
     * @param Business $business
     * @return array
     */
    public function generateMonthlyInsightsSummary(Business $business): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $insights = AiInsight::where('business_id', $business->id)
            ->where('generated_at', '>=', $startOfMonth)
            ->get();

        return [
            'total_insights' => $insights->count(),
            'by_priority' => [
                'urgent' => $insights->where('priority', 'urgent')->count(),
                'high' => $insights->where('priority', 'high')->count(),
                'medium' => $insights->where('priority', 'medium')->count(),
                'low' => $insights->where('priority', 'low')->count(),
            ],
            'by_type' => $insights->groupBy('type')->map->count(),
            'actionable' => $insights->where('is_actionable', true)->count(),
            'acted_upon' => $insights->whereNotNull('action_taken')->count(),
            'unread' => $insights->where('is_read', false)->count(),
        ];
    }

    /**
     * Get top priority insights
     *
     * @param Business $business
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopPriorityInsights(Business $business, int $limit = 5)
    {
        return AiInsight::where('business_id', $business->id)
            ->unread()
            ->highPriority()
            ->orderByRaw("
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END
            ")
            ->orderBy('generated_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
