<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\GeneratedReport;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ReportGeneratorService
 *
 * Main orchestrator for report generation
 * Coordinates MetricsCalculator, TrendAnalyzer, and InsightEngine
 */
class ReportGeneratorService
{
    protected MetricsCalculatorService $metricsCalculator;
    protected TrendAnalyzerService $trendAnalyzer;
    protected InsightEngineService $insightEngine;
    protected HealthScoreService $healthScoreService;

    public function __construct(
        MetricsCalculatorService $metricsCalculator,
        TrendAnalyzerService $trendAnalyzer,
        InsightEngineService $insightEngine,
        HealthScoreService $healthScoreService
    ) {
        $this->metricsCalculator = $metricsCalculator;
        $this->trendAnalyzer = $trendAnalyzer;
        $this->insightEngine = $insightEngine;
        $this->healthScoreService = $healthScoreService;
    }

    /**
     * Generate a report for a business
     */
    public function generate(
        Business $business,
        Carbon $startDate,
        Carbon $endDate,
        string $type = 'manual',
        ?ReportTemplate $template = null,
        ?ReportSchedule $schedule = null
    ): GeneratedReport {
        $startTime = microtime(true);

        // Create report record
        $report = GeneratedReport::create([
            'business_id' => $business->id,
            'user_id' => auth()->id(),
            'report_schedule_id' => $schedule?->id,
            'report_template_id' => $template?->id,
            'title' => $this->generateTitle($business, $startDate, $endDate),
            'type' => $type,
            'category' => $template?->category ?? 'comprehensive',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'period_type' => $this->determinePeriodType($startDate, $endDate),
            'status' => GeneratedReport::STATUS_GENERATING,
            'language' => 'uz',
        ]);

        try {
            // Calculate metrics
            $metrics = $this->metricsCalculator->calculate($business, $startDate, $endDate);

            // Analyze trends
            $trends = $this->trendAnalyzer->analyze($business, $startDate, $endDate);

            // Generate insights
            $insightsData = $this->insightEngine->generate($business, $metrics, $trends);

            // Calculate health score
            $healthScore = $this->healthScoreService->calculate($business, $metrics, $trends);

            // Generate comparisons
            $comparisons = $this->generateComparisons($business, $startDate, $endDate, $metrics);

            // Generate text content
            $contentText = $this->generateTextContent($business, $metrics, $trends, $insightsData, $healthScore);

            // Generate HTML content
            $contentHtml = $this->generateHtmlContent($business, $metrics, $trends, $insightsData, $healthScore);

            // Calculate generation time
            $generationTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Update report with data
            $report->update([
                'metrics_data' => $metrics,
                'trends_data' => $trends,
                'insights' => $insightsData['insights'],
                'recommendations' => $insightsData['recommendations'],
                'comparisons' => $comparisons,
                'anomalies' => $trends['anomalies'] ?? [],
                'health_score' => $healthScore['score'],
                'health_breakdown' => $healthScore['breakdown'],
                'content_text' => $contentText,
                'content_html' => $contentHtml,
                'generation_time_ms' => $generationTimeMs,
                'status' => GeneratedReport::STATUS_COMPLETED,
            ]);

            // Increment template usage if used
            if ($template) {
                $template->incrementUsage();
            }

            Log::info("Report generated successfully", [
                'report_id' => $report->id,
                'business_id' => $business->id,
                'generation_time_ms' => $generationTimeMs,
            ]);

        } catch (\Exception $e) {
            Log::error("Report generation failed", [
                'report_id' => $report->id,
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            $report->markAsFailed($e->getMessage());
            throw $e;
        }

        return $report->fresh();
    }

    /**
     * Generate report from schedule
     */
    public function generateFromSchedule(ReportSchedule $schedule): GeneratedReport
    {
        $business = $schedule->business;

        // Determine period based on schedule configuration
        [$startDate, $endDate] = $this->getPeriodDates($schedule->period);

        // Get template if specified
        $template = $schedule->report_template_id
            ? ReportTemplate::find($schedule->report_template_id)
            : null;

        return $this->generate(
            $business,
            $startDate,
            $endDate,
            GeneratedReport::TYPE_SCHEDULED,
            $template,
            $schedule
        );
    }

    /**
     * Generate real-time report (instant)
     */
    public function generateRealtime(Business $business): array
    {
        $endDate = now();
        $startDate = now()->subDays(7);

        // Quick calculation without creating a record
        $metrics = $this->metricsCalculator->calculate($business, $startDate, $endDate);
        $healthScore = $this->healthScoreService->calculate($business, $metrics, []);

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'health_score' => $healthScore['score'],
            'health_label' => $healthScore['label'],
            'key_metrics' => [
                'total_sales' => $metrics['sales']['total_sales'] ?? 0,
                'total_revenue' => $metrics['sales']['total_revenue'] ?? 0,
                'total_leads' => $metrics['marketing']['total_leads'] ?? 0,
                'conversion_rate' => $metrics['marketing']['conversion_rate'] ?? 0,
                'roi' => $metrics['financial']['roi'] ?? 0,
            ],
            'kpi_progress' => $metrics['kpi_progress'] ?? null,
        ];
    }

    /**
     * Generate title for report
     */
    protected function generateTitle(Business $business, Carbon $startDate, Carbon $endDate): string
    {
        $periodType = $this->determinePeriodType($startDate, $endDate);

        $periodLabel = match ($periodType) {
            'daily' => $startDate->format('d.m.Y') . ' kunlik',
            'weekly' => $startDate->format('d.m') . ' - ' . $endDate->format('d.m.Y') . ' haftalik',
            'monthly' => $startDate->format('F Y') . ' oylik',
            'quarterly' => 'Q' . ceil($startDate->month / 3) . ' ' . $startDate->year . ' choraklik',
            default => $startDate->format('d.m') . ' - ' . $endDate->format('d.m.Y'),
        };

        return sprintf('%s - %s hisobot', $business->name, $periodLabel);
    }

    /**
     * Determine period type from dates
     */
    protected function determinePeriodType(Carbon $startDate, Carbon $endDate): string
    {
        $days = $startDate->diffInDays($endDate) + 1;

        return match (true) {
            $days === 1 => 'daily',
            $days <= 7 => 'weekly',
            $days <= 31 => 'monthly',
            $days <= 92 => 'quarterly',
            $days <= 366 => 'yearly',
            default => 'custom',
        };
    }

    /**
     * Get period dates from period type
     */
    protected function getPeriodDates(string $period): array
    {
        $endDate = now()->subDay(); // Yesterday

        return match ($period) {
            'daily' => [$endDate->copy(), $endDate],
            'weekly' => [$endDate->copy()->subDays(6), $endDate],
            'monthly' => [$endDate->copy()->startOfMonth(), $endDate],
            'quarterly' => [$endDate->copy()->startOfQuarter(), $endDate],
            default => [$endDate->copy()->subDays(6), $endDate],
        };
    }

    /**
     * Generate comparisons with previous periods
     */
    protected function generateComparisons(Business $business, Carbon $startDate, Carbon $endDate, array $currentMetrics): array
    {
        $periodDays = $startDate->diffInDays($endDate) + 1;

        // Previous period
        $prevStart = $startDate->copy()->subDays($periodDays);
        $prevEnd = $startDate->copy()->subDay();
        $prevMetrics = $this->metricsCalculator->calculate($business, $prevStart, $prevEnd);

        // Calculate changes
        $comparisons = [
            'previous_period' => [
                'period' => [
                    'start' => $prevStart->format('Y-m-d'),
                    'end' => $prevEnd->format('Y-m-d'),
                ],
                'metrics' => [],
            ],
        ];

        // Compare key metrics
        $metricsToCompare = [
            ['path' => 'sales.total_sales', 'label' => 'Sotuvlar'],
            ['path' => 'sales.total_revenue', 'label' => 'Daromad'],
            ['path' => 'marketing.total_leads', 'label' => 'Lidlar'],
            ['path' => 'marketing.conversion_rate', 'label' => 'Konversiya'],
            ['path' => 'financial.roi', 'label' => 'ROI'],
        ];

        foreach ($metricsToCompare as $metric) {
            $current = $this->getNestedValue($currentMetrics, $metric['path']);
            $previous = $this->getNestedValue($prevMetrics, $metric['path']);

            $change = $current - $previous;
            $changePercent = $previous > 0 ? (($current - $previous) / $previous) * 100 : ($current > 0 ? 100 : 0);

            $comparisons['previous_period']['metrics'][$metric['path']] = [
                'label' => $metric['label'],
                'current' => round($current, 2),
                'previous' => round($previous, 2),
                'change' => round($change, 2),
                'change_percent' => round($changePercent, 1),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            ];
        }

        return $comparisons;
    }

    /**
     * Get nested array value by dot notation path
     */
    protected function getNestedValue(array $array, string $path): float
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            $value = $value[$key] ?? 0;
            if (!is_array($value) && !is_numeric($value)) {
                return 0;
            }
        }

