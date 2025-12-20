<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Models\MarketingChannel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAnalyticsService
{
    /**
     * Get conversion funnel data with stage-by-stage metrics
     *
     * @param int $businessId
     * @param array $filters ['date_from', 'date_to', 'dream_buyer_id', 'offer_id', 'source_id']
     * @return array
     */
    public function getFunnelData(int $businessId, array $filters = []): array
    {
        $query = Lead::where('business_id', $businessId);

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }
        if (!empty($filters['dream_buyer_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('dream_buyer_id', $filters['dream_buyer_id']);
            });
        }
        if (!empty($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        $leads = $query->get();

        // Funnel stages
        $stages = [
            'new' => 'Yangi Lead',
            'contacted' => 'Bog\'lanildi',
            'qualified' => 'Kvalifikatsiya',
            'proposal' => 'Taklif',
            'negotiation' => 'Muzokaralar',
            'won' => 'Yutildi',
            'lost' => 'Yo\'qotildi',
        ];

        $funnelData = [];
        $totalLeads = $leads->count();
        $previousStageCount = $totalLeads;

        foreach ($stages as $status => $label) {
            $count = $leads->where('status', $status)->count();
            $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;
            $dropoffFromPrevious = $previousStageCount > 0
                ? round((($previousStageCount - $count) / $previousStageCount) * 100, 2)
                : 0;

            $funnelData[] = [
                'stage' => $status,
                'label' => $label,
                'count' => $count,
                'percentage' => $percentage,
                'dropoff_rate' => $status !== 'new' ? $dropoffFromPrevious : 0,
                'conversion_rate' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0,
            ];

            // For active stages, don't drop off to won/lost
            if (!in_array($status, ['won', 'lost'])) {
                $previousStageCount = $count;
            }
        }

        // Overall conversion metrics
        $wonLeads = $leads->where('status', 'won')->count();
        $lostLeads = $leads->where('status', 'lost')->count();
        $activeLeads = $leads->whereNotIn('status', ['won', 'lost'])->count();

        return [
            'funnel_stages' => $funnelData,
            'summary' => [
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'lost_leads' => $lostLeads,
                'active_leads' => $activeLeads,
                'overall_conversion_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2)
                    : 0,
                'win_rate' => ($wonLeads + $lostLeads) > 0
                    ? round(($wonLeads / ($wonLeads + $lostLeads)) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Get Dream Buyer performance analysis
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getDreamBuyerPerformance(int $businessId, array $filters = []): array
    {
        $dreamBuyers = DreamBuyer::where('business_id', $businessId)->get();

        $performance = [];

        foreach ($dreamBuyers as $dreamBuyer) {
            // Get all leads associated with this Dream Buyer through customers
            $leadsQuery = Lead::where('business_id', $businessId)
                ->whereHas('customer', function ($q) use ($dreamBuyer) {
                    $q->where('dream_buyer_id', $dreamBuyer->id);
                });

            // Apply date filters
            if (!empty($filters['date_from'])) {
                $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
            }
            if (!empty($filters['date_to'])) {
                $leadsQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
            }

            $leads = $leadsQuery->get();
            $totalLeads = $leads->count();
            $wonLeads = $leads->where('status', 'won')->count();
            $totalRevenue = $leads->where('status', 'won')->sum('estimated_value');
            $avgDealSize = $wonLeads > 0 ? $totalRevenue / $wonLeads : 0;

            $performance[] = [
                'dream_buyer_id' => $dreamBuyer->id,
                'dream_buyer_name' => $dreamBuyer->name,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'conversion_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2)
                    : 0,
                'total_revenue' => $totalRevenue,
                'avg_deal_size' => round($avgDealSize, 2),
                'lifetime_value' => round($avgDealSize, 2), // Can be enhanced with repeat purchases
            ];
        }

        // Sort by conversion rate
        usort($performance, function ($a, $b) {
            return $b['conversion_rate'] <=> $a['conversion_rate'];
        });

        return $performance;
    }

    /**
     * Get Offer performance metrics
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getOfferPerformance(int $businessId, array $filters = []): array
    {
        $offers = Offer::where('business_id', $businessId)
            ->where('status', 'active')
            ->get();

        $performance = [];

        foreach ($offers as $offer) {
            // Get leads associated with this offer (assuming data->offer_id in leads)
            $leadsQuery = Lead::where('business_id', $businessId)
                ->where(function ($q) use ($offer) {
                    $q->whereJsonContains('data->offer_id', $offer->id)
                      ->orWhereJsonContains('data->selected_offer', $offer->id);
                });

            // Apply date filters
            if (!empty($filters['date_from'])) {
                $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
            }
            if (!empty($filters['date_to'])) {
                $leadsQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
            }

            $leads = $leadsQuery->get();
            $totalLeads = $leads->count();
            $wonLeads = $leads->where('status', 'won')->count();
            $totalRevenue = $leads->where('status', 'won')->sum('estimated_value');

            $performance[] = [
                'offer_id' => $offer->id,
                'offer_name' => $offer->name,
                'value_score' => $offer->value_score,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'conversion_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2)
                    : 0,
                'total_revenue' => $totalRevenue,
                'avg_deal_size' => $wonLeads > 0
                    ? round($totalRevenue / $wonLeads, 2)
                    : 0,
                'roi' => ($offer->pricing > 0 && $wonLeads > 0)
                    ? round((($totalRevenue - ($offer->pricing * $wonLeads)) / ($offer->pricing * $wonLeads)) * 100, 2)
                    : 0,
            ];
        }

        // Sort by conversion rate
        usort($performance, function ($a, $b) {
            return $b['conversion_rate'] <=> $a['conversion_rate'];
        });

        return $performance;
    }

    /**
     * Get lead source performance analysis
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getLeadSourceAnalysis(int $businessId, array $filters = []): array
    {
        $sources = MarketingChannel::where('business_id', $businessId)->get();

        $analysis = [];

        foreach ($sources as $source) {
            $leadsQuery = Lead::where('business_id', $businessId)
                ->where('source_id', $source->id);

            // Apply date filters
            if (!empty($filters['date_from'])) {
                $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
            }
            if (!empty($filters['date_to'])) {
                $leadsQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
            }

            $leads = $leadsQuery->get();
            $totalLeads = $leads->count();
            $wonLeads = $leads->where('status', 'won')->count();
            $totalRevenue = $leads->where('status', 'won')->sum('estimated_value');
            $totalCost = $source->total_spent ?? 0;

            $analysis[] = [
                'source_id' => $source->id,
                'source_name' => $source->name,
                'channel_type' => $source->channel_type,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'conversion_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2)
                    : 0,
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'cost_per_lead' => $totalLeads > 0
                    ? round($totalCost / $totalLeads, 2)
                    : 0,
                'cost_per_acquisition' => $wonLeads > 0
                    ? round($totalCost / $wonLeads, 2)
                    : 0,
                'roi' => $totalCost > 0
                    ? round((($totalRevenue - $totalCost) / $totalCost) * 100, 2)
                    : 0,
                'roas' => $totalCost > 0
                    ? round($totalRevenue / $totalCost, 2)
                    : 0,
            ];
        }

        // Sort by ROI
        usort($analysis, function ($a, $b) {
            return $b['roi'] <=> $a['roi'];
        });

        return $analysis;
    }

    /**
     * Get revenue trends over time
     *
     * @param int $businessId
     * @param string $period 'daily', 'weekly', 'monthly'
     * @param int $points Number of data points to return
     * @return array
     */
    public function getRevenueTrends(int $businessId, string $period = 'daily', int $points = 30): array
    {
        $endDate = Carbon::now();
        $startDate = match($period) {
            'daily' => $endDate->copy()->subDays($points),
            'weekly' => $endDate->copy()->subWeeks($points),
            'monthly' => $endDate->copy()->subMonths($points),
            default => $endDate->copy()->subDays($points),
        };

        $wonLeads = Lead::where('business_id', $businessId)
            ->where('status', 'won')
            ->whereBetween('converted_at', [$startDate, $endDate])
            ->orderBy('converted_at')
            ->get();

        $trends = [];
        $labels = [];
        $revenues = [];
        $dealCounts = [];

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $nextDate = match($period) {
                'daily' => $currentDate->copy()->addDay(),
                'weekly' => $currentDate->copy()->addWeek(),
                'monthly' => $currentDate->copy()->addMonth(),
                default => $currentDate->copy()->addDay(),
            };

            $periodLeads = $wonLeads->filter(function ($lead) use ($currentDate, $nextDate) {
                return $lead->converted_at >= $currentDate && $lead->converted_at < $nextDate;
            });

            $revenue = $periodLeads->sum('estimated_value');
            $dealCount = $periodLeads->count();

            $label = match($period) {
                'daily' => $currentDate->format('M d'),
                'weekly' => $currentDate->format('M d'),
                'monthly' => $currentDate->format('M Y'),
                default => $currentDate->format('M d'),
            };

            $labels[] = $label;
            $revenues[] = $revenue;
            $dealCounts[] = $dealCount;

            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $label,
                'revenue' => $revenue,
                'deal_count' => $dealCount,
                'avg_deal_size' => $dealCount > 0 ? round($revenue / $dealCount, 2) : 0,
            ];

            $currentDate = $nextDate;
        }

        return [
            'trends' => $trends,
            'chart_data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => $revenues,
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'tension' => 0.4,
                    ],
                ],
            ],
            'deal_count_chart' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Deals Won',
                        'data' => $dealCounts,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4,
                    ],
                ],
            ],
        ];
    }

    /**
     * Forecast future revenue based on historical trends
     *
     * @param int $businessId
     * @param int $forecastDays
     * @return array
     */
    public function forecastRevenue(int $businessId, int $forecastDays = 30): array
    {
        // Get last 90 days of data for trend analysis
        $historicalData = $this->getRevenueTrends($businessId, 'daily', 90);
        $trends = $historicalData['trends'];

        if (count($trends) < 7) {
            return [
                'forecast' => [],
                'message' => 'Yetarli ma\'lumot yo\'q',
            ];
        }

        // Simple linear regression
        $revenues = array_column($trends, 'revenue');
        $n = count($revenues);

        // Calculate average daily revenue
        $avgRevenue = array_sum($revenues) / $n;

        // Calculate trend (simple moving average of last 30 days vs previous 30 days)
        $recent30 = array_slice($revenues, -30);
        $previous30 = array_slice($revenues, -60, 30);

        $recentAvg = array_sum($recent30) / count($recent30);
        $previousAvg = count($previous30) > 0 ? array_sum($previous30) / count($previous30) : $recentAvg;

        $growthRate = $previousAvg > 0 ? ($recentAvg - $previousAvg) / $previousAvg : 0;

        // Generate forecast
        $forecast = [];
        $lastDate = Carbon::parse($trends[count($trends) - 1]['date']);

        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecastDate = $lastDate->copy()->addDays($i);
            $forecastRevenue = $recentAvg * (1 + ($growthRate * ($i / 30)));

            // Add some variance based on historical data
            $stdDev = $this->calculateStdDev($revenues);
            $lowerBound = max(0, $forecastRevenue - $stdDev);
            $upperBound = $forecastRevenue + $stdDev;

            $forecast[] = [
                'date' => $forecastDate->format('Y-m-d'),
                'label' => $forecastDate->format('M d'),
                'forecast_revenue' => round($forecastRevenue, 2),
                'lower_bound' => round($lowerBound, 2),
                'upper_bound' => round($upperBound, 2),
                'confidence' => 'medium',
            ];
        }

        return [
            'forecast' => $forecast,
            'summary' => [
                'avg_daily_revenue' => round($avgRevenue, 2),
                'recent_avg' => round($recentAvg, 2),
                'growth_rate' => round($growthRate * 100, 2),
                'forecast_total' => round(array_sum(array_column($forecast, 'forecast_revenue')), 2),
            ],
        ];
    }

    /**
     * Calculate standard deviation
     *
     * @param array $values
     * @return float
     */
    protected function calculateStdDev(array $values): float
    {
        $n = count($values);
        if ($n === 0) return 0;

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $n;

        return sqrt($variance);
    }

    /**
     * Get conversion rates by various dimensions
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getConversionRates(int $businessId, array $filters = []): array
    {
        $query = Lead::where('business_id', $businessId);

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $leads = $query->get();
        $totalLeads = $leads->count();
        $wonLeads = $leads->where('status', 'won')->count();

        return [
            'overall' => [
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'conversion_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2)
                    : 0,
            ],
            'by_stage' => $this->getStageConversionRates($leads),
            'by_source' => $this->getSourceConversionRates($businessId, $filters),
            'avg_time_to_close' => $this->getAvgTimeToClose($leads),
        ];
    }

    /**
     * Get conversion rates by stage
     *
     * @param \Illuminate\Support\Collection $leads
     * @return array
     */
    protected function getStageConversionRates($leads): array
    {
        $stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation'];
        $rates = [];

        foreach ($stages as $index => $stage) {
            $currentStageLeads = $leads->whereIn('status', array_slice($stages, $index));
            $nextStage = $stages[$index + 1] ?? 'won';
            $nextStageLeads = $leads->whereIn('status', array_slice($stages, $index + 1));

            $currentCount = $currentStageLeads->count();
            $nextCount = $nextStageLeads->count() + $leads->where('status', 'won')->count();

            $rates[$stage] = [
                'stage' => $stage,
                'current_count' => $currentCount,
                'next_stage_count' => $nextCount,
                'conversion_rate' => $currentCount > 0
                    ? round(($nextCount / $currentCount) * 100, 2)
                    : 0,
            ];
        }

        return $rates;
    }

    /**
     * Get conversion rates by source
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    protected function getSourceConversionRates(int $businessId, array $filters): array
    {
        $sources = MarketingChannel::where('business_id', $businessId)->get();
        $rates = [];

        foreach ($sources as $source) {
            $leadsQuery = Lead::where('business_id', $businessId)
                ->where('source_id', $source->id);

            if (!empty($filters['date_from'])) {
                $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
            }
            if (!empty($filters['date_to'])) {
                $leadsQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
            }

            $leads = $leadsQuery->get();
            $total = $leads->count();
            $won = $leads->where('status', 'won')->count();

            $rates[] = [
                'source_name' => $source->name,
                'total_leads' => $total,
                'won_leads' => $won,
                'conversion_rate' => $total > 0
                    ? round(($won / $total) * 100, 2)
                    : 0,
            ];
        }

        return $rates;
    }

    /**
     * Get average time to close deals
     *
     * @param \Illuminate\Support\Collection $leads
     * @return array
     */
    protected function getAvgTimeToClose($leads): array
    {
        $wonLeads = $leads->where('status', 'won')->filter(function ($lead) {
            return $lead->converted_at !== null;
        });

        $totalDays = 0;
        $count = 0;

        foreach ($wonLeads as $lead) {
            $days = $lead->created_at->diffInDays($lead->converted_at);
            $totalDays += $days;
            $count++;
        }

        $avgDays = $count > 0 ? round($totalDays / $count, 1) : 0;

        return [
            'avg_days' => $avgDays,
            'total_won_leads' => $count,
            'fastest_close' => $wonLeads->min(fn($lead) => $lead->created_at->diffInDays($lead->converted_at)) ?? 0,
            'slowest_close' => $wonLeads->max(fn($lead) => $lead->created_at->diffInDays($lead->converted_at)) ?? 0,
        ];
    }

    /**
     * Get top performers summary
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getTopPerformers(int $businessId, array $filters = []): array
    {
        $dreamBuyerPerformance = $this->getDreamBuyerPerformance($businessId, $filters);
        $offerPerformance = $this->getOfferPerformance($businessId, $filters);
        $sourceAnalysis = $this->getLeadSourceAnalysis($businessId, $filters);

        return [
            'top_dream_buyer' => $dreamBuyerPerformance[0] ?? null,
            'top_offer' => $offerPerformance[0] ?? null,
            'top_source' => $sourceAnalysis[0] ?? null,
        ];
    }

    /**
     * Get comprehensive dashboard metrics
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getDashboardMetrics(int $businessId, array $filters = []): array
    {
        $leads = Lead::where('business_id', $businessId);

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $leads->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $leads->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $allLeads = $leads->get();
        $wonLeads = $allLeads->where('status', 'won');
        $activeLeads = $allLeads->whereNotIn('status', ['won', 'lost']);

        $totalRevenue = $wonLeads->sum('estimated_value');
        $pipelineValue = $activeLeads->sum('estimated_value');

        // Calculate previous period for comparison
        $dateFrom = !empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])
            : Carbon::now()->subDays(30);
        $dateTo = !empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])
            : Carbon::now();

        $periodDays = $dateFrom->diffInDays($dateTo);
        $previousPeriodFrom = $dateFrom->copy()->subDays($periodDays);

        $previousLeads = Lead::where('business_id', $businessId)
            ->whereBetween('created_at', [$previousPeriodFrom, $dateFrom])
            ->get();

        $previousRevenue = $previousLeads->where('status', 'won')->sum('estimated_value');

        $revenueGrowth = $previousRevenue > 0
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0;

        return [
            'total_leads' => $allLeads->count(),
            'new_leads' => $allLeads->where('status', 'new')->count(),
            'won_deals' => $wonLeads->count(),
            'total_revenue' => $totalRevenue,
            'pipeline_value' => $pipelineValue,
            'conversion_rate' => $allLeads->count() > 0
                ? round(($wonLeads->count() / $allLeads->count()) * 100, 2)
                : 0,
            'avg_deal_size' => $wonLeads->count() > 0
                ? round($totalRevenue / $wonLeads->count(), 2)
                : 0,
            'revenue_growth' => $revenueGrowth,
            'active_pipeline_deals' => $activeLeads->count(),
        ];
    }
}
