<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDiagnosticJob;
use App\Models\AIDiagnostic;
use App\Models\DiagnosticQuestion;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosticController extends Controller
{
    protected DiagnosticService $diagnosticService;

    public function __construct(DiagnosticService $diagnosticService)
    {
        $this->diagnosticService = $diagnosticService;
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
            'latestDiagnostic' => $latestDiagnostic,
            'history' => $history,
            'canStart' => $canStart,
            'onboardingProgress' => $business->onboarding_progress,
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
     * Start new diagnostic
     */
    public function start(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        try {
            $diagnostic = $this->diagnosticService->startDiagnostic($business);

            // Dispatch job for async processing
            ProcessDiagnosticJob::dispatch($diagnostic);

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

        $diagnostic->load(['questions', 'kpiCalculation', 'reports']);

        return Inertia::render('Diagnostic/Result', [
            'diagnostic' => [
                'id' => $diagnostic->id,
                'version' => $diagnostic->version,
                'overall_score' => $diagnostic->overall_score,
                'category_scores' => [
                    'marketing' => $diagnostic->marketing_score,
                    'sales' => $diagnostic->sales_score,
                    'content' => $diagnostic->content_score,
                    'funnel' => $diagnostic->funnel_score,
                ],
                'swot' => $diagnostic->swot_analysis,
                'strengths' => $diagnostic->strengths,
                'weaknesses' => $diagnostic->weaknesses,
                'recommendations' => $diagnostic->recommendations,
                'ai_insights' => $diagnostic->ai_insights,
                'benchmark_summary' => $diagnostic->benchmark_summary,
                'trend_data' => $diagnostic->trend_data,
                'completed_at' => $diagnostic->completed_at?->format('d.m.Y H:i'),
            ],
            'questions' => $diagnostic->questions->map(fn ($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'category' => $q->category,
                'priority' => $q->priority,
                'answer' => $q->answer,
                'answered_at' => $q->answered_at?->format('d.m.Y H:i'),
            ]),
            'kpis' => $diagnostic->kpiCalculation?->getAllKPIs(),
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
            'diagnostics' => $diagnostics->map(fn ($d) => [
                'id' => $d->id,
                'version' => $d->version,
                'overall_score' => $d->overall_score,
                'status' => $d->status,
                'completed_at' => $d->completed_at?->format('d.m.Y'),
            ]),
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

        // Check if report exists
        $report = $diagnostic->reports()
            ->where('report_type', $type)
            ->first();

        if (!$report) {
            $report = $this->diagnosticService->generateReport($diagnostic, $type);
        }

        $report->incrementDownloadCount();

        // Return HTML for now - PDF generation can be added later
        return response($report->html_content)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="diagnostic-report-' . $diagnostic->version . '.html"');
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
            'diagnostic' => [
                'id' => $diagnostic->id,
                'version' => $diagnostic->version,
                'overall_score' => $diagnostic->overall_score,
                'category_scores' => [
                    'marketing' => $diagnostic->marketing_score,
                    'sales' => $diagnostic->sales_score,
                    'content' => $diagnostic->content_score,
                    'funnel' => $diagnostic->funnel_score,
                ],
                'completed_at' => $diagnostic->completed_at?->toISOString(),
            ],
        ]);
    }
}