        return (float) $value;
    }

    /**
     * Generate plain text content for Telegram
     */
    protected function generateTextContent(Business $business, array $metrics, array $trends, array $insightsData, array $healthScore): string
    {
        $lines = [];

        // Header
        $lines[] = "📊 *{$business->name}* - Hisobot";
        $lines[] = "📅 Davr: {$metrics['period']['start']} - {$metrics['period']['end']}";
        $lines[] = "";

        // Health Score
        $healthEmoji = match (true) {
            $healthScore['score'] >= 80 => '🟢',
            $healthScore['score'] >= 60 => '🔵',
            $healthScore['score'] >= 40 => '🟡',
            default => '🔴',
        };
        $lines[] = "{$healthEmoji} *Salomatlik balli: {$healthScore['score']}/100* ({$healthScore['label']})";
        $lines[] = "";

        // Key Metrics
        $lines[] = "📈 *Asosiy ko'rsatkichlar:*";

        $sales = $metrics['sales'] ?? [];
        $marketing = $metrics['marketing'] ?? [];
        $financial = $metrics['financial'] ?? [];

        if (isset($sales['total_sales'])) {
            $lines[] = "• Sotuvlar: {$sales['total_sales']} ta";
        }
        if (isset($sales['total_revenue'])) {
            $revenue = number_format($sales['total_revenue'], 0, '.', ' ');
            $lines[] = "• Daromad: {$revenue} UZS";
        }
        if (isset($marketing['total_leads'])) {
            $lines[] = "• Lidlar: {$marketing['total_leads']} ta";
        }
        if (isset($marketing['conversion_rate'])) {
            $lines[] = "• Konversiya: {$marketing['conversion_rate']}%";
        }
        if (isset($financial['roi'])) {
            $lines[] = "• ROI: {$financial['roi']}%";
        }
        $lines[] = "";

        // Top Insights
        $insights = array_slice($insightsData['insights'] ?? [], 0, 5);
        if (!empty($insights)) {
            $lines[] = "💡 *Asosiy tushunchalar:*";
            foreach ($insights as $insight) {
                $lines[] = "{$insight['icon']} {$insight['message']}";
            }
            $lines[] = "";
        }

        // Top Recommendations
        $recommendations = array_slice($insightsData['recommendations'] ?? [], 0, 3);
        if (!empty($recommendations)) {
            $lines[] = "✅ *Tavsiyalar:*";
            foreach ($recommendations as $rec) {
                $lines[] = "{$rec['icon']} *{$rec['title']}*";
                $lines[] = "   {$rec['description']}";
            }
            $lines[] = "";
        }

        // KPI Progress
        $kpiProgress = $metrics['kpi_progress'] ?? [];
        if ($kpiProgress['has_plan'] ?? false) {
            $lines[] = "🎯 *KPI bajarilishi:*";
            if (isset($kpiProgress['sales'])) {
                $s = $kpiProgress['sales'];
                $lines[] = "• Sotuvlar: {$s['actual']}/{$s['planned']} ({$s['progress']}%)";
            }
            if (isset($kpiProgress['revenue'])) {
                $r = $kpiProgress['revenue'];
                $actual = number_format($r['actual'], 0, '.', ' ');
                $planned = number_format($r['planned'], 0, '.', ' ');
                $lines[] = "• Daromad: {$actual}/{$planned} ({$r['progress']}%)";
            }
            $lines[] = "";
        }

        // Footer
        $lines[] = "---";
        $lines[] = "🤖 BiznesPilot AI tomonidan yaratildi";

        return implode("\n", $lines);
    }

    /**
     * Generate HTML content
     */
    protected function generateHtmlContent(Business $business, array $metrics, array $trends, array $insightsData, array $healthScore): string
    {
        // Simple HTML structure
        $html = <<<HTML
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$business->name} - Hisobot</title>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; padding: 24px; }
        h1 { color: #1a1a1a; font-size: 24px; margin-bottom: 8px; }
        h2 { color: #333; font-size: 18px; margin: 24px 0 12px; }
        .period { color: #666; font-size: 14px; }
        .health-score { display: flex; align-items: center; gap: 16px; padding: 16px; background: #f0f9ff; border-radius: 8px; margin: 16px 0; }
        .score { font-size: 48px; font-weight: bold; }
        .score.excellent { color: #22c55e; }
        .score.good { color: #3b82f6; }
        .score.average { color: #eab308; }
        .score.poor { color: #ef4444; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; }
        .metric-card { background: #f9fafb; padding: 16px; border-radius: 8px; }
        .metric-value { font-size: 24px; font-weight: bold; color: #1a1a1a; }
        .metric-label { color: #666; font-size: 12px; }
        .insight { padding: 8px 12px; border-left: 3px solid; margin: 8px 0; background: #fafafa; }
        .insight.positive { border-color: #22c55e; }
        .insight.negative { border-color: #ef4444; }
        .insight.warning { border-color: #eab308; }
        .insight.neutral { border-color: #3b82f6; }
        .recommendation { background: #f0f9ff; padding: 12px; border-radius: 8px; margin: 8px 0; }
        .rec-title { font-weight: bold; color: #1a1a1a; }
        .rec-desc { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$business->name}</h1>
        <p class="period">📅 {$metrics['period']['start']} - {$metrics['period']['end']}</p>

        <div class="health-score">
            <div class="score {$healthScore['color']}">{$healthScore['score']}</div>
            <div>
                <div style="font-weight: bold;">Salomatlik balli</div>
                <div style="color: #666;">{$healthScore['label']}</div>
            </div>
        </div>

        <h2>📈 Asosiy ko'rsatkichlar</h2>
        <div class="metrics-grid">
HTML;

        // Add metrics
        $sales = $metrics['sales'] ?? [];
        $marketing = $metrics['marketing'] ?? [];
        $financial = $metrics['financial'] ?? [];

        $metricsToShow = [
            ['value' => $sales['total_sales'] ?? 0, 'label' => 'Sotuvlar', 'suffix' => ' ta'],
            ['value' => number_format($sales['total_revenue'] ?? 0, 0, '.', ' '), 'label' => 'Daromad', 'suffix' => ' UZS'],
            ['value' => $marketing['total_leads'] ?? 0, 'label' => 'Lidlar', 'suffix' => ' ta'],
            ['value' => $marketing['conversion_rate'] ?? 0, 'label' => 'Konversiya', 'suffix' => '%'],
            ['value' => $financial['roi'] ?? 0, 'label' => 'ROI', 'suffix' => '%'],
            ['value' => number_format($financial['cac'] ?? 0, 0, '.', ' '), 'label' => 'CAC', 'suffix' => ' UZS'],
        ];

        foreach ($metricsToShow as $m) {
            $html .= <<<HTML
            <div class="metric-card">
                <div class="metric-value">{$m['value']}{$m['suffix']}</div>
                <div class="metric-label">{$m['label']}</div>
            </div>
HTML;
        }

        $html .= "</div>";

        // Add insights
        $insights = array_slice($insightsData['insights'] ?? [], 0, 5);
        if (!empty($insights)) {
            $html .= "<h2>💡 Tushunchalar</h2>";
            foreach ($insights as $insight) {
                $html .= "<div class=\"insight {$insight['type']}\">{$insight['icon']} {$insight['message']}</div>";
            }
        }

        // Add recommendations
        $recommendations = array_slice($insightsData['recommendations'] ?? [], 0, 3);
        if (!empty($recommendations)) {
            $html .= "<h2>✅ Tavsiyalar</h2>";
            foreach ($recommendations as $rec) {
                $html .= <<<HTML
            <div class="recommendation">
                <div class="rec-title">{$rec['icon']} {$rec['title']}</div>
                <div class="rec-desc">{$rec['description']}</div>
            </div>
HTML;
            }
        }

        $html .= <<<HTML
        <hr style="margin: 24px 0; border: none; border-top: 1px solid #e5e5e5;">
        <p style="text-align: center; color: #999; font-size: 12px;">🤖 BiznesPilot AI tomonidan yaratildi</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}
