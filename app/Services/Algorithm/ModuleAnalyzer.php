<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use App\Models\ContentSchedule;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\Sale;
use Carbon\Carbon;

/**
 * Module Analyzer
 *
 * Analyzes each business module independently and provides
 * detailed insights with accuracy scores.
 */
class ModuleAnalyzer extends AlgorithmEngine
{
    protected string $cachePrefix = 'module_analyzer_';

    protected int $cacheTTL = 1800; // 30 minutes

    protected array $moduleWeights = [
        'sales' => 0.30,
        'marketing' => 0.25,
        'customers' => 0.20,
        'content' => 0.15,
        'funnel' => 0.10,
    ];

    /**
     * Analyze all modules for a business
     */
    public function analyzeAllModules(Business $business): array
    {
        $cacheKey = "business_{$business->id}_all_modules";

        return $this->cached($cacheKey, function () use ($business) {
            $modules = [
                'sales' => $this->analyzeSalesModule($business),
                'marketing' => $this->analyzeMarketingModule($business),
                'customers' => $this->analyzeCustomerModule($business),
                'content' => $this->analyzeContentModule($business),
                'funnel' => $this->analyzeFunnelModule($business),
            ];

            // Calculate overall score
            $overallScore = $this->calculateOverallScore($modules);

            // Calculate data accuracy
            $dataAccuracy = $this->calculateDataAccuracy($modules);

            // Generate cross-module insights
            $crossModuleInsights = $this->generateCrossModuleInsights($modules);

            return [
                'modules' => $modules,
                'overall_score' => $overallScore,
                'data_accuracy' => $dataAccuracy,
                'cross_module_insights' => $crossModuleInsights,
                'analyzed_at' => now()->toIso8601String(),
                'cache_ttl' => $this->cacheTTL,
            ];
        });
    }

    /**
     * Analyze Sales Module
     */
    public function analyzeSalesModule(Business $business): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Get sales data
        $currentMonthSales = Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->get();

        $lastMonthSales = Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->get();

        // Calculate metrics
        $currentRevenue = $currentMonthSales->sum('amount');
        $lastRevenue = $lastMonthSales->sum('amount');
        $revenueGrowth = $this->growthRate($currentRevenue, $lastRevenue);

        $currentCount = $currentMonthSales->count();
        $lastCount = $lastMonthSales->count();
        $salesGrowth = $this->growthRate($currentCount, $lastCount);

        // Average order value
        $currentAOV = $currentCount > 0 ? $currentRevenue / $currentCount : 0;
        $lastAOV = $lastCount > 0 ? $lastRevenue / $lastCount : 0;
        $aovGrowth = $this->growthRate($currentAOV, $lastAOV);

        // Daily sales trend
        $dailySales = Sale::where('business_id', $business->id)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyRevenues = $dailySales->pluck('total')->toArray();
        $trend = $this->linearRegression($dailyRevenues);

        // Calculate health score
        $healthScore = $this->calculateSalesHealthScore($revenueGrowth, $salesGrowth, $aovGrowth, $trend);

        // Data completeness
        $dataCompleteness = $this->calculateSalesDataCompleteness($currentMonthSales);

