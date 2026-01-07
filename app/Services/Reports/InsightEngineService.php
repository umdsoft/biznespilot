<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\IndustryBenchmark;

/**
 * InsightEngineService
 *
 * Generates rule-based insights from metrics and trends
 * Pure algorithmic approach - no AI/LLM calls
 */
class InsightEngineService
{
    // Insight types
    public const TYPE_POSITIVE = 'positive';
    public const TYPE_NEGATIVE = 'negative';
    public const TYPE_NEUTRAL = 'neutral';
    public const TYPE_WARNING = 'warning';

    // Insight priorities
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    // Insight categories
    public const CATEGORY_SALES = 'sales';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_FINANCIAL = 'financial';
    public const CATEGORY_CUSTOMER = 'customer';
    public const CATEGORY_EFFICIENCY = 'efficiency';

    protected array $insights = [];
    protected array $recommendations = [];
    protected array $benchmarks = [];

    /**
     * Generate insights from metrics and trends
     */
    public function generate(Business $business, array $metrics, array $trends): array
    {
        $this->insights = [];
        $this->recommendations = [];
        $this->benchmarks = $this->loadBenchmarks($business);

        // Generate insights from different areas
        $this->generateSalesInsights($metrics, $trends);
        $this->generateMarketingInsights($metrics, $trends);
        $this->generateFinancialInsights($metrics, $trends);
        $this->generateCustomerInsights($metrics, $trends);
        $this->generateEfficiencyInsights($metrics, $trends);
        $this->generateTrendInsights($trends);
        $this->generateAnomalyInsights($trends);
        $this->generateKpiProgressInsights($metrics);

        // Sort by priority
        $this->sortInsights();

        return [
            'insights' => $this->insights,
            'recommendations' => $this->recommendations,
            'summary' => $this->generateSummary(),
        ];
    }

