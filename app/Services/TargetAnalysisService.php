<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\DreamBuyer;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TargetAnalysisService
{
    protected ClaudeAIService $claudeService;

    /**
     * Cache TTL in seconds (15 minutes)
     */
    protected int $cacheTTL = 900;

    public function __construct(ClaudeAIService $claudeService)
    {
        $this->claudeService = $claudeService;
    }

    /**
     * Get comprehensive target analysis for a business
     * NOTE: AI insights are NOT loaded here - they should be loaded lazily via API
     */
    public function getTargetAnalysis(Business $business): array
    {
        $cacheKey = "target_analysis_{$business->id}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($business) {
            return [
                'overview' => $this->getOverview($business),
                'dream_buyer_match' => $this->analyzeDreamBuyerMatch($business),
                'customer_segments' => $this->getCustomerSegments($business),
                'conversion_funnel' => $this->getConversionFunnel($business),
                'demographic_insights' => $this->getDemographicInsights($business),
                // AI insights NOT loaded on page load - loaded lazily via separate API call
                'ai_insights' => ['success' => false, 'lazy_load' => true, 'message' => 'AI tahlil tugmani bosing'],
                'top_performers' => $this->getTopPerformingCustomers($business),
                'churn_risk' => $this->getChurnRiskAnalysis($business),
            ];
        });
    }

    /**
     * Invalidate cache for a business
     */
    public function invalidateCache(Business $business): void
    {
        Cache::forget("target_analysis_{$business->id}");
    }

    /**
     * Get overview statistics
     * OPTIMIZED: Single query for customer stats, single query for lead stats
     */
    protected function getOverview(Business $business): array
    {
        // Single query for all customer stats
        $customerStats = Customer::where('business_id', $business->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                AVG(total_spent) as avg_ltv,
                SUM(total_spent) as total_revenue,
                SUM(orders_count) as total_orders
            ')
            ->first();

        // Single query for all lead stats
        $leadStats = Lead::where('business_id', $business->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "qualified" THEN 1 ELSE 0 END) as qualified,
                SUM(CASE WHEN status = "converted" THEN 1 ELSE 0 END) as converted
            ')
            ->first();

        $totalLeads = $leadStats->total ?? 0;
        $convertedLeads = $leadStats->converted ?? 0;
        $totalOrders = $customerStats->total_orders ?? 0;
        $totalRevenue = $customerStats->total_revenue ?? 0;

        return [
            'total_customers' => $customerStats->total ?? 0,
            'active_customers' => $customerStats->active ?? 0,
            'total_leads' => $totalLeads,
            'qualified_leads' => $leadStats->qualified ?? 0,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
            'avg_ltv' => round($customerStats->avg_ltv ?? 0, 2),
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
        ];
    }

    /**
     * Analyze how well customers match Dream Buyer profiles
     */
    public function analyzeDreamBuyerMatch(Business $business): array
    {
        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        if ($dreamBuyers->isEmpty()) {
            return [
                'has_dream_buyers' => false,
                'message' => 'Dream Buyer profillari yaratilmagan',
                'matches' => [],
            ];
        }

        $customers = Customer::where('business_id', $business->id)
            ->where('status', 'active')
            ->get();

        $matches = [];
        foreach ($dreamBuyers as $dreamBuyer) {
            $matchingCustomers = $this->findMatchingCustomers($dreamBuyer, $customers);

            $matches[] = [
                'dream_buyer_id' => $dreamBuyer->id,
                'dream_buyer_name' => $dreamBuyer->persona_name,
                'total_matches' => count($matchingCustomers),
                'match_percentage' => $customers->count() > 0
                    ? round((count($matchingCustomers) / $customers->count()) * 100, 2)
                    : 0,
                'avg_ltv_of_matches' => collect($matchingCustomers)->avg('total_spent') ?? 0,
                'total_revenue_from_matches' => collect($matchingCustomers)->sum('total_spent') ?? 0,
                'top_matching_customers' => collect($matchingCustomers)
                    ->sortByDesc('total_spent')
                    ->take(5)
                    ->values()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'total_spent' => $c->total_spent ?? 0,
                        'orders_count' => $c->orders_count ?? 0,
                    ])
                    ->toArray(),
            ];
        }

        return [
            'has_dream_buyers' => true,
            'total_dream_buyers' => $dreamBuyers->count(),
            'matches' => $matches,
            'best_match' => collect($matches)->sortByDesc('total_matches')->first(),
        ];
    }

    /**
     * Find customers matching a Dream Buyer profile
     */
    protected function findMatchingCustomers(DreamBuyer $dreamBuyer, $customers): array
    {
        $matching = [];
        $demographics = $dreamBuyer->demographics ?? [];

        foreach ($customers as $customer) {
            $score = 0;
            $maxScore = 0;

            // Match by location/city
            if (isset($demographics['location']) && $customer->city) {
                $maxScore++;
                if (stripos($customer->city, $demographics['location']) !== false) {
                    $score++;
                }
            }

            // Match by acquisition source
            if (isset($demographics['preferred_channels']) && $customer->acquisition_source) {
                $maxScore++;
                $channels = is_array($demographics['preferred_channels'])
                    ? $demographics['preferred_channels']
                    : [$demographics['preferred_channels']];

                foreach ($channels as $channel) {
                    if (stripos($customer->acquisition_source, $channel) !== false) {
                        $score++;
                        break;
                    }
                }
            }

            // Match by spending behavior
            if (isset($demographics['income_level']) && $customer->total_spent > 0) {
                $maxScore++;
                $incomeLevel = $demographics['income_level'];

                if ($incomeLevel === 'high' && $customer->total_spent > 5000000) {
                    $score++;
                } elseif ($incomeLevel === 'medium' && $customer->total_spent > 1000000 && $customer->total_spent <= 5000000) {
                    $score++;
                } elseif ($incomeLevel === 'low' && $customer->total_spent <= 1000000) {
                    $score++;
                }
            }

            // If customer matches at least 50% of criteria, consider it a match
            if ($maxScore > 0 && ($score / $maxScore) >= 0.5) {
                $matching[] = $customer;
            }
        }

        return $matching;
    }

    /**
     * Get customer segmentation analysis
     */
    public function getCustomerSegments(Business $business): array
    {
        $customers = Customer::where('business_id', $business->id)->get();

        // Segment by LTV (Low, Medium, High value) - using total_spent
        $ltvSegments = [
            'high_value' => $customers->filter(fn ($c) => ($c->total_spent ?? 0) >= 5000000)->count(),
            'medium_value' => $customers->filter(fn ($c) => ($c->total_spent ?? 0) >= 1000000 && ($c->total_spent ?? 0) < 5000000)->count(),
            'low_value' => $customers->filter(fn ($c) => ($c->total_spent ?? 0) < 1000000)->count(),
        ];

        // Segment by status
        $statusSegments = [
            'active' => $customers->where('status', 'active')->count(),
            'inactive' => $customers->where('status', 'inactive')->count(),
            'churned' => $customers->where('status', 'churned')->count(),
        ];

        // Segment by acquisition source
        $sourceSegments = $customers
            ->groupBy('acquisition_source')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'avg_ltv' => $group->avg('total_spent'),
                'total_revenue' => $group->sum('total_spent'),
            ])
            ->toArray();

        // Segment by location
        $locationSegments = $customers
            ->filter(fn ($c) => $c->city)
            ->groupBy('city')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'avg_ltv' => $group->avg('total_spent'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->toArray();

        // RFM Segmentation (Recency, Frequency, Monetary)
        $rfmSegments = $this->getRFMSegmentation($customers);

        return [
            'by_ltv' => $ltvSegments,
            'by_status' => $statusSegments,
            'by_source' => $sourceSegments,
            'by_location' => $locationSegments,
            'rfm_segments' => $rfmSegments,
        ];
    }

    /**
     * RFM (Recency, Frequency, Monetary) Segmentation
     */
    protected function getRFMSegmentation($customers): array
    {
        $segments = [
            'champions' => 0,        // High F, High M, Recent
            'loyal' => 0,            // High F, High M
            'potential' => 0,        // Recent, Low F
            'at_risk' => 0,          // High F, High M, Old
            'need_attention' => 0,   // Medium F, Medium M, Old
        ];

        foreach ($customers as $customer) {
            $recency = $customer->last_purchase_at
                ? now()->diffInDays($customer->last_purchase_at)
                : 999;
            $frequency = $customer->orders_count ?? 0;
            $monetary = $customer->total_spent ?? 0;

            if ($recency <= 30 && $frequency >= 5 && $monetary >= 1000000) {
                $segments['champions']++;
            } elseif ($frequency >= 5 && $monetary >= 1000000) {
                $segments['loyal']++;
            } elseif ($recency <= 30 && $frequency < 3) {
                $segments['potential']++;
            } elseif ($recency > 90 && $frequency >= 5 && $monetary >= 1000000) {
                $segments['at_risk']++;
            } elseif ($recency > 60) {
                $segments['need_attention']++;
            }
        }

        return $segments;
    }

    /**
     * Get conversion funnel analysis
     */
    protected function getConversionFunnel(Business $business): array
    {
        $totalLeads = Lead::where('business_id', $business->id)->count();
        $qualifiedLeads = Lead::where('business_id', $business->id)
            ->where('status', 'qualified')
            ->count();
        $convertedLeads = Lead::where('business_id', $business->id)
            ->where('status', 'converted')
            ->count();
        $totalCustomers = Customer::where('business_id', $business->id)->count();
        $activeCustomers = Customer::where('business_id', $business->id)
            ->where('status', 'active')
            ->count();

        return [
            'stages' => [
                [
                    'name' => 'Leads',
                    'count' => $totalLeads,
                    'percentage' => 100,
                ],
                [
                    'name' => 'Qualified',
                    'count' => $qualifiedLeads,
                    'percentage' => $totalLeads > 0 ? round(($qualifiedLeads / $totalLeads) * 100, 2) : 0,
                ],
                [
                    'name' => 'Converted',
                    'count' => $convertedLeads,
                    'percentage' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
                ],
                [
                    'name' => 'Customers',
                    'count' => $totalCustomers,
                    'percentage' => $totalLeads > 0 ? round(($totalCustomers / $totalLeads) * 100, 2) : 0,
                ],
                [
                    'name' => 'Active Customers',
                    'count' => $activeCustomers,
                    'percentage' => $totalLeads > 0 ? round(($activeCustomers / $totalLeads) * 100, 2) : 0,
                ],
            ],
            'conversion_rates' => [
                'lead_to_qualified' => $totalLeads > 0 ? round(($qualifiedLeads / $totalLeads) * 100, 2) : 0,
                'qualified_to_converted' => $qualifiedLeads > 0 ? round(($convertedLeads / $qualifiedLeads) * 100, 2) : 0,
                'lead_to_customer' => $totalLeads > 0 ? round(($totalCustomers / $totalLeads) * 100, 2) : 0,
                'customer_retention' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Get demographic insights
     */
    protected function getDemographicInsights(Business $business): array
    {
        $customers = Customer::where('business_id', $business->id)->get();

        // City distribution
        $cityDistribution = $customers
            ->filter(fn ($c) => $c->city)
            ->groupBy('city')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'percentage' => round(($group->count() / $customers->count()) * 100, 2),
                'avg_ltv' => round($group->avg('total_spent') ?? 0, 2),
            ])
            ->sortByDesc('count')
            ->toArray();

        // Country distribution
        $countryDistribution = $customers
            ->filter(fn ($c) => $c->region)
            ->groupBy('region')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'percentage' => round(($group->count() / $customers->count()) * 100, 2),
            ])
            ->sortByDesc('count')
            ->toArray();

        // Acquisition source performance
        $sourcePerformance = $customers
            ->filter(fn ($c) => $c->type)
            ->groupBy('type')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'avg_ltv' => round($group->avg('total_spent') ?? 0, 2),
                'total_revenue' => round($group->sum('total_spent') ?? 0, 2),
                'avg_orders' => round($group->avg('orders_count') ?? 0, 2),
            ])
            ->sortByDesc('total_revenue')
            ->toArray();

        return [
            'city_distribution' => $cityDistribution,
            'country_distribution' => $countryDistribution,
            'source_performance' => $sourcePerformance,
        ];
    }

    /**
     * Generate AI-powered insights using Claude
     */
    protected function generateAIInsights(Business $business): array
    {
        try {
            $overview = $this->getOverview($business);
            $segments = $this->getCustomerSegments($business);
            $dreamBuyerMatch = $this->analyzeDreamBuyerMatch($business);
            $funnel = $this->getConversionFunnel($business);

            $prompt = $this->buildInsightPrompt($business, $overview, $segments, $dreamBuyerMatch, $funnel);

            $response = $this->claudeService->chat([
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], 'claude-sonnet-4-20250514', 2000);

            return [
                'success' => true,
                'insights' => $response,
                'generated_at' => now()->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('AI Insights Generation Error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'AI tahlil yaratishda xatolik yuz berdi',
                'insights' => 'AI tahlil hozircha mavjud emas',
            ];
        }
    }

    /**
     * Build prompt for AI insights
     */
    protected function buildInsightPrompt(Business $business, array $overview, array $segments, array $dreamBuyerMatch, array $funnel): string
    {
        $prompt = "Siz professional biznes tahlilchisisiz. Quyidagi biznes ma'lumotlarini tahlil qiling va muhim xulosalar chiqaring.\n\n";
        $prompt .= "BIZNES: {$business->name}\n";
        $prompt .= "SOHA: {$business->industry}\n\n";

        $prompt .= "ASOSIY KO'RSATKICHLAR:\n";
        $prompt .= '- Jami mijozlar: '.number_format($overview['total_customers'])."\n";
        $prompt .= '- Faol mijozlar: '.number_format($overview['active_customers'])."\n";
        $prompt .= "- Konversiya darajasi: {$overview['conversion_rate']}%\n";
        $prompt .= "- O'rtacha LTV: ".number_format($overview['avg_ltv'])." so'm\n";
        $prompt .= '- Umumiy daromad: '.number_format($overview['total_revenue'])." so'm\n\n";

        $prompt .= "MIJOZLAR SEGMENTATSIYASI:\n";
        $prompt .= '- Yuqori qiymatli: '.$segments['by_ltv']['high_value']."\n";
        $prompt .= "- O'rta qiymatli: ".$segments['by_ltv']['medium_value']."\n";
        $prompt .= '- Past qiymatli: '.$segments['by_ltv']['low_value']."\n\n";

        if ($dreamBuyerMatch['has_dream_buyers']) {
            $prompt .= "DREAM BUYER MOSLIK:\n";
            foreach ($dreamBuyerMatch['matches'] as $match) {
                $prompt .= "- {$match['dream_buyer_name']}: {$match['total_matches']} mijoz ({$match['match_percentage']}%)\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "KONVERSIYA VORONKASI:\n";
        $prompt .= "- Lead → Qualified: {$funnel['conversion_rates']['lead_to_qualified']}%\n";
        $prompt .= "- Qualified → Converted: {$funnel['conversion_rates']['qualified_to_converted']}%\n";
        $prompt .= "- Lead → Customer: {$funnel['conversion_rates']['lead_to_customer']}%\n\n";

        $prompt .= "TOPSHIRIQ:\n";
        $prompt .= "1. Ushbu ma'lumotlarga asoslanib 5-7 ta asosiy xulosalar chiqaring\n";
        $prompt .= "2. Kuchli tomonlarni aniqlang\n";
        $prompt .= "3. Yaxshilash kerak bo'lgan sohalarni ko'rsating\n";
        $prompt .= "4. Konkret 3-5 ta amaliy tavsiyalar bering\n\n";
        $prompt .= "Javobni o'zbek tilida, qisqa va aniq formatda bering. Har bir bo'limni alohida sarlavha ostida yozing.";

        return $prompt;
    }

    /**
     * Get top performing customers
     */
    public function getTopPerformingCustomers(Business $business, int $limit = 10): array
    {
        return Customer::where('business_id', $business->id)
            ->where('status', 'active')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'total_spent' => $customer->total_spent ?? 0,
                'orders_count' => $customer->orders_count ?? 0,
                'type' => $customer->type,
                'last_purchase' => $customer->last_purchase_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Get churn risk analysis
     */
    public function getChurnRiskAnalysis(Business $business): array
    {
        $customers = Customer::where('business_id', $business->id)
            ->where('status', 'active')
            ->get();

        $riskLevels = [
            'high_risk' => [],
            'medium_risk' => [],
            'low_risk' => [],
        ];

        foreach ($customers as $customer) {
            $daysSinceLastPurchase = $customer->last_purchase_at
                ? now()->diffInDays($customer->last_purchase_at)
                : 999;

            if ($daysSinceLastPurchase > 90) {
                $riskLevels['high_risk'][] = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'days_since_purchase' => $daysSinceLastPurchase,
                    'total_spent' => $customer->total_spent ?? 0,
                ];
            } elseif ($daysSinceLastPurchase > 60) {
                $riskLevels['medium_risk'][] = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'days_since_purchase' => $daysSinceLastPurchase,
                    'total_spent' => $customer->total_spent ?? 0,
                ];
            } else {
                $riskLevels['low_risk'][] = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'days_since_purchase' => $daysSinceLastPurchase,
                    'total_spent' => $customer->total_spent ?? 0,
                ];
            }
        }

        return [
            'high_risk_count' => count($riskLevels['high_risk']),
            'medium_risk_count' => count($riskLevels['medium_risk']),
            'low_risk_count' => count($riskLevels['low_risk']),
            'high_risk_customers' => array_slice($riskLevels['high_risk'], 0, 10),
            'potential_lost_revenue' => collect($riskLevels['high_risk'])->sum('total_spent'),
        ];
    }

    /**
     * Calculate conversion rate
     */
    protected function calculateConversionRate(Business $business): float
    {
        $totalLeads = Lead::where('business_id', $business->id)->count();
        $convertedLeads = Lead::where('business_id', $business->id)
            ->where('status', 'converted')
            ->count();

        return $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 2)
            : 0;
    }

    /**
     * Calculate average order value
     */
    protected function calculateAvgOrderValue(Business $business): float
    {
        $customers = Customer::where('business_id', $business->id)
            ->where('orders_count', '>', 0)
            ->get();

        if ($customers->isEmpty()) {
            return 0;
        }

        $totalRevenue = $customers->sum('total_spent');
        $totalOrders = $customers->sum('orders_count');

        return $totalOrders > 0
            ? round($totalRevenue / $totalOrders, 2)
            : 0;
    }

    /**
     * Get customer growth trends
     */
    public function getGrowthTrends(Business $business, int $months = 6): array
    {
        $trends = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = now()->subMonths($i)->startOfMonth();
            $endDate = now()->subMonths($i)->endOfMonth();

            $newCustomers = Customer::where('business_id', $business->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $revenue = Customer::where('business_id', $business->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_spent');

            $trends[] = [
                'month' => $startDate->format('M Y'),
                'new_customers' => $newCustomers,
                'revenue' => $revenue,
            ];
        }

        return $trends;
    }

    /**
     * Export target analysis to array format
     */
    public function exportAnalysis(Business $business): array
    {
        return $this->getTargetAnalysis($business);
    }
}
