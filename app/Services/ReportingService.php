<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Business;
use App\Models\GeneratedReport;
use App\Models\KpiDailySnapshot;
use App\Models\ScheduledReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportingService
{
    public function generateReport(ScheduledReport $scheduledReport): ?GeneratedReport
    {
        $business = $scheduledReport->business;
        $startTime = microtime(true);

        try {
            $report = match ($scheduledReport->report_type) {
                'daily_brief' => $this->generateDailyBrief($business),
                'weekly_summary' => $this->generateWeeklySummary($business),
                'monthly_report' => $this->generateMonthlyReport($business),
                'quarterly_review' => $this->generateQuarterlyReview($business),
                default => null,
            };

            if ($report) {
                $report->update([
                    'scheduled_report_id' => $scheduledReport->id,
                    'generation_time_seconds' => round(microtime(true) - $startTime, 2),
                ]);

                // Update scheduled report
                $scheduledReport->update([
                    'last_generated_at' => now(),
                    'next_scheduled_at' => $scheduledReport->calculateNextScheduledAt(),
                ]);
            }

            return $report;
        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'scheduled_report_id' => $scheduledReport->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function generateDailyBrief(Business $business, ?Carbon $date = null): GeneratedReport
    {
        $date = $date ?? Carbon::today();
        $snapshot = $this->getSnapshot($business, $date);
        $yesterdaySnapshot = $this->getSnapshot($business, $date->copy()->subDay());

        $content = [
            'date' => $date->format('Y-m-d'),
            'kpis' => $this->buildKpiSection($snapshot, $yesterdaySnapshot),
            'alerts' => $this->getAlertsForPeriod($business, $date, $date),
            'insights' => $this->getInsightsForPeriod($business, $date, $date),
            'funnel' => $this->buildFunnelSection($snapshot),
        ];

        $highlights = $this->extractHighlights($content);
        $summary = $this->generateSummary('daily', $content);

        return GeneratedReport::create([
            'business_id' => $business->id,
            'report_type' => 'daily_brief',
            'title' => 'Kunlik Xulosa - '.$date->format('d.m.Y'),
            'period_start' => $date,
            'period_end' => $date,
            'content' => $content,
            'summary' => $summary,
            'highlights' => $highlights,
        ]);
    }

    public function generateWeeklySummary(Business $business, ?Carbon $endDate = null): GeneratedReport
    {
        $endDate = $endDate ?? Carbon::today();
        $startDate = $endDate->copy()->subDays(6);

        $snapshots = $this->getSnapshots($business, $startDate, $endDate);
        $previousWeekSnapshots = $this->getSnapshots(
            $business,
            $startDate->copy()->subWeek(),
            $endDate->copy()->subWeek()
        );

        $content = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary_kpis' => $this->buildWeeklySummaryKpis($snapshots, $previousWeekSnapshots),
            'daily_breakdown' => $this->buildDailyBreakdown($snapshots),
            'trends' => $this->buildTrendSection($snapshots),
            'alerts' => $this->getAlertsForPeriod($business, $startDate, $endDate),
            'insights' => $this->getInsightsForPeriod($business, $startDate, $endDate),
            'top_performers' => $this->identifyTopPerformers($snapshots),
            'areas_for_improvement' => $this->identifyAreasForImprovement($snapshots),
        ];

        $highlights = $this->extractHighlights($content);
        $summary = $this->generateSummary('weekly', $content);

        return GeneratedReport::create([
            'business_id' => $business->id,
            'report_type' => 'weekly_summary',
            'title' => 'Haftalik Hisobot - '.$startDate->format('d.m').' - '.$endDate->format('d.m.Y'),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'content' => $content,
            'summary' => $summary,
            'highlights' => $highlights,
        ]);
    }

    public function generateMonthlyReport(Business $business, ?Carbon $month = null): GeneratedReport
    {
        $month = $month ?? Carbon::today()->startOfMonth();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        if ($endDate->isFuture()) {
            $endDate = Carbon::today();
        }

        $snapshots = $this->getSnapshots($business, $startDate, $endDate);
        $previousMonthSnapshots = $this->getSnapshots(
            $business,
            $startDate->copy()->subMonth(),
            $endDate->copy()->subMonth()
        );

        $content = [
            'period' => [
                'month' => $month->format('F Y'),
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'executive_summary' => $this->buildExecutiveSummary($snapshots, $previousMonthSnapshots),
            'kpi_analysis' => $this->buildKpiAnalysis($snapshots, $previousMonthSnapshots),
            'weekly_comparison' => $this->buildWeeklyComparison($snapshots),
            'channel_performance' => $this->buildChannelPerformance($snapshots),
            'alerts_summary' => $this->buildAlertsSummary($business, $startDate, $endDate),
            'insights_summary' => $this->buildInsightsSummary($business, $startDate, $endDate),
            'recommendations' => $this->buildMonthlyRecommendations($snapshots),
        ];

        $highlights = $this->extractHighlights($content);
        $summary = $this->generateSummary('monthly', $content);

        return GeneratedReport::create([
            'business_id' => $business->id,
            'report_type' => 'monthly_report',
            'title' => 'Oylik Hisobot - '.$month->format('F Y'),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'content' => $content,
            'summary' => $summary,
            'highlights' => $highlights,
        ]);
    }

    public function generateQuarterlyReview(Business $business, ?int $quarter = null, ?int $year = null): GeneratedReport
    {
        $year = $year ?? Carbon::today()->year;
        $quarter = $quarter ?? Carbon::today()->quarter;

        $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        if ($endDate->isFuture()) {
            $endDate = Carbon::today();
        }

        $snapshots = $this->getSnapshots($business, $startDate, $endDate);
        $previousQuarterSnapshots = $this->getSnapshots(
            $business,
            $startDate->copy()->subMonths(3),
            $endDate->copy()->subMonths(3)
        );

        $content = [
            'period' => [
                'quarter' => "Q{$quarter} {$year}",
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'executive_summary' => $this->buildExecutiveSummary($snapshots, $previousQuarterSnapshots),
            'quarterly_kpis' => $this->buildQuarterlyKpis($snapshots, $previousQuarterSnapshots),
            'monthly_breakdown' => $this->buildMonthlyBreakdown($snapshots),
            'growth_analysis' => $this->buildGrowthAnalysis($snapshots, $previousQuarterSnapshots),
            'strategic_insights' => $this->buildStrategicInsights($snapshots),
            'next_quarter_recommendations' => $this->buildNextQuarterRecommendations($snapshots),
        ];

        $highlights = $this->extractHighlights($content);
        $summary = $this->generateSummary('quarterly', $content);

        return GeneratedReport::create([
            'business_id' => $business->id,
            'report_type' => 'quarterly_review',
            'title' => "Choraklik Tahlil - Q{$quarter} {$year}",
            'period_start' => $startDate,
            'period_end' => $endDate,
            'content' => $content,
            'summary' => $summary,
            'highlights' => $highlights,
        ]);
    }

    public function exportToPDF(GeneratedReport $report): string
    {
        $html = view('reports.pdf', [
            'report' => $report,
            'business' => $report->business,
        ])->render();

        $pdf = Pdf::loadHTML($html);

        $path = "reports/{$report->business_id}/{$report->id}.pdf";
        Storage::put($path, $pdf->output());

        $report->update(['pdf_path' => $path]);

        return $path;
    }

    public function exportToHTML(GeneratedReport $report): string
    {
        $html = view('reports.html', [
            'report' => $report,
            'business' => $report->business,
        ])->render();

        $path = "reports/{$report->business_id}/{$report->id}.html";
        Storage::put($path, $html);

        $report->update(['html_path' => $path]);

        return $path;
    }

    protected function getSnapshot(Business $business, Carbon $date): ?KpiDailySnapshot
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', $date->format('Y-m-d'))
            ->first();
    }

    protected function getSnapshots(Business $business, Carbon $start, Carbon $end): Collection
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->whereBetween('snapshot_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('snapshot_date')
            ->get();
    }

    protected function buildKpiSection(?KpiDailySnapshot $today, ?KpiDailySnapshot $yesterday): array
    {
        if (! $today) {
            return [];
        }

        $kpis = [
            'revenue' => [
                'label' => 'Daromad',
                'value' => $today->revenue_total ?? 0,
                'previous' => $yesterday?->revenue_total ?? 0,
            ],
            'leads' => [
                'label' => 'Lidlar',
                'value' => $today->leads_total ?? 0,
                'previous' => $yesterday?->leads_total ?? 0,
            ],
            'cac' => [
                'label' => 'CAC',
                'value' => $today->cac ?? 0,
                'previous' => $yesterday?->cac ?? 0,
            ],
            'roas' => [
                'label' => 'ROAS',
                'value' => $today->ad_roas ?? 0,
                'previous' => $yesterday?->ad_roas ?? 0,
            ],
        ];

        foreach ($kpis as $key => &$kpi) {
            if ($kpi['previous'] > 0) {
                $kpi['change'] = (($kpi['value'] - $kpi['previous']) / $kpi['previous']) * 100;
            } else {
                $kpi['change'] = null;
            }
        }

        return $kpis;
    }

    protected function buildFunnelSection(?KpiDailySnapshot $snapshot): array
    {
        if (! $snapshot) {
            return [];
        }

        return [
            ['stage' => 'Awareness', 'value' => $snapshot->funnel_awareness ?? 0],
            ['stage' => 'Interest', 'value' => $snapshot->funnel_interest ?? 0],
            ['stage' => 'Consideration', 'value' => $snapshot->funnel_consideration ?? 0],
            ['stage' => 'Intent', 'value' => $snapshot->funnel_intent ?? 0],
            ['stage' => 'Purchase', 'value' => $snapshot->funnel_purchase ?? 0],
        ];
    }

    protected function buildWeeklySummaryKpis(Collection $snapshots, Collection $previousWeek): array
    {
        $metrics = ['revenue_total', 'leads_total', 'cac', 'ad_roas', 'conversion_rate'];
        $result = [];

        foreach ($metrics as $metric) {
            $current = $snapshots->avg($metric) ?? 0;
            $previous = $previousWeek->avg($metric) ?? 0;

            $result[$metric] = [
                'current' => $current,
                'previous' => $previous,
                'change' => $previous > 0 ? (($current - $previous) / $previous) * 100 : null,
            ];
        }

        return $result;
    }

    protected function buildDailyBreakdown(Collection $snapshots): array
    {
        return $snapshots->map(fn ($s) => [
            'date' => $s->snapshot_date->format('Y-m-d'),
            'revenue' => $s->revenue_total,
            'leads' => $s->leads_total,
            'health_score' => $s->health_score,
        ])->toArray();
    }

    protected function buildTrendSection(Collection $snapshots): array
    {
        return [
            'revenue' => $snapshots->pluck('revenue_total', 'snapshot_date')->toArray(),
            'leads' => $snapshots->pluck('leads_total', 'snapshot_date')->toArray(),
        ];
    }

    protected function getAlertsForPeriod(Business $business, Carbon $start, Carbon $end): array
    {
        return Alert::where('business_id', $business->id)
            ->whereBetween('triggered_at', [$start, $end])
            ->orderBy('triggered_at', 'desc')
            ->get()
            ->map(fn ($a) => [
                'title' => $a->title,
                'severity' => $a->severity,
                'status' => $a->status,
                'triggered_at' => $a->triggered_at->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    protected function getInsightsForPeriod(Business $business, Carbon $start, Carbon $end): array
    {
        // AI Insights disabled - return empty array
        return [];
    }

    protected function identifyTopPerformers(Collection $snapshots): array
    {
        $best = $snapshots->sortByDesc('health_score')->first();

        if (! $best) {
            return [];
        }

        return [
            'best_day' => $best->snapshot_date->format('Y-m-d'),
            'health_score' => $best->health_score,
            'revenue' => $best->revenue_total,
        ];
    }

    protected function identifyAreasForImprovement(Collection $snapshots): array
    {
        $areas = [];
        $avgConversion = $snapshots->avg('conversion_rate');
        $avgRoas = $snapshots->avg('ad_roas');

        if ($avgConversion < 3) {
            $areas[] = [
                'area' => 'Konversiya',
                'current' => round($avgConversion, 2).'%',
                'target' => '3%+',
            ];
        }

        if ($avgRoas < 3) {
            $areas[] = [
                'area' => 'ROAS',
                'current' => round($avgRoas, 2).'x',
                'target' => '3x+',
            ];
        }

        return $areas;
    }

    protected function buildExecutiveSummary(Collection $current, Collection $previous): array
    {
        $currentRevenue = $current->sum('revenue_total');
        $previousRevenue = $previous->sum('revenue_total');

        return [
            'total_revenue' => $currentRevenue,
            'revenue_change' => $previousRevenue > 0
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
                : null,
            'total_leads' => $current->sum('leads_total'),
            'avg_health_score' => round($current->avg('health_score'), 1),
        ];
    }

    protected function buildKpiAnalysis(Collection $current, Collection $previous): array
    {
        return [
            'revenue' => [
                'total' => $current->sum('revenue_total'),
                'average' => $current->avg('revenue_total'),
                'max' => $current->max('revenue_total'),
                'min' => $current->min('revenue_total'),
            ],
            'leads' => [
                'total' => $current->sum('leads_total'),
                'average' => $current->avg('leads_total'),
            ],
        ];
    }

    protected function buildWeeklyComparison(Collection $snapshots): array
    {
        return $snapshots->groupBy(fn ($s) => $s->snapshot_date->weekOfMonth)
            ->map(fn ($week) => [
                'revenue' => $week->sum('revenue_total'),
                'leads' => $week->sum('leads_total'),
            ])
            ->toArray();
    }

    protected function buildChannelPerformance(Collection $snapshots): array
    {
        $last = $snapshots->last();
        if (! $last) {
            return [];
        }

        return [
            'instagram' => ['leads' => $last->leads_instagram ?? 0],
            'telegram' => ['leads' => $last->leads_telegram ?? 0],
            'facebook' => ['leads' => $last->leads_facebook ?? 0],
        ];
    }

    protected function buildAlertsSummary(Business $business, Carbon $start, Carbon $end): array
    {
        $alerts = Alert::where('business_id', $business->id)
            ->whereBetween('triggered_at', [$start, $end])
            ->get();

        return [
            'total' => $alerts->count(),
            'by_severity' => $alerts->groupBy('severity')->map->count()->toArray(),
            'resolved' => $alerts->where('status', 'resolved')->count(),
        ];
    }

    protected function buildInsightsSummary(Business $business, Carbon $start, Carbon $end): array
    {
        // AI Insights disabled - return empty summary
        return [
            'total' => 0,
            'by_type' => [],
            'acted_upon' => 0,
        ];
    }

    protected function buildMonthlyRecommendations(Collection $snapshots): array
    {
        $recommendations = [];
        $avgConversion = $snapshots->avg('conversion_rate');

        if ($avgConversion < 3) {
            $recommendations[] = 'Konversiya darajasini oshirish uchun savdo jarayonini optimallashtiring';
        }

        return $recommendations;
    }

    protected function buildQuarterlyKpis(Collection $current, Collection $previous): array
    {
        return [
            'revenue' => [
                'total' => $current->sum('revenue_total'),
                'vs_previous' => $this->calculateGrowth($current->sum('revenue_total'), $previous->sum('revenue_total')),
            ],
            'leads' => [
                'total' => $current->sum('leads_total'),
                'vs_previous' => $this->calculateGrowth($current->sum('leads_total'), $previous->sum('leads_total')),
            ],
        ];
    }

    protected function buildMonthlyBreakdown(Collection $snapshots): array
    {
        return $snapshots->groupBy(fn ($s) => $s->snapshot_date->format('Y-m'))
            ->map(fn ($month) => [
                'revenue' => $month->sum('revenue_total'),
                'leads' => $month->sum('leads_total'),
                'avg_health' => round($month->avg('health_score'), 1),
            ])
            ->toArray();
    }

    protected function buildGrowthAnalysis(Collection $current, Collection $previous): array
    {
        return [
            'revenue_growth' => $this->calculateGrowth(
                $current->sum('revenue_total'),
                $previous->sum('revenue_total')
            ),
            'lead_growth' => $this->calculateGrowth(
                $current->sum('leads_total'),
                $previous->sum('leads_total')
            ),
        ];
    }

    protected function buildStrategicInsights(Collection $snapshots): array
    {
        return [
            'best_performing_period' => $snapshots->sortByDesc('revenue_total')->first()?->snapshot_date->format('Y-m-d'),
            'consistency_score' => $this->calculateConsistencyScore($snapshots),
        ];
    }

    protected function buildNextQuarterRecommendations(Collection $snapshots): array
    {
        return [
            'Joriy o\'sish tendensiyasini davom ettiring',
            'Kam samarali kanallarni optimallashtiring',
            'Yangi bozor segmentlarini o\'rganing',
        ];
    }

    protected function calculateGrowth($current, $previous): ?float
    {
        if (! $previous || $previous == 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected function calculateConsistencyScore(Collection $snapshots): float
    {
        $revenues = $snapshots->pluck('revenue_total')->filter();
        if ($revenues->isEmpty()) {
            return 0;
        }

        $mean = $revenues->avg();
        $variance = $revenues->map(fn ($v) => pow($v - $mean, 2))->avg();
        $stdDev = sqrt($variance);

        // Lower coefficient of variation = higher consistency
        $cv = $mean > 0 ? ($stdDev / $mean) : 0;

        return max(0, min(100, 100 - ($cv * 100)));
    }

    protected function extractHighlights(array $content): array
    {
        $highlights = [];

        if (isset($content['kpis']['revenue']['change']) && $content['kpis']['revenue']['change'] > 10) {
            $highlights[] = sprintf('Daromad %.1f%% o\'sdi', $content['kpis']['revenue']['change']);
        }

        if (isset($content['alerts']) && count($content['alerts']) > 0) {
            $critical = collect($content['alerts'])->where('severity', 'critical')->count();
            if ($critical > 0) {
                $highlights[] = sprintf('%d ta kritik ogohlantirish', $critical);
            }
        }

        return $highlights;
    }

    protected function generateSummary(string $type, array $content): string
    {
        $summaries = [
            'daily' => 'Kunlik biznes ko\'rsatkichlari xulosasi.',
            'weekly' => 'Haftalik natijalar va tendensiyalar tahlili.',
            'monthly' => 'Oylik biznes faoliyati batafsil hisoboti.',
            'quarterly' => 'Choraklik strategik tahlil va tavsiyalar.',
        ];

        return $summaries[$type] ?? 'Biznes hisoboti.';
    }
}
