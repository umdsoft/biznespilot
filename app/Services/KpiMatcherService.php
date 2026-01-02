<?php

namespace App\Services;

use App\Models\KpiTemplate;
use App\Models\IndustryBenchmark;
use App\Models\Business;
use App\Models\BusinessKpiConfiguration;
use Illuminate\Support\Collection;

class KpiMatcherService
{
    /**
     * Generate recommended KPIs for a business
     */
    public function generateRecommendedKpis(
        Business $business,
        string $primaryGoal,
        array $secondaryGoals = [],
        array $preferences = []
    ): array {
        // Get all applicable KPIs
        $applicableKpis = $this->getApplicableKpis($business);

        // Score and rank KPIs
        $scoredKpis = $this->scoreKpis(
            $applicableKpis,
            $business,
            $primaryGoal,
            $secondaryGoals,
            $preferences
        );

        // Select top KPIs
        $selectedKpis = $this->selectTopKpis($scoredKpis, $preferences);

        // Assign priorities and weights
        $configuration = $this->assignPrioritiesAndWeights($selectedKpis, $primaryGoal);

        return [
            'selected_kpis' => $configuration['kpi_codes'],
            'kpi_priorities' => $configuration['priorities'],
            'kpi_weights' => $configuration['weights'],
            'kpi_details' => $configuration['details'],
            'total_count' => count($configuration['kpi_codes']),
            'critical_count' => count(array_filter($configuration['priorities'], fn($p) => $p === 'critical')),
            'recommendations' => $this->generateRecommendations($configuration, $primaryGoal),
        ];
    }

    /**
     * Get KPIs applicable to the business
     */
    protected function getApplicableKpis(Business $business): Collection
    {
        $industryCode = $business->industry_code ?? 'all';
        $subCategory = $business->sub_category ?? null;
        $maturity = $business->business_maturity ?? 'startup';

        return KpiTemplate::where('is_active', true)
            ->where(function ($query) use ($industryCode, $subCategory, $maturity) {
                // Universal KPIs
                $query->where('is_universal', true)
                    // OR industry-specific KPIs
                    ->orWhere(function ($q) use ($industryCode, $subCategory, $maturity) {
                        $q->whereRaw("JSON_CONTAINS(applicable_industries, '\"$industryCode\"') OR JSON_CONTAINS(applicable_industries, '\"all\"')");

                        if ($subCategory) {
                            $q->where(function ($sq) use ($subCategory) {
                                $sq->whereNull('applicable_subcategories')
                                    ->orWhereRaw("JSON_CONTAINS(applicable_subcategories, '\"$subCategory\"')");
                            });
                        }

                        $q->where(function ($mq) use ($maturity) {
                            $mq->whereNull('min_maturity_level')
                                ->orWhereRaw("FIND_IN_SET('$maturity', min_maturity_level)");
                        });
                    });
            })
            ->get();
    }

    /**
     * Score KPIs based on relevance
     */
    protected function scoreKpis(
        Collection $kpis,
        Business $business,
        string $primaryGoal,
        array $secondaryGoals,
        array $preferences
    ): Collection {
        return $kpis->map(function ($kpi) use ($business, $primaryGoal, $secondaryGoals, $preferences) {
            $score = 0;

            // Base score from template priority
            $score += match($kpi->priority_level) {
                'critical' => 100,
                'high' => 75,
                'medium' => 50,
                'low' => 25,
                default => 0,
            };

            // Goal alignment score
            $goalScore = $this->calculateGoalAlignment($kpi, $primaryGoal, $secondaryGoals);
            $score += $goalScore * 2; // Double weight for goal alignment

            // Industry specificity score
            if (!$kpi->is_universal) {
                $score += 30; // Prefer industry-specific KPIs
            }

            // Subcategory specificity score
            if ($kpi->applicable_subcategories && $business->sub_category) {
                $subcategories = $kpi->applicable_subcategories;
                if (is_array($subcategories) && in_array($business->sub_category, $subcategories)) {
                    $score += 20;
                }
            }

            // Maturity level score
            $maturityScore = $this->calculateMaturityScore($kpi, $business->business_maturity ?? 'startup');
            $score += $maturityScore;

            // User preferences
            if (isset($preferences['preferred_categories'])) {
                if (in_array($kpi->category, $preferences['preferred_categories'])) {
                    $score += 40;
                }
            }

            // Benchmark availability bonus
            if ($this->hasBenchmarkData($kpi, $business)) {
                $score += 15;
            }

            $kpi->relevance_score = $score;
            return $kpi;
        })->sortByDesc('relevance_score');
    }