    /**
     * Load industry benchmarks
     */
    protected function loadBenchmarks(Business $business): array
    {
        try {
            $benchmark = IndustryBenchmark::where('industry', 'LIKE', "%{$business->industry}%")->first()
                ?? IndustryBenchmark::where('industry', 'default')->first();

            if ($benchmark) {
                return $benchmark->toAlgorithmArray();
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to load benchmarks: ' . $e->getMessage());
        }

        return [
            'conversion_rate' => 20,
            'churn_rate' => 5,
            'cac_ltv_ratio' => 3,
            'repeat_purchase_rate' => 25,
        ];
    }

    /**
     * Generate sales insights
     */
    protected function generateSalesInsights(array $metrics, array $trends): void
    {
        $sales = $metrics['sales'] ?? [];

        // Total sales insight
        if (isset($sales['total_sales'])) {
            $dailyAvg = $sales['daily_avg_sales'] ?? 0;

            if ($dailyAvg >= 5) {
                $this->addInsight(
                    'Kunlik o\'rtacha ' . round($dailyAvg, 1) . ' ta sotuv amalga oshirilmoqda',
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_SALES
                );
            } elseif ($dailyAvg > 0 && $dailyAvg < 2) {
                $this->addInsight(
                    'Sotuvlar kam - kunlik o\'rtacha ' . round($dailyAvg, 1) . ' ta',
                    self::TYPE_WARNING,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_SALES
                );
                $this->addRecommendation(
                    'Sotuvlarni oshirish',
                    'Lidlar sonini ko\'paytiring yoki konversiyani yaxshilang',
                    self::PRIORITY_HIGH
                );
            }
        }

        // Repeat sales rate
        if (isset($sales['repeat_rate'])) {
            $repeatRate = $sales['repeat_rate'];
            $benchmark = $this->benchmarks['repeat_purchase_rate'] ?? 25;

            if ($repeatRate >= $benchmark) {
                $this->addInsight(
                    sprintf('Qayta sotuvlar %d%% - bu yaxshi ko\'rsatkich', round($repeatRate)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_SALES
                );
            } elseif ($repeatRate < $benchmark * 0.7) {
                $this->addInsight(
                    sprintf('Qayta sotuvlar %d%% - sanoat o\'rtachasidan past (%d%%)', round($repeatRate), round($benchmark)),
                    self::TYPE_WARNING,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_SALES
                );
                $this->addRecommendation(
                    'Qayta sotuvlarni oshiring',
                    'Mijozlar bilan aloqani mustahkamlang, bonus dasturlarini joriy qiling',
                    self::PRIORITY_MEDIUM
                );
            }
        }

        // Average check
        if (isset($sales['avg_check'])) {
            $avgCheck = $sales['avg_check'];
            if ($avgCheck > 0) {
                $this->addInsight(
                    sprintf('O\'rtacha chek: %s UZS', number_format($avgCheck, 0, '.', ' ')),
                    self::TYPE_NEUTRAL,
                    self::PRIORITY_LOW,
                    self::CATEGORY_SALES
                );
            }
        }
    }

    /**
     * Generate marketing insights
     */
    protected function generateMarketingInsights(array $metrics, array $trends): void
    {
        $marketing = $metrics['marketing'] ?? [];

        // Lead cost insight
        if (isset($marketing['lead_cost']) && $marketing['lead_cost'] > 0) {
            $leadCost = $marketing['lead_cost'];

            if ($leadCost > 50000) {
                $this->addInsight(
                    sprintf('Lid narxi yuqori: %s UZS', number_format($leadCost, 0, '.', ' ')),
                    self::TYPE_WARNING,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_MARKETING
                );
                $this->addRecommendation(
                    'Lid narxini kamaytiring',
                    'Reklama kanallarini optimallashtiring, targeting ni yaxshilang',
                    self::PRIORITY_HIGH
                );
            } elseif ($leadCost < 20000) {
                $this->addInsight(
                    sprintf('Lid narxi optimal: %s UZS', number_format($leadCost, 0, '.', ' ')),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_MARKETING
                );
            }
        }

        // Conversion rate insight
        if (isset($marketing['conversion_rate'])) {
            $conversion = $marketing['conversion_rate'];
            $benchmark = $this->benchmarks['conversion_rate'] ?? 20;

            if ($conversion >= $benchmark) {
                $this->addInsight(
                    sprintf('Konversiya %d%% - sanoat o\'rtachasidan yuqori', round($conversion)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_MARKETING
                );
            } elseif ($conversion > 0 && $conversion < $benchmark * 0.5) {
                $this->addInsight(
                    sprintf('Konversiya past: %d%% (sanoat o\'rtachasi: %d%%)', round($conversion), round($benchmark)),
                    self::TYPE_NEGATIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_MARKETING
                );
                $this->addRecommendation(
                    'Konversiyani oshiring',
                    'Sotuvchilarni o\'qiting, taklifni yaxshilang, follow-up jarayonini tuzating',
                    self::PRIORITY_HIGH
                );
            }
        }

        // Channel performance
        if (!empty($marketing['channel_breakdown'])) {
            $bestChannel = null;
            $bestConversion = 0;

            foreach ($marketing['channel_breakdown'] as $channel) {
                if ($channel['conversion'] > $bestConversion && $channel['leads'] >= 5) {
                    $bestConversion = $channel['conversion'];
                    $bestChannel = $channel;
                }
            }

            if ($bestChannel) {
                $this->addInsight(
                    sprintf('Eng samarali kanal: %s (%d%% konversiya)', $bestChannel['name'], round($bestConversion)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_MARKETING
                );
            }
        }
    }

    /**
     * Generate financial insights
     */
    protected function generateFinancialInsights(array $metrics, array $trends): void
    {
        $financial = $metrics['financial'] ?? [];

        // ROI insight
        if (isset($financial['roi'])) {
            $roi = $financial['roi'];

            if ($roi >= 200) {
                $this->addInsight(
                    sprintf('ROI ajoyib: %d%%', round($roi)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_FINANCIAL
                );
            } elseif ($roi >= 100) {
                $this->addInsight(
                    sprintf('ROI yaxshi: %d%%', round($roi)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_FINANCIAL
                );
            } elseif ($roi > 0 && $roi < 50) {
                $this->addInsight(
                    sprintf('ROI past: %d%%', round($roi)),
                    self::TYPE_WARNING,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_FINANCIAL
                );
                $this->addRecommendation(
                    'ROI ni yaxshilang',
                    'Reklama xarajatlarini optimallashtiring yoki o\'rtacha chekni oshiring',
                    self::PRIORITY_HIGH
                );
            } elseif ($roi <= 0) {
                $this->addInsight(
                    'Reklama zarar keltirmoqda (ROI salbiy)',
                    self::TYPE_NEGATIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_FINANCIAL
                );
                $this->addRecommendation(
                    'Zudlik bilan reklama strategiyasini qayta ko\'ring',
                    'Samarasiz kanallarni to\'xtating, maqsadli auditoriyani qayta aniqlang',
                    self::PRIORITY_HIGH
                );
            }
        }

        // LTV/CAC ratio
        if (isset($financial['ltv_cac_ratio'])) {
            $ratio = $financial['ltv_cac_ratio'];
            $benchmark = $this->benchmarks['cac_ltv_ratio'] ?? 3;

            if ($ratio >= $benchmark) {
                $this->addInsight(
                    sprintf('LTV/CAC nisbati yaxshi: %.1fx', $ratio),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_FINANCIAL
                );
            } elseif ($ratio > 0 && $ratio < 2) {
                $this->addInsight(
                    sprintf('LTV/CAC nisbati past: %.1fx (tavsiya: 3x+)', $ratio),
                    self::TYPE_WARNING,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_FINANCIAL
                );
                $this->addRecommendation(
                    'Mijoz qiymatini oshiring',
                    'Qayta sotuvlarni ko\'paytiring yoki mijoz olish xarajatini kamaytiring',
                    self::PRIORITY_HIGH
                );
            }
        }

        // ROAS
        if (isset($financial['roas'])) {
            $roas = $financial['roas'];

            if ($roas >= 4) {
                $this->addInsight(
                    sprintf('ROAS ajoyib: %.1fx', $roas),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_FINANCIAL
                );
            } elseif ($roas > 0 && $roas < 2) {
                $this->addInsight(
                    sprintf('ROAS past: %.1fx (minimum 2x tavsiya)', $roas),
                    self::TYPE_WARNING,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_FINANCIAL
                );
            }
        }
    }

    /**
     * Generate customer insights
     */
    protected function generateCustomerInsights(array $metrics, array $trends): void
    {
        $customer = $metrics['customer'] ?? [];

        // New customers
        if (isset($customer['new_customers']) && $customer['new_customers'] > 0) {
            $this->addInsight(
                sprintf('Bu davrda %d ta yangi mijoz qo\'shildi', $customer['new_customers']),
                self::TYPE_POSITIVE,
                self::PRIORITY_LOW,
                self::CATEGORY_CUSTOMER
            );
        }

        // Retention rate
        if (isset($customer['retention_rate'])) {
            $retention = $customer['retention_rate'];

            if ($retention >= 70) {
                $this->addInsight(
                    sprintf('Mijozlarni saqlab qolish %d%% - yaxshi ko\'rsatkich', round($retention)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_CUSTOMER
                );
            } elseif ($retention < 50) {
                $this->addInsight(
                    sprintf('Mijozlarni saqlab qolish past: %d%%', round($retention)),
                    self::TYPE_WARNING,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_CUSTOMER
                );
                $this->addRecommendation(
                    'Mijozlar sadoqatini oshiring',
                    'Sifatli xizmat ko\'rsating, feedback so\'rang, maxsus takliflar bering',
                    self::PRIORITY_HIGH
                );
            }
        }

        // Churn rate
        if (isset($customer['churn_rate'])) {
            $churn = $customer['churn_rate'];
            $benchmark = $this->benchmarks['churn_rate'] ?? 5;

            if ($churn > $benchmark * 1.5) {
                $this->addInsight(
                    sprintf('Mijozlar yo\'qotilishi yuqori: %d%% (norma: %d%%)', round($churn), round($benchmark)),
                    self::TYPE_NEGATIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_CUSTOMER
                );
            }
        }
    }

    /**
     * Generate efficiency insights
     */
    protected function generateEfficiencyInsights(array $metrics, array $trends): void
    {
        $efficiency = $metrics['efficiency'] ?? [];

        // Marketing efficiency
        if (isset($efficiency['marketing_efficiency'])) {
            $eff = $efficiency['marketing_efficiency'];

            if ($eff >= 5) {
                $this->addInsight(
                    sprintf('Marketing samaradorligi yuqori: %.1fx', $eff),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_EFFICIENCY
                );
            } elseif ($eff < 2) {
                $this->addInsight(
                    sprintf('Marketing samaradorligi past: %.1fx', $eff),
                    self::TYPE_WARNING,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_EFFICIENCY
                );
            }
        }

        // Revenue per lead
        if (isset($efficiency['revenue_per_lead']) && $efficiency['revenue_per_lead'] > 0) {
            $this->addInsight(
                sprintf('Har bir liddan o\'rtacha %s UZS daromad', number_format($efficiency['revenue_per_lead'], 0, '.', ' ')),
                self::TYPE_NEUTRAL,
                self::PRIORITY_LOW,
                self::CATEGORY_EFFICIENCY
            );
        }
    }

    /**
     * Generate trend-based insights
     */
    protected function generateTrendInsights(array $trends): void
    {
        if (!($trends['has_data'] ?? false)) return;

        // Sales trend
        if (isset($trends['sales_trend'])) {
            $salesTrend = $trends['sales_trend'];
            $change = $salesTrend['change_percent'] ?? 0;

            if ($change > 10) {
                $this->addInsight(
                    sprintf('Sotuvlar %d%% o\'sdi (trend: %s)', round($change), $salesTrend['direction_label']),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_SALES
                );
            } elseif ($change < -10) {
                $this->addInsight(
                    sprintf('Sotuvlar %d%% pasaydi', abs(round($change))),
                    self::TYPE_NEGATIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_SALES
                );
                $this->addRecommendation(
                    'Sotuvlar pasayishini to\'xtating',
                    'Sabablari tahlil qiling: mavsumiylik, raqobat, sifat muammolari',
                    self::PRIORITY_HIGH
                );
            }
        }

        // Comparisons (WoW, MoM)
        if (isset($trends['comparisons'])) {
            $wow = $trends['comparisons']['wow'] ?? [];
            $mom = $trends['comparisons']['mom'] ?? [];

            // Week over week revenue change
            if (isset($wow['revenue']['change_percent'])) {
                $wowChange = $wow['revenue']['change_percent'];
                if (abs($wowChange) > 20) {
                    $direction = $wowChange > 0 ? 'o\'sdi' : 'pasaydi';
                    $type = $wowChange > 0 ? self::TYPE_POSITIVE : self::TYPE_NEGATIVE;
                    $this->addInsight(
                        sprintf('Haftalik daromad %d%% %s', abs(round($wowChange)), $direction),
                        $type,
                        self::PRIORITY_MEDIUM,
                        self::CATEGORY_FINANCIAL
                    );
                }
            }

            // Month over month comparison
            if (isset($mom['sales']['change_percent'])) {
                $momChange = $mom['sales']['change_percent'];
                if (abs($momChange) > 15) {
                    $direction = $momChange > 0 ? 'o\'sdi' : 'pasaydi';
                    $type = $momChange > 0 ? self::TYPE_POSITIVE : self::TYPE_NEGATIVE;
                    $this->addInsight(
                        sprintf('Oylik sotuvlar %d%% %s (o\'tgan oyga nisbatan)', abs(round($momChange)), $direction),
                        $type,
                        self::PRIORITY_MEDIUM,
                        self::CATEGORY_SALES
                    );
                }
            }
        }
    }

    /**
     * Generate anomaly insights
     */
    protected function generateAnomalyInsights(array $trends): void
    {
        $anomalies = $trends['anomalies'] ?? [];

        foreach ($anomalies as $anomaly) {
            $type = $anomaly['type'] === 'spike' ? self::TYPE_WARNING : self::TYPE_NEGATIVE;
            $priority = $anomaly['severity'] === 'high' ? self::PRIORITY_HIGH : self::PRIORITY_MEDIUM;

            $this->addInsight(
                sprintf('%s: %s', $anomaly['date'], $anomaly['message']),
                $type,
                $priority,
                self::CATEGORY_SALES
            );
        }
    }

    /**
     * Generate KPI progress insights
     */
    protected function generateKpiProgressInsights(array $metrics): void
    {
        $kpiProgress = $metrics['kpi_progress'] ?? [];

        if (!($kpiProgress['has_plan'] ?? false)) return;

        // Sales progress
        if (isset($kpiProgress['sales'])) {
            $sales = $kpiProgress['sales'];
            $expected = $kpiProgress['expected_progress'] ?? 0;

            if ($sales['status'] === 'critical') {
                $this->addInsight(
                    sprintf('Sotuvlar rejadan %d%% orqada', round($expected - $sales['progress'])),
                    self::TYPE_NEGATIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_SALES
                );
                $this->addRecommendation(
                    'KPI rejasini bajarish uchun harakat qiling',
                    sprintf('Qolgan kunlarda kunlik %d ta sotuv kerak', ceil(($sales['planned'] - $sales['actual']) / max(1, 30 - round($expected * 30 / 100)))),
                    self::PRIORITY_HIGH
                );
            } elseif ($sales['status'] === 'excellent') {
                $this->addInsight(
                    sprintf('Sotuvlar rejadan %d%% oldinda', round($sales['progress'] - $expected)),
                    self::TYPE_POSITIVE,
                    self::PRIORITY_MEDIUM,
                    self::CATEGORY_SALES
                );
            }
        }

        // Revenue progress
        if (isset($kpiProgress['revenue'])) {
            $revenue = $kpiProgress['revenue'];
            if ($revenue['status'] === 'critical') {
                $this->addInsight(
                    'Daromad rejasi xavf ostida',
                    self::TYPE_NEGATIVE,
                    self::PRIORITY_HIGH,
                    self::CATEGORY_FINANCIAL
                );
            }
        }
    }

    /**
     * Add insight to collection
     */
    protected function addInsight(string $message, string $type, string $priority, string $category): void
    {
        $this->insights[] = [
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'category' => $category,
            'icon' => $this->getInsightIcon($type),
            'color' => $this->getInsightColor($type),
        ];
    }

    /**
     * Add recommendation to collection
     */
    protected function addRecommendation(string $title, string $description, string $priority): void
    {
        $this->recommendations[] = [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'icon' => $this->getRecommendationIcon($priority),
        ];
    }

    /**
     * Sort insights by priority
     */
    protected function sortInsights(): void
    {
        $priorityOrder = [
            self::PRIORITY_HIGH => 1,
            self::PRIORITY_MEDIUM => 2,
            self::PRIORITY_LOW => 3,
        ];

        usort($this->insights, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['priority']] ?? 99) <=> ($priorityOrder[$b['priority']] ?? 99);
        });

        usort($this->recommendations, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['priority']] ?? 99) <=> ($priorityOrder[$b['priority']] ?? 99);
        });
    }

    /**
     * Generate summary
     */
    protected function generateSummary(): array
    {
        $positive = count(array_filter($this->insights, fn($i) => $i['type'] === self::TYPE_POSITIVE));
        $negative = count(array_filter($this->insights, fn($i) => $i['type'] === self::TYPE_NEGATIVE));
        $warnings = count(array_filter($this->insights, fn($i) => $i['type'] === self::TYPE_WARNING));

        $sentiment = match (true) {
            $positive > $negative + $warnings => 'positive',
            $negative > $positive => 'negative',
            default => 'neutral',
        };

        return [
            'total_insights' => count($this->insights),
            'positive_count' => $positive,
            'negative_count' => $negative,
            'warning_count' => $warnings,
            'recommendations_count' => count($this->recommendations),
            'sentiment' => $sentiment,
            'sentiment_label' => match ($sentiment) {
                'positive' => 'Ijobiy',
                'negative' => 'Salbiy',
                default => 'Neytral',
            },
        ];
    }

    /**
     * Get insight icon
     */
    protected function getInsightIcon(string $type): string
    {
        return match ($type) {
            self::TYPE_POSITIVE => '✅',
            self::TYPE_NEGATIVE => '❌',
            self::TYPE_WARNING => '⚠️',
            default => 'ℹ️',
        };
    }

    /**
     * Get insight color
     */
    protected function getInsightColor(string $type): string
    {
        return match ($type) {
            self::TYPE_POSITIVE => 'green',
            self::TYPE_NEGATIVE => 'red',
            self::TYPE_WARNING => 'yellow',
            default => 'blue',
        };
    }

    /**
     * Get recommendation icon
     */
    protected function getRecommendationIcon(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_HIGH => '🔴',
            self::PRIORITY_MEDIUM => '🟡',
            self::PRIORITY_LOW => '🟢',
            default => '⚪',
        };
    }
}
