<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\EmployeeEngagement;
use App\Models\HRAlert;
use App\Models\HRSurvey;
use App\Models\HRSurveyResponse;
use App\Models\Notification;
use App\Models\User;
use App\Services\HR\EngagementService;
use App\Services\HR\RetentionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Survey API Controller
 *
 * HR so'rovnomalari uchun API.
 * Gallup Q12, Pulse va boshqa survey turlarini qo'llab-quvvatlaydi.
 */
class SurveyController extends Controller
{
    /**
     * Barcha so'rovnomalar ro'yxati
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
        $status = $request->input('status'); // draft, active, closed
        $type = $request->input('type'); // engagement, pulse, exit, etc.

        $query = HRSurvey::where('business_id', $businessId)
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $surveys = $query->paginate($perPage);

        $surveys->getCollection()->transform(function ($survey) {
            return [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'type' => $survey->type,
                'type_label' => $this->getTypeLabel($survey->type),
                'status' => $survey->status,
                'status_label' => $this->getStatusLabel($survey->status),
                'is_anonymous' => $survey->is_anonymous,
                'questions_count' => count($survey->questions ?? []),
                'response_count' => $survey->response_count ?? 0,
                'response_rate' => $survey->getResponseRate(),
                'start_date' => $survey->start_date?->format('d.m.Y'),
                'end_date' => $survey->end_date?->format('d.m.Y'),
                'is_active' => $survey->isActive(),
                'creator' => $survey->creator ? [
                    'id' => $survey->creator->id,
                    'name' => $survey->creator->name,
                ] : null,
                'created_at' => $survey->created_at->format('d.m.Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $surveys,
        ]);
    }

    /**
     * Bitta so'rovnoma tafsilotlari
     */
    public function show(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->with('creator:id,name')
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'type' => $survey->type,
                'type_label' => $this->getTypeLabel($survey->type),
                'status' => $survey->status,
                'status_label' => $this->getStatusLabel($survey->status),
                'questions' => $survey->questions,
                'target_audience' => $survey->target_audience,
                'is_anonymous' => $survey->is_anonymous,
                'start_date' => $survey->start_date?->format('Y-m-d'),
                'end_date' => $survey->end_date?->format('Y-m-d'),
                'response_count' => $survey->response_count ?? 0,
                'response_rate' => $survey->getResponseRate(),
                'target_count' => $survey->getTargetAudienceCount(),
                'settings' => $survey->settings,
                'is_active' => $survey->isActive(),
                'creator' => $survey->creator ? [
                    'id' => $survey->creator->id,
                    'name' => $survey->creator->name,
                ] : null,
                'created_at' => $survey->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Yangi so'rovnoma yaratish
     */
    public function store(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:engagement,pulse,exit,onboarding,360_feedback,custom',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.type' => 'required|in:scale,rating,choice,multiple_choice,yes_no,text',
            'target_audience' => 'nullable|array',
            'is_anonymous' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'settings' => 'nullable|array',
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

        $survey = HRSurvey::create([
            'business_id' => $businessId,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'status' => HRSurvey::STATUS_DRAFT,
            'questions' => $request->questions,
            'target_audience' => $request->target_audience ?? ['type' => 'all'],
            'is_anonymous' => $request->is_anonymous ?? true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'created_by' => Auth::id(),
            'settings' => $request->settings ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => "So'rovnoma muvaffaqiyatli yaratildi",
            'data' => [
                'id' => $survey->id,
            ],
        ], 201);
    }

    /**
     * Tayyor shablondan so'rovnoma yaratish
     */
    public function createFromTemplate(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template' => 'required|in:q12,pulse,exit',
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

        $survey = match ($request->template) {
            'q12' => HRSurvey::createQ12Survey($businessId, Auth::id()),
            'pulse' => HRSurvey::createPulseSurvey($businessId, Auth::id()),
            'exit' => $this->createExitSurvey($businessId),
            default => null,
        };

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Noma\'lum shablon',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "So'rovnoma shablondan yaratildi",
            'data' => [
                'id' => $survey->id,
                'title' => $survey->title,
            ],
        ], 201);
    }

    /**
     * So'rovnomani yangilash
     */
    public function update(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'questions' => 'sometimes|array|min:1',
            'target_audience' => 'nullable|array',
            'is_anonymous' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        // Faol so'rovnoma savollarini o'zgartirish mumkin emas
        if ($survey->status === HRSurvey::STATUS_ACTIVE && $request->has('questions')) {
            return response()->json([
                'success' => false,
                'message' => "Faol so'rovnoma savollarini o'zgartirish mumkin emas",
            ], 422);
        }

        $survey->update($request->only([
            'title', 'description', 'questions', 'target_audience',
            'is_anonymous', 'start_date', 'end_date', 'settings'
        ]));

        return response()->json([
            'success' => true,
            'message' => "So'rovnoma yangilandi",
        ]);
    }

    /**
     * So'rovnomani faollashtirish
     */
    public function activate(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        if (empty($survey->questions)) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnomada savollar yo'q",
            ], 422);
        }

        $survey->update([
            'status' => HRSurvey::STATUS_ACTIVE,
            'start_date' => $survey->start_date ?? now(),
        ]);

        // Barcha target xodimlarga bildirishnoma yuborish
        $this->sendSurveyAlerts($survey);

        return response()->json([
            'success' => true,
            'message' => "So'rovnoma faollashtirildi",
        ]);
    }

    /**
     * So'rovnoma haqida xodimlarga alert yuborish
     */
    protected function sendSurveyAlerts(HRSurvey $survey): void
    {
        $audience = $survey->target_audience ?? ['type' => 'all'];

        // Target xodimlarni olish
        $query = BusinessUser::where('business_id', $survey->business_id)
            ->whereNotNull('accepted_at');

        if (($audience['type'] ?? 'all') === 'department' && !empty($audience['departments'])) {
            $query->whereIn('department', $audience['departments']);
        } elseif (($audience['type'] ?? 'all') === 'users' && !empty($audience['user_ids'])) {
            $query->whereIn('user_id', $audience['user_ids']);
        }

        $employees = $query->get();

        foreach ($employees as $employee) {
            // HR Alert yaratish (HR bo'limida ko'rinadi)
            HRAlert::create([
                'business_id' => $survey->business_id,
                'user_id' => $employee->user_id,
                'type' => HRAlert::TYPE_SURVEY_AVAILABLE,
                'title' => "Yangi so'rovnoma: {$survey->title}",
                'message' => $survey->description ?? "Iltimos, so'rovnomani to'ldiring",
                'priority' => HRAlert::PRIORITY_MEDIUM,
                'status' => HRAlert::STATUS_NEW,
                'data' => [
                    'survey_id' => $survey->id,
                    'survey_title' => $survey->title,
                    'questions_count' => count($survey->questions ?? []),
                    'is_anonymous' => $survey->is_anonymous,
                    'end_date' => $survey->end_date?->format('d.m.Y'),
                    'action_url' => "/hr/surveys/{$survey->id}/fill",
                ],
            ]);

            // Asosiy bildirishnoma yaratish (bosh sahifada ko'rinadi)
            Notification::create([
                'business_id' => $survey->business_id,
                'user_id' => $employee->user_id,
                'type' => 'survey',
                'title' => "Yangi so'rovnoma: {$survey->title}",
                'message' => $survey->description ?? "Iltimos, so'rovnomani to'ldiring. " . count($survey->questions ?? []) . " ta savol.",
                'icon' => 'clipboard-document-list',
                'action_url' => "/hr/surveys/{$survey->id}/fill",
                'action_text' => "To'ldirish",
                'is_read' => false,
            ]);
        }
    }

    /**
     * So'rovnomani yopish
     */
    public function close(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        $survey->update([
            'status' => HRSurvey::STATUS_CLOSED,
            'end_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "So'rovnoma yopildi",
        ]);
    }

    /**
     * So'rovnomaga javob yuborish
     */
    public function submitResponse(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'time_spent_seconds' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        if (!$survey->isActive()) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma faol emas",
            ], 422);
        }

        // Avval javob berganligini tekshirish
        $existingResponse = HRSurveyResponse::where('survey_id', $surveyId)
            ->where('user_id', Auth::id())
            ->exists();

        if ($existingResponse) {
            return response()->json([
                'success' => false,
                'message' => "Siz bu so'rovnomaga allaqachon javob bergansiz",
            ], 422);
        }

        $response = HRSurveyResponse::create([
            'business_id' => $businessId,
            'survey_id' => $surveyId,
            'user_id' => $survey->is_anonymous ? null : Auth::id(),
            'answers' => $request->answers,
            'time_spent_seconds' => $request->time_spent_seconds,
            'is_complete' => true,
            'completed_at' => now(),
        ]);

        // Response count ni yangilash
        $survey->increment('response_count');

        // Engagement so'rovnomasi bo'lsa - avtomatik engagement hisoblash
        // Hatto anonim bo'lsa ham, engagement ballini hisoblash uchun user_id kerak
        if (in_array($survey->type, ['engagement', 'pulse'])) {
            $this->processEngagementSurvey($survey, $request->answers, Auth::user(), $businessId);
        }

        return response()->json([
            'success' => true,
            'message' => "Javobingiz qabul qilindi",
            'data' => [
                'id' => $response->id,
            ],
        ], 201);
    }

    /**
     * Engagement so'rovnomasini qayta ishlash
     */
    protected function processEngagementSurvey(HRSurvey $survey, array $answers, User $user, string $businessId): void
    {
        $business = auth()->user()->businesses()->findOrFail($businessId);
        if (!$business) return;

        $questions = $survey->questions ?? [];

        // Gallup Q12 mapping - savollarni komponentlarga bog'lash
        // Q12 standart savollar tartibida
        $q12Mapping = [
            0 => 'resources_adequacy',      // Q1: Nimalar kutilishini bilaman
            1 => 'resources_adequacy',      // Q2: Ishni bajarish uchun materiallar
            2 => 'work_satisfaction',       // Q3: Har kuni eng yaxshi ishni qilish
            3 => 'recognition_frequency',   // Q4: Oxirgi 7 kunda tan olinish
            4 => 'manager_support',         // Q5: Menejer menga g'amxo'rlik qiladi
            5 => 'growth_opportunities',    // Q6: Rivojlanishimni rag'batlantiruvchi
            6 => 'purpose_clarity',         // Q7: Fikrim inobatga olinadi
            7 => 'purpose_clarity',         // Q8: Kompaniya missiyasi
            8 => 'team_collaboration',      // Q9: Hamkasblar sifatli ish qiladi
            9 => 'team_collaboration',      // Q10: Eng yaxshi do'stim bor
            10 => 'growth_opportunities',   // Q11: 6 oyda progress haqida gaplashdik
            11 => 'growth_opportunities',   // Q12: O'rganish va o'sish imkoniyatlari
        ];

        // Har bir komponent uchun balllarni yig'ish
        $componentScores = [
            'work_satisfaction' => [],
            'team_collaboration' => [],
            'growth_opportunities' => [],
            'recognition_frequency' => [],
            'manager_support' => [],
            'work_life_balance' => [],
            'purpose_clarity' => [],
            'resources_adequacy' => [],
        ];

        foreach ($answers as $key => $value) {
            // q_0, q_1, ... formatidan indeksni olish
            if (preg_match('/q_(\d+)/', $key, $matches)) {
                $index = (int)$matches[1];

                // Agar scale/rating javob bo'lsa (1-5)
                if (is_numeric($value)) {
                    $component = $q12Mapping[$index] ?? null;
                    if ($component && isset($componentScores[$component])) {
                        // 1-5 ni 0-100 ga o'girish
                        $normalizedScore = (($value - 1) / 4) * 100;
                        $componentScores[$component][] = $normalizedScore;
                    }
                }
            }
        }

        // Har bir komponent uchun o'rtacha ball hisoblash
        $period = now()->format('Y-m');
        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $user->id,
                'period' => $period,
            ],
            [
                'overall_score' => 50.0,
                'work_satisfaction' => 50.0,
                'team_collaboration' => 50.0,
                'growth_opportunities' => 50.0,
                'recognition_frequency' => 50.0,
                'manager_support' => 50.0,
                'work_life_balance' => 50.0,
                'purpose_clarity' => 50.0,
                'resources_adequacy' => 50.0,
            ]
        );

        $updateData = ['last_survey_at' => now()];

        foreach ($componentScores as $component => $scores) {
            if (!empty($scores)) {
                $avgScore = array_sum($scores) / count($scores);
                $updateData[$component] = round($avgScore, 2);
            }
        }

        $engagement->update($updateData);

        // Overall score ni hisoblash
        $overallScore = $engagement->fresh()->calculateOverallScore();

        // Engagement level aniqlash
        $engagementLevel = match(true) {
            $overallScore >= 80 => 'highly_engaged',
            $overallScore >= 65 => 'engaged',
            $overallScore >= 50 => 'neutral',
            default => 'disengaged',
        };

        $engagement->update([
            'overall_score' => $overallScore,
            'engagement_level' => $engagementLevel,
            'q12_responses' => $answers,
        ]);

        // Flight risk ni yangilash (engagement past bo'lsa - risk yuqori)
        try {
            $retentionService = app(RetentionService::class);
            $retentionService->updateFlightRiskFromEngagement($user, $business, $overallScore);
        } catch (\Exception $e) {
            Log::error('Flight risk yangilashda xatolik', ['error' => $e->getMessage()]);
        }

        Log::info('Engagement so\'rovnomasi qayta ishlandi', [
            'user_id' => $user->id,
            'business_id' => $businessId,
            'overall_score' => $overallScore,
            'engagement_level' => $engagementLevel,
        ]);
    }

    /**
     * So'rovnoma natijalari
     */
    public function results(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        $results = $survey->calculateResults();

        return response()->json([
            'success' => true,
            'data' => [
                'survey' => [
                    'id' => $survey->id,
                    'title' => $survey->title,
                    'type' => $survey->type,
                ],
                'summary' => [
                    'response_count' => $survey->response_count ?? 0,
                    'response_rate' => $survey->getResponseRate(),
                    'target_count' => $survey->getTargetAudienceCount(),
                ],
                'results' => $results,
            ],
        ]);
    }

    /**
     * Hodim uchun mavjud so'rovnomalar
     */
    public function myAvailableSurveys(Request $request, string $businessId): JsonResponse
    {
        $userId = Auth::id();

        $surveys = HRSurvey::where('business_id', $businessId)
            ->where('status', HRSurvey::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get()
            ->filter(function ($survey) use ($userId) {
                // Avval javob berganligini tekshirish
                $hasResponded = HRSurveyResponse::where('survey_id', $survey->id)
                    ->where('user_id', $userId)
                    ->exists();

                return !$hasResponded;
            })
            ->map(fn($survey) => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'type' => $survey->type,
                'type_label' => $this->getTypeLabel($survey->type),
                'questions_count' => count($survey->questions ?? []),
                'end_date' => $survey->end_date?->format('d.m.Y'),
                'is_anonymous' => $survey->is_anonymous,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $surveys,
        ]);
    }

    /**
     * Survey statistikasi
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

        // Status bo'yicha taqsimot
        $statusDistribution = HRSurvey::where('business_id', $businessId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Type bo'yicha taqsimot
        $typeDistribution = HRSurvey::where('business_id', $businessId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // O'rtacha response rate
        $avgResponseRate = HRSurvey::where('business_id', $businessId)
            ->where('status', HRSurvey::STATUS_CLOSED)
            ->get()
            ->avg(fn($s) => $s->getResponseRate()) ?? 0;

        // Jami javoblar soni
        $totalResponses = HRSurveyResponse::whereHas('survey', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_surveys' => array_sum($statusDistribution),
                'status_distribution' => [
                    'draft' => $statusDistribution['draft'] ?? 0,
                    'active' => $statusDistribution['active'] ?? 0,
                    'closed' => $statusDistribution['closed'] ?? 0,
                ],
                'type_distribution' => $typeDistribution,
                'avg_response_rate' => round($avgResponseRate, 1),
                'total_responses' => $totalResponses,
            ],
        ]);
    }

    /**
     * Type label
     */
    protected function getTypeLabel(string $type): string
    {
        return match ($type) {
            'engagement' => 'Engagement Survey',
            'pulse' => 'Pulse Survey',
            'exit' => 'Exit Interview',
            'onboarding' => "Onboarding So'rovnomasi",
            '360_feedback' => '360 Feedback',
            'custom' => "Maxsus so'rovnoma",
            default => $type,
        };
    }

    /**
     * Status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Qoralama',
            'active' => 'Faol',
            'closed' => 'Yopilgan',
            default => $status,
        };
    }

    /**
     * So'rovnoma asosidagi engagement ma'lumotlari
     */
    public function surveyEngagement(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        // Faqat engagement va pulse turdagi so'rovnomalar
        if (!in_array($survey->type, ['engagement', 'pulse'])) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => "Bu turdagi so'rovnoma uchun engagement ma'lumotlari mavjud emas",
            ]);
        }

        // So'rovnoma davri - survey yaratilgan oy
        $period = $survey->created_at->format('Y-m');

        // So'rovnomaga javob bergan xodimlarning engagement ma'lumotlari
        $responses = HRSurveyResponse::where('survey_id', $surveyId)
            ->where('business_id', $businessId)
            ->get();

        if ($responses->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'avg_score' => 0,
                    'engagement_status' => 'unknown',
                    'trend' => ['direction' => 'stable', 'change' => 0],
                    'distribution' => [
                        'highly_engaged' => ['count' => 0, 'percentage' => 0],
                        'engaged' => ['count' => 0, 'percentage' => 0],
                        'neutral' => ['count' => 0, 'percentage' => 0],
                        'disengaged' => ['count' => 0, 'percentage' => 0],
                    ],
                    'components' => [],
                    'employees' => [],
                ],
            ]);
        }

        // So'rovnomaga javob bergan user_id lar
        $respondentUserIds = $responses->pluck('user_id')->filter()->unique()->toArray();

        // Bu period uchun engagement ma'lumotlarini olish
        $engagements = EmployeeEngagement::where('business_id', $businessId)
            ->where('period', $period)
            ->when(!empty($respondentUserIds), function ($q) use ($respondentUserIds) {
                $q->whereIn('user_id', $respondentUserIds);
            })
            ->with('user:id,name,email')
            ->get();

        if ($engagements->isEmpty()) {
            // Javoblardan to'g'ridan-to'g'ri hisoblash
            $engagements = $this->calculateEngagementFromResponses($responses, $businessId, $period);
        }

        // O'rtacha ball
        $avgScore = $engagements->avg('overall_score') ?? 0;

        // Engagement status
        $engagementStatus = match(true) {
            $avgScore >= 80 => 'excellent',
            $avgScore >= 65 => 'good',
            $avgScore >= 50 => 'average',
            default => 'needs_attention',
        };

        // Distribution
        $distribution = [
            'highly_engaged' => ['count' => $engagements->where('engagement_level', 'highly_engaged')->count()],
            'engaged' => ['count' => $engagements->where('engagement_level', 'engaged')->count()],
            'neutral' => ['count' => $engagements->where('engagement_level', 'neutral')->count()],
            'disengaged' => ['count' => $engagements->where('engagement_level', 'disengaged')->count()],
        ];

        $total = $engagements->count();
        foreach ($distribution as $level => &$data) {
            $data['percentage'] = $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0;
        }

        // Trend - oldingi period bilan solishtirish
        $previousPeriod = Carbon::parse($period . '-01')->subMonth()->format('Y-m');
        $previousAvg = EmployeeEngagement::where('business_id', $businessId)
            ->where('period', $previousPeriod)
            ->when(!empty($respondentUserIds), function ($q) use ($respondentUserIds) {
                $q->whereIn('user_id', $respondentUserIds);
            })
            ->avg('overall_score') ?? $avgScore;

        $change = $avgScore - $previousAvg;
        $trend = [
            'direction' => $change > 1 ? 'up' : ($change < -1 ? 'down' : 'stable'),
            'change' => round($change, 1),
        ];

        // Components
        $components = [
            'work_satisfaction' => round($engagements->avg('work_satisfaction') ?? 0, 1),
            'team_collaboration' => round($engagements->avg('team_collaboration') ?? 0, 1),
            'growth_opportunities' => round($engagements->avg('growth_opportunities') ?? 0, 1),
            'recognition_frequency' => round($engagements->avg('recognition_frequency') ?? 0, 1),
            'manager_support' => round($engagements->avg('manager_support') ?? 0, 1),
            'work_life_balance' => round($engagements->avg('work_life_balance') ?? 0, 1),
            'purpose_clarity' => round($engagements->avg('purpose_clarity') ?? 0, 1),
            'resources_adequacy' => round($engagements->avg('resources_adequacy') ?? 0, 1),
        ];

        // Employees list
        $employees = $engagements->map(fn($e) => [
            'id' => $e->user_id,
            'name' => $e->user->name ?? 'Noma\'lum',
            'overall_score' => round($e->overall_score, 1),
            'engagement_level' => $e->engagement_level,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'avg_score' => round($avgScore, 1),
                'engagement_status' => $engagementStatus,
                'trend' => $trend,
                'distribution' => $distribution,
                'components' => $components,
                'employees' => $employees,
                'period' => $period,
                'response_count' => $responses->count(),
            ],
        ]);
    }

    /**
     * Javoblardan engagement hisoblash
     */
    protected function calculateEngagementFromResponses($responses, string $businessId, string $period): \Illuminate\Support\Collection
    {
        $q12Mapping = [
            0 => 'resources_adequacy',
            1 => 'resources_adequacy',
            2 => 'work_satisfaction',
            3 => 'recognition_frequency',
            4 => 'manager_support',
            5 => 'growth_opportunities',
            6 => 'purpose_clarity',
            7 => 'purpose_clarity',
            8 => 'team_collaboration',
            9 => 'team_collaboration',
            10 => 'growth_opportunities',
            11 => 'growth_opportunities',
        ];

        $engagements = collect();

        foreach ($responses as $response) {
            $userId = $response->user_id;
            if (!$userId) continue;

            $answers = $response->answers ?? [];
            $componentScores = [
                'work_satisfaction' => [],
                'team_collaboration' => [],
                'growth_opportunities' => [],
                'recognition_frequency' => [],
                'manager_support' => [],
                'work_life_balance' => [],
                'purpose_clarity' => [],
                'resources_adequacy' => [],
            ];

            foreach ($answers as $key => $value) {
                if (preg_match('/q_(\d+)/', $key, $matches)) {
                    $index = (int)$matches[1];
                    if (is_numeric($value)) {
                        $component = $q12Mapping[$index] ?? null;
                        if ($component && isset($componentScores[$component])) {
                            $normalizedScore = (($value - 1) / 4) * 100;
                            $componentScores[$component][] = $normalizedScore;
                        }
                    }
                }
            }

            $engagement = EmployeeEngagement::firstOrCreate(
                ['business_id' => $businessId, 'user_id' => $userId, 'period' => $period],
                ['overall_score' => 50.0]
            );

            $updateData = [];
            foreach ($componentScores as $component => $scores) {
                if (!empty($scores)) {
                    $updateData[$component] = round(array_sum($scores) / count($scores), 2);
                }
            }

            if (!empty($updateData)) {
                $engagement->update($updateData);
                $engagement->refresh();
                $overallScore = $engagement->calculateOverallScore();
                $engagementLevel = match(true) {
                    $overallScore >= 80 => 'highly_engaged',
                    $overallScore >= 65 => 'engaged',
                    $overallScore >= 50 => 'neutral',
                    default => 'disengaged',
                };
                $engagement->update([
                    'overall_score' => $overallScore,
                    'engagement_level' => $engagementLevel,
                ]);
            }

            $engagement->load('user:id,name,email');
            $engagements->push($engagement);
        }

        return $engagements;
    }

    /**
     * So'rovnoma asosidagi flight risk ma'lumotlari
     */
    public function surveyFlightRisk(Request $request, string $businessId, string $surveyId): JsonResponse
    {
        $survey = HRSurvey::where('business_id', $businessId)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => "So'rovnoma topilmadi",
            ], 404);
        }

        if (!in_array($survey->type, ['engagement', 'pulse', 'exit'])) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => "Bu turdagi so'rovnoma uchun flight risk ma'lumotlari mavjud emas",
            ]);
        }

        $responses = HRSurveyResponse::where('survey_id', $surveyId)
            ->where('business_id', $businessId)
            ->get();

        if ($responses->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => 0,
                    'at_risk_count' => 0,
                    'risk_distribution' => [
                        'critical' => ['count' => 0, 'percentage' => 0],
                        'high' => ['count' => 0, 'percentage' => 0],
                        'medium' => ['count' => 0, 'percentage' => 0],
                        'low' => ['count' => 0, 'percentage' => 0],
                    ],
                    'factors' => [],
                    'employees' => [],
                ],
            ]);
        }

        $respondentUserIds = $responses->pluck('user_id')->filter()->unique()->toArray();

        $flightRisks = \App\Models\FlightRisk::where('business_id', $businessId)
            ->when(!empty($respondentUserIds), function ($q) use ($respondentUserIds) {
                $q->whereIn('user_id', $respondentUserIds);
            })
            ->with('user:id,name,email')
            ->get();

        if ($flightRisks->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => count($respondentUserIds),
                    'at_risk_count' => 0,
                    'risk_distribution' => [
                        'critical' => ['count' => 0, 'percentage' => 0],
                        'high' => ['count' => 0, 'percentage' => 0],
                        'medium' => ['count' => 0, 'percentage' => 0],
                        'low' => ['count' => count($respondentUserIds), 'percentage' => 100],
                    ],
                    'factors' => [],
                    'employees' => [],
                ],
            ]);
        }

        $total = $flightRisks->count();
        $atRiskCount = $flightRisks->whereIn('risk_level', ['critical', 'high'])->count();

        $distribution = [
            'critical' => ['count' => $flightRisks->where('risk_level', 'critical')->count()],
            'high' => ['count' => $flightRisks->where('risk_level', 'high')->count()],
            'medium' => ['count' => $flightRisks->where('risk_level', 'medium')->count()],
            'low' => ['count' => $flightRisks->where('risk_level', 'low')->count()],
        ];

        foreach ($distribution as $level => &$data) {
            $data['percentage'] = $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0;
        }

        // Risk factors aggregation
        $allFactors = [];
        foreach ($flightRisks as $risk) {
            $factors = $risk->risk_factors ?? [];
            foreach ($factors as $factor) {
                $name = $factor['name'] ?? $factor['factor'] ?? 'unknown';
                if (!isset($allFactors[$name])) {
                    $allFactors[$name] = ['count' => 0, 'total_impact' => 0];
                }
                $allFactors[$name]['count']++;
                $allFactors[$name]['total_impact'] += $factor['impact'] ?? $factor['score'] ?? 0;
            }
        }

        $factors = collect($allFactors)->map(fn($data, $name) => [
            'name' => $name,
            'count' => $data['count'],
            'avg_impact' => $data['count'] > 0 ? round($data['total_impact'] / $data['count'], 1) : 0,
        ])->sortByDesc('count')->values()->take(5);

        $employees = $flightRisks->map(fn($r) => [
            'id' => $r->user_id,
            'name' => $r->user->name ?? 'Noma\'lum',
            'risk_level' => $r->risk_level,
            'risk_score' => round($r->risk_score, 1),
            'main_factors' => collect($r->risk_factors ?? [])->take(2)->pluck('name')->toArray(),
        ])->sortByDesc('risk_score')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_employees' => $total,
                'at_risk_count' => $atRiskCount,
                'risk_distribution' => $distribution,
                'factors' => $factors,
                'employees' => $employees,
            ],
        ]);
    }

    /**
     * Exit survey yaratish
     */
    protected function createExitSurvey(string $businessId): HRSurvey
    {
        $questions = [
            ['text' => 'Kompaniyadan ketish qaroringizga nima sabab bo\'ldi?', 'type' => 'choice', 'options' => [
                "Yaxshiroq ish imkoniyati",
                "Maosh qoniqarsiz",
                "Boshqaruv muammolari",
                "O'sish imkoniyati yo'q",
                "Ish-hayot balansi",
                "Shaxsiy sabablar",
                "Boshqa",
            ]],
            ['text' => "Kompaniyada ishingizdan qanchalik qoniqgan edingiz?", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "Rahbaringiz bilan munosabatlaringiz qanday edi?", 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => 'Jamoa bilan ishlash tajribangiz qanday edi?', 'type' => 'scale', 'scale_min' => 1, 'scale_max' => 5],
            ['text' => "Kompaniyani do'stlaringizga tavsiya qilarmidingiz?", 'type' => 'yes_no'],
            ['text' => 'Kompaniya nimani yaxshilashi kerak deb o\'ylaysiz?', 'type' => 'text'],
            ['text' => 'Qo\'shimcha fikr-mulohazalaringiz', 'type' => 'text'],
        ];

        return HRSurvey::create([
            'business_id' => $businessId,
            'title' => "Exit Interview So'rovnomasi",
            'description' => "Kompaniyadan ketayotgan hodimlar uchun so'rovnoma",
            'type' => HRSurvey::TYPE_EXIT,
            'status' => HRSurvey::STATUS_DRAFT,
            'questions' => $questions,
            'target_audience' => ['type' => 'all'],
            'is_anonymous' => false, // Exit interviewlar odatda anonim emas
            'created_by' => Auth::id(),
            'settings' => [
                'allow_skip' => true,
                'show_progress' => true,
            ],
        ]);
    }
}
