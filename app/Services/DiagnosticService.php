<?php

namespace App\Services;

use App\Models\AIDiagnostic;
use App\Models\Business;
use App\Models\DiagnosticQuestion;
use App\Models\DiagnosticReport;
use App\Models\KPICalculation;
use App\Models\MaturityAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnosticService
{
    protected DiagnosticDataAggregator $dataAggregator;
    protected BenchmarkService $benchmarkService;
    protected HealthScoreCalculator $healthScoreCalculator;
    protected AIAnalysisService $aiAnalysisService;
    protected RecommendationEngine $recommendationEngine;

    public function __construct(
        DiagnosticDataAggregator $dataAggregator,
        BenchmarkService $benchmarkService,
        HealthScoreCalculator $healthScoreCalculator,
        AIAnalysisService $aiAnalysisService,
        RecommendationEngine $recommendationEngine
    ) {
        $this->dataAggregator = $dataAggregator;
        $this->benchmarkService = $benchmarkService;
        $this->healthScoreCalculator = $healthScoreCalculator;
        $this->aiAnalysisService = $aiAnalysisService;
        $this->recommendationEngine = $recommendationEngine;
    }

    /**
     * Check if business can start diagnostic
     */
    public function canStartDiagnostic(Business $business): array
    {
        $reasons = [];

        // Check onboarding completion
        if ($business->onboarding_progress < 100) {
            $reasons[] = [
                'code' => 'onboarding_incomplete',
                'message' => 'Onboarding jarayoni tugallanmagan',
                'current' => $business->onboarding_progress,
                'required' => 100,
            ];
        }

        // Check if maturity assessment exists
        $hasMaturity = MaturityAssessment::where('business_id', $business->id)->exists();
        if (!$hasMaturity) {
            $reasons[] = [
                'code' => 'no_maturity_assessment',
                'message' => 'Maturity baholash o\'tkazilmagan',
            ];
        }

        // Check for active diagnostic
        $activeDiagnostic = AIDiagnostic::where('business_id', $business->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($activeDiagnostic) {
            $reasons[] = [
                'code' => 'diagnostic_in_progress',
                'message' => 'Diagnostika allaqachon jarayonda',
                'diagnostic_id' => $activeDiagnostic->id,
            ];
        }

        return [
            'can_start' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Start new diagnostic for business
     */
    public function startDiagnostic(Business $business): AIDiagnostic
    {
        $check = $this->canStartDiagnostic($business);

        if (!$check['can_start']) {
            throw new \Exception('Diagnostika boshlab bo\'lmaydi: ' . $check['reasons'][0]['message']);
        }

        // Get previous diagnostic for trend analysis
        $previousDiagnostic = AIDiagnostic::where('business_id', $business->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        // Create new diagnostic record
        $diagnostic = AIDiagnostic::create([
            'business_id' => $business->id,
            'status' => 'pending',
            'version' => $this->getNextVersion($business->id),
            'previous_diagnostic_id' => $previousDiagnostic?->id,
            'started_at' => now(),
        ]);

        return $diagnostic;
    }

    /**
     * Process diagnostic (main workflow)
     */
    public function processDiagnostic(AIDiagnostic $diagnostic): AIDiagnostic
    {
        $business = $diagnostic->business;

        try {
            // Update status to processing
            $diagnostic->update(['status' => 'processing']);

            // Step 1: Aggregate data
            $diagnostic->update(['processing_step' => 'aggregating_data']);
            $aggregatedData = $this->dataAggregator->aggregateForBusiness($business);

            // Step 2: Get maturity assessment
            $maturity = MaturityAssessment::where('business_id', $business->id)
                ->latest()
                ->first();

            // Step 3: Calculate KPIs (if integration data available)
            $diagnostic->update(['processing_step' => 'calculating_kpis']);
            $kpis = $this->calculateKPIs($business, $aggregatedData);

            // Step 4: Compare with benchmarks
            $diagnostic->update(['processing_step' => 'comparing_benchmarks']);
            $benchmarks = $this->benchmarkService->getBenchmarksForBusiness($business);
            $benchmarkComparison = $this->benchmarkService->compareWithBenchmarks($kpis, $benchmarks);
            $strengths = $this->benchmarkService->identifyStrengths($benchmarkComparison);
            $weaknesses = $this->benchmarkService->identifyWeaknesses($benchmarkComparison);

            // Step 5: Calculate health score
            $diagnostic->update(['processing_step' => 'calculating_scores']);
            $healthScore = $this->healthScoreCalculator->calculateHealthScore(
                $business,
                $kpis,
                $benchmarkComparison,
                $maturity
            );

            // Step 6: AI Analysis (SWOT, Insights, Questions)
            $diagnostic->update(['processing_step' => 'ai_analysis']);
            $aiResults = $this->aiAnalysisService->performAnalysis(
                $business,
                $aggregatedData,
                $benchmarkComparison,
                $healthScore
            );

            // Step 7: Generate recommendations
            $diagnostic->update(['processing_step' => 'generating_recommendations']);
            $recommendations = $this->recommendationEngine->generateRecommendations(
                $healthScore,
                $benchmarkComparison,
                $weaknesses,
                $aggregatedData
            );

            // Step 8: Calculate trends (if previous diagnostic exists)
            $trends = null;
            if ($diagnostic->previousDiagnostic) {
                $trends = $this->calculateTrends($diagnostic, $diagnostic->previousDiagnostic);
            }

            // Step 9: Save KPI calculation
            $diagnostic->update(['processing_step' => 'saving_results']);
            $kpiCalculation = $this->saveKPICalculation($business, $diagnostic, $kpis, $benchmarkComparison);

            // Step 10: Update diagnostic with results
            $diagnostic->update([
                'status' => 'completed',
                'processing_step' => 'completed',
                'overall_score' => $healthScore['overall_score'],
                'marketing_score' => $healthScore['category_scores']['marketing']['score'] ?? null,
                'sales_score' => $healthScore['category_scores']['sales']['score'] ?? null,
                'content_score' => $healthScore['category_scores']['content']['score'] ?? null,
                'funnel_score' => $healthScore['category_scores']['funnel']['score'] ?? null,
                'swot_analysis' => $aiResults['swot'],
                'strengths' => array_column($strengths, 'metric'),
                'weaknesses' => array_column($weaknesses, 'metric'),
                'recommendations' => $recommendations,
                'ai_insights' => $aiResults['ai_insights'],
                'benchmark_summary' => $this->benchmarkService->getBenchmarkSummary($benchmarkComparison),
                'trend_data' => $trends,
                'ai_tokens_used' => $aiResults['tokens_used'],
                'ai_cost' => $aiResults['cost'],
                'completed_at' => now(),
            ]);

            // Step 11: Save AI questions
            $this->saveQuestions($diagnostic, $aiResults['questions']);

            return $diagnostic->fresh();

        } catch (\Exception $e) {
            Log::error('Diagnostic processing failed', [
                'diagnostic_id' => $diagnostic->id,
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $diagnostic->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate KPIs from aggregated data
     */
    protected function calculateKPIs(Business $business, array $aggregatedData): array
    {
        $kpis = [];

        // Get integration metrics
        $integrations = $aggregatedData['integrations']['details'] ?? [];

        foreach ($integrations as $integration) {
            $metrics = $integration['metrics'] ?? [];

            // Aggregate social media metrics
            if (in_array($integration['platform'], ['instagram', 'facebook', 'telegram', 'tiktok'])) {
                $kpis['engagement_rate'] = $kpis['engagement_rate'] ?? $metrics['engagement_rate'] ?? null;
                $kpis['follower_growth_rate'] = $kpis['follower_growth_rate'] ?? $metrics['follower_growth_rate'] ?? null;
                $kpis['content_frequency'] = ($kpis['content_frequency'] ?? 0) + ($metrics['posts_per_week'] ?? 0);
            }

            // CRM metrics
            if (in_array($integration['platform'], ['bitrix24', 'amocrm'])) {
                $kpis['total_leads'] = $kpis['total_leads'] ?? $metrics['total_leads'] ?? null;
                $kpis['conversion_rate'] = $kpis['conversion_rate'] ?? $metrics['conversion_rate'] ?? null;
                $kpis['sales_cycle_days'] = $kpis['sales_cycle_days'] ?? $metrics['avg_deal_time'] ?? null;
            }

            // Advertising metrics
            if (in_array($integration['platform'], ['google_ads', 'facebook_ads', 'yandex_direct'])) {
                $kpis['cpc'] = $kpis['cpc'] ?? $metrics['cpc'] ?? null;
                $kpis['ctr'] = $kpis['ctr'] ?? $metrics['ctr'] ?? null;
                $kpis['cpl'] = $kpis['cpl'] ?? $metrics['cpl'] ?? null;
                $kpis['roas'] = $kpis['roas'] ?? $metrics['roas'] ?? null;
            }
        }

        // Calculate derived KPIs if we have business data
        $businessData = $aggregatedData['business'] ?? [];

        // Estimate CAC if we have budget and customer data
        if (!isset($kpis['cac']) && !empty($businessData['marketing_budget'])) {
            // This is a rough estimate - would be better with actual data
            $kpis['cac'] = null; // Need actual customer count
        }

        // Use maturity-based estimates for missing KPIs
        $maturity = $aggregatedData['maturity'] ?? [];
        if (!empty($maturity['overall_score'])) {
            // Estimate missing values based on maturity level
            $this->fillMissingKPIs($kpis, $maturity);
        }

        return $kpis;
    }

    /**
     * Fill missing KPIs with estimates based on maturity
     */
    protected function fillMissingKPIs(array &$kpis, array $maturity): void
    {
        $maturityScore = $maturity['overall_score'] ?? 50;

        // Estimate engagement rate if missing
        if (!isset($kpis['engagement_rate'])) {
            $kpis['engagement_rate'] = match (true) {
                $maturityScore >= 80 => rand(35, 50) / 10, // 3.5-5.0%
                $maturityScore >= 60 => rand(25, 40) / 10, // 2.5-4.0%
                $maturityScore >= 40 => rand(15, 30) / 10, // 1.5-3.0%
                default => rand(10, 20) / 10, // 1.0-2.0%
            };
        }

        // Estimate conversion rate if missing
        if (!isset($kpis['conversion_rate'])) {
            $salesScore = $maturity['categories']['sales']['score'] ?? $maturityScore;
            $kpis['conversion_rate'] = match (true) {
                $salesScore >= 80 => rand(20, 30), // 20-30%
                $salesScore >= 60 => rand(12, 22), // 12-22%
                $salesScore >= 40 => rand(8, 15), // 8-15%
                default => rand(3, 10), // 3-10%
            };
        }

        // Estimate churn rate if missing
        if (!isset($kpis['churn_rate'])) {
            $kpis['churn_rate'] = match (true) {
                $maturityScore >= 80 => rand(15, 35) / 10, // 1.5-3.5%
                $maturityScore >= 60 => rand(35, 60) / 10, // 3.5-6.0%
                $maturityScore >= 40 => rand(60, 100) / 10, // 6.0-10.0%
                default => rand(100, 150) / 10, // 10-15%
            };
        }
    }

    /**
     * Save KPI calculation record
     */
    protected function saveKPICalculation(
        Business $business,
        AIDiagnostic $diagnostic,
        array $kpis,
        array $benchmarkComparison
    ): KPICalculation {
        return KPICalculation::create([
            'business_id' => $business->id,
            'diagnostic_id' => $diagnostic->id,
            'calculation_date' => now(),
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            // Marketing KPIs
            'engagement_rate' => $kpis['engagement_rate'] ?? null,
            'follower_growth_rate' => $kpis['follower_growth_rate'] ?? null,
            'content_posts_count' => $kpis['content_frequency'] ?? null,
            // Advertising KPIs
            'cpc' => $kpis['cpc'] ?? null,
            'ctr' => $kpis['ctr'] ?? null,
            'cpl' => $kpis['cpl'] ?? null,
            'roas' => $kpis['roas'] ?? null,
            // Sales KPIs
            'total_leads' => $kpis['total_leads'] ?? null,
            'conversion_rate' => $kpis['conversion_rate'] ?? null,
            'cac' => $kpis['cac'] ?? null,
            'sales_cycle_days' => $kpis['sales_cycle_days'] ?? null,
            'ltv_cac_ratio' => $kpis['ltv_cac_ratio'] ?? null,
            // Retention KPIs
            'churn_rate' => $kpis['churn_rate'] ?? null,
            'repeat_purchase_rate' => $kpis['repeat_purchase_rate'] ?? null,
            // Funnel KPIs
            'funnel_conversion_rate' => $kpis['funnel_conversion'] ?? null,
            // Benchmark comparison
            'benchmark_comparison' => $benchmarkComparison,
        ]);
    }

    /**
     * Save AI-generated questions
     */
    protected function saveQuestions(AIDiagnostic $diagnostic, array $questions): void
    {
        foreach ($questions as $index => $question) {
            DiagnosticQuestion::create([
                'diagnostic_id' => $diagnostic->id,
                'question' => $question['question'],
                'question_type' => 'open_ended',
                'category' => $question['category'] ?? 'general',
                'priority' => $question['priority'] ?? 'medium',
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Calculate trends compared to previous diagnostic
     */
    protected function calculateTrends(AIDiagnostic $current, AIDiagnostic $previous): array
    {
        return [
            'overall' => $this->healthScoreCalculator->calculateTrend(
                $current->overall_score,
                $previous->overall_score
            ),
            'categories' => [
                'marketing' => $this->calculateCategoryTrend($current, $previous, 'marketing_score'),
                'sales' => $this->calculateCategoryTrend($current, $previous, 'sales_score'),
                'content' => $this->calculateCategoryTrend($current, $previous, 'content_score'),
                'funnel' => $this->calculateCategoryTrend($current, $previous, 'funnel_score'),
            ],
            'days_between' => $current->started_at->diffInDays($previous->completed_at),
        ];
    }

    /**
     * Calculate trend for a category
     */
    protected function calculateCategoryTrend(
        AIDiagnostic $current,
        AIDiagnostic $previous,
        string $field
    ): array {
        $currentScore = $current->$field ?? 0;
        $previousScore = $previous->$field ?? 0;
        $change = $currentScore - $previousScore;

        return [
            'current' => $currentScore,
            'previous' => $previousScore,
            'change' => $change,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get next version number for diagnostic
     */
    protected function getNextVersion(int $businessId): int
    {
        $lastVersion = AIDiagnostic::where('business_id', $businessId)
            ->max('version');

        return ($lastVersion ?? 0) + 1;
    }

    /**
     * Get latest completed diagnostic for business
     */
    public function getLatestDiagnostic(Business $business): ?AIDiagnostic
    {
        return AIDiagnostic::where('business_id', $business->id)
            ->where('status', 'completed')
            ->with(['questions', 'reports', 'kpiCalculation'])
            ->latest('completed_at')
            ->first();
    }

    /**
     * Get diagnostic history for business
     */
    public function getDiagnosticHistory(Business $business, int $limit = 10): \Illuminate\Support\Collection
    {
        return AIDiagnostic::where('business_id', $business->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate report from diagnostic
     */
    public function generateReport(AIDiagnostic $diagnostic, string $type = 'detailed'): DiagnosticReport
    {
        $content = $this->buildReportContent($diagnostic, $type);

        return DiagnosticReport::create([
            'diagnostic_id' => $diagnostic->id,
            'report_type' => $type,
            'report_format' => 'html',
            'title' => "Diagnostika Hisoboti #{$diagnostic->version}",
            'content' => $content,
            'html_content' => $this->renderReportHtml($content),
        ]);
    }

    /**
     * Build report content
     */
    protected function buildReportContent(AIDiagnostic $diagnostic, string $type): array
    {
        $business = $diagnostic->business;

        return [
            'business' => [
                'name' => $business->name,
                'industry' => $business->industry?->name,
            ],
            'diagnostic' => [
                'version' => $diagnostic->version,
                'date' => $diagnostic->completed_at->format('d.m.Y'),
                'overall_score' => $diagnostic->overall_score,
            ],
            'scores' => [
                'marketing' => $diagnostic->marketing_score,
                'sales' => $diagnostic->sales_score,
                'content' => $diagnostic->content_score,
                'funnel' => $diagnostic->funnel_score,
            ],
            'swot' => $diagnostic->swot_analysis,
            'recommendations' => $type === 'summary'
                ? array_slice($diagnostic->recommendations ?? [], 0, 5)
                : $diagnostic->recommendations,
            'insights' => $diagnostic->ai_insights,
            'benchmark' => $diagnostic->benchmark_summary,
            'trends' => $diagnostic->trend_data,
        ];
    }

    /**
     * Render report as HTML
     */
    protected function renderReportHtml(array $content): string
    {
        // Simple HTML rendering - in production, use a blade template
        $html = "<html><head><title>Diagnostika Hisoboti</title></head><body>";
        $html .= "<h1>Biznes Diagnostikasi: {$content['business']['name']}</h1>";
        $html .= "<p>Sana: {$content['diagnostic']['date']}</p>";
        $html .= "<h2>Umumiy Ball: {$content['diagnostic']['overall_score']}/100</h2>";

        // Scores
        $html .= "<h3>Kategoriya Ballari</h3><ul>";
        foreach ($content['scores'] as $category => $score) {
            $html .= "<li>" . ucfirst($category) . ": {$score}/100</li>";
        }
        $html .= "</ul>";

        // SWOT
        if (!empty($content['swot'])) {
            $html .= "<h3>SWOT Tahlili</h3>";
            foreach ($content['swot'] as $type => $items) {
                $html .= "<h4>" . ucfirst($type) . "</h4><ul>";
                foreach ($items as $item) {
                    $html .= "<li>{$item}</li>";
                }
                $html .= "</ul>";
            }
        }

        // Recommendations
        $html .= "<h3>Tavsiyalar</h3><ol>";
        foreach ($content['recommendations'] ?? [] as $rec) {
            $html .= "<li><strong>{$rec['title']}</strong><br>{$rec['description']}</li>";
        }
        $html .= "</ol>";

        $html .= "</body></html>";

        return $html;
    }
}
