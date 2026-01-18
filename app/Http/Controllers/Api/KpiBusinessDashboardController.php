<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Services\KPI\BusinessCategoryMapper;
use App\Services\KPI\IndustryKpiConfiguration;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KPI Business Dashboard Controller
 *
 * Professional UI/UX uchun industry-specific dashboard
 * Marketing va biznes samaradorligi uchun optimallashtirilgan
 */
class KpiBusinessDashboardController extends Controller
{
    /**
     * Get professional dashboard with table view
     * Industry-specific KPIs bilan
     */
    public function getDashboard(Request $request, int $businessId): JsonResponse
    {
        $business = Business::findOrFail($businessId);

        // AUTO-DETECT industry from business category/industry/type
        $industryCode = BusinessCategoryMapper::detectFromBusiness($business);

        // Get industry display name
        $industryName = BusinessCategoryMapper::getIndustryName($industryCode);
        $industryIcon = BusinessCategoryMapper::getIndustryIcon($industryCode);

        // Get industry-specific KPI configuration
        $industryKpis = IndustryKpiConfiguration::getIndustryKpis($industryCode);
        $industryConfig = (new \ReflectionClass(IndustryKpiConfiguration::class))
            ->getMethod('getIndustryConfiguration')
            ->invoke(null, $industryCode);

        // Date range
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $startDate = $request->input('start_date', Carbon::today()->subDays(30)->format('Y-m-d'));
        $period = $request->input('period', 'daily'); // daily, weekly, monthly

        // Get KPI data
        $kpiData = $this->getKpiData($businessId, $industryKpis, $startDate, $endDate);

        // Build dashboard response
        return response()->json([
            'success' => true,
            'data' => [
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'industry_name' => $industryName,
                    'industry_code' => $industryCode,
                    'industry_icon' => $industryIcon,
                    'category' => $business->category,
                    'original_industry' => $business->industry,
                ],

                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                    'period_type' => $period,
                ],

                // Summary cards - Yuqori qismda ko'rsatiladigan asosiy metrikalar
                'summary_cards' => $this->buildSummaryCards($kpiData, $industryKpis),

                // Table view - Barcha KPI'lar jadvali
                'kpi_table' => $this->buildKpiTable($kpiData, $industryKpis, $industryConfig['dashboard_sections']),

                // Trend data - Grafiklar uchun
                'trend_data' => $this->buildTrendData($businessId, $industryKpis, $startDate, $endDate),

                // Performance overview
                'performance_overview' => $this->buildPerformanceOverview($kpiData, $industryKpis),

                // Recommendations - AI-powered tavsiyalar
                'recommendations' => $this->buildRecommendations($kpiData, $industryKpis),

                '_meta' => [
                    'total_kpis' => count($industryKpis),
                    'data_completeness' => $this->calculateDataCompleteness($kpiData, $industryKpis),
                    'last_updated' => Carbon::now()->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Build summary cards for top metrics
     */
    protected function buildSummaryCards(array $kpiData, array $industryKpis): array
    {
        $cards = [];
        $topKpis = array_slice($industryKpis, 0, 4); // Eng muhim 4 ta KPI

        foreach ($topKpis as $kpi) {
            if (! $kpi) {
                continue;
            }

            $code = $kpi['code'];
            $data = $kpiData[$code] ?? null;

            if (! $data) {
                $cards[] = [
                    'kpi_code' => $code,
                    'name' => $kpi['name'],
                    'icon' => $kpi['icon'],
                    'value' => null,
                    'status' => 'no_data',
                    'message' => 'Ma\'lumot yo\'q',
                ];

                continue;
            }

            $actual = $data['current_value'];
            $target = $data['target_value'];
            $previous = $data['previous_value'];

            // Calculate change
            $change = 0;
            $changePercent = 0;
            if ($previous > 0) {
                $change = $actual - $previous;
                $changePercent = (($actual - $previous) / $previous) * 100;
            }

            $cards[] = [
                'kpi_code' => $code,
                'name' => $kpi['name'],
                'name_en' => $kpi['name_en'],
                'icon' => $kpi['icon'],
                'color' => $kpi['color'],

                // Current value
                'value' => $this->formatValue($actual, $kpi['format']),
                'value_raw' => $actual,

                // Target
                'target' => $this->formatValue($target, $kpi['format']),
                'target_raw' => $target,

                // Performance
                'performance_percent' => $target > 0 ? round(($actual / $target) * 100, 1) : 100,
                'performance_status' => IndustryKpiConfiguration::getPerformanceStatus($actual, $target, $kpi['good_direction']),
                'performance_color' => IndustryKpiConfiguration::getPerformanceColor($actual, $target, $kpi['good_direction']),

                // Change from previous period
                'change' => $this->formatValue($change, $kpi['format']),
                'change_percent' => round($changePercent, 1),
                'change_direction' => $change >= 0 ? 'up' : 'down',
                'change_is_good' => $this->isChangeGood($change, $kpi['good_direction']),

                // Display metadata
                'unit' => $kpi['unit'],
                'format' => $kpi['format'],
                'description' => $kpi['description'],
            ];
        }

        return $cards;
    }

    /**
     * Build KPI table with sections
     */
    protected function buildKpiTable(array $kpiData, array $industryKpis, array $sections): array
    {
        $table = [
            'sections' => [],
        ];

        foreach ($sections as $sectionName => $kpiCodes) {
            $sectionRows = [];

            foreach ($kpiCodes as $code) {
                $kpi = $this->findKpiByCode($industryKpis, $code);
                if (! $kpi) {
                    continue;
                }

                $data = $kpiData[$code] ?? null;

                if (! $data) {
                    $sectionRows[] = [
                        'kpi_code' => $code,
                        'name' => $kpi['name'],
                        'icon' => $kpi['icon'],
                        'current' => '-',
                        'target' => '-',
                        'status' => 'no_data',
                        'trend' => [],
                        'action' => 'Ma\'lumot kiritish',
                    ];

                    continue;
                }

                $actual = $data['current_value'];
                $target = $data['target_value'];

                $sectionRows[] = [
                    'kpi_code' => $code,
                    'name' => $kpi['name'],
                    'name_en' => $kpi['name_en'],
                    'icon' => $kpi['icon'],
                    'category' => $kpi['category'],

                    // Values
                    'current' => [
                        'value' => $this->formatValue($actual, $kpi['format']),
                        'raw' => $actual,
                    ],

                    'target' => [
                        'value' => $this->formatValue($target, $kpi['format']),
                        'raw' => $target,
                    ],

                    'previous' => [
                        'value' => $this->formatValue($data['previous_value'], $kpi['format']),
                        'raw' => $data['previous_value'],
                    ],

                    // Performance
                    'performance' => [
                        'percent' => $target > 0 ? round(($actual / $target) * 100, 1) : 100,
                        'status' => IndustryKpiConfiguration::getPerformanceStatus($actual, $target, $kpi['good_direction']),
                        'color' => IndustryKpiConfiguration::getPerformanceColor($actual, $target, $kpi['good_direction']),
                        'label' => $this->getPerformanceLabel($actual, $target, $kpi['good_direction']),
                    ],

                    // Trend (last 7 days sparkline data)
                    'trend' => $data['trend'] ?? [],
                    'trend_direction' => $this->getTrendDirection($data['trend'] ?? []),

                    // Meta
                    'unit' => $kpi['unit'],
                    'format' => $kpi['format'],
                    'description' => $kpi['description'],
                    'good_direction' => $kpi['good_direction'],
                ];
            }

            $table['sections'][] = [
                'name' => $sectionName,
                'rows' => $sectionRows,
                'row_count' => count($sectionRows),
            ];
        }

        return $table;
    }

    /**
     * Build trend data for charts
     */
    protected function buildTrendData(int $businessId, array $industryKpis, string $startDate, string $endDate): array
    {
        $trends = [];

        foreach ($industryKpis as $kpi) {
            if (! $kpi) {
                continue;
            }

            $code = $kpi['code'];

            // Get daily data for period
            $dailyData = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $code)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get(['date', 'actual_value', 'target_value'])
                ->map(function ($item) use ($kpi) {
                    return [
                        'date' => $item->date,
                        'actual' => $item->actual_value,
                        'target' => $item->target_value,
                        'formatted_actual' => $this->formatValue($item->actual_value, $kpi['format']),
                        'formatted_target' => $this->formatValue($item->target_value, $kpi['format']),
                    ];
                });

            $trends[$code] = [
                'kpi_name' => $kpi['name'],
                'icon' => $kpi['icon'],
                'color' => $kpi['color'],
                'data' => $dailyData,
                'data_points' => $dailyData->count(),
            ];
        }

        return $trends;
    }

    /**
     * Build performance overview
     */
    protected function buildPerformanceOverview(array $kpiData, array $industryKpis): array
    {
        $totalKpis = count($industryKpis);
        $achieved = 0;
        $onTrack = 0;
        $needsAttention = 0;
        $noData = 0;

        foreach ($industryKpis as $kpi) {
            if (! $kpi) {
                continue;
            }

            $code = $kpi['code'];
            $data = $kpiData[$code] ?? null;

            if (! $data) {
                $noData++;

                continue;
            }

            $actual = $data['current_value'];
            $target = $data['target_value'];

            if ($target == 0) {
                $onTrack++;

                continue;
            }

            $performance = ($actual / $target) * 100;

            if ($kpi['good_direction'] === 'up') {
                if ($performance >= 100) {
                    $achieved++;
                } elseif ($performance >= 80) {
                    $onTrack++;
                } else {
                    $needsAttention++;
                }
            } else {
                if ($performance <= 100) {
                    $achieved++;
                } elseif ($performance <= 120) {
                    $onTrack++;
                } else {
                    $needsAttention++;
                }
            }
        }

        return [
            'total_kpis' => $totalKpis,
            'achieved' => $achieved,
            'on_track' => $onTrack,
            'needs_attention' => $needsAttention,
            'no_data' => $noData,
            'achievement_rate' => $totalKpis > 0 ? round(($achieved / $totalKpis) * 100, 1) : 0,
            'health_score' => $this->calculateHealthScore($achieved, $onTrack, $needsAttention, $totalKpis),
        ];
    }

    /**
     * Build AI-powered recommendations
     */
    protected function buildRecommendations(array $kpiData, array $industryKpis): array
    {
        $recommendations = [];

        foreach ($industryKpis as $kpi) {
            if (! $kpi) {
                continue;
            }

            $code = $kpi['code'];
            $data = $kpiData[$code] ?? null;

            if (! $data) {
                continue;
            }

            $actual = $data['current_value'];
            $target = $data['target_value'];

            if ($target == 0) {
                continue;
            }

            $performance = ($actual / $target) * 100;

            // Generate recommendation if needs attention
            if (($kpi['good_direction'] === 'up' && $performance < 80) ||
                ($kpi['good_direction'] === 'down' && $performance > 120)) {

                $recommendations[] = [
                    'kpi_code' => $code,
                    'kpi_name' => $kpi['name'],
                    'icon' => $kpi['icon'],
                    'priority' => $performance < 50 ? 'high' : 'medium',
                    'type' => 'improvement',
                    'title' => $this->getRecommendationTitle($kpi, $performance),
                    'message' => $this->getRecommendationMessage($kpi, $actual, $target, $performance),
                    'actions' => $this->getRecommendationActions($kpi, $performance),
                ];
            }
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

            return $priorityOrder[$a['priority']] - $priorityOrder[$b['priority']];
        });

        return array_slice($recommendations, 0, 5); // Top 5 recommendations
    }

    // ==================== HELPER METHODS ====================

    protected function getKpiData(int $businessId, array $industryKpis, string $startDate, string $endDate): array
    {
        $data = [];
        $endDateCarbon = Carbon::parse($endDate);
        $startDateCarbon = Carbon::parse($startDate);

        foreach ($industryKpis as $kpi) {
            if (! $kpi) {
                continue;
            }

            $code = $kpi['code'];

            // Current period value (average)
            $current = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $code)
                ->whereBetween('date', [$startDate, $endDate])
                ->avg('actual_value') ?? 0;

            // Target value
            $target = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $code)
                ->whereBetween('date', [$startDate, $endDate])
                ->avg('target_value') ?? 0;

            // Previous period (for comparison)
            $periodLength = $startDateCarbon->diffInDays($endDateCarbon);
            $previousStart = $startDateCarbon->copy()->subDays($periodLength);
            $previousEnd = $startDateCarbon->copy()->subDay();

            $previous = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $code)
                ->whereBetween('date', [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')])
                ->avg('actual_value') ?? 0;

            // Trend (last 7 days)
            $trendData = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $code)
                ->whereBetween('date', [$endDateCarbon->copy()->subDays(6)->format('Y-m-d'), $endDate])
                ->orderBy('date')
                ->pluck('actual_value')
                ->toArray();

            $data[$code] = [
                'current_value' => round($current, 2),
                'target_value' => round($target, 2),
                'previous_value' => round($previous, 2),
                'trend' => $trendData,
            ];
        }

