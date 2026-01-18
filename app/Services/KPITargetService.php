<?php

namespace App\Services;

use App\Models\AnnualStrategy;
use App\Models\Business;
use App\Models\KpiTarget;
use App\Models\MonthlyPlan;
use App\Models\QuarterlyPlan;
use App\Models\WeeklyPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KPITargetService
{
    /**
     * Default KPIs to track for any business
     */
    private const DEFAULT_KPIS = [
        ['key' => 'revenue', 'name' => 'Daromad', 'category' => 'revenue', 'unit' => 'sum'],
        ['key' => 'leads', 'name' => 'Yangi lidlar', 'category' => 'marketing', 'unit' => 'count'],
        ['key' => 'customers', 'name' => 'Yangi mijozlar', 'category' => 'sales', 'unit' => 'count'],
        ['key' => 'conversion_rate', 'name' => 'Konversiya darajasi', 'category' => 'sales', 'unit' => '%'],
        ['key' => 'posts', 'name' => 'Kontentlar soni', 'category' => 'content', 'unit' => 'count'],
        ['key' => 'engagement_rate', 'name' => 'Faollik darajasi', 'category' => 'content', 'unit' => '%'],
        ['key' => 'followers', 'name' => 'Obunachilar', 'category' => 'marketing', 'unit' => 'count'],
        ['key' => 'website_traffic', 'name' => 'Sayt trafigi', 'category' => 'marketing', 'unit' => 'count'],
        ['key' => 'cpl', 'name' => 'Lid narxi', 'category' => 'marketing', 'unit' => 'sum'],
        ['key' => 'cac', 'name' => 'Mijoz olish narxi', 'category' => 'sales', 'unit' => 'sum'],
        ['key' => 'retention_rate', 'name' => 'Mijoz saqlash', 'category' => 'customer', 'unit' => '%'],
        ['key' => 'nps', 'name' => 'NPS ball', 'category' => 'customer', 'unit' => 'count'],
    ];

    /**
     * Create KPI targets from annual strategy
     */
    public function createKPIsFromStrategy(AnnualStrategy $annual): Collection
    {
        $kpis = collect();

        // Create annual KPIs
        $annualKpis = $this->createAnnualKPIs($annual);
        $kpis = $kpis->merge($annualKpis);

        // Create quarterly KPIs for each quarter
        foreach ($annual->quarterlyPlans as $quarterly) {
            $quarterlyKpis = $this->createQuarterlyKPIs($quarterly, $annualKpis);
            $kpis = $kpis->merge($quarterlyKpis);
        }

        return $kpis;
    }

    /**
     * Create annual KPI targets
     */
    public function createAnnualKPIs(AnnualStrategy $annual): Collection
    {
        $kpis = collect();

        foreach (self::DEFAULT_KPIS as $kpiDef) {
            $target = $this->calculateAnnualTarget($annual, $kpiDef['key']);

            if ($target !== null) {
                $kpi = KpiTarget::create([
                    'uuid' => Str::uuid(),
                    'business_id' => $annual->business_id,
                    'period_type' => 'annual',
                    'annual_strategy_id' => $annual->id,
                    'year' => $annual->year,
                    'kpi_name' => $kpiDef['name'],
                    'kpi_key' => $kpiDef['key'],
                    'category' => $kpiDef['category'],
                    'target_value' => $target,
                    'minimum_value' => $target * 0.8,
                    'stretch_value' => $target * 1.2,
                    'unit' => $kpiDef['unit'],
                    'status' => 'not_started',
                    'enable_alerts' => true,
                    'alert_threshold_percent' => 80,
                ]);

                $kpis->push($kpi);
            }
        }

        return $kpis;
    }

    /**
     * Create quarterly KPI targets
     */
    public function createQuarterlyKPIs(QuarterlyPlan $quarterly, ?Collection $annualKpis = null): Collection
    {
        $kpis = collect();

        // If annual KPIs provided, break them down
        if ($annualKpis) {
            foreach ($annualKpis as $annualKpi) {
                $quarterTarget = $annualKpi->target_value / 4;

                $kpi = KpiTarget::create([
                    'uuid' => Str::uuid(),
                    'business_id' => $quarterly->business_id,
                    'period_type' => 'quarterly',
                    'annual_strategy_id' => $quarterly->annual_strategy_id,
                    'quarterly_plan_id' => $quarterly->id,
                    'year' => $quarterly->year,
                    'quarter' => $quarterly->quarter,
                    'kpi_name' => $annualKpi->kpi_name,
                    'kpi_key' => $annualKpi->kpi_key,
                    'category' => $annualKpi->category,
                    'target_value' => $quarterTarget,
                    'minimum_value' => $quarterTarget * 0.8,
                    'stretch_value' => $quarterTarget * 1.2,
                    'unit' => $annualKpi->unit,
                    'status' => 'not_started',
                    'enable_alerts' => true,
                    'alert_threshold_percent' => 80,
                ]);

                $kpis->push($kpi);
            }
        } else {
            // Create from scratch
            foreach (self::DEFAULT_KPIS as $kpiDef) {
                $target = $this->calculateQuarterlyTarget($quarterly, $kpiDef['key']);

                if ($target !== null) {
                    $kpi = KpiTarget::create([
                        'uuid' => Str::uuid(),
                        'business_id' => $quarterly->business_id,
                        'period_type' => 'quarterly',
                        'quarterly_plan_id' => $quarterly->id,
                        'year' => $quarterly->year,
                        'quarter' => $quarterly->quarter,
                        'kpi_name' => $kpiDef['name'],
                        'kpi_key' => $kpiDef['key'],
                        'category' => $kpiDef['category'],
                        'target_value' => $target,
                        'minimum_value' => $target * 0.8,
                        'stretch_value' => $target * 1.2,
                        'unit' => $kpiDef['unit'],
                        'status' => 'not_started',
                        'enable_alerts' => true,
                        'alert_threshold_percent' => 80,
                    ]);

                    $kpis->push($kpi);
                }
            }
        }

        return $kpis;
    }

    /**
     * Create monthly KPI targets
     */
    public function createMonthlyKPIs(MonthlyPlan $monthly): Collection
    {
        $kpis = collect();

        // Get quarterly KPIs to break down
        $quarterlyKpis = KpiTarget::where('quarterly_plan_id', $monthly->quarterly_plan_id)
            ->where('period_type', 'quarterly')
            ->get();

        foreach ($quarterlyKpis as $quarterlyKpi) {
            $monthTarget = $quarterlyKpi->target_value / 3;

            $kpi = KpiTarget::create([
                'uuid' => Str::uuid(),
                'business_id' => $monthly->business_id,
                'period_type' => 'monthly',
                'quarterly_plan_id' => $monthly->quarterly_plan_id,
                'monthly_plan_id' => $monthly->id,
                'year' => $monthly->year,
                'month' => $monthly->month,
                'kpi_name' => $quarterlyKpi->kpi_name,
                'kpi_key' => $quarterlyKpi->kpi_key,
                'category' => $quarterlyKpi->category,
                'target_value' => $monthTarget,
                'minimum_value' => $monthTarget * 0.8,
                'stretch_value' => $monthTarget * 1.2,
                'unit' => $quarterlyKpi->unit,
                'status' => 'not_started',
                'enable_alerts' => true,
                'alert_threshold_percent' => 80,
            ]);

            $kpis->push($kpi);
        }

        return $kpis;
    }

    /**
     * Create weekly KPI targets
     */
    public function createWeeklyKPIs(WeeklyPlan $weekly): Collection
    {
        $kpis = collect();

        // Get monthly KPIs to break down
        $monthlyKpis = KpiTarget::where('monthly_plan_id', $weekly->monthly_plan_id)
            ->where('period_type', 'monthly')
            ->get();

        foreach ($monthlyKpis as $monthlyKpi) {
            $weekTarget = $monthlyKpi->target_value / 4;

            $kpi = KpiTarget::create([
                'uuid' => Str::uuid(),
                'business_id' => $weekly->business_id,
                'period_type' => 'weekly',
                'monthly_plan_id' => $weekly->monthly_plan_id,
                'weekly_plan_id' => $weekly->id,
                'year' => $weekly->year,
                'month' => $weekly->month,
                'week' => $weekly->week_number,
                'kpi_name' => $monthlyKpi->kpi_name,
                'kpi_key' => $monthlyKpi->kpi_key,
                'category' => $monthlyKpi->category,
                'target_value' => $weekTarget,
                'minimum_value' => $weekTarget * 0.8,
                'stretch_value' => $weekTarget * 1.2,
                'unit' => $monthlyKpi->unit,
                'status' => 'not_started',
                'enable_alerts' => true,
                'alert_threshold_percent' => 80,
            ]);

            $kpis->push($kpi);
        }

        return $kpis;
    }

    /**
     * Update KPI value and recalculate status
     */
    public function updateKPIValue(KpiTarget $kpi, float $value): KpiTarget
    {
        $kpi->updateValue($value);

        return $kpi->fresh();
    }

    /**
     * Get KPIs summary for dashboard
     */
    public function getKPISummary(Business $business, string $periodType = 'monthly', ?int $year = null, ?int $period = null): array
    {
        $query = KpiTarget::where('business_id', $business->id)
            ->where('period_type', $periodType);

        if ($year) {
            $query->where('year', $year);
        }

        if ($period) {
            $periodColumn = match ($periodType) {
                'quarterly' => 'quarter',
                'monthly' => 'month',
                'weekly' => 'week',
                default => null,
            };

            if ($periodColumn) {
                $query->where($periodColumn, $period);
            }
        }

        $kpis = $query->get();

        return [
            'total' => $kpis->count(),
            'achieved' => $kpis->whereIn('status', ['achieved', 'exceeded'])->count(),
            'on_track' => $kpis->where('status', 'on_track')->count(),
            'at_risk' => $kpis->where('status', 'at_risk')->count(),
            'behind' => $kpis->where('status', 'behind')->count(),
            'avg_progress' => round($kpis->avg('progress_percent') ?? 0, 1),
            'by_category' => $kpis->groupBy('category')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg_progress' => round($group->avg('progress_percent') ?? 0, 1),
                ];
            }),
            'alerts' => $kpis->where('alert_triggered', true)->count(),
        ];
    }

    /**
     * Get KPIs that need attention
     */
    public function getKPIsNeedingAttention(Business $business, int $limit = 5): Collection
    {
        return KpiTarget::where('business_id', $business->id)
            ->whereIn('status', ['at_risk', 'behind'])
            ->orderByRaw("CASE status WHEN 'behind' THEN 1 WHEN 'at_risk' THEN 2 ELSE 3 END")
            ->orderBy('progress_percent', 'asc')
            ->limit($limit)
            ->get();
    }

    // Helper methods
    private function calculateAnnualTarget(AnnualStrategy $annual, string $kpiKey): ?float
    {
        return match ($kpiKey) {
            'revenue' => $annual->revenue_target,
            'leads' => $annual->lead_target ?? ($annual->customer_target ? $annual->customer_target * 10 : 1000),
            'customers' => $annual->customer_target ?? 100,
            'conversion_rate' => 10, // 10% target
            'posts' => 365, // 1 post per day
            'engagement_rate' => 5, // 5% engagement
            'followers' => 10000, // Default target
            'website_traffic' => 50000, // Annual visits
            'cpl' => $annual->cac_target ? $annual->cac_target / 10 : 50000,
            'cac' => $annual->cac_target ?? 500000,
            'retention_rate' => 80, // 80% retention
            'nps' => 50, // NPS score
            default => null,
        };
    }

    private function calculateQuarterlyTarget(QuarterlyPlan $quarterly, string $kpiKey): ?float
    {
        return match ($kpiKey) {
            'revenue' => $quarterly->revenue_target,
            'leads' => $quarterly->lead_target ?? 250,
            'customers' => $quarterly->customer_target ?? 25,
            'conversion_rate' => 10,
            'posts' => 90, // ~1 per day
            'engagement_rate' => 5,
            'followers' => 2500,
            'website_traffic' => 12500,
            'cpl' => 50000,
            'cac' => 500000,
            'retention_rate' => 80,
            'nps' => 50,
            default => null,
        };
    }
}
