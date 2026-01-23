<?php

namespace App\Http\Controllers\Api\CallCenter;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Jobs\CallCenter\ProcessCallAnalysisJob;
use App\Models\CallAnalysis;
use App\Models\CallLog;
use App\Services\CallCenter\CallAnalysisService;
use App\Services\CallCenter\SpeechToTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallAnalysisController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected SpeechToTextService $sttService,
        protected CallAnalysisService $analysisService
    ) {}

    /**
     * Get calls list for a lead or business
     * GET /api/v1/call-center/calls
     *
     * Query params:
     * - lead_id: UUID (filter by lead)
     * - status: string (filter by call status)
     * - analysis_status: string (filter by analysis status)
     * - date_from: date
     * - date_to: date
     * - per_page: int (default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $query = CallLog::where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'user:id,name', 'analysis'])
            ->orderBy('started_at', 'desc');

        // Filter by lead
        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        // Filter by call status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by analysis status
        if ($request->has('analysis_status')) {
            $query->where('analysis_status', $request->analysis_status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('started_at', '<=', $request->date_to);
        }

        // Only analyzable calls (with recording, minimum duration)
        if ($request->boolean('analyzable_only')) {
            $query->analyzable();
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $calls = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $calls->items(),
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
            ],
        ]);
    }

    /**
     * Get single call with analysis
     * GET /api/v1/call-center/calls/{id}
     */
    public function show(string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $call = CallLog::where('id', $id)
            ->where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'user:id,name', 'analysis'])
            ->first();

        if (! $call) {
            return response()->json(['success' => false, 'error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatCallResponse($call),
        ]);
    }

    /**
     * Queue single call for analysis
     * POST /api/v1/call-center/calls/{id}/analyze
     */
    public function analyze(string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $call = CallLog::where('id', $id)
            ->where('business_id', $business->id)
            ->first();

        if (! $call) {
            return response()->json(['success' => false, 'error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        // Check if already analyzing or completed
        if (in_array($call->analysis_status, [
            CallLog::ANALYSIS_STATUS_QUEUED,
            CallLog::ANALYSIS_STATUS_TRANSCRIBING,
            CallLog::ANALYSIS_STATUS_ANALYZING,
        ])) {
            return response()->json([
                'success' => false,
                'error' => 'Qo\'ng\'iroq allaqachon tahlil jarayonida',
            ], 409);
        }

        // Check if can be analyzed
        if (! $call->canBeAnalyzed() && $call->analysis_status !== CallLog::ANALYSIS_STATUS_FAILED) {
            return response()->json([
                'success' => false,
                'error' => 'Bu qo\'ng\'iroqni tahlil qilib bo\'lmaydi (yozuv yo\'q yoki davomiylik 30 sekunddan kam)',
            ], 422);
        }

        // Queue for analysis
        $call->queueForAnalysis();
        ProcessCallAnalysisJob::dispatch($call);

        // Estimate cost
        $sttCost = $this->sttService->estimateCost($call->duration);
        $analysisCost = $this->analysisService->estimateCost();

        return response()->json([
            'success' => true,
            'message' => 'Qo\'ng\'iroq tahlil navbatiga qo\'shildi',
            'estimated_cost' => [
                'stt' => $sttCost,
                'analysis' => $analysisCost,
                'total_uzs' => $sttCost['uzs'] + $analysisCost['uzs'],
                'total_formatted' => number_format($sttCost['uzs'] + $analysisCost['uzs'], 0, '.', ' ').' so\'m',
            ],
        ]);
    }

    /**
     * Queue multiple calls for analysis
     * POST /api/v1/call-center/calls/analyze-bulk
     *
     * Request body:
     * { "call_ids": ["uuid1", "uuid2", "uuid3"] }
     */
    public function analyzeBulk(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $request->validate([
            'call_ids' => 'required|array|min:1|max:'.config('call-center.rate_limits.bulk_max_calls', 10),
            'call_ids.*' => 'uuid',
        ]);

        $callIds = $request->input('call_ids');

        $calls = CallLog::whereIn('id', $callIds)
            ->where('business_id', $business->id)
            ->get();

        if ($calls->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'Qo\'ng\'iroqlar topilmadi'], 404);
        }

        $queued = [];
        $skipped = [];
        $totalEstimatedCost = 0;

        foreach ($calls as $call) {
            // Skip if already processing
            if (in_array($call->analysis_status, [
                CallLog::ANALYSIS_STATUS_QUEUED,
                CallLog::ANALYSIS_STATUS_TRANSCRIBING,
                CallLog::ANALYSIS_STATUS_ANALYZING,
            ])) {
                $skipped[] = [
                    'id' => $call->id,
                    'reason' => 'Allaqachon tahlil jarayonida',
                ];

                continue;
            }

            // Skip if can't be analyzed (unless previously failed)
            if (! $call->canBeAnalyzed() && $call->analysis_status !== CallLog::ANALYSIS_STATUS_FAILED) {
                $skipped[] = [
                    'id' => $call->id,
                    'reason' => 'Tahlil qilib bo\'lmaydi (yozuv yo\'q yoki davomiylik kam)',
                ];

                continue;
            }

            // Queue for analysis
            $call->queueForAnalysis();
            ProcessCallAnalysisJob::dispatch($call);

            // Calculate cost
            $sttCost = $this->sttService->estimateCost($call->duration);
            $analysisCost = $this->analysisService->estimateCost();
            $callCost = $sttCost['uzs'] + $analysisCost['uzs'];
            $totalEstimatedCost += $callCost;

            $queued[] = [
                'id' => $call->id,
                'estimated_cost_uzs' => $callCost,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($queued).' ta qo\'ng\'iroq tahlil navbatiga qo\'shildi',
            'queued' => $queued,
            'skipped' => $skipped,
            'total_estimated_cost' => [
                'uzs' => $totalEstimatedCost,
                'formatted' => number_format($totalEstimatedCost, 0, '.', ' ').' so\'m',
            ],
        ]);
    }

    /**
     * Get analysis details
     * GET /api/v1/call-center/calls/{id}/analysis
     */
    public function getAnalysis(string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $call = CallLog::where('id', $id)
            ->where('business_id', $business->id)
            ->first();

        if (! $call) {
            return response()->json(['success' => false, 'error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        $analysis = $call->analysis;

        if (! $analysis) {
            return response()->json([
                'success' => false,
                'error' => 'Tahlil mavjud emas',
                'analysis_status' => $call->analysis_status,
                'analysis_error' => $call->analysis_error,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $analysis->id,
                'call_log_id' => $analysis->call_log_id,
                'overall_score' => $analysis->overall_score,
                'score_label' => $analysis->score_label,
                'score_color' => $analysis->score_color,
                'stages' => $analysis->stages_with_labels,
                'anti_patterns' => $analysis->anti_patterns,
                'recommendations' => $analysis->recommendations,
                'strengths' => $analysis->strengths,
                'weaknesses' => $analysis->weaknesses,
                'transcript' => $analysis->transcript,
                'cost' => [
                    'stt' => $analysis->stt_cost,
                    'analysis' => $analysis->analysis_cost,
                    'total' => $analysis->total_cost,
                    'total_uzs' => $analysis->total_cost_uzs,
                    'formatted' => $analysis->formatted_cost,
                ],
                'models' => [
                    'stt' => $analysis->stt_model,
                    'analysis' => $analysis->analysis_model,
                ],
                'tokens' => [
                    'input' => $analysis->input_tokens,
                    'output' => $analysis->output_tokens,
                ],
                'processing_time_ms' => $analysis->processing_time_ms,
                'created_at' => $analysis->created_at,
                'updated_at' => $analysis->updated_at,
            ],
        ]);
    }

    /**
     * Estimate cost for calls
     * POST /api/v1/call-center/calls/estimate-cost
     *
     * Request body:
     * { "call_ids": ["uuid1", "uuid2"] }
     */
    public function estimateCost(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $request->validate([
            'call_ids' => 'required|array|min:1',
            'call_ids.*' => 'uuid',
        ]);

        $calls = CallLog::whereIn('id', $request->input('call_ids'))
            ->where('business_id', $business->id)
            ->get();

        $totalDuration = $calls->sum('duration');
        $callCount = $calls->count();

        $sttCost = $this->sttService->estimateCost($totalDuration);
        $analysisCost = $this->analysisService->estimateCost();
        $totalAnalysisCost = $analysisCost['uzs'] * $callCount;
        $totalCost = $sttCost['uzs'] + $totalAnalysisCost;

        return response()->json([
            'success' => true,
            'data' => [
                'call_count' => $callCount,
                'total_duration' => $totalDuration,
                'total_duration_formatted' => gmdate('H:i:s', $totalDuration),
                'stt_cost' => $sttCost,
                'analysis_cost_per_call' => $analysisCost,
                'total_analysis_cost_uzs' => $totalAnalysisCost,
                'total_cost_uzs' => $totalCost,
                'total_cost_formatted' => number_format($totalCost, 0, '.', ' ').' so\'m',
            ],
        ]);
    }

    /**
     * Get analysis statistics for business
     * GET /api/v1/call-center/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        // Date range
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $analysisQuery = CallAnalysis::whereHas('callLog', function ($q) use ($business, $dateFrom, $dateTo) {
            $q->where('business_id', $business->id)
                ->whereDate('started_at', '>=', $dateFrom)
                ->whereDate('started_at', '<=', $dateTo);
        });

        $stats = [
            'total_analyzed' => (clone $analysisQuery)->count(),
            'average_score' => round((clone $analysisQuery)->avg('overall_score') ?? 0, 1),
            'total_cost' => round((clone $analysisQuery)->sum(\DB::raw('stt_cost + analysis_cost')), 4),
            'total_cost_uzs' => round(
                ((clone $analysisQuery)->sum(\DB::raw('stt_cost + analysis_cost')) ?? 0) * config('call-center.currency.usd_to_uzs', 12800)
            ),
        ];

        // Score distribution
        $stats['score_distribution'] = [
            'excellent' => (clone $analysisQuery)->where('overall_score', '>=', 80)->count(),
            'good' => (clone $analysisQuery)->whereBetween('overall_score', [60, 79.99])->count(),
            'average' => (clone $analysisQuery)->whereBetween('overall_score', [40, 59.99])->count(),
            'poor' => (clone $analysisQuery)->where('overall_score', '<', 40)->count(),
        ];

        // Pending analysis count
        $stats['pending_analysis'] = CallLog::where('business_id', $business->id)
            ->analyzable()
            ->pendingAnalysis()
            ->count();

        $stats['total_cost_formatted'] = number_format($stats['total_cost_uzs'], 0, '.', ' ').' so\'m';

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Format call response with analysis data
     */
    protected function formatCallResponse(CallLog $call): array
    {
        $data = [
            'id' => $call->id,
            'provider_call_id' => $call->provider_call_id,
            'direction' => $call->direction,
            'direction_label' => $call->direction_label,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'status' => $call->status,
            'status_label' => $call->status_label,
            'duration' => $call->duration,
            'duration_formatted' => $call->formatted_duration,
            'recording_url' => $call->recording_url,
            'started_at' => $call->started_at,
            'ended_at' => $call->ended_at,
            'analysis_status' => $call->analysis_status,
            'analysis_status_label' => $call->analysis_status_label,
            'analysis_error' => $call->analysis_error,
            'can_be_analyzed' => $call->canBeAnalyzed(),
            'lead' => $call->lead ? [
                'id' => $call->lead->id,
                'name' => $call->lead->name,
                'phone' => $call->lead->phone ?? null,
            ] : null,
            'operator' => $call->user ? [
                'id' => $call->user->id,
                'name' => $call->user->name,
            ] : null,
        ];

        if ($call->analysis) {
            $data['analysis'] = [
                'id' => $call->analysis->id,
                'overall_score' => $call->analysis->overall_score,
                'score_label' => $call->analysis->score_label,
                'score_color' => $call->analysis->score_color,
                'cost' => [
                    'total_uzs' => $call->analysis->total_cost_uzs,
                    'formatted' => $call->analysis->formatted_cost,
                ],
            ];
        }

        return $data;
    }
}