        return $data;
    }

    protected function formatValue($value, string $format): string
    {
        if ($value === null) {
            return '-';
        }

        switch ($format) {
            case 'money':
                return number_format($value, 0, '.', ' ').' so\'m';

            case 'percent':
                return round($value, 1).'%';

            case 'number':
                return number_format($value, 0, '.', ' ');

            case 'decimal':
                return number_format($value, 2, '.', '');

            default:
                return (string) $value;
        }
    }

    protected function isChangeGood(float $change, string $goodDirection): bool
    {
        if ($change == 0) {
            return true;
        }

        return ($goodDirection === 'up' && $change > 0) ||
               ($goodDirection === 'down' && $change < 0);
    }

    protected function getTrendDirection(array $trend): string
    {
        if (count($trend) < 2) {
            return 'stable';
        }

        $first = array_slice($trend, 0, ceil(count($trend) / 2));
        $second = array_slice($trend, ceil(count($trend) / 2));

        $firstAvg = count($first) > 0 ? array_sum($first) / count($first) : 0;
        $secondAvg = count($second) > 0 ? array_sum($second) / count($second) : 0;

        if ($secondAvg > $firstAvg * 1.05) {
            return 'up';
        }
        if ($secondAvg < $firstAvg * 0.95) {
            return 'down';
        }

        return 'stable';
    }

    protected function findKpiByCode(array $industryKpis, string $code): ?array
    {
        foreach ($industryKpis as $kpi) {
            if ($kpi && $kpi['code'] === $code) {
                return $kpi;
            }
        }

        return null;
    }

    protected function calculateDataCompleteness(array $kpiData, array $industryKpis): float
    {
        $total = count($industryKpis);
        $filled = 0;

        foreach ($industryKpis as $kpi) {
            if (! $kpi) {
                continue;
            }
            $code = $kpi['code'];
            if (isset($kpiData[$code]) && $kpiData[$code]['current_value'] > 0) {
                $filled++;
            }
        }

        return $total > 0 ? round(($filled / $total) * 100, 1) : 0;
    }

    protected function calculateHealthScore(int $achieved, int $onTrack, int $needsAttention, int $total): int
    {
        if ($total == 0) {
            return 0;
        }

        $score = (($achieved * 100) + ($onTrack * 70) + ($needsAttention * 30)) / $total;

        return round($score);
    }

    protected function getPerformanceLabel(float $actual, float $target, string $direction): string
    {
        if ($target == 0) {
            return 'N/A';
        }

        $performance = ($actual / $target) * 100;

        if ($direction === 'up') {
            if ($performance >= 100) {
                return 'Ajoyib! 🎉';
            }
            if ($performance >= 80) {
                return 'Yaxshi';
            }
            if ($performance >= 50) {
                return 'O\'rtacha';
            }

            return 'Diqqat talab!';
        } else {
            if ($performance <= 100) {
                return 'Ajoyib! 🎉';
            }
            if ($performance <= 120) {
                return 'Yaxshi';
            }
            if ($performance <= 150) {
                return 'O\'rtacha';
            }

            return 'Diqqat talab!';
        }
    }

    protected function getRecommendationTitle(array $kpi, float $performance): string
    {
        $gap = abs(100 - $performance);

        if ($gap > 50) {
            return "{$kpi['name']} jiddiy yaxshilanishga muhtoj";
        } elseif ($gap > 30) {
            return "{$kpi['name']} ni oshirish kerak";
        } else {
            return "{$kpi['name']} maqsadga yaqinlashmoqda";
        }
    }

    protected function getRecommendationMessage(array $kpi, float $actual, float $target, float $performance): string
    {
        $gap = round(abs($target - $actual), 2);
        $actualFormatted = $this->formatValue($actual, $kpi['format']);
        $targetFormatted = $this->formatValue($target, $kpi['format']);

        return "Hozirgi natija: {$actualFormatted}. Maqsad: {$targetFormatted}. ".
               'Maqsadga erishish uchun yana '.$this->formatValue($gap, $kpi['format']).' kerak.';
    }

    protected function getRecommendationActions(array $kpi, float $performance): array
    {
        // Generic actions based on KPI category
        $actions = [];

        switch ($kpi['category']) {
            case 'financial':
                $actions = [
                    'Xarajatlarni tahlil qiling',
                    'Narxlarni qayta ko\'rib chiqing',
                    'Yangi daromad manbalari toping',
                ];
                break;

            case 'customer':
                $actions = [
                    'Mijozlar bilan bog\'laning',
                    'Feedback yig\'ing',
                    'Loyalty dasturini ishga tushiring',
                ];
                break;

            case 'marketing':
                $actions = [
                    'Kontent strategiyasini qayta ko\'ring',
                    'Reklama kampaniyasi boshlang',
                    'Ijtimoiy tarmoqlarda faollashtirilsin',
                ];
                break;

            default:
                $actions = [
                    'Jarayonlarni tahlil qiling',
                    'Maqsadlarni qayta belgilang',
                    'Muntazam monitoring qiling',
                ];
        }

        return $actions;
    }
}