    /**
     * Calculate goal alignment score
     */
    protected function calculateGoalAlignment(KpiTemplate $kpi, string $primaryGoal, array $secondaryGoals): int
    {
        $score = 0;

        $goalMapping = [
            'revenue_growth' => ['marketing', 'sales', 'advertising'],
            'customer_acquisition' => ['marketing', 'advertising'],
            'customer_retention' => ['retention', 'operational'],
            'profitability' => ['financial', 'operational'],
            'brand_awareness' => ['marketing', 'advertising'],
            'operational_efficiency' => ['operational', 'financial'],
            'market_expansion' => ['marketing', 'sales'],
            'customer_satisfaction' => ['retention', 'operational'],
        ];

        // Primary goal match
        if (isset($goalMapping[$primaryGoal]) && in_array($kpi->category, $goalMapping[$primaryGoal])) {
            $score += 50;
        }

        // Secondary goals match
        foreach ($secondaryGoals as $goal) {
            if (isset($goalMapping[$goal]) && in_array($kpi->category, $goalMapping[$goal])) {
                $score += 20;
            }
        }

        // Specific KPI to goal mapping
        $specificMapping = [
            'revenue_growth' => ['daily_revenue', 'average_check_size', 'avg_transaction_value', 'avg_order_value'],
            'customer_acquisition' => ['instagram_follower_growth', 'new_member_acquisition', 'student_enrollment_rate'],
            'customer_retention' => ['repeat_customer_rate', 'client_retention_rate', 'member_retention_rate', 'rebooking_rate'],
            'brand_awareness' => ['instagram_engagement_rate', 'brand_awareness_score', 'instagram_reach_rate'],
        ];

        if (isset($specificMapping[$primaryGoal]) && in_array($kpi->kpi_code, $specificMapping[$primaryGoal])) {
            $score += 30;
        }

        return $score;
    }

    /**
     * Calculate maturity level score
     */
    protected function calculateMaturityScore(KpiTemplate $kpi, string $maturity): int
    {
        $maturityLevels = ['startup' => 1, 'growing' => 2, 'mature' => 3, 'established' => 4];
        $currentLevel = $maturityLevels[$maturity] ?? 1;

        // Some KPIs are more important at certain maturity stages
        $advancedKpis = [
            'customer_lifetime_value',
            'customer_loyalty_index',
            'brand_awareness_score',
            'seasonality_index',
        ];

        if (in_array($kpi->kpi_code, $advancedKpis)) {
            // These are more valuable for mature businesses
            return ($currentLevel - 1) * 10;
        }

        // Basic KPIs are more important for startups
        if ($kpi->priority_level === 'critical' && $currentLevel === 1) {
            return 25;
        }

        return 10;
    }

    /**
     * Check if benchmark data exists
     */
    protected function hasBenchmarkData(KpiTemplate $kpi, Business $business): bool
    {
        return IndustryBenchmark::where('kpi_code', $kpi->kpi_code)
            ->where(function ($query) use ($business) {
                $query->where('industry_code', $business->industry_code ?? 'all')
                    ->orWhere('industry_code', 'all');
            })
            ->exists();
    }

