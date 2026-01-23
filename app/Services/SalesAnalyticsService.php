<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    /**
     * Get conversion funnel data with stage-by-stage metrics
     *
     * @param  array  $filters  ['date_from', 'date_to', 'dream_buyer_id', 'offer_id', 'source_id']
     */
    public function getFunnelData(string $businessId, array $filters = []): array
    {
        $query = Lead::where('business_id', $businessId);

        // Apply filters
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }
        if (! empty($filters['dream_buyer_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('dream_buyer_id', $filters['dream_buyer_id']);
            });
        }
        if (! empty($filters['source_id'])) {
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
            if (! in_array($status, ['won', 'lost'])) {
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
     * Optimized: Single query with aggregation instead of N+1
     */
    public function getDreamBuyerPerformance(string $businessId, array $filters = []): array
    {
        // Get all dream buyers with pre-calculated lead stats in a single query
        $query = DB::table('dream_buyers')
            ->leftJoin('customers', 'dream_buyers.id', '=', 'customers.dream_buyer_id')
            ->leftJoin('leads', function ($join) use ($businessId) {
                $join->on('customers.id', '=', 'leads.customer_id')
                    ->where('leads.business_id', '=', $businessId);
            })
            ->where('dream_buyers.business_id', $businessId)
            ->select(
                'dream_buyers.id as dream_buyer_id',
                'dream_buyers.name as dream_buyer_name',
                DB::raw('COUNT(DISTINCT leads.id) as total_leads'),
                DB::raw('COUNT(DISTINCT CASE WHEN leads.status = "won" THEN leads.id END) as won_leads'),
                DB::raw('COALESCE(SUM(CASE WHEN leads.status = "won" THEN leads.estimated_value ELSE 0 END), 0) as total_revenue')
            )
            ->groupBy('dream_buyers.id', 'dream_buyers.name');

        // Apply date filters
        if (! empty($filters['date_from'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('leads.created_at')
                    ->orWhere('leads.created_at', '>=', Carbon::parse($filters['date_from']));
            });
        }
        if (! empty($filters['date_to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('leads.created_at')
                    ->orWhere('leads.created_at', '<=', Carbon::parse($filters['date_to']));
            });
        }

        $results = $query->get();

        $performance = $results->map(function ($row) {
            $totalLeads = (int) $row->total_leads;
            $wonLeads = (int) $row->won_leads;
            $totalRevenue = (float) $row->total_revenue;
            $avgDealSize = $wonLeads > 0 ? $totalRevenue / $wonLeads : 0;

            return [
                'dream_buyer_id' => $row->dream_buyer_id,
                'dream_buyer_name' => $row->dream_buyer_name,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads,
                'conversion_rate' => $totalLeads > 0
                    ? round(($wonLeads / $totalLeads) * 100, 2)
                    : 0,
                'total_revenue' => $totalRevenue,
                'avg_deal_size' => round($avgDealSize, 2),
                'lifetime_value' => round($avgDealSize, 2),
            ];
        })->toArray();

        // Sort by conversion rate
        usort($performance, function ($a, $b) {
            return $b['conversion_rate'] <=> $a['conversion_rate'];
        });

        return $performance;
    }

    /**
     * Get Offer performance metrics
     * Optimized: Reduced queries using eager loading and collection processing
     */
    public function getOfferPerformance(string $businessId, array $filters = []): array
    {
        // Get all offers in one query
        $offers = Offer::where('business_id', $businessId)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        if ($offers->isEmpty()) {
            return [];
        }

        // Build leads query with date filters
        $leadsQuery = Lead::where('business_id', $businessId)
            ->whereNotNull('data');

        if (! empty($filters['date_from'])) {
            $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $leadsQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        // Get all leads in one query
        $allLeads = $leadsQuery->get();

        // Pre-calculate performance for each offer from loaded data
        $performance = [];

        foreach ($offers as $offer) {
            // Filter leads that belong to this offer using collection methods
            $offerLeads = $allLeads->filter(function ($lead) use ($offer) {
                $data = is_array($lead->data) ? $lead->data : json_decode($lead->data, true);
                if (! $data) {
                    return false;
                }

                $offerId = $data['offer_id'] ?? null;
                $selectedOffer = $data['selected_offer'] ?? null;

                return $offerId == $offer->id || $selectedOffer == $offer->id;
            });

            $totalLeads = $offerLeads->count();
            $wonLeads = $offerLeads->where('status', 'won')->count();
            $totalRevenue = $offerLeads->where('status', 'won')->sum('estimated_value');

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
     * Optimized: Single query with aggregation instead of N+1
     */
    public function getLeadSourceAnalysis(string $businessId, array $filters = []): array
    {
        // Get all sources with pre-calculated lead stats in a single query
        $query = DB::table('marketing_channels')
            ->leftJoin('leads', function ($join) use ($businessId) {
                $join->on('marketing_channels.id', '=', 'leads.source_id')
                    ->where('leads.business_id', '=', $businessId);
            })
            ->where('marketing_channels.business_id', $businessId)
            ->select(
                'marketing_channels.id as source_id',
                'marketing_channels.name as source_name',
                'marketing_channels.channel_type',
                'marketing_channels.total_spent',
                DB::raw('COUNT(DISTINCT leads.id) as total_leads'),
                DB::raw('COUNT(DISTINCT CASE WHEN leads.status = "won" THEN leads.id END) as won_leads'),
                DB::raw('COALESCE(SUM(CASE WHEN leads.status = "won" THEN leads.estimated_value ELSE 0 END), 0) as total_revenue')
            )
            ->groupBy(
                'marketing_channels.id',
                'marketing_channels.name',
                'marketing_channels.channel_type',
                'marketing_channels.total_spent'
            );

        // Apply date filters
        if (! empty($filters['date_from'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('leads.created_at')
                    ->orWhere('leads.created_at', '>=', Carbon::parse($filters['date_from']));
            });
        }
        if (! empty($filters['date_to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('leads.created_at')
                    ->orWhere('leads.created_at', '<=', Carbon::parse($filters['date_to']));
            });
        }

        $results = $query->get();

        $analysis = $results->map(function ($row) {
            $totalLeads = (int) $row->total_leads;
            $wonLeads = (int) $row->won_leads;
            $totalRevenue = (float) $row->total_revenue;
            $totalCost = (float) ($row->total_spent ?? 0);

            return [
                'source_id' => $row->source_id,
                'source_name' => $row->source_name,
                'channel_type' => $row->channel_type,
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
        })->toArray();

        // Sort by ROI
        usort($analysis, function ($a, $b) {
            return $b['roi'] <=> $a['roi'];
        });

        return $analysis;
    }

    /**
     * Get revenue trends over time
     *
     * @param  string  $period  'daily', 'weekly', 'monthly'
     * @param  int  $points  Number of data points to return
     */
    public function getRevenueTrends(string $businessId, string $period = 'daily', int $points = 30): array
    {
        $endDate = Carbon::now();
        $startDate = match ($period) {
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
            $nextDate = match ($period) {
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

            $label = match ($period) {
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
     */
    public function forecastRevenue(string $businessId, int $forecastDays = 30): array
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
     */
    protected function calculateStdDev(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0;
        }

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $n;

        return sqrt($variance);
    }

    /**
     * Get conversion rates by various dimensions
     */
    public function getConversionRates(string $businessId, array $filters = []): array
    {
        $query = Lead::where('business_id', $businessId);

        // Apply date filters
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
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
     * @param  \Illuminate\Support\Collection  $leads
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
     */
    protected function getSourceConversionRates(string $businessId, array $filters): array
    {
        $sources = MarketingChannel::where('business_id', $businessId)->get();
        $rates = [];

        foreach ($sources as $source) {
            $leadsQuery = Lead::where('business_id', $businessId)
                ->where('source_id', $source->id);

            if (! empty($filters['date_from'])) {
                $leadsQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
            }
            if (! empty($filters['date_to'])) {
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
     * @param  \Illuminate\Support\Collection  $leads
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
            'fastest_close' => $wonLeads->min(fn ($lead) => $lead->created_at->diffInDays($lead->converted_at)) ?? 0,
            'slowest_close' => $wonLeads->max(fn ($lead) => $lead->created_at->diffInDays($lead->converted_at)) ?? 0,
        ];
    }

    /**
     * Get top performers summary
     */
    public function getTopPerformers(string $businessId, array $filters = []): array
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
     */
    public function getDashboardMetrics(string $businessId, array $filters = []): array
    {
        $leads = Lead::where('business_id', $businessId);

        // Apply date filters
        if (! empty($filters['date_from'])) {
            $leads->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $leads->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $allLeads = $leads->get();
        $wonLeads = $allLeads->where('status', 'won');
        $activeLeads = $allLeads->whereNotIn('status', ['won', 'lost']);

        $totalRevenue = $wonLeads->sum('estimated_value');
        $pipelineValue = $activeLeads->sum('estimated_value');

        // Calculate previous period for comparison
        $dateFrom = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])
            : Carbon::now()->subDays(30);
        $dateTo = ! empty($filters['date_to'])
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

    /**
     * Lost Deal Analysis - Nega sotuvlar yo'qoldi
     */
    public function getLostDealAnalysis(string $businessId, array $filters = []): array
    {
        $query = Lead::where('business_id', $businessId)
            ->where('status', 'lost');

        // Apply date filters
        if (! empty($filters['date_from'])) {
            $query->where('updated_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->where('updated_at', '<=', Carbon::parse($filters['date_to']));
        }

        $lostLeads = $query->get();

        // Group by lost reason
        $byReason = $lostLeads->groupBy('lost_reason')->map(function ($group, $reason) use ($lostLeads) {
            $totalLost = $lostLeads->count();
            return [
                'reason' => $reason ?: 'unknown',
                'reason_label' => Lead::LOST_REASONS[$reason] ?? 'Noma\'lum',
                'count' => $group->count(),
                'percentage' => $totalLost > 0 ? round(($group->count() / $totalLost) * 100, 1) : 0,
                'total_value' => $group->sum('estimated_value'),
                'avg_value' => $group->count() > 0 ? round($group->sum('estimated_value') / $group->count(), 0) : 0,
            ];
        })->sortByDesc('count')->values();

        // Group by stage where lost
        $byStage = $lostLeads->groupBy('sales_funnel_stage_id')->map(function ($group, $stageId) use ($lostLeads) {
            $stage = $stageId ? \App\Models\SalesFunnelStage::find($stageId) : null;
            $totalLost = $lostLeads->count();
            return [
                'stage_id' => $stageId,
                'stage_name' => $stage?->name ?? 'Bosqich belgilanmagan',
                'count' => $group->count(),
                'percentage' => $totalLost > 0 ? round(($group->count() / $totalLost) * 100, 1) : 0,
                'total_value' => $group->sum('estimated_value'),
            ];
        })->sortByDesc('count')->values();

        // Group by operator (assigned_to)
        $byOperator = $lostLeads->groupBy('assigned_to')->map(function ($group, $userId) use ($lostLeads) {
            $user = $userId ? \App\Models\User::find($userId) : null;
            $totalLost = $lostLeads->count();
            return [
                'user_id' => $userId,
                'user_name' => $user?->name ?? 'Tayinlanmagan',
                'count' => $group->count(),
                'percentage' => $totalLost > 0 ? round(($group->count() / $totalLost) * 100, 1) : 0,
                'total_value' => $group->sum('estimated_value'),
            ];
        })->sortByDesc('count')->values();

        // Group by source
        $bySource = $lostLeads->groupBy('marketing_channel_id')->map(function ($group, $channelId) use ($lostLeads) {
            $channel = $channelId ? \App\Models\MarketingChannel::find($channelId) : null;
            $totalLost = $lostLeads->count();
            return [
                'channel_id' => $channelId,
                'channel_name' => $channel?->name ?? 'Manba noma\'lum',
                'count' => $group->count(),
                'percentage' => $totalLost > 0 ? round(($group->count() / $totalLost) * 100, 1) : 0,
                'total_value' => $group->sum('estimated_value'),
            ];
        })->sortByDesc('count')->values();

        // Monthly trend
        $monthlyTrend = $lostLeads->groupBy(function ($lead) {
            return $lead->updated_at->format('Y-m');
        })->map(function ($group, $month) {
            return [
                'month' => $month,
                'count' => $group->count(),
                'total_value' => $group->sum('estimated_value'),
            ];
        })->sortKeys()->values();

        return [
            'summary' => [
                'total_lost' => $lostLeads->count(),
                'total_value_lost' => $lostLeads->sum('estimated_value'),
                'avg_value_lost' => $lostLeads->count() > 0 ? round($lostLeads->sum('estimated_value') / $lostLeads->count(), 0) : 0,
            ],
            'by_reason' => $byReason,
            'by_stage' => $byStage,
            'by_operator' => $byOperator,
            'by_source' => $bySource,
            'monthly_trend' => $monthlyTrend,
        ];
    }

    /**
     * Operator Performance Analysis
     */
    public function getOperatorPerformance(string $businessId, array $filters = []): array
    {
        $query = Lead::where('business_id', $businessId)
            ->whereNotNull('assigned_to');

        // Apply date filters
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $leads = $query->get();

        // Group by operator
        $operators = $leads->groupBy('assigned_to')->map(function ($group, $userId) {
            $user = \App\Models\User::find($userId);

            $totalLeads = $group->count();
            $wonLeads = $group->where('status', 'won');
            $lostLeads = $group->where('status', 'lost');
            $activeLeads = $group->whereNotIn('status', ['won', 'lost']);

            $totalRevenue = $wonLeads->sum('estimated_value');
            $conversionRate = $totalLeads > 0 ? round(($wonLeads->count() / $totalLeads) * 100, 1) : 0;
            $avgDealSize = $wonLeads->count() > 0 ? round($totalRevenue / $wonLeads->count(), 0) : 0;

            // Calculate avg response time (if first_contact_at exists)
            $avgResponseTime = null;
            $leadsWithResponse = $group->filter(fn ($lead) => $lead->first_contact_at !== null);
            if ($leadsWithResponse->count() > 0) {
                $totalMinutes = $leadsWithResponse->sum(function ($lead) {
                    return $lead->created_at->diffInMinutes($lead->first_contact_at);
                });
                $avgResponseTime = round($totalMinutes / $leadsWithResponse->count(), 0);
            }

            return [
                'user_id' => $userId,
                'user_name' => $user?->name ?? 'Noma\'lum',
                'user_avatar' => $user?->avatar_url ?? null,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads->count(),
                'lost_leads' => $lostLeads->count(),
                'active_leads' => $activeLeads->count(),
                'total_revenue' => $totalRevenue,
                'conversion_rate' => $conversionRate,
                'avg_deal_size' => $avgDealSize,
                'avg_response_time_minutes' => $avgResponseTime,
                'pipeline_value' => $activeLeads->sum('estimated_value'),
            ];
        })->sortByDesc('total_revenue')->values();

        // Calculate team averages
        $teamStats = [
            'total_operators' => $operators->count(),
            'avg_conversion_rate' => $operators->count() > 0 ? round($operators->avg('conversion_rate'), 1) : 0,
            'avg_deal_size' => $operators->count() > 0 ? round($operators->avg('avg_deal_size'), 0) : 0,
            'total_revenue' => $operators->sum('total_revenue'),
            'total_leads' => $operators->sum('total_leads'),
        ];

        // Top and bottom performers
        $topPerformers = $operators->take(3);
        $bottomPerformers = $operators->sortBy('conversion_rate')->take(3)->values();

        return [
            'operators' => $operators,
            'team_stats' => $teamStats,
            'top_performers' => $topPerformers,
            'bottom_performers' => $bottomPerformers,
        ];
    }

    /**
     * Marketing Channel ROI Analysis
     */
    public function getChannelROI(string $businessId, array $filters = []): array
    {
        $channels = \App\Models\MarketingChannel::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        $result = [];

        foreach ($channels as $channel) {
            $query = Lead::where('business_id', $businessId)
                ->where('marketing_channel_id', $channel->id);

            // Apply date filters
            if (! empty($filters['date_from'])) {
                $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
            }
            if (! empty($filters['date_to'])) {
                $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
            }

            $leads = $query->get();
            $wonLeads = $leads->where('status', 'won');

            $totalLeads = $leads->count();
            $revenue = $wonLeads->sum('estimated_value');
            $conversionRate = $totalLeads > 0 ? round(($wonLeads->count() / $totalLeads) * 100, 1) : 0;

            // Get spend from MarketingSpend records or campaigns
            $spendQuery = \App\Models\MarketingSpend::where('channel_id', $channel->id);
            if (! empty($filters['date_from'])) {
                $spendQuery->where('date', '>=', Carbon::parse($filters['date_from']));
            }
            if (! empty($filters['date_to'])) {
                $spendQuery->where('date', '<=', Carbon::parse($filters['date_to']));
            }
            $spend = $spendQuery->sum('amount') ?: $channel->campaigns()->sum('budget_spent') ?: 0;

            // Calculate metrics
            $costPerLead = $totalLeads > 0 && $spend > 0 ? round($spend / $totalLeads, 0) : 0;
            $costPerAcquisition = $wonLeads->count() > 0 && $spend > 0 ? round($spend / $wonLeads->count(), 0) : 0;
            $roi = $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 1) : 0;
            $roas = $spend > 0 ? round($revenue / $spend, 2) : 0;

            $result[] = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'total_leads' => $totalLeads,
                'won_leads' => $wonLeads->count(),
                'revenue' => $revenue,
                'spend' => $spend,
                'conversion_rate' => $conversionRate,
                'cost_per_lead' => $costPerLead,
                'cost_per_acquisition' => $costPerAcquisition,
                'roi' => $roi,
                'roas' => $roas,
                'recommendation' => $this->getChannelRecommendation($roi, $conversionRate, $totalLeads),
            ];
        }

        // Sort by ROI
        usort($result, fn ($a, $b) => $b['roi'] <=> $a['roi']);

        return [
            'channels' => $result,
            'summary' => [
                'total_spend' => collect($result)->sum('spend'),
                'total_revenue' => collect($result)->sum('revenue'),
                'overall_roi' => collect($result)->sum('spend') > 0
                    ? round(((collect($result)->sum('revenue') - collect($result)->sum('spend')) / collect($result)->sum('spend')) * 100, 1)
                    : 0,
                'best_channel' => $result[0] ?? null,
            ],
        ];
    }

    /**
     * Get channel recommendation based on metrics
     */
    private function getChannelRecommendation(float $roi, float $conversionRate, int $totalLeads): string
    {
        if ($roi > 200 && $conversionRate > 10) {
            return 'scale_up'; // Byudjetni oshiring
        } elseif ($roi > 100 && $conversionRate > 5) {
            return 'maintain'; // Davom eting
        } elseif ($roi > 0 && $totalLeads > 10) {
            return 'optimize'; // Optimizatsiya qiling
        } elseif ($totalLeads < 5) {
            return 'test_more'; // Ko'proq test qiling
        } else {
            return 'reduce'; // Byudjetni kamaytiring
        }
    }

    /**
     * Business Owner Insights - Barcha ma'lumotlar bir joyda
     */
    public function getBusinessInsights(string $businessId, array $filters = []): array
    {
        $lostDealAnalysis = $this->getLostDealAnalysis($businessId, $filters);
        $operatorPerformance = $this->getOperatorPerformance($businessId, $filters);
        $channelROI = $this->getChannelROI($businessId, $filters);
        $dashboardMetrics = $this->getDashboardMetrics($businessId, $filters);

        // Key insights for business owner
        $insights = [];

        // 1. Top losing reason
        if ($lostDealAnalysis['by_reason']->isNotEmpty()) {
            $topReason = $lostDealAnalysis['by_reason']->first();
            $insights[] = [
                'type' => 'warning',
                'icon' => 'alert',
                'title' => "Eng ko'p yo'qotish sababi: {$topReason['reason_label']}",
                'description' => "{$topReason['count']} ta lid ({$topReason['percentage']}%) shu sababdan yo'qoldi. Jami " . number_format($topReason['total_value'], 0, '', ' ') . " so'm.",
                'action' => 'Sotuv jarayonini tahlil qiling',
            ];
        }

        // 2. Best performing operator
        if ($operatorPerformance['top_performers']->isNotEmpty()) {
            $topOp = $operatorPerformance['top_performers']->first();
            $insights[] = [
                'type' => 'success',
                'icon' => 'star',
                'title' => "Eng yaxshi operator: {$topOp['user_name']}",
                'description' => "{$topOp['conversion_rate']}% konversiya, " . number_format($topOp['total_revenue'], 0, '', ' ') . " so'm daromad.",
                'action' => 'Uning usullarini jamoaga o\'rgating',
            ];
        }

        // 3. Worst performing operator
        if ($operatorPerformance['bottom_performers']->isNotEmpty()) {
            $bottomOp = $operatorPerformance['bottom_performers']->first();
            if ($bottomOp['conversion_rate'] < $operatorPerformance['team_stats']['avg_conversion_rate']) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'user',
                    'title' => "{$bottomOp['user_name']} ga yordam kerak",
                    'description' => "Konversiya {$bottomOp['conversion_rate']}% (jamoa o'rtachasi: {$operatorPerformance['team_stats']['avg_conversion_rate']}%)",
                    'action' => 'Coaching o\'tkazing',
                ];
            }
        }

        // 4. Best ROI channel
        if (! empty($channelROI['channels'])) {
            $bestChannel = $channelROI['channels'][0];
            if ($bestChannel['roi'] > 0) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'trending-up',
                    'title' => "Eng samarali kanal: {$bestChannel['channel_name']}",
                    'description' => "ROI: {$bestChannel['roi']}%, ROAS: {$bestChannel['roas']}x",
                    'action' => $bestChannel['recommendation'] === 'scale_up' ? 'Byudjetni oshiring' : 'Davom eting',
                ];
            }
        }

        // 5. Channel to reduce
        if (! empty($channelROI['channels'])) {
            $worstChannel = end($channelROI['channels']);
            if ($worstChannel && $worstChannel['roi'] < 0 && $worstChannel['spend'] > 0) {
                $insights[] = [
                    'type' => 'danger',
                    'icon' => 'trending-down',
                    'title' => "{$worstChannel['channel_name']} zarar keltirmoqda",
                    'description' => "ROI: {$worstChannel['roi']}%, " . number_format($worstChannel['spend'], 0, '', ' ') . " so'm sarflandi.",
                    'action' => 'Byudjetni kamaytiring yoki to\'xtating',
                ];
            }
        }

        // 6. Money lost this period
        if ($lostDealAnalysis['summary']['total_value_lost'] > 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'dollar',
                'title' => 'Yo\'qotilgan potensial daromad',
                'description' => number_format($lostDealAnalysis['summary']['total_value_lost'], 0, '', ' ') . " so'm ({$lostDealAnalysis['summary']['total_lost']} ta lid)",
                'action' => 'Lost Deal tahlilini ko\'ring',
            ];
        }

        return [
            'insights' => $insights,
            'metrics' => $dashboardMetrics,
            'lost_deals' => $lostDealAnalysis,
            'operators' => $operatorPerformance,
            'channels' => $channelROI,
        ];
    }
}
