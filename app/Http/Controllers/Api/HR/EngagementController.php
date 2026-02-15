<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\EmployeeEngagement;
use App\Models\User;
use App\Services\HR\EngagementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Engagement API Controller
 *
 * Hodimlar engagement (ishga qiziqish) ballari va tahlili uchun API.
 * Gallup Q12 metodologiyasiga asoslangan.
 */
class EngagementController extends Controller
{
    public function __construct(
        protected EngagementService $engagementService
    ) {}

    /**
     * Barcha hodimlar engagement ro'yxati
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
        $engagementLevel = $request->input('engagement_level'); // highly_engaged, engaged, neutral, disengaged

        $query = EmployeeEngagement::where('business_id', $businessId)
            ->with(['user:id,name,email'])
            ->orderBy('period', 'desc')
            ->orderBy('overall_score', 'desc');

        if ($engagementLevel) {
            $query->where('engagement_level', $engagementLevel);
        }

        // Faqat eng so'nggi yozuvlarni ko'rsatish
        if ($request->input('latest_only', true)) {
            $latestPeriod = EmployeeEngagement::where('business_id', $businessId)
                ->max('period');

            if ($latestPeriod) {
                $query->where('period', $latestPeriod);
            }
        }

        $engagements = $query->paginate($perPage);

        // Har bir yozuvga qo'shimcha ma'lumot qo'shish
        $engagements->getCollection()->transform(function ($engagement) {
            return [
                'id' => $engagement->id,
                'user' => $engagement->user ? [
                    'id' => $engagement->user->id,
                    'name' => $engagement->user->name,
                    'email' => $engagement->user->email,
                ] : null,
                'overall_score' => $engagement->overall_score,
                'engagement_level' => $engagement->engagement_level,
                'engagement_level_label' => $this->getEngagementLevelLabel($engagement->engagement_level),
                'period' => $engagement->period,
                'score_breakdown' => $engagement->getScoreBreakdown(),
                'updated_at' => $engagement->updated_at?->format('d.m.Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $engagements,
        ]);
    }

    /**
     * Bitta hodim engagement ma'lumotlari
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

        // Joriy oylik engagement
        $currentEngagement = EmployeeEngagement::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->orderBy('period', 'desc')
            ->first();

        // Engagement tarixi (so'nggi 6 oy)
        $history = EmployeeEngagement::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->orderBy('period', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($e) => [
                'period' => $e->period,
                'score' => $e->overall_score,
                'level' => $e->engagement_level,
            ]);

        // Trend hisoblash
        $trend = $this->calculateEngagementTrend($history);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'current' => $currentEngagement ? [
                    'id' => $currentEngagement->id,
                    'overall_score' => $currentEngagement->overall_score,
                    'engagement_level' => $currentEngagement->engagement_level,
                    'engagement_level_label' => $this->getEngagementLevelLabel($currentEngagement->engagement_level),
                    'score_breakdown' => $currentEngagement->getScoreBreakdown(),
                    'period' => $currentEngagement->period,
                    'updated_at' => $currentEngagement->updated_at?->format('d.m.Y H:i'),
                ] : null,
                'history' => $history,
                'trend' => $trend,
            ],
        ]);
    }

    /**
     * Engagement ballni qo'lda yangilash/kiritish
     */
    public function store(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'work_satisfaction' => 'nullable|numeric|min:0|max:100',
            'team_collaboration' => 'nullable|numeric|min:0|max:100',
            'growth_opportunities' => 'nullable|numeric|min:0|max:100',
            'recognition_frequency' => 'nullable|numeric|min:0|max:100',
            'manager_support' => 'nullable|numeric|min:0|max:100',
            'work_life_balance' => 'nullable|numeric|min:0|max:100',
            'purpose_clarity' => 'nullable|numeric|min:0|max:100',
            'resources_adequacy' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = auth()->user()->businesses()->findOrFail($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        // Joriy oy uchun engagement topish yoki yaratish
        $currentPeriod = Carbon::now()->format('Y-m');

        $engagement = EmployeeEngagement::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $request->user_id,
                'period' => $currentPeriod,
            ],
            [
                'work_satisfaction' => $request->work_satisfaction ?? 0,
                'team_collaboration' => $request->team_collaboration ?? 0,
                'growth_opportunities' => $request->growth_opportunities ?? 0,
                'recognition_frequency' => $request->recognition_frequency ?? 0,
                'manager_support' => $request->manager_support ?? 0,
                'work_life_balance' => $request->work_life_balance ?? 0,
                'purpose_clarity' => $request->purpose_clarity ?? 0,
                'resources_adequacy' => $request->resources_adequacy ?? 0,
            ]
        );

        // Overall score ni hisoblash
        $engagement->overall_score = $engagement->calculateOverallScore();
        $engagement->engagement_level = $this->determineEngagementLevel($engagement->overall_score);
        $engagement->save();