    /**
     * Select top KPIs from scored list
     */
    protected function selectTopKpis(Collection $scoredKpis, array $preferences): Collection
    {
        $maxKpis = $preferences['max_kpis'] ?? 15;
        $minKpis = $preferences['min_kpis'] ?? 8;

        // Always include critical priority KPIs
        $criticalKpis = $scoredKpis->where('priority_level', 'critical')->take(5);

        // Add high-scoring KPIs until we reach max
        $additionalKpis = $scoredKpis
            ->whereNotIn('kpi_code', $criticalKpis->pluck('kpi_code'))
            ->take($maxKpis - $criticalKpis->count());

        $selectedKpis = $criticalKpis->merge($additionalKpis);

        // Ensure minimum count
        if ($selectedKpis->count() < $minKpis) {
            $moreKpis = $scoredKpis
                ->whereNotIn('kpi_code', $selectedKpis->pluck('kpi_code'))
                ->take($minKpis - $selectedKpis->count());
            $selectedKpis = $selectedKpis->merge($moreKpis);
        }

        return $selectedKpis;
    }

    /**
     * Assign priorities and weights to selected KPIs
     */
    protected function assignPrioritiesAndWeights(Collection $kpis, string $primaryGoal): array
    {
        $kpiCodes = [];
        $priorities = [];
        $weights = [];
        $details = [];

        $totalWeight = 0;

        foreach ($kpis as $kpi) {
            $kpiCode = $kpi->kpi_code;
            $kpiCodes[] = $kpiCode;

            // Determine priority based on relevance score
            if ($kpi->relevance_score >= 150) {
                $priority = 'critical';
                $weight = $kpi->default_weight ?? 3.0;
            } elseif ($kpi->relevance_score >= 100) {
                $priority = 'high';
                $weight = $kpi->default_weight ?? 2.5;
            } elseif ($kpi->relevance_score >= 50) {
                $priority = 'medium';
                $weight = $kpi->default_weight ?? 2.0;
            } else {
                $priority = 'low';
                $weight = $kpi->default_weight ?? 1.5;
            }

            $priorities[$kpiCode] = $priority;
            $weights[$kpiCode] = $weight;
            $totalWeight += $weight;

            $details[$kpiCode] = [
                'name' => $kpi->kpi_name,
                'name_uz' => $kpi->kpi_name_uz,
                'category' => $kpi->category,
                'priority' => $priority,
                'weight' => $weight,
                'relevance_score' => $kpi->relevance_score,
                'unit' => $kpi->default_unit,
                'frequency' => $kpi->default_frequency,
                'icon' => $kpi->icon,
            ];
        }

        // Normalize weights to sum to 100
        foreach ($weights as $kpiCode => $weight) {
            $weights[$kpiCode] = round(($weight / $totalWeight) * 100, 2);
            $details[$kpiCode]['normalized_weight'] = $weights[$kpiCode];
        }

        return [
            'kpi_codes' => $kpiCodes,
            'priorities' => $priorities,
            'weights' => $weights,
            'details' => $details,
        ];
    }

