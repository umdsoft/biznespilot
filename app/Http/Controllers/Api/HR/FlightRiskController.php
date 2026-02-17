<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\FlightRisk;
use App\Models\User;
use App\Services\HR\FlightRiskService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Flight Risk API Controller
 *
 * Hodimlarning ketish xavfi (flight risk) tahlili uchun API.
 * Proaktiv HR qarorlarini qo'llab-quvvatlaydi.
 */
class FlightRiskController extends Controller
{
    public function __construct(
        protected FlightRiskService $flightRiskService
    ) {}

    /**
     * Barcha hodimlar flight risk ro'yxati
     */
    public function index(Request $request, string $businessId): JsonResponse
    {
        $business = auth()->user()->businesses()->findOrFail($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $riskLevel = $request->input('risk_level'); // low, medium, high, critical
        $sortBy = $request->input('sort_by', 'risk_score');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = FlightRisk::where('business_id', $businessId)
            ->with(['user:id,name,email'])
            ->orderBy($sortBy, $sortDir);

        if ($riskLevel) {
            $query->where('risk_level', $riskLevel);
        }

        $risks = $query->paginate($perPage);

        $risks->getCollection()->transform(function ($risk) {
            return [
                'id' => $risk->id,
                'user' => $risk->user ? [
                    'id' => $risk->user->id,
                    'name' => $risk->user->name,
                    'email' => $risk->user->email,
                ] : null,
                'risk_score' => $risk->risk_score,
                'risk_level' => $risk->risk_level,
                'risk_level_label' => $this->getRiskLevelLabel($risk->risk_level),
                'risk_factors' => $risk->risk_factors,
                'factor_scores' => $this->getFactorScores($risk),
                'top_risk_factors' => $risk->top_risk_factors,
                'recommended_actions' => $risk->recommended_actions ?? [],
                'updated_at' => $risk->updated_at->format('d.m.Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $risks,
        ]);
    }

    /**
     * Bitta hodim flight risk ma'lumotlari
     */
    public function show(Request $request, string $businessId, string $userId): JsonResponse
    {
        $business = auth()->user()->businesses()->findOrFail($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Hodim topilmadi',
            ], 404);
        }

        $risk = FlightRisk::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->first();

        if (!$risk) {
            return response()->json([
                'success' => false,
                'message' => 'Flight risk ma\'lumotlari topilmadi',
            ], 404);
        }

        // Risk tarixi
        $history = $risk->level_history ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'current' => [
                    'id' => $risk->id,
                    'risk_score' => $risk->risk_score,
                    'risk_level' => $risk->risk_level,
                    'risk_level_label' => $this->getRiskLevelLabel($risk->risk_level),
                ],
                'factors' => $this->formatRiskFactors($risk),
                'recommended_actions' => $risk->recommended_actions ?? [],
                'actions_taken' => $risk->actions_taken ?? [],
                'history' => array_slice($history, 0, 10),
                'updated_at' => $risk->updated_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Flight risk statistikasi
     */
    public function statistics(Request $request, string $businessId): JsonResponse
    {
        $business = auth()->user()->businesses()->findOrFail($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        // Risk level distribution
        $distribution = FlightRisk::where('business_id', $businessId)
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        // O'rtacha risk score
        $avgRiskScore = FlightRisk::where('business_id', $businessId)
            ->avg('risk_score') ?? 0;

        // Top 5 xavfli hodimlar
        $topRisks = FlightRisk::where('business_id', $businessId)
            ->whereIn('risk_level', [FlightRisk::LEVEL_HIGH, FlightRisk::LEVEL_CRITICAL])
            ->with('user:id,name,email')
            ->orderBy('risk_score', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'user' => $r->user ? [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                ] : null,
                'risk_score' => $r->risk_score,
                'risk_level' => $r->risk_level,
                'top_factors' => array_slice($r->top_risk_factors, 0, 2),
            ]);

        // Factor analysis
        $factorAnalysis = $this->analyzeRiskFactors($businessId);

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_employees' => array_sum($distribution),
                    'avg_risk_score' => round($avgRiskScore, 1),
                ],
                'distribution' => [
                    'critical' => [
                        'count' => $distribution['critical'] ?? 0,
                        'label' => 'Juda yuqori xavf',
                        'color' => 'red',
                    ],
                    'high' => [
                        'count' => $distribution['high'] ?? 0,
                        'label' => 'Yuqori xavf',
                        'color' => 'orange',
                    ],
                    'moderate' => [
                        'count' => $distribution['moderate'] ?? 0,
                        'label' => "O'rtacha xavf",
                        'color' => 'yellow',
                    ],
                    'low' => [
                        'count' => $distribution['low'] ?? 0,
                        'label' => 'Past xavf',
                        'color' => 'green',
                    ],
                ],
                'top_risk_employees' => $topRisks,
                'factor_analysis' => $factorAnalysis,
            ],
        ]);
    }

    /**
     * Hodim uchun flight risk ni qayta hisoblash
     */
    public function recalculate(Request $request, string $businessId, string $userId): JsonResponse
    {
        $business = auth()->user()->businesses()->findOrFail($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Hodim topilmadi',
            ], 404);
        }

        try {
            $risk = $this->flightRiskService->calculateForEmployee($business, $user);

            return response()->json([
                'success' => true,
                'message' => 'Flight risk muvaffaqiyatli qayta hisoblandi',
                'data' => [
                    'id' => $risk->id,
                    'risk_score' => $risk->risk_score,
                    'risk_level' => $risk->risk_level,
                    'risk_level_label' => $this->getRiskLevelLabel($risk->risk_level),
                    'factor_scores' => $this->getFactorScores($risk),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hisoblashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mitigation action qo'shish
     */
    public function addMitigationAction(Request $request, string $businessId, string $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|max:500',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $risk = FlightRisk::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->first();

        if (!$risk) {
            return response()->json([
                'success' => false,
                'message' => 'Flight risk ma\'lumotlari topilmadi',
            ], 404);
        }

        $actions = $risk->actions_taken ?? [];
        $actions[] = [
            'action' => $request->action,
            'due_date' => $request->due_date,
            'assigned_to' => $request->assigned_to,
            'status' => 'pending',
            'created_at' => now()->toISOString(),
            'created_by' => auth()->id(),
        ];

        $risk->update(['actions_taken' => $actions]);

        return response()->json([
            'success' => true,
            'message' => 'Chora-tadbir qo\'shildi',
            'data' => [
                'actions_taken' => $risk->actions_taken,
            ],
        ]);
    }

    /**
     * Mitigation action ni yakunlash
     */
    public function completeMitigationAction(Request $request, string $businessId, string $userId, int $actionIndex): JsonResponse
    {
        $risk = FlightRisk::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->first();

        if (!$risk) {
            return response()->json([
                'success' => false,
                'message' => 'Flight risk ma\'lumotlari topilmadi',
            ], 404);
        }

        $actions = $risk->actions_taken ?? [];

        if (!isset($actions[$actionIndex])) {
            return response()->json([
                'success' => false,
                'message' => 'Chora-tadbir topilmadi',
            ], 404);
        }

        $actions[$actionIndex]['status'] = 'completed';
        $actions[$actionIndex]['completed_at'] = now()->toISOString();
        $actions[$actionIndex]['completed_by'] = auth()->id();

        $risk->update(['actions_taken' => $actions]);

        return response()->json([
            'success' => true,
            'message' => 'Chora-tadbir yakunlandi',
        ]);
    }

    /**
     * Risk level label
     */
    protected function getRiskLevelLabel(string $level): string
    {
        return match ($level) {
            'critical' => 'Juda yuqori xavf',
            'high' => 'Yuqori xavf',
            'medium' => "O'rtacha xavf",
            'low' => 'Past xavf',
            default => $level,
        };
    }

    /**
     * Risk factors ni formatlash
     */
    protected function formatRiskFactors(FlightRisk $risk): array
    {
        $factorScores = $this->getFactorScores($risk);
        $factorLabels = [
            'engagement' => 'Ishga qiziqish',
            'tenure' => 'Ish staji',
            'compensation' => 'Kompensatsiya',
            'growth' => "O'sish imkoniyati",
            'workload' => 'Ish yuki',
            'recognition' => 'Tan olish',
        ];

        $factors = [];
        foreach ($factorScores as $key => $score) {
            $factors[] = [
                'key' => $key,
                'label' => $factorLabels[$key] ?? $key,
                'score' => $score,
                'status' => $this->getFactorStatus($score),
            ];
        }

        // Score bo'yicha tartiblash (yuqoridan pastga)
        usort($factors, fn($a, $b) => $b['score'] <=> $a['score']);

        return $factors;
    }

    /**
     * Factor status
     */
    protected function getFactorStatus(float $score): string
    {
        return match (true) {
            $score >= 70 => 'critical',
            $score >= 50 => 'warning',
            $score >= 30 => 'elevated',
            default => 'normal',
        };
    }

    /**
     * Risk factors tahlili
     */
    protected function analyzeRiskFactors(string $businessId): array
    {
        $risks = FlightRisk::where('business_id', $businessId)->get();

        if ($risks->isEmpty()) {
            return [];
        }

        $factorTotals = [
            'engagement' => 0,
            'tenure' => 0,
            'compensation' => 0,
            'growth' => 0,
            'workload' => 0,
            'recognition' => 0,
        ];

        foreach ($risks as $risk) {
            $scores = $this->getFactorScores($risk);
            foreach ($scores as $key => $score) {
                if (isset($factorTotals[$key])) {
                    $factorTotals[$key] += $score;
                }
            }
        }

        $count = $risks->count();
        $factorLabels = [
            'engagement' => 'Ishga qiziqish',
            'tenure' => 'Ish staji',
            'compensation' => 'Kompensatsiya',
            'growth' => "O'sish imkoniyati",
            'workload' => 'Ish yuki',
            'recognition' => 'Tan olish',
        ];

        $analysis = [];
        foreach ($factorTotals as $key => $total) {
            $avg = $total / $count;
            $analysis[] = [
                'key' => $key,
                'label' => $factorLabels[$key],
                'avg_score' => round($avg, 1),
                'impact' => $this->getImpactLevel($avg),
            ];
        }

        // O'rtacha ball bo'yicha tartiblash
        usort($analysis, fn($a, $b) => $b['avg_score'] <=> $a['avg_score']);

        return $analysis;
    }

    /**
     * Impact level
     */
    protected function getImpactLevel(float $avgScore): string
    {
        return match (true) {
            $avgScore >= 60 => 'Yuqori ta\'sir',
            $avgScore >= 40 => "O'rtacha ta'sir",
            default => 'Past ta\'sir',
        };
    }

    /**
     * Get factor scores from individual factor columns
     */
    protected function getFactorScores(FlightRisk $risk): array
    {
        return [
            'engagement' => $risk->engagement_factor ?? 0,
            'tenure' => $risk->tenure_factor ?? 0,
            'compensation' => $risk->compensation_factor ?? 0,
            'growth' => $risk->growth_factor ?? 0,
            'workload' => $risk->workload_factor ?? 0,
            'recognition' => $risk->recognition_factor ?? 0,
        ];
    }
}
