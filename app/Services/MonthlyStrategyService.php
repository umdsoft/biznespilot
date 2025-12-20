<?php

namespace App\Services;

use App\Models\Business;
use App\Models\AiMonthlyStrategy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyStrategyService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Generate monthly strategy for a business
     */
    public function generateMonthlyStrategy(Business $business, ?int $year = null, ?int $month = null): AiMonthlyStrategy
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        // Check if strategy already exists
        $existing = AiMonthlyStrategy::where('business_id', $business->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Gather comprehensive business data
        $businessData = $this->gatherBusinessData($business);

        // Generate strategy using Claude AI
        $strategyData = $this->claudeAI->generateMonthlyStrategy($businessData, $year, $month);

        // Create strategy record
        $strategy = AiMonthlyStrategy::create([
            'business_id' => $business->id,
            'year' => $year,
            'month' => $month,
            'period_label' => Carbon::create($year, $month, 1)->format('F Y'),
            'title' => $strategyData['title'] ?? 'Oylik Marketing va Sotuv Strategiyasi',
            'executive_summary' => $strategyData['executive_summary'] ?? '',
            'goals' => $strategyData['goals'] ?? [],
            'action_plan' => $strategyData['action_plan'] ?? [],
            'focus_areas' => $strategyData['focus_areas'] ?? [],
            'content_strategy' => $strategyData['content_strategy'] ?? [],
            'advertising_strategy' => $strategyData['advertising_strategy'] ?? [],
            'channel_strategy' => $strategyData['channel_strategy'] ?? [],
            'sales_targets' => $strategyData['sales_targets'] ?? [],
            'pricing_recommendations' => $strategyData['pricing_recommendations'] ?? [],
            'offer_recommendations' => $strategyData['offer_recommendations'] ?? [],
            'recommended_budget' => $strategyData['recommended_budget'] ?? null,
            'budget_breakdown' => $strategyData['budget_breakdown'] ?? [],
            'predicted_metrics' => $strategyData['predicted_metrics'] ?? [],
            'confidence_score' => $strategyData['confidence_score'] ?? 0.75,
            'status' => 'draft',
            'generated_at' => now(),
        ]);

        return $strategy;
    }

    /**
     * Gather comprehensive business data for strategy generation
     */
    private function gatherBusinessData(Business $business): array
    {
        $now = now();
        $last30Days = $now->copy()->subDays(30);
        $last90Days = $now->copy()->subDays(90);

        return [
            'business_info' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
                'target_market' => $business->target_market,
                'description' => $business->description,
            ],

            // Marketing channels data (last 30 days)
            'marketing_channels' => $this->gatherMarketingChannelsData($business, $last30Days, $now),

            // Sales data (last 30 and 90 days comparison)
            'sales_data' => $this->gatherSalesData($business, $last30Days, $last90Days, $now),

            // Customer data
            'customer_data' => $this->gatherCustomerData($business, $last30Days, $last90Days, $now),

            // Dream buyer information
            'dream_buyer' => $this->gatherDreamBuyerData($business),

            // Competitors data
            'competitors' => $this->gatherCompetitorsData($business),

            // Current offers
            'offers' => $this->gatherOffersData($business),

            // Historical performance
            'historical_performance' => $this->gatherHistoricalPerformance($business),

            // Current month and year
            'target_period' => [
                'year' => $now->year,
                'month' => $now->month,
                'month_name' => $now->format('F'),
                'days_in_month' => $now->daysInMonth,
            ],
        ];
    }

    /**
     * Gather marketing channels data
     */
    private function gatherMarketingChannelsData(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $channels = $business->marketingChannels()->get();
        $channelsData = [];

        foreach ($channels as $channel) {
            $metrics = [];

            switch ($channel->platform) {
                case 'instagram':
                    $metrics = DB::table('instagram_metrics')
                        ->where('channel_id', $channel->id)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->select([
                            DB::raw('SUM(followers_count) as total_followers'),
                            DB::raw('SUM(reach) as total_reach'),
                            DB::raw('SUM(impressions) as total_impressions'),
                            DB::raw('SUM(engagement) as total_engagement'),
                            DB::raw('AVG(engagement_rate) as avg_engagement_rate'),
                        ])
                        ->first();
                    break;

                case 'telegram':
                    $metrics = DB::table('telegram_metrics')
                        ->where('channel_id', $channel->id)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->select([
                            DB::raw('SUM(subscribers_count) as total_subscribers'),
                            DB::raw('SUM(views) as total_views'),
                            DB::raw('SUM(shares) as total_shares'),
                            DB::raw('AVG(average_views_per_post) as avg_views_per_post'),
                        ])
                        ->first();
                    break;

                case 'facebook':
                    $metrics = DB::table('facebook_metrics')
                        ->where('channel_id', $channel->id)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->select([
                            DB::raw('SUM(page_likes) as total_likes'),
                            DB::raw('SUM(reach) as total_reach'),
                            DB::raw('SUM(engagement) as total_engagement'),
                            DB::raw('AVG(engagement_rate) as avg_engagement_rate'),
                        ])
                        ->first();
                    break;

                case 'google_ads':
                    $metrics = DB::table('google_ads_metrics')
                        ->where('channel_id', $channel->id)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->select([
                            DB::raw('SUM(impressions) as total_impressions'),
                            DB::raw('SUM(clicks) as total_clicks'),
                            DB::raw('SUM(conversions) as total_conversions'),
                            DB::raw('SUM(cost) as total_cost'),
                            DB::raw('AVG(ctr) as avg_ctr'),
                            DB::raw('AVG(cpc) as avg_cpc'),
                        ])
                        ->first();
                    break;
            }

            $channelsData[] = [
                'platform' => $channel->platform,
                'name' => $channel->name,
                'is_active' => $channel->is_active,
                'metrics' => $metrics ? (array) $metrics : [],
            ];
        }

        return $channelsData;
    }

    /**
     * Gather sales data
     */
    private function gatherSalesData(Business $business, Carbon $last30Days, Carbon $last90Days, Carbon $now): array
    {
        $last30DaysSales = $business->sales()
            ->whereBetween('created_at', [$last30Days, $now])
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('AVG(amount) as average_order_value'),
            ])
            ->first();

        $last90DaysSales = $business->sales()
            ->whereBetween('created_at', [$last90Days, $now])
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('AVG(amount) as average_order_value'),
            ])
            ->first();

        return [
            'last_30_days' => $last30DaysSales ? (array) $last30DaysSales : [],
            'last_90_days' => $last90DaysSales ? (array) $last90DaysSales : [],
        ];
    }

    /**
     * Gather customer data
     */
    private function gatherCustomerData(Business $business, Carbon $last30Days, Carbon $last90Days, Carbon $now): array
    {
        $newCustomers30Days = $business->customers()
            ->whereBetween('created_at', [$last30Days, $now])
            ->count();

        $repeatCustomers30Days = $business->sales()
            ->select('customer_id')
            ->whereBetween('created_at', [$last30Days, $now])
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return [
            'total_customers' => $business->customers()->count(),
            'new_customers_30_days' => $newCustomers30Days,
            'repeat_customers_30_days' => $repeatCustomers30Days,
            'retention_rate' => $newCustomers30Days > 0
                ? round(($repeatCustomers30Days / $newCustomers30Days) * 100, 2)
                : 0,
        ];
    }

    /**
     * Gather dream buyer data
     */
    private function gatherDreamBuyerData(Business $business): array
    {
        $dreamBuyers = $business->dreamBuyers()->get();

        return $dreamBuyers->map(function ($buyer) {
            return [
                'name' => $buyer->name,
                'demographics' => $buyer->demographics,
                'psychographics' => $buyer->psychographics,
                'pain_points' => $buyer->pain_points,
                'goals' => $buyer->goals,
            ];
        })->toArray();
    }

    /**
     * Gather competitors data
     */
    private function gatherCompetitorsData(Business $business): array
    {
        $competitors = $business->competitors()->get();

        return $competitors->map(function ($competitor) {
            return [
                'name' => $competitor->name,
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
                'market_position' => $competitor->market_position,
            ];
        })->toArray();
    }

    /**
     * Gather offers data
     */
    private function gatherOffersData(Business $business): array
    {
        $offers = $business->offers()
            ->where('is_active', true)
            ->get();

        return $offers->map(function ($offer) {
            return [
                'name' => $offer->name,
                'type' => $offer->type,
                'price' => $offer->price,
                'description' => $offer->description,
            ];
        })->toArray();
    }

    /**
     * Gather historical performance data
     */
    private function gatherHistoricalPerformance(Business $business): array
    {
        $lastThreeMonths = [];

        for ($i = 1; $i <= 3; $i++) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $sales = $business->sales()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->select([
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(amount) as total_revenue'),
                ])
                ->first();

            $lastThreeMonths[] = [
                'month' => $monthStart->format('F Y'),
                'orders' => $sales->total_orders ?? 0,
                'revenue' => $sales->total_revenue ?? 0,
            ];
        }

        return $lastThreeMonths;
    }

    /**
     * Get all strategies for a business
     */
    public function getStrategiesForBusiness(Business $business, ?int $year = null)
    {
        $query = AiMonthlyStrategy::where('business_id', $business->id);

        if ($year) {
            $query->byYear($year);
        }

        return $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }

    /**
     * Get current month's strategy
     */
    public function getCurrentMonthStrategy(Business $business): ?AiMonthlyStrategy
    {
        return AiMonthlyStrategy::where('business_id', $business->id)
            ->currentMonth()
            ->first();
    }
}