        return [
            'module' => 'sales',
            'label' => 'Sotuvlar',
            'health_score' => $healthScore,
            'data_completeness' => $dataCompleteness,
            'metrics' => [
                'current_revenue' => round($currentRevenue, 2),
                'last_revenue' => round($lastRevenue, 2),
                'revenue_growth' => $revenueGrowth,
                'current_sales_count' => $currentCount,
                'last_sales_count' => $lastCount,
                'sales_growth' => $salesGrowth,
                'current_aov' => round($currentAOV, 2),
                'last_aov' => round($lastAOV, 2),
                'aov_growth' => $aovGrowth,
            ],
            'trend' => [
                'direction' => $trend['trend'],
                'slope' => $trend['slope'],
                'r_squared' => $trend['r_squared'],
                'confidence' => $trend['r_squared'] > 0.7 ? 'high' : ($trend['r_squared'] > 0.4 ? 'medium' : 'low'),
            ],
            'insights' => $this->generateSalesInsights($revenueGrowth, $salesGrowth, $aovGrowth, $trend),
            'daily_data' => $dailySales->toArray(),
        ];
    }

    /**
     * Analyze Marketing Module
     */
    public function analyzeMarketingModule(Business $business): array
    {
        $channels = MarketingChannel::where('business_id', $business->id)->get();

        $totalBudget = $channels->sum('monthly_budget');
        $activeChannels = $channels->where('is_active', true)->count();

        // Channel performance
        $channelPerformance = $channels->map(function ($channel) {
            return [
                'name' => $channel->name,
                'type' => $channel->channel_type,
                'budget' => $channel->monthly_budget,
                'is_active' => $channel->is_active,
            ];
        })->toArray();

        // Calculate budget distribution efficiency
        $budgetDistribution = $this->analyzeBudgetDistribution($channels);

        // Calculate channel diversity score
        $diversityScore = $this->calculateChannelDiversity($channels);

        // Health score
        $healthScore = $this->calculateMarketingHealthScore($activeChannels, $totalBudget, $diversityScore);

        // Data completeness
        $dataCompleteness = $channels->count() > 0 ? 100 : 0;

        return [
            'module' => 'marketing',
            'label' => 'Marketing',
            'health_score' => $healthScore,
            'data_completeness' => $dataCompleteness,
            'metrics' => [
                'total_budget' => round($totalBudget, 2),
                'active_channels' => $activeChannels,
                'total_channels' => $channels->count(),
                'diversity_score' => $diversityScore,
            ],
            'budget_distribution' => $budgetDistribution,
            'channel_performance' => $channelPerformance,
            'insights' => $this->generateMarketingInsights($activeChannels, $totalBudget, $diversityScore),
        ];
    }

    /**
     * Analyze Customer Module
     */
    public function analyzeCustomerModule(Business $business): array
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // Customer data from sales
        $recentCustomers = Sale::where('business_id', $business->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $previousCustomers = Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $customerGrowth = $this->growthRate($recentCustomers, $previousCustomers);

        // Customer purchase frequency
        $customerPurchases = Sale::where('business_id', $business->id)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as purchase_count, SUM(amount) as total_spent')
            ->groupBy('customer_id')
            ->get();

        $avgPurchaseFrequency = $customerPurchases->count() > 0
            ? $customerPurchases->avg('purchase_count')
            : 0;

        $avgCustomerValue = $customerPurchases->count() > 0
            ? $customerPurchases->avg('total_spent')
            : 0;

        // RFM Analysis
        $rfmAnalysis = $this->performRFMAnalysis($business);

        // Customer segments
        $segments = $this->segmentCustomers($customerPurchases);

        // Health score
        $healthScore = $this->calculateCustomerHealthScore($customerGrowth, $avgPurchaseFrequency, $segments);

        return [
            'module' => 'customers',
            'label' => 'Mijozlar',
            'health_score' => $healthScore,
            'data_completeness' => $customerPurchases->count() > 0 ? 100 : 0,
            'metrics' => [
                'recent_customers' => $recentCustomers,
                'previous_customers' => $previousCustomers,
                'customer_growth' => $customerGrowth,
                'avg_purchase_frequency' => round($avgPurchaseFrequency, 2),
                'avg_customer_value' => round($avgCustomerValue, 2),
                'total_unique_customers' => $customerPurchases->count(),
            ],
            'rfm_analysis' => $rfmAnalysis,
            'segments' => $segments,
            'insights' => $this->generateCustomerInsights($customerGrowth, $avgPurchaseFrequency, $segments),
        ];
    }

    /**
     * Analyze Content Module
     */
    public function analyzeContentModule(Business $business): array
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Content schedule data
        $scheduledContent = ContentSchedule::where('business_id', $business->id)
            ->where('scheduled_at', '>=', $thirtyDaysAgo)
            ->get();

        $publishedContent = $scheduledContent->where('status', 'published')->count();
        $totalScheduled = $scheduledContent->count();

        $contentFrequency = $totalScheduled / 30; // Posts per day

        // Content type distribution
        $contentTypes = $scheduledContent->groupBy('content_type')
            ->map(fn ($items) => $items->count())
            ->toArray();

        // Publishing consistency
        $publishDates = $scheduledContent->pluck('scheduled_at')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->count();

        $consistencyScore = min(100, ($publishDates / 30) * 100);

        // Health score
        $healthScore = $this->calculateContentHealthScore($contentFrequency, $consistencyScore, $totalScheduled);

        return [
            'module' => 'content',
            'label' => 'Kontent',
            'health_score' => $healthScore,
            'data_completeness' => $totalScheduled > 0 ? 100 : 0,
            'metrics' => [
                'total_scheduled' => $totalScheduled,
                'published' => $publishedContent,
                'content_frequency' => round($contentFrequency, 2),
                'consistency_score' => round($consistencyScore, 2),
                'active_days' => $publishDates,
            ],
            'content_types' => $contentTypes,
            'insights' => $this->generateContentInsights($contentFrequency, $consistencyScore),
        ];
    }

    /**
     * Analyze Funnel Module
     */
    public function analyzeFunnelModule(Business $business): array
    {
        // Lead data
        $leads = Lead::where('business_id', $business->id)->get();

        $totalLeads = $leads->count();
        $convertedLeads = $leads->where('status', 'converted')->count();
        $activeLeads = $leads->whereIn('status', ['new', 'contacted', 'qualified'])->count();

        $conversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        // Funnel stages
        $funnelStages = $leads->groupBy('status')
            ->map(fn ($items) => $items->count())
            ->toArray();

        // Calculate funnel efficiency
        $funnelEfficiency = $this->calculateFunnelEfficiency($funnelStages);

        // Health score
        $healthScore = $this->calculateFunnelHealthScore($conversionRate, $funnelEfficiency, $totalLeads);

        return [
            'module' => 'funnel',
            'label' => 'Funnel',
            'health_score' => $healthScore,
            'data_completeness' => $totalLeads > 0 ? 100 : 0,
            'metrics' => [
                'total_leads' => $totalLeads,
                'converted_leads' => $convertedLeads,
                'active_leads' => $activeLeads,
                'conversion_rate' => round($conversionRate, 2),
                'funnel_efficiency' => $funnelEfficiency,
            ],
            'funnel_stages' => $funnelStages,
            'insights' => $this->generateFunnelInsights($conversionRate, $funnelEfficiency, $totalLeads),
        ];
    }

    /**
     * Calculate overall score from all modules
     */
    protected function calculateOverallScore(array $modules): array
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($modules as $key => $module) {
            $weight = $this->moduleWeights[$key] ?? 0.1;
            $weightedSum += $module['health_score'] * $weight;
            $totalWeight += $weight;
        }

        $score = $totalWeight > 0 ? round($weightedSum / $totalWeight) : 50;

        return [
            'score' => $score,
            'status' => $this->getScoreStatus($score),
            'breakdown' => array_map(function ($key, $module) {
                return [
                    'module' => $key,
                    'score' => $module['health_score'],
                    'weight' => $this->moduleWeights[$key] ?? 0.1,
                    'contribution' => round($module['health_score'] * ($this->moduleWeights[$key] ?? 0.1), 2),
                ];
            }, array_keys($modules), $modules),
        ];
    }

    /**
     * Calculate data accuracy across modules
     */
    protected function calculateDataAccuracy(array $modules): array
    {
        $completenessScores = array_column($modules, 'data_completeness');
        $avgCompleteness = count($completenessScores) > 0
            ? array_sum($completenessScores) / count($completenessScores)
            : 0;

        return [
            'overall' => round($avgCompleteness, 2),
            'by_module' => array_map(fn ($m) => [
                'module' => $m['module'],
                'completeness' => $m['data_completeness'],
            ], $modules),
            'recommendation' => $avgCompleteness < 50
                ? "Ma'lumotlar to'liqligini oshirish kerak. Kamida 70% ma'lumot to'ldirilishi tavsiya etiladi."
                : ($avgCompleteness < 80
                    ? "Ma'lumotlar yaxshi to'ldirilgan, lekin yana yaxshilash mumkin."
                    : "Ma'lumotlar to'liqligi ajoyib darajada."),
        ];
    }

    /**
     * Generate cross-module insights
     */
    protected function generateCrossModuleInsights(array $modules): array
    {
        $insights = [];

        // Sales vs Marketing correlation
        if (isset($modules['sales']) && isset($modules['marketing'])) {
            $salesGrowth = $modules['sales']['metrics']['revenue_growth'] ?? 0;
            $marketingBudget = $modules['marketing']['metrics']['total_budget'] ?? 0;

            if ($salesGrowth < 0 && $marketingBudget > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'modules' => ['sales', 'marketing'],
                    'message' => 'Marketing xarajatlari mavjud, lekin sotuvlar pasaymoqda. Strategiyani qayta ko\'rib chiqish tavsiya etiladi.',
                    'priority' => 'high',
                ];
            }
        }

        // Customer vs Sales correlation
        if (isset($modules['customers']) && isset($modules['sales'])) {
            $customerGrowth = $modules['customers']['metrics']['customer_growth'] ?? 0;
            $salesGrowth = $modules['sales']['metrics']['sales_growth'] ?? 0;

            if ($customerGrowth > 10 && $salesGrowth < 0) {
                $insights[] = [
                    'type' => 'opportunity',
                    'modules' => ['customers', 'sales'],
                    'message' => 'Mijozlar soni ortmoqda, lekin sotuvlar pasaymoqda. Konversiyani yaxshilash imkoniyati bor.',
                    'priority' => 'high',
                ];
            }
        }

        // Content vs Customer engagement
        if (isset($modules['content']) && isset($modules['customers'])) {
            $contentFrequency = $modules['content']['metrics']['content_frequency'] ?? 0;
            $customerGrowth = $modules['customers']['metrics']['customer_growth'] ?? 0;

            if ($contentFrequency > 1 && $customerGrowth > 5) {
                $insights[] = [
                    'type' => 'success',
                    'modules' => ['content', 'customers'],
                    'message' => 'Kontent strategiyasi mijozlar o\'sishiga ijobiy ta\'sir ko\'rsatmoqda.',
                    'priority' => 'low',
                ];
            }
        }

        // Funnel efficiency
        if (isset($modules['funnel'])) {
            $conversionRate = $modules['funnel']['metrics']['conversion_rate'] ?? 0;

            if ($conversionRate < 5) {
                $insights[] = [
                    'type' => 'warning',
                    'modules' => ['funnel'],
                    'message' => 'Funnel konversiyasi past. Lead nurturing strategiyasini yaxshilash kerak.',
                    'priority' => 'medium',
                ];
            }
        }

        return $insights;
    }

    // Health score calculators
    protected function calculateSalesHealthScore(float $revenueGrowth, float $salesGrowth, float $aovGrowth, array $trend): int
    {
        $score = 50; // Base score

        // Revenue growth contribution (max 30 points)
        if ($revenueGrowth > 20) {
            $score += 30;
        } elseif ($revenueGrowth > 10) {
            $score += 25;
        } elseif ($revenueGrowth > 0) {
            $score += 15;
        } elseif ($revenueGrowth > -10) {
            $score += 5;
        }

        // Sales count growth (max 20 points)
        if ($salesGrowth > 20) {
            $score += 20;
        } elseif ($salesGrowth > 10) {
            $score += 15;
        } elseif ($salesGrowth > 0) {
            $score += 10;
        } elseif ($salesGrowth > -10) {
            $score += 5;
        }

        // Trend direction (max 10 points)
        if ($trend['trend'] === 'up') {
            $score += 10;
        } elseif ($trend['trend'] === 'stable') {
            $score += 5;
        }

        return min(100, max(0, $score));
    }

    protected function calculateMarketingHealthScore(int $activeChannels, float $totalBudget, float $diversityScore): int
    {
        $score = 30; // Base score

        // Active channels (max 30 points)
        if ($activeChannels >= 4) {
            $score += 30;
        } elseif ($activeChannels >= 2) {
            $score += 20;
        } elseif ($activeChannels >= 1) {
            $score += 10;
        }

        // Budget allocation (max 20 points)
        if ($totalBudget > 0) {
            $score += 20;
        }

        // Diversity (max 20 points)
        $score += min(20, $diversityScore / 5);

        return min(100, max(0, $score));
    }

    protected function calculateCustomerHealthScore(float $customerGrowth, float $avgFrequency, array $segments): int
    {
        $score = 40; // Base score

        // Customer growth (max 30 points)
        if ($customerGrowth > 20) {
            $score += 30;
        } elseif ($customerGrowth > 10) {
            $score += 25;
        } elseif ($customerGrowth > 0) {
            $score += 15;
        } elseif ($customerGrowth > -10) {
            $score += 5;
        }

        // Purchase frequency (max 20 points)
        if ($avgFrequency > 3) {
            $score += 20;
        } elseif ($avgFrequency > 2) {
            $score += 15;
        } elseif ($avgFrequency > 1) {
            $score += 10;
        }

        // Loyal customers (max 10 points)
        $loyalPercent = $segments['loyal'] ?? 0;
        $score += min(10, $loyalPercent / 10);

        return min(100, max(0, $score));
    }

    protected function calculateContentHealthScore(float $frequency, float $consistency, int $total): int
    {
        $score = 30; // Base score

        // Frequency (max 30 points)
        if ($frequency >= 1) {
            $score += 30;
        } elseif ($frequency >= 0.5) {
            $score += 20;
        } elseif ($frequency >= 0.2) {
            $score += 10;
        }

        // Consistency (max 30 points)
        $score += min(30, $consistency / 3.33);

        // Total content (max 10 points)
        if ($total >= 30) {
            $score += 10;
        } elseif ($total >= 15) {
            $score += 5;
        }

        return min(100, max(0, (int) $score));
    }

    protected function calculateFunnelHealthScore(float $conversionRate, float $efficiency, int $totalLeads): int
    {
        $score = 30; // Base score

        // Conversion rate (max 40 points)
        if ($conversionRate >= 20) {
            $score += 40;
        } elseif ($conversionRate >= 10) {
            $score += 30;
        } elseif ($conversionRate >= 5) {
            $score += 20;
        } elseif ($conversionRate >= 2) {
            $score += 10;
        }

        // Efficiency (max 20 points)
        $score += min(20, $efficiency / 5);

        // Lead volume (max 10 points)
        if ($totalLeads >= 100) {
            $score += 10;
        } elseif ($totalLeads >= 50) {
            $score += 7;
        } elseif ($totalLeads >= 20) {
            $score += 4;
        }

        return min(100, max(0, (int) $score));
    }

    // Helper methods
    protected function calculateSalesDataCompleteness($sales): float
    {
        if ($sales->isEmpty()) {
            return 0;
        }

        $requiredFields = ['amount', 'customer_id', 'created_at'];
        $completenessSum = 0;

        foreach ($sales as $sale) {
            $complete = 0;
            foreach ($requiredFields as $field) {
                if (! empty($sale->$field)) {
                    $complete++;
                }
            }
            $completenessSum += ($complete / count($requiredFields)) * 100;
        }

        return round($completenessSum / $sales->count(), 2);
    }

    protected function analyzeBudgetDistribution($channels): array
    {
        $totalBudget = $channels->sum('monthly_budget');
        if ($totalBudget === 0) {
            return [];
        }

        return $channels->map(function ($channel) use ($totalBudget) {
            return [
                'name' => $channel->name,
                'budget' => $channel->monthly_budget,
                'percentage' => round(($channel->monthly_budget / $totalBudget) * 100, 2),
            ];
        })->toArray();
    }

    protected function calculateChannelDiversity($channels): float
    {
        $types = $channels->pluck('channel_type')->unique()->count();

        return min(100, $types * 25); // Max 100 for 4+ different types
    }

    protected function performRFMAnalysis(Business $business): array
    {
        // Simplified RFM
        $customers = Sale::where('business_id', $business->id)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, MAX(created_at) as last_purchase, COUNT(*) as frequency, SUM(amount) as monetary')
            ->groupBy('customer_id')
            ->get();

        if ($customers->isEmpty()) {
            return ['segments' => [], 'summary' => 'Ma\'lumot yetarli emas'];
        }

        $avgRecency = $customers->avg(fn ($c) => Carbon::parse($c->last_purchase)->diffInDays(now()));
        $avgFrequency = $customers->avg('frequency');
        $avgMonetary = $customers->avg('monetary');

        return [
            'avg_recency_days' => round($avgRecency, 1),
            'avg_frequency' => round($avgFrequency, 2),
            'avg_monetary' => round($avgMonetary, 2),
            'customer_count' => $customers->count(),
        ];
    }

    protected function segmentCustomers($customerPurchases): array
    {
        if ($customerPurchases->isEmpty()) {
            return ['new' => 0, 'active' => 0, 'loyal' => 0, 'at_risk' => 0];
        }

        $avgPurchases = $customerPurchases->avg('purchase_count');

        $segments = [
            'new' => 0,
            'active' => 0,
            'loyal' => 0,
            'at_risk' => 0,
        ];

        foreach ($customerPurchases as $customer) {
            if ($customer->purchase_count == 1) {
                $segments['new']++;
            } elseif ($customer->purchase_count >= $avgPurchases * 2) {
                $segments['loyal']++;
            } elseif ($customer->purchase_count >= $avgPurchases) {
                $segments['active']++;
            } else {
                $segments['at_risk']++;
            }
        }

        $total = array_sum($segments);

        return array_map(fn ($v) => round(($v / $total) * 100, 1), $segments);
    }

    protected function calculateFunnelEfficiency(array $stages): float
    {
        $totalLeads = array_sum($stages);
        if ($totalLeads === 0) {
            return 0;
        }

        $converted = $stages['converted'] ?? 0;

        return round(($converted / $totalLeads) * 100, 2);
    }

    protected function getScoreStatus(int $score): array
    {
        if ($score >= 80) {
            return ['level' => 'excellent', 'label' => 'Ajoyib', 'color' => 'emerald'];
        }
        if ($score >= 60) {
            return ['level' => 'good', 'label' => 'Yaxshi', 'color' => 'green'];
        }
        if ($score >= 40) {
            return ['level' => 'average', 'label' => 'O\'rtacha', 'color' => 'yellow'];
        }

        return ['level' => 'poor', 'label' => 'Zaif', 'color' => 'red'];
    }

    // Insight generators
    protected function generateSalesInsights(float $revenueGrowth, float $salesGrowth, float $aovGrowth, array $trend): array
    {
        $insights = [];

        if ($revenueGrowth > 20) {
            $insights[] = ['type' => 'success', 'message' => "Daromad {$revenueGrowth}% o'sdi - ajoyib natija!"];
        } elseif ($revenueGrowth < -10) {
            $insights[] = ['type' => 'warning', 'message' => "Daromad {$revenueGrowth}% pasaydi - e'tibor qaratish kerak."];
        }

        if ($aovGrowth > 10) {
            $insights[] = ['type' => 'success', 'message' => "O'rtacha chek {$aovGrowth}% oshdi."];
        }

        if ($trend['trend'] === 'down' && $trend['r_squared'] > 0.5) {
            $insights[] = ['type' => 'alert', 'message' => 'Sotuvlarda pasayish trendi kuzatilmoqda.'];
        }

        return $insights;
    }

    protected function generateMarketingInsights(int $activeChannels, float $totalBudget, float $diversityScore): array
    {
        $insights = [];

        if ($activeChannels < 2) {
            $insights[] = ['type' => 'suggestion', 'message' => 'Marketing kanallarini ko\'paytirish tavsiya etiladi.'];
        }

        if ($diversityScore < 50) {
            $insights[] = ['type' => 'suggestion', 'message' => 'Turli xil marketing kanallaridan foydalaning.'];
        }

        if ($totalBudget > 0) {
            $insights[] = ['type' => 'info', 'message' => 'Jami marketing byudjeti: '.number_format($totalBudget)." so'm"];
        }

        return $insights;
    }

    protected function generateCustomerInsights(float $customerGrowth, float $avgFrequency, array $segments): array
    {
        $insights = [];

        if ($customerGrowth > 10) {
            $insights[] = ['type' => 'success', 'message' => "Mijozlar soni {$customerGrowth}% o'sdi."];
        }

        if ($avgFrequency < 1.5) {
            $insights[] = ['type' => 'suggestion', 'message' => 'Mijozlarni qayta sotib olishga undash kerak.'];
        }

        $loyalPercent = $segments['loyal'] ?? 0;
        if ($loyalPercent < 20) {
            $insights[] = ['type' => 'suggestion', 'message' => 'Sodiq mijozlar foizini oshirish kerak.'];
        }

        return $insights;
    }

    protected function generateContentInsights(float $frequency, float $consistency): array
    {
        $insights = [];

        if ($frequency < 0.5) {
            $insights[] = ['type' => 'warning', 'message' => 'Kontent chiqarish chastotasi past. Kamida haftada 3-4 marta kontnet chiqarish tavsiya etiladi.'];
        }

        if ($consistency < 50) {
            $insights[] = ['type' => 'suggestion', 'message' => 'Kontent muntazamligi yaxshilanishi kerak.'];
        }

        return $insights;
    }

    protected function generateFunnelInsights(float $conversionRate, float $efficiency, int $totalLeads): array
    {
        $insights = [];

        if ($totalLeads < 20) {
            $insights[] = ['type' => 'warning', 'message' => 'Lead soni kam. Marketing faoliyatini kuchaytirish kerak.'];
        }

        if ($conversionRate < 5) {
            $insights[] = ['type' => 'warning', 'message' => 'Konversiya foizi past. Sotuvchilar treningini o\'tkazish tavsiya etiladi.'];
        } elseif ($conversionRate > 15) {
            $insights[] = ['type' => 'success', 'message' => "Konversiya foizi yaxshi - {$conversionRate}%"];
        }

        return $insights;
    }
}
