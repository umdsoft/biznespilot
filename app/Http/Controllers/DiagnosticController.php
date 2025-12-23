<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDiagnosticJob;
use App\Models\AIDiagnostic;
use App\Models\DiagnosticQuestion;
use App\Services\ClaudeDiagnosticService;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosticController extends Controller
{
    protected DiagnosticService $diagnosticService;
    protected ClaudeDiagnosticService $claudeService;

    public function __construct(DiagnosticService $diagnosticService, ClaudeDiagnosticService $claudeService)
    {
        $this->diagnosticService = $diagnosticService;
        $this->claudeService = $claudeService;
    }

    /**
     * Show diagnostic dashboard
     */
    public function index(): Response
    {
        $business = auth()->user()->currentBusiness;

        $latestDiagnostic = $this->diagnosticService->getLatestDiagnostic($business);
        $history = $this->diagnosticService->getDiagnosticHistory($business, 5);
        $canStart = $this->diagnosticService->canStartDiagnostic($business);

        return Inertia::render('Diagnostic/Index', [
            'latestDiagnostic' => $latestDiagnostic ? $this->formatDiagnosticForView($latestDiagnostic) : null,
            'history' => $history->map(fn ($d) => $this->formatDiagnosticForList($d)),
            'canStart' => $canStart,
            'onboardingProgress' => $business->onboarding_percent,
            'claudeAvailable' => $this->claudeService->isAvailable(),
        ]);
    }

    /**
     * Check if diagnostic can be started
     */
    public function checkEligibility(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;
        $check = $this->diagnosticService->canStartDiagnostic($business);

        return response()->json($check);
    }

    /**
     * Start new diagnostic with Claude AI (creates record only, doesn't run)
     */
    public function start(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        try {
            // Check if Claude is available
            if (!$this->claudeService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI xizmati hozirda mavjud emas. Iltimos, keyinroq urinib ko\'ring.',
                ], 422);
            }

            // Get next version number
            $lastVersion = AIDiagnostic::where('business_id', $business->id)->max('version');
            $nextVersion = ($lastVersion ?? 0) + 1;

            // Create pending diagnostic (don't dispatch job yet - let processing page do it)
            $diagnostic = AIDiagnostic::create([
                'business_id' => $business->id,
                'diagnostic_type' => 'onboarding',
                'status' => 'pending',
                'version' => $nextVersion,
            ]);

            return response()->json([
                'success' => true,
                'diagnostic_id' => $diagnostic->id,
                'message' => 'Diagnostika boshlandi',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Run diagnostic processing (called from processing page via AJAX)
     */
    public function run(AIDiagnostic $diagnostic): JsonResponse
    {
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        // Only run if pending
        if ($diagnostic->status !== 'pending') {
            return response()->json([
                'success' => true,
                'status' => $diagnostic->status,
                'message' => 'Diagnostika allaqachon ishga tushirilgan',
            ]);
        }

        // Increase timeout for Claude API call (5 minutes)
        set_time_limit(300);

        // Set initial processing state immediately
        $diagnostic->update([
            'status' => 'processing',
            'processing_step' => 'aggregating_data',
            'started_at' => now(),
        ]);

        // Dispatch the job to run after response is sent
        // This allows the frontend to start polling immediately
        ProcessDiagnosticJob::dispatchAfterResponse($diagnostic);

        return response()->json([
            'success' => true,
            'status' => 'processing',
            'processing_step' => 'aggregating_data',
            'message' => 'Diagnostika boshlandi',
        ]);
    }

    /**
     * Start diagnostic synchronously (for testing)
     */
    public function startSync(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        try {
            $diagnostic = $this->claudeService->runDiagnostics($business);

            return response()->json([
                'success' => true,
                'diagnostic_id' => $diagnostic->id,
                'message' => 'Diagnostika tugallandi',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get diagnostic status
     */
    public function status(AIDiagnostic $diagnostic): JsonResponse
    {
        // Ensure user owns this diagnostic
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        return response()->json([
            'id' => $diagnostic->id,
            'status' => $diagnostic->status,
            'processing_step' => $diagnostic->processing_step,
            'overall_score' => $diagnostic->overall_score,
            'status_level' => $diagnostic->status_level,
            'completed_at' => $diagnostic->completed_at?->toISOString(),
            'error_message' => $diagnostic->error_message,
        ]);
    }

    /**
     * Show processing page
     */
    public function processing(AIDiagnostic $diagnostic): Response
    {
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        return Inertia::render('Diagnostic/Processing', [
            'diagnostic' => [
                'id' => $diagnostic->id,
                'status' => $diagnostic->status,
                'processing_step' => $diagnostic->processing_step,
                'version' => $diagnostic->version,
            ],
        ]);
    }

    /**
     * Show diagnostic result
     */
    public function show(AIDiagnostic $diagnostic): Response
    {
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        if ($diagnostic->status !== 'completed') {
            return redirect()->route('diagnostic.processing', $diagnostic);
        }

        $diagnostic->load(['reports', 'actionProgress']);

        return Inertia::render('Diagnostic/Show', [
            'diagnostic' => $this->formatDiagnosticForResult($diagnostic),
            'actionProgress' => $diagnostic->actionProgress->map(fn ($p) => [
                'id' => $p->id,
                'step_order' => $p->step_order,
                'step_title' => $p->step_title,
                'module_route' => $p->module_route,
                'status' => $p->status,
                'completed_at' => $p->completed_at?->format('d.m.Y H:i'),
            ]),
        ]);
    }

    /**
     * Show questions page
     */
    public function questions(AIDiagnostic $diagnostic): Response
    {
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        $questions = $diagnostic->questions()
            ->orderBy('priority', 'desc')
            ->orderBy('order')
            ->get();

        return Inertia::render('Diagnostic/Questions', [
            'diagnostic_id' => $diagnostic->id,
            'questions' => $questions->map(fn ($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'category' => $q->category,
                'category_label' => $q->getCategoryLabel(),
                'priority' => $q->priority,
                'answer' => $q->answer,
                'answered_at' => $q->answered_at?->format('d.m.Y H:i'),
            ]),
            'answered_count' => $questions->whereNotNull('answer')->count(),
            'total_count' => $questions->count(),
        ]);
    }

    /**
     * Answer a question
     */
    public function answerQuestion(Request $request, DiagnosticQuestion $question): JsonResponse
    {
        // Verify ownership
        $diagnostic = $question->diagnostic;
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        $validated = $request->validate([
            'answer' => 'required|string|min:10|max:2000',
        ]);

        $question->update([
            'answer' => $validated['answer'],
            'answered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Javob saqlandi',
        ]);
    }

    /**
     * Get diagnostic history
     */
    public function history(): Response
    {
        $business = auth()->user()->currentBusiness;
        $diagnostics = $this->diagnosticService->getDiagnosticHistory($business, 20);

        return Inertia::render('Diagnostic/History', [
            'diagnostics' => $diagnostics->map(fn ($d) => $this->formatDiagnosticForList($d)),
        ]);
    }

    /**
     * Compare two diagnostics
     */
    public function compare(AIDiagnostic $diagnostic1, AIDiagnostic $diagnostic2): Response
    {
        $business = auth()->user()->currentBusiness;

        if ($diagnostic1->business_id !== $business->id || $diagnostic2->business_id !== $business->id) {
            abort(403);
        }

        return Inertia::render('Diagnostic/Compare', [
            'diagnostic1' => $this->formatDiagnosticForComparison($diagnostic1),
            'diagnostic2' => $this->formatDiagnosticForComparison($diagnostic2),
        ]);
    }

    /**
     * Generate and download report
     */
    public function downloadReport(AIDiagnostic $diagnostic, string $type = 'detailed'): \Symfony\Component\HttpFoundation\Response
    {
        if ($diagnostic->business_id !== auth()->user()->currentBusiness->id) {
            abort(403);
        }

        $business = $diagnostic->business;

        // Generate HTML report directly
        $html = $this->generateReportHtml($diagnostic, $business, $type);

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="diagnostic-report-' . ($diagnostic->version ?? date('Y-m-d')) . '.html"');
    }

    /**
     * Generate HTML report content
     */
    protected function generateReportHtml(AIDiagnostic $diagnostic, $business, string $type): string
    {
        $date = $diagnostic->completed_at ? $diagnostic->completed_at->format('d.m.Y H:i') : date('d.m.Y H:i');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostika Hisoboti - {$business->name}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .report { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .score-section { padding: 30px; text-align: center; border-bottom: 1px solid #eee; }
        .score-circle { width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: bold; color: white; }
        .score-good { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .score-medium { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .score-weak { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .section { padding: 30px; border-bottom: 1px solid #eee; }
        .section:last-child { border-bottom: none; }
        .section h2 { color: #667eea; font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .section h3 { font-size: 16px; margin: 15px 0 10px; color: #555; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .card { background: #f8f9fa; border-radius: 8px; padding: 20px; }
        .card-title { font-size: 14px; color: #666; margin-bottom: 5px; }
        .card-value { font-size: 24px; font-weight: bold; color: #333; }
        .list { list-style: none; }
        .list li { padding: 10px 0; border-bottom: 1px solid #eee; }
        .list li:last-child { border-bottom: none; }
        .list li::before { content: "•"; color: #667eea; font-weight: bold; margin-right: 10px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .success { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .danger { background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
        @media print { body { background: white; } .container { padding: 0; } .report { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="report">
            <div class="header">
                <h1>🎯 AI Diagnostika Hisoboti</h1>
                <p>{$business->name} | {$date}</p>
            </div>
HTML;

        // Score section
        $score = $diagnostic->overall_score ?? 0;
        $scoreClass = $score >= 60 ? 'score-good' : ($score >= 40 ? 'score-medium' : 'score-weak');
        $statusMessage = $diagnostic->status_message ?? 'Diagnostika yakunlandi';

        $html .= <<<HTML
            <div class="score-section">
                <div class="score-circle {$scoreClass}">{$score}</div>
                <h2 style="color: #333;">Umumiy Ball: {$score}/100</h2>
                <p style="color: #666; max-width: 600px; margin: 10px auto;">{$statusMessage}</p>
            </div>
HTML;

        // Money Loss Analysis
        if (!empty($diagnostic->money_loss_analysis)) {
            $moneyLoss = $diagnostic->money_loss_analysis;
            $monthlyLoss = number_format($moneyLoss['monthly_loss'] ?? 0, 0, '', ' ');
            $yearlyLoss = number_format($moneyLoss['yearly_loss'] ?? 0, 0, '', ' ');
            $dailyLoss = number_format($moneyLoss['daily_loss'] ?? 0, 0, '', ' ');

            $html .= <<<HTML
            <div class="section">
                <h2>💰 Yo'qotilayotgan Daromad</h2>
                <div class="danger">
                    <strong>Diqqat!</strong> Har kuni {$dailyLoss} UZS yo'qotilmoqda
                </div>
                <div class="grid">
                    <div class="card">
                        <div class="card-title">Kunlik yo'qotish</div>
                        <div class="card-value" style="color: #dc3545;">{$dailyLoss} UZS</div>
                    </div>
                    <div class="card">
                        <div class="card-title">Oylik yo'qotish</div>
                        <div class="card-value" style="color: #dc3545;">{$monthlyLoss} UZS</div>
                    </div>
                    <div class="card">
                        <div class="card-title">Yillik yo'qotish</div>
                        <div class="card-value" style="color: #dc3545;">{$yearlyLoss} UZS</div>
                    </div>
                </div>
HTML;

            if (!empty($moneyLoss['breakdown'])) {
                $html .= '<h3>Yo\'qotish sabablari:</h3><ul class="list">';
                foreach ($moneyLoss['breakdown'] as $item) {
                    $problem = $item['problem'] ?? $item['reason'] ?? 'Noma\'lum';
                    $amount = number_format($item['amount'] ?? 0, 0, '', ' ');
                    $html .= "<li><strong>{$problem}</strong> - {$amount} UZS/oy</li>";
                }
                $html .= '</ul>';
            }
            $html .= '</div>';
        }

        // Channels Analysis
        if (!empty($diagnostic->channels_analysis)) {
            $channels = $diagnostic->channels_analysis;
            $html .= '<div class="section"><h2>📱 Marketing Kanallari</h2><div class="grid">';

            $channelsList = $channels['channels'] ?? [];
            if (empty($channelsList)) {
                // Old format
                foreach ($channels as $key => $value) {
                    if (is_array($value) && $key !== 'recommended_channels') {
                        $channelsList[] = array_merge(['name' => ucfirst($key)], $value);
                    }
                }
            }

            foreach ($channelsList as $channel) {
                $name = $channel['name'] ?? 'Kanal';
                $effectiveness = $channel['effectiveness'] ?? 'low';
                $effLabel = $effectiveness === 'high' ? 'Yuqori' : ($effectiveness === 'medium' ? 'O\'rta' : 'Past');
                $effColor = $effectiveness === 'high' ? '#28a745' : ($effectiveness === 'medium' ? '#ffc107' : '#dc3545');
                $recommendation = $channel['recommendation'] ?? '';

                $html .= <<<HTML
                <div class="card">
                    <div class="card-title">{$name}</div>
                    <div class="card-value" style="color: {$effColor}; font-size: 16px;">{$effLabel}</div>
                    <p style="font-size: 13px; color: #666; margin-top: 10px;">{$recommendation}</p>
                </div>
HTML;
            }
            $html .= '</div></div>';
        }

        // Action Plan
        if (!empty($diagnostic->action_plan['steps'])) {
            $html .= '<div class="section"><h2>📋 Harakat Rejasi</h2><ul class="list">';
            foreach ($diagnostic->action_plan['steps'] as $i => $step) {
                $title = $step['title'] ?? 'Qadam';
                $why = $step['why'] ?? '';
                $time = $step['time_minutes'] ?? 0;
                $order = $i + 1;
                $html .= "<li><strong>{$order}. {$title}</strong>";
                if ($time) $html .= " ({$time} daqiqa)";
                if ($why) $html .= "<br><span style='color: #666; font-size: 14px;'>{$why}</span>";
                $html .= "</li>";
            }
            $html .= '</ul></div>';
        }

        // SWOT
        if (!empty($diagnostic->swot)) {
            $swot = $diagnostic->swot;
            $html .= '<div class="section"><h2>📊 SWOT Tahlil</h2><div class="grid">';

            if (!empty($swot['strengths'])) {
                $html .= '<div class="card" style="background: #d4edda;"><div class="card-title" style="color: #155724;">Kuchli tomonlar</div><ul style="font-size: 14px; margin-top: 10px;">';
                foreach ($swot['strengths'] as $item) {
                    $html .= "<li>{$item}</li>";
                }
                $html .= '</ul></div>';
            }
            if (!empty($swot['weaknesses'])) {
                $html .= '<div class="card" style="background: #f8d7da;"><div class="card-title" style="color: #721c24;">Zaif tomonlar</div><ul style="font-size: 14px; margin-top: 10px;">';
                foreach ($swot['weaknesses'] as $item) {
                    $html .= "<li>{$item}</li>";
                }
                $html .= '</ul></div>';
            }
            if (!empty($swot['opportunities'])) {
                $html .= '<div class="card" style="background: #cce5ff;"><div class="card-title" style="color: #004085;">Imkoniyatlar</div><ul style="font-size: 14px; margin-top: 10px;">';
                foreach ($swot['opportunities'] as $item) {
                    $html .= "<li>{$item}</li>";
                }
                $html .= '</ul></div>';
            }
            if (!empty($swot['threats'])) {
                $html .= '<div class="card" style="background: #fff3cd;"><div class="card-title" style="color: #856404;">Xavflar</div><ul style="font-size: 14px; margin-top: 10px;">';
                foreach ($swot['threats'] as $item) {
                    $html .= "<li>{$item}</li>";
                }
                $html .= '</ul></div>';
            }
            $html .= '</div></div>';
        }

        // Footer
        $html .= <<<HTML
            <div class="footer">
                <p>Bu hisobot BiznesPilot AI tomonidan avtomatik yaratildi</p>
                <p style="margin-top: 5px;">© 2024 BiznesPilot. Barcha huquqlar himoyalangan.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Format diagnostic for main view
     */
    protected function formatDiagnosticForView(AIDiagnostic $diagnostic): array
    {
        return [
            'id' => $diagnostic->id,
            'version' => $diagnostic->version,
            'status' => $diagnostic->status,
            'overall_score' => $diagnostic->overall_score,
            'status_level' => $diagnostic->status_level,
            'status_message' => $diagnostic->status_message,
            'status_level_info' => $diagnostic->getStatusLevelInfo(),
            'category_scores' => [
                'marketing' => $diagnostic->marketing_score,
                'sales' => $diagnostic->sales_score,
                'content' => $diagnostic->content_score,
                'funnel' => $diagnostic->funnel_score,
            ],
            'money_loss' => $diagnostic->money_loss_analysis,
            'action_plan' => $diagnostic->action_plan,
            'expected_results' => $diagnostic->expected_results,
            'roi_calculations' => $diagnostic->roi_calculations,
            'cause_effect_matrix' => $diagnostic->cause_effect_matrix,
            'quick_strategies' => $diagnostic->quick_strategies,
            'completed_at' => $diagnostic->completed_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * Format diagnostic for list
     */
    protected function formatDiagnosticForList(AIDiagnostic $diagnostic): array
    {
        return [
            'id' => $diagnostic->id,
            'version' => $diagnostic->version,
            'overall_score' => $diagnostic->overall_score,
            'status' => $diagnostic->status,
            'status_level' => $diagnostic->status_level,
            'completed_at' => $diagnostic->completed_at?->format('d.m.Y'),
        ];
    }

    /**
     * Format diagnostic for result page
     */
    protected function formatDiagnosticForResult(AIDiagnostic $diagnostic): array
    {
        return [
            'id' => $diagnostic->id,
            'version' => $diagnostic->version,
            'overall_score' => $diagnostic->overall_score,
            'status_level' => $diagnostic->status_level,
            'status_message' => $diagnostic->status_message,
            'status_level_info' => $diagnostic->getStatusLevelInfo(),
            'industry_avg_score' => $diagnostic->industry_avg_score,
            'category_scores' => [
                'marketing' => $diagnostic->marketing_score,
                'sales' => $diagnostic->sales_score,
                'content' => $diagnostic->content_score,
                'funnel' => $diagnostic->funnel_score,
            ],
            'money_loss_analysis' => $diagnostic->money_loss_analysis,
            'similar_businesses' => $diagnostic->similar_businesses,
            'ideal_customer_analysis' => $diagnostic->ideal_customer_analysis,
            'offer_strength' => $diagnostic->offer_strength,
            'channels_analysis' => $diagnostic->channels_analysis,
            'funnel_analysis' => $diagnostic->funnel_analysis,
            'roi_calculations' => $diagnostic->roi_calculations,
            'cause_effect_matrix' => $diagnostic->cause_effect_matrix,
            'quick_strategies' => $diagnostic->quick_strategies,
            'automation_analysis' => $diagnostic->automation_analysis,
            'risks' => $diagnostic->risks,
            'action_plan' => $diagnostic->action_plan,
            'expected_results' => $diagnostic->expected_results,
            'platform_recommendations' => $diagnostic->platform_recommendations,
            'recommended_videos' => $diagnostic->recommended_videos,
            'swot' => $diagnostic->swot_analysis,
            'strengths' => $diagnostic->strengths,
            'weaknesses' => $diagnostic->weaknesses,
            'recommendations' => $diagnostic->recommendations,
            'ai_insights' => $diagnostic->ai_insights,
            'benchmark_summary' => $diagnostic->benchmark_summary,
            'trend_data' => $diagnostic->trend_data,
            'tokens_used' => $diagnostic->tokens_used,
            'generation_time_ms' => $diagnostic->generation_time_ms,
            'completed_at' => $diagnostic->completed_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * Format diagnostic for comparison view
     */
    protected function formatDiagnosticForComparison(AIDiagnostic $diagnostic): array
    {
        return [
            'id' => $diagnostic->id,
            'version' => $diagnostic->version,
            'overall_score' => $diagnostic->overall_score,
            'status_level' => $diagnostic->status_level,
            'category_scores' => [
                'marketing' => $diagnostic->marketing_score,
                'sales' => $diagnostic->sales_score,
                'content' => $diagnostic->content_score,
                'funnel' => $diagnostic->funnel_score,
            ],
            'swot' => $diagnostic->swot_analysis,
            'recommendations_count' => count($diagnostic->recommendations ?? []),
            'completed_at' => $diagnostic->completed_at?->format('d.m.Y'),
        ];
    }

    /**
     * API: Get latest diagnostic data
     */
    public function apiLatest(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;
        $diagnostic = $this->diagnosticService->getLatestDiagnostic($business);

        if (!$diagnostic) {
            return response()->json(['diagnostic' => null]);
        }

        return response()->json([
            'diagnostic' => $this->formatDiagnosticForView($diagnostic),
        ]);
    }
}