        return response()->json([
            'success' => true,
            'message' => 'Engagement muvaffaqiyatli saqlandi',
            'data' => [
                'id' => $engagement->id,
                'overall_score' => $engagement->overall_score,
                'engagement_level' => $engagement->engagement_level,
                'score_breakdown' => $engagement->getScoreBreakdown(),
            ],
        ]);
    }

    /**
     * Umumiy engagement statistikasi
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

        $currentPeriod = Carbon::now()->format('Y-m');

        // Joriy oy statistikasi
        $currentStats = EmployeeEngagement::where('business_id', $businessId)
            ->where('period', $currentPeriod)
            ->selectRaw('
                COUNT(*) as total,
                AVG(overall_score) as avg_score,
                MIN(overall_score) as min_score,
                MAX(overall_score) as max_score,
                SUM(CASE WHEN engagement_level = "highly_engaged" THEN 1 ELSE 0 END) as highly_engaged,
                SUM(CASE WHEN engagement_level = "engaged" THEN 1 ELSE 0 END) as engaged,
                SUM(CASE WHEN engagement_level = "neutral" THEN 1 ELSE 0 END) as neutral,
                SUM(CASE WHEN engagement_level = "disengaged" THEN 1 ELSE 0 END) as disengaged
            ')
            ->first();

        // Komponent bo'yicha o'rtacha balllar
        $componentAvg = EmployeeEngagement::where('business_id', $businessId)
            ->where('period', $currentPeriod)
            ->selectRaw('
                AVG(work_satisfaction) as work_satisfaction,
                AVG(team_collaboration) as team_collaboration,
                AVG(growth_opportunities) as growth_opportunities,
                AVG(recognition_frequency) as recognition_frequency,
                AVG(manager_support) as manager_support,
                AVG(work_life_balance) as work_life_balance,
                AVG(purpose_clarity) as purpose_clarity,
                AVG(resources_adequacy) as resources_adequacy
            ')
            ->first();

        // Trend (so'nggi 6 oy)
        $trend = EmployeeEngagement::where('business_id', $businessId)
            ->where('period', '>=', Carbon::now()->subMonths(6)->format('Y-m'))
            ->selectRaw('period, AVG(overall_score) as avg_score')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'current_period' => Carbon::now()->format('F Y'),
                'overview' => [
                    'total_employees' => $currentStats->total ?? 0,
                    'avg_score' => round($currentStats->avg_score ?? 0, 1),
                    'min_score' => round($currentStats->min_score ?? 0, 1),
                    'max_score' => round($currentStats->max_score ?? 0, 1),
                ],
                'distribution' => [
                    'highly_engaged' => [
                        'count' => $currentStats->highly_engaged ?? 0,
                        'label' => "Juda qiziqgan",
                    ],
                    'engaged' => [
                        'count' => $currentStats->engaged ?? 0,
                        'label' => 'Qiziqgan',
                    ],
                    'neutral' => [
                        'count' => $currentStats->neutral ?? 0,
                        'label' => 'Neytral',
                    ],
                    'disengaged' => [
                        'count' => $currentStats->disengaged ?? 0,
                        'label' => 'Qiziqmagan',
                    ],
                ],
                'components' => [
                    ['key' => 'work_satisfaction', 'label' => 'Ish qoniqishi', 'score' => round($componentAvg->work_satisfaction ?? 0, 1)],
                    ['key' => 'team_collaboration', 'label' => 'Jamoaviy hamkorlik', 'score' => round($componentAvg->team_collaboration ?? 0, 1)],
                    ['key' => 'growth_opportunities', 'label' => "O'sish imkoniyatlari", 'score' => round($componentAvg->growth_opportunities ?? 0, 1)],
                    ['key' => 'recognition_frequency', 'label' => 'Tan olish', 'score' => round($componentAvg->recognition_frequency ?? 0, 1)],
                    ['key' => 'manager_support', 'label' => "Rahbar qo'llovi", 'score' => round($componentAvg->manager_support ?? 0, 1)],
                    ['key' => 'work_life_balance', 'label' => 'Ish-hayot balansi', 'score' => round($componentAvg->work_life_balance ?? 0, 1)],
                    ['key' => 'purpose_clarity', 'label' => 'Maqsad aniqligi', 'score' => round($componentAvg->purpose_clarity ?? 0, 1)],
                    ['key' => 'resources_adequacy', 'label' => 'Resurslar yetarliligi', 'score' => round($componentAvg->resources_adequacy ?? 0, 1)],
                ],
                'trend' => $trend->map(fn($t) => [
                    'period' => $t->period,
                    'score' => round($t->avg_score, 1),
                ]),
            ],
        ]);
    }

    /**
     * Hodim uchun engagement ballni qayta hisoblash
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
            $engagement = $this->engagementService->calculateForEmployee($business, $user);

            return response()->json([
                'success' => true,
                'message' => 'Engagement muvaffaqiyatli qayta hisoblandi',
                'data' => [
                    'id' => $engagement->id,
                    'overall_score' => $engagement->overall_score,
                    'engagement_level' => $engagement->engagement_level,
                    'score_breakdown' => $engagement->getScoreBreakdown(),
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
     * Engagement level label
     */
    protected function getEngagementLevelLabel(?string $level): string
    {
        return match ($level) {
            'highly_engaged' => "Juda qiziqgan",
            'engaged' => 'Qiziqgan',
            'neutral' => 'Neytral',
            'disengaged' => 'Qiziqmagan',
            default => $level ?? 'Noma\'lum',
        };
    }

    /**
     * Determine engagement level from score
     */
    protected function determineEngagementLevel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'highly_engaged',
            $score >= 65 => 'engaged',
            $score >= 50 => 'neutral',
            default => 'disengaged',
        };
    }

    /**
     * Trend hisoblash
     */
    protected function calculateEngagementTrend($history): array
    {
        if ($history->count() < 2) {
            return [
                'direction' => 'stable',
                'change' => 0,
            ];
        }

        $latest = $history->first()['score'] ?? 0;
        $previous = $history->skip(1)->first()['score'] ?? 0;

        $change = $latest - $previous;

        return [
            'direction' => $change > 2 ? 'up' : ($change < -2 ? 'down' : 'stable'),
            'change' => round($change, 1),
        ];
    }
}