    /**
     * Generate recommendations for the configuration
     */
    protected function generateRecommendations(array $configuration, string $primaryGoal): array
    {
        $recommendations = [];

        // Count by category
        $categoryCounts = [];
        foreach ($configuration['details'] as $detail) {
            $category = $detail['category'];
            $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
        }

        // Recommend balance
        $criticalCount = count(array_filter($configuration['priorities'], fn($p) => $p === 'critical'));
        if ($criticalCount > 8) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => "You have $criticalCount critical KPIs. Consider focusing on fewer critical metrics for better clarity.",
                'message_uz' => "$criticalCount ta kritik KPI mavjud. Aniqroq ko'rish uchun kamroq kritik ko'rsatkichlarga e'tibor bering.",
            ];
        }

        // Category balance
        if (isset($categoryCounts['marketing']) && $categoryCounts['marketing'] > 5) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'You have many marketing KPIs. Ensure you also track operational and financial metrics.',
                'message_uz' => 'Ko\'p marketing KPIlari bor. Operatsion va moliyaviy ko\'rsatkichlarni ham kuzatganingizga ishonch hosil qiling.',
            ];
        }

        // Goal-specific recommendations
        $goalRecommendations = [
            'revenue_growth' => [
                'en' => 'Focus on tracking daily revenue, average transaction value, and customer acquisition cost.',
                'uz' => 'Kunlik daromad, o\'rtacha tranzaksiya qiymati va mijozlarni jalb qilish xarajatlarini kuzatishga e\'tibor bering.',
            ],
            'customer_retention' => [
                'en' => 'Monitor repeat customer rate, retention rate, and customer satisfaction closely.',
                'uz' => 'Takroriy mijozlar darajasi, ushlab qolish darajasi va mijozlar qoniqishini yaqindan kuzating.',
            ],
        ];

        if (isset($goalRecommendations[$primaryGoal])) {
            $recommendations[] = [
                'type' => 'success',
                'message' => $goalRecommendations[$primaryGoal]['en'],
                'message_uz' => $goalRecommendations[$primaryGoal]['uz'],
            ];
        }

        return $recommendations;
    }

    /**
     * Get benchmark targets for selected KPIs
     */
    public function getBenchmarkTargets(
        Business $business,
        array $kpiCodes,
        string $scenario = 'realistic'
    ): array {
        $targets = [];

        foreach ($kpiCodes as $kpiCode) {
            $benchmark = IndustryBenchmark::where('kpi_code', $kpiCode)
                ->where(function ($query) use ($business) {
                    $query->where('industry_code', $business->industry_code ?? 'all')
                        ->orWhere('industry_code', 'all');
                })
                ->where('business_size', $business->business_size ?? 'micro')
                ->where('business_maturity', $business->business_maturity ?? 'startup')
                ->first();

            if ($benchmark) {
                $targets[$kpiCode] = [
                    'benchmark_value' => $benchmark->benchmark_value,
                    'conservative' => $benchmark->percentile_25,
                    'realistic' => $benchmark->percentile_50,
                    'optimistic' => $benchmark->percentile_75,
                    'aggressive' => $benchmark->percentile_90,
                    'recommended' => match($scenario) {
                        'conservative' => $benchmark->percentile_25,
                        'realistic' => $benchmark->percentile_50,
                        'optimistic' => $benchmark->percentile_75,
                        'aggressive' => $benchmark->percentile_90,
                        default => $benchmark->benchmark_value,
                    },
                ];
            } else {
                // No benchmark available, use template defaults
                $template = KpiTemplate::where('kpi_code', $kpiCode)->first();
                if ($template) {
                    $targets[$kpiCode] = [
                        'benchmark_value' => null,
                        'recommended' => null,
                        'note' => 'No benchmark data available for your business profile',
                        'note_uz' => 'Sizning biznes profilingiz uchun benchmark ma\'lumotlari mavjud emas',
                    ];
                }
            }
        }

        return $targets;
    }

    /**
     * Create or update business KPI configuration
     */
    public function createConfiguration(
        Business $business,
        string $primaryGoal,
        array $secondaryGoals = [],
        array $preferences = []
    ): BusinessKpiConfiguration {
        // Generate recommendations
        $recommendations = $this->generateRecommendedKpis(
            $business,
            $primaryGoal,
            $secondaryGoals,
            $preferences
        );

        // Get benchmark targets
        $targets = $this->getBenchmarkTargets(
            $business,
            $recommendations['selected_kpis'],
            $preferences['target_scenario'] ?? 'realistic'
        );

        // Create or update configuration
        $configuration = BusinessKpiConfiguration::updateOrCreate(
            ['business_id' => $business->id],
            [
                'industry_code' => $business->industry_code ?? 'all',
                'sub_category' => $business->sub_category,
                'business_size' => $business->business_size ?? 'micro',
                'business_maturity' => $business->business_maturity ?? 'startup',
                'primary_goal' => $primaryGoal,
                'secondary_goals' => $secondaryGoals,
                'selected_kpis' => $recommendations['selected_kpis'],
                'kpi_priorities' => $recommendations['kpi_priorities'],
                'kpi_weights' => $recommendations['kpi_weights'],
                'is_auto_generated' => true,
                'generation_params' => [
                    'preferences' => $preferences,
                    'targets' => $targets,
                    'recommendations' => $recommendations['recommendations'],
                ],
                'total_kpis_count' => $recommendations['total_count'],
                'critical_kpis_count' => $recommendations['critical_count'],
                'status' => 'draft',
                'review_frequency_days' => $preferences['review_frequency_days'] ?? 30,
            ]
        );

        return $configuration;
    }

    /**
     * Suggest additional KPIs based on current performance
     */
    public function suggestAdditionalKpis(BusinessKpiConfiguration $configuration): array
    {
        $business = $configuration->business;
        $currentKpis = $configuration->selected_kpis ?? [];

        // Get all applicable KPIs not currently selected
        $applicableKpis = $this->getApplicableKpis($business)
            ->whereNotIn('kpi_code', $currentKpis);

        // Score based on complementary nature
        $suggestions = $applicableKpis->map(function ($kpi) use ($configuration) {
            $score = 0;

            // Check if this KPI complements existing ones
            $currentCategories = array_count_values(
                array_map(function ($code) {
                    $template = KpiTemplate::where('kpi_code', $code)->first();
                    return $template ? $template->category : null;
                }, $configuration->selected_kpis ?? [])
            );

            // Suggest KPIs from underrepresented categories
            $categoryCount = $currentCategories[$kpi->category] ?? 0;
            if ($categoryCount === 0) {
                $score += 50; // New category
            } elseif ($categoryCount < 2) {
                $score += 30; // Underrepresented category
            }

            // Prioritize based on template priority
            $score += match($kpi->priority_level) {
                'critical' => 40,
                'high' => 30,
                'medium' => 20,
                'low' => 10,
                default => 0,
            };

            return [
                'kpi_code' => $kpi->kpi_code,
                'name' => $kpi->kpi_name,
                'name_uz' => $kpi->kpi_name_uz,
                'category' => $kpi->category,
                'priority' => $kpi->priority_level,
                'reason' => $this->getSuggestionReason($kpi, $currentCategories),
                'reason_uz' => $this->getSuggestionReasonUz($kpi, $currentCategories),
                'score' => $score,
            ];
        })->sortByDesc('score')->take(5)->values();

        return $suggestions->toArray();
    }

    /**
     * Get suggestion reason in English
     */
    protected function getSuggestionReason(KpiTemplate $kpi, array $currentCategories): string
    {
        $categoryCount = $currentCategories[$kpi->category] ?? 0;

        if ($categoryCount === 0) {
            return "Adds {$kpi->category} tracking to your dashboard";
        }

        if ($kpi->priority_level === 'critical') {
            return "Critical KPI for comprehensive business monitoring";
        }

        return "Complements your existing {$kpi->category} metrics";
    }

    /**
     * Get suggestion reason in Uzbek
     */
    protected function getSuggestionReasonUz(KpiTemplate $kpi, array $currentCategories): string
    {
        $categoryCount = $currentCategories[$kpi->category] ?? 0;

        $categoryNames = [
            'marketing' => 'marketing',
            'sales' => 'sotuv',
            'advertising' => 'reklama',
            'operational' => 'operatsion',
            'retention' => 'ushlab qolish',
            'financial' => 'moliyaviy',
        ];

        $categoryName = $categoryNames[$kpi->category] ?? $kpi->category;

        if ($categoryCount === 0) {
            return "Boshqaruv panelingizga {$categoryName} kuzatuvini qo'shadi";
        }

        if ($kpi->priority_level === 'critical') {
            return "Biznesni keng qamrovli monitoring qilish uchun kritik KPI";
        }

        return "Mavjud {$categoryName} ko'rsatkichlaringizni to'ldiradi";
    }
}
