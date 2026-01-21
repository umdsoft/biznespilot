<?php

namespace App\Services\HR;

use App\Events\HR\AttendancePatternChanged;
use App\Events\HR\EmployeeHired;
use App\Events\HR\EmployeePromoted;
use App\Events\HR\EmployeeTerminated;
use App\Events\HR\EngagementScoreChanged;
use App\Events\HR\FeedbackGiven;
use App\Events\HR\FlightRiskLevelChanged;
use App\Events\HR\GoalCompleted;
use App\Events\HR\OKRProgressUpdated;
use App\Events\HR\OneOnOneCompleted;
use App\Events\HR\RecognitionGiven;
use App\Events\HR\SurveySubmitted;
use App\Events\HR\WorkAnniversary;
use App\Models\Business;
use App\Models\EmployeeGoal;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * HROrchestrator - HR modulini markaziy boshqaruvchi service
 *
 * Vazifalar:
 * 1. HR eventlarni koordinatsiya qilish
 * 2. Avtomatik harakatlarni ishga tushirish
 * 3. Cross-module integratsiyani boshqarish
 * 4. Employee Health Score ni yangilash
 * 5. Flight Risk ni kuzatish
 *
 * DRY printsipi: Barcha HR logikasi shu yerda markazlashgan
 */
class HROrchestrator
{
    public function __construct(
        protected HRAlertService $alertService,
        protected EngagementService $engagementService,
        protected RetentionService $retentionService,
        protected OnboardingService $onboardingService,
        protected PerformanceService $performanceService
    ) {}

    // ================================================
    // EMPLOYEE LIFECYCLE EVENTS
    // ================================================

    /**
     * Yangi hodim ishga qabul qilinganda
     */
    public function onEmployeeHired(User $employee, Business $business, array $data = []): void
    {
        Log::info('HROrchestrator: Yangi hodim ishga qabul qilindi', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'business_id' => $business->id,
            'department' => $data['department'] ?? null,
        ]);

        // 1. Onboarding jarayonini boshlash
        $this->onboardingService->startOnboarding($employee, $business, $data);

        // 2. Welcome alert yaratish
        $this->alertService->createAlert(
            $business,
            'employee_hired',
            'Yangi hodim!',
            "{$employee->name} jamoaga qo'shildi. Xush kelibsiz!",
            [
                'priority' => 'medium',
                'user_id' => null, // HR va manager uchun
                'data' => [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'department' => $data['department'] ?? null,
                    'position' => $data['position'] ?? null,
                ],
            ]
        );

        // 3. Event dispatch
        event(new EmployeeHired(
            $employee,
            $business,
            $data['department'] ?? null,
            $data['position'] ?? null,
            $data['hired_by'] ?? null,
            $data
        ));

        // 4. HR dashboard yangilash
        $this->updateHRDashboard($business);
    }

    /**
     * Hodim ishdan ketganda
     */
    public function onEmployeeTerminated(User $employee, Business $business, string $reason, array $data = []): void
    {
        Log::info('HROrchestrator: Hodim ishdan ketdi', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'reason' => $reason,
        ]);

        // 1. Offboarding jarayonini boshlash
        $this->onboardingService->startOffboarding($employee, $business, $reason, $data);

        // 2. Turnover analytics yangilash
        $this->retentionService->recordTermination($employee, $business, $reason, $data);

        // 3. Alert yaratish
        $this->alertService->createAlert(
            $business,
            'employee_terminated',
            'Hodim ishdan ketdi',
            "{$employee->name} - " . $this->getReasonLabel($reason),
            [
                'priority' => 'high',
                'user_id' => null,
                'data' => [
                    'employee_id' => $employee->id,
                    'reason' => $reason,
                    'last_working_day' => $data['last_working_day'] ?? now()->toDateString(),
                ],
            ]
        );

        // 4. Event dispatch
        event(new EmployeeTerminated(
            $employee,
            $business,
            $reason,
            $data['terminated_by'] ?? null,
            $data['notes'] ?? null,
            isset($data['last_working_day']) ? new \DateTime($data['last_working_day']) : null
        ));

        // 5. HR dashboard yangilash
        $this->updateHRDashboard($business);
    }

    /**
     * Hodim lavozimi oshganda
     */
    public function onEmployeePromoted(User $employee, Business $business, string $oldPosition, string $newPosition, array $data = []): void
    {
        Log::info('HROrchestrator: Hodim lavozimi oshdi', [
            'employee_id' => $employee->id,
            'old_position' => $oldPosition,
            'new_position' => $newPosition,
        ]);

        // 1. Maosh tuzilmasini yangilash (agar kerak bo'lsa)
        if (isset($data['salary_change'])) {
            $this->performanceService->updateSalaryForPromotion($employee, $business, $data['salary_change']);
        }

        // 2. Tabriklov xabari
        $this->alertService->createAlert(
            $business,
            'employee_promoted',
            'Tabriklaymiz!',
            "{$employee->name} {$newPosition} lavozimiga ko'tarildi!",
            [
                'priority' => 'medium',
                'user_id' => null, // Hammaga
                'is_celebration' => true,
                'data' => [
                    'employee_id' => $employee->id,
                    'old_position' => $oldPosition,
                    'new_position' => $newPosition,
                ],
            ]
        );

        // 3. Event dispatch
        event(new EmployeePromoted(
            $employee,
            $business,
            $oldPosition,
            $newPosition,
            $data['old_department'] ?? null,
            $data['new_department'] ?? null,
            $data['salary_change'] ?? null,
            $data['promoted_by'] ?? null,
            $data['effective_date'] ?? now()->toDateString()
        ));

        // 4. Engagement ball oshirish
        $this->engagementService->boostEngagement($employee, $business, 'promotion', 10);
    }

    // ================================================
    // ENGAGEMENT EVENTS
    // ================================================

    /**
     * Engagement ball o'zgarganda
     */
    public function onEngagementScoreChanged(User $employee, Business $business, float $oldScore, float $newScore, string $source = 'auto'): void
    {
        Log::info('HROrchestrator: Engagement ball o\'zgaradi', [
            'employee_id' => $employee->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'source' => $source,
        ]);

        // Event yaratish va daraja aniqlash
        $event = new EngagementScoreChanged(
            $employee,
            $business,
            $oldScore,
            $newScore,
            $source
        );

        // 1. Agar ball keskin tushgan bo'lsa - ogohlantirish
        if ($event->requiresAttention()) {
            $this->alertService->createAlert(
                $business,
                'engagement_low',
                'Engagement pasaydi!',
                "{$employee->name} - engagement ball {$newScore}% ga tushdi",
                [
                    'priority' => 'high',
                    'user_id' => null, // HR va manager
                    'data' => [
                        'employee_id' => $employee->id,
                        'old_score' => $oldScore,
                        'new_score' => $newScore,
                        'level' => $event->getLevel(),
                    ],
                ]
            );

            // Proactive 1-on-1 taklif qilish
            $this->performanceService->scheduleOneOnOne(
                $employee,
                $business,
                'engagement_concern',
                "Engagement holatini muhokama qilish"
            );
        }

        // 2. Flight risk yangilash
        $this->retentionService->updateFlightRiskFromEngagement($employee, $business, $newScore);

        // 3. Event dispatch
        event($event);
    }

    /**
     * So'rovnoma javoblari kelganda
     */
    public function onSurveySubmitted(User $employee, Business $business, string $surveyId, string $surveyType, float $score, array $responses = []): void
    {
        Log::info('HROrchestrator: So\'rovnoma javoblari keldi', [
            'employee_id' => $employee->id,
            'survey_type' => $surveyType,
            'score' => $score,
        ]);

        // 1. Engagement ballni yangilash
        if (in_array($surveyType, ['q12', 'pulse', 'enps'])) {
            $oldScore = $this->engagementService->getEmployeeScore($employee, $business);
            $newScore = $this->engagementService->updateFromSurvey($employee, $business, $surveyType, $score, $responses);

            if (abs($newScore - $oldScore) > 5) {
                $this->onEngagementScoreChanged($employee, $business, $oldScore, $newScore, 'survey');
            }
        }

        // 2. Exit survey bo'lsa - alohida tahlil
        if ($surveyType === 'exit') {
            $this->retentionService->analyzeExitSurvey($employee, $business, $responses);
        }

        // 3. Alert (past ball bo'lsa)
        $event = new SurveySubmitted(
            $employee,
            $business,
            $surveyId,
            $surveyType,
            null,
            $score,
            $responses
        );

        if ($event->requiresAttention()) {
            $this->alertService->createAlert(
                $business,
                'survey_low_score',
                'Past baholash!',
                "{$employee->name} so'rovnomada past ball berdi - {$score}",
                [
                    'priority' => 'high',
                    'user_id' => null,
                    'data' => [
                        'employee_id' => $employee->id,
                        'survey_type' => $surveyType,
                        'score' => $score,
                    ],
                ]
            );
        }

        // 4. Event dispatch
        event($event);
    }

    // ================================================
    // PERFORMANCE EVENTS
    // ================================================

    /**
     * OKR progress yangilanganda
     */
    public function onOKRProgressUpdated(User $employee, Business $business, string $objectiveId, string $title, float $oldProgress, float $newProgress, array $data = []): void
    {
        Log::info('HROrchestrator: OKR progress yangilandi', [
            'employee_id' => $employee->id,
            'objective_id' => $objectiveId,
            'old_progress' => $oldProgress,
            'new_progress' => $newProgress,
        ]);

        $event = new OKRProgressUpdated(
            $employee,
            $business,
            $objectiveId,
            $title,
            $oldProgress,
            $newProgress,
            $data['key_result_id'] ?? null,
            $data['key_result_title'] ?? null,
            $data['quarter'] ?? null
        );

        // 1. Yakunlandi bo'lsa - tabriklov
        if ($event->isCompleted()) {
            $this->alertService->createAlert(
                $business,
                'okr_completed',
                'OKR maqsadi yakunlandi!',
                "{$employee->name} - \"{$title}\" maqsadiga erishdi!",
                [
                    'priority' => 'medium',
                    'is_celebration' => true,
                    'user_id' => null,
                    'data' => [
                        'employee_id' => $employee->id,
                        'objective_id' => $objectiveId,
                        'title' => $title,
                    ],
                ]
            );

            // Engagement ball oshirish
            $this->engagementService->boostEngagement($employee, $business, 'okr_completed', 5);
        }

        // 2. Orqada qolmoqda - ogohlantirish
        if ($event->getStatus() === 'behind' && $newProgress < 30) {
            $this->alertService->createAlert(
                $business,
                'okr_behind',
                'OKR orqada qolmoqda',
                "{$employee->name} - \"{$title}\" faqat {$newProgress}% bajarildi",
                [
                    'priority' => 'medium',
                    'user_id' => $employee->id,
                    'data' => [
                        'objective_id' => $objectiveId,
                        'progress' => $newProgress,
                    ],
                ]
            );
        }

        // 3. Event dispatch
        event($event);
    }

    /**
     * Maqsad yakunlanganda
     */
    public function onGoalCompleted(User $employee, Business $business, EmployeeGoal $goal, array $data = []): void
    {
        Log::info('HROrchestrator: Maqsad yakunlandi', [
            'employee_id' => $employee->id,
            'goal_id' => $goal->id,
            'goal_title' => $goal->title,
        ]);

        // Muddatdan necha kun oldin
        $daysEarly = null;
        if ($goal->due_date) {
            $daysEarly = now()->diffInDays($goal->due_date, false);
            $daysEarly = $daysEarly > 0 ? (int)$daysEarly : null;
        }

        $event = new GoalCompleted(
            $employee,
            $business,
            $goal,
            $daysEarly,
            $data['achievement_percentage'] ?? 100
        );

        // 1. Tabriklov
        $this->alertService->createAlert(
            $business,
            'goal_completed',
            'Maqsad yakunlandi!',
            "{$employee->name} - \"{$goal->title}\" " . $event->getCompletionQualityLabel(),
            [
                'priority' => 'low',
                'is_celebration' => true,
                'user_id' => null,
                'data' => [
                    'employee_id' => $employee->id,
                    'goal_id' => $goal->id,
                    'quality' => $event->getCompletionQuality(),
                ],
            ]
        );

        // 2. Engagement ball oshirish
        $boostPoints = match($event->getCompletionQuality()) {
            'exceptional' => 8,
            'excellent' => 5,
            'good' => 3,
            default => 1,
        };
        $this->engagementService->boostEngagement($employee, $business, 'goal_completed', $boostPoints);

        // 3. Event dispatch
        event($event);
    }

    /**
     * 1-on-1 uchrashuv yakunlanganda
     */
    public function onOneOnOneCompleted(User $employee, User $manager, Business $business, string $meetingId, array $data = []): void
    {
        Log::info('HROrchestrator: 1-on-1 yakunlandi', [
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'meeting_id' => $meetingId,
        ]);

        $event = new OneOnOneCompleted(
            $employee,
            $manager,
            $business,
            $meetingId,
            $data['duration'] ?? null,
            $data['action_items'] ?? null,
            $data['sentiment'] ?? null,
            $data['topics'] ?? null
        );

        // 1. Action itemlarni task sifatida yaratish
        if ($event->hasActionItems()) {
            foreach ($data['action_items'] as $item) {
                $this->performanceService->createActionItemTask(
                    $employee,
                    $business,
                    $item,
                    $meetingId
                );
            }
        }

        // 2. Salbiy sentiment bo'lsa - ogohlantirish
        if ($event->isNegative()) {
            $this->alertService->createAlert(
                $business,
                'one_on_one_negative',
                '1-on-1 salbiy',
                "{$employee->name} bilan uchrashuv salbiy baholandi",
                [
                    'priority' => 'high',
                    'user_id' => null, // HR uchun
                    'data' => [
                        'employee_id' => $employee->id,
                        'manager_id' => $manager->id,
                        'sentiment' => $data['sentiment'],
                    ],
                ]
            );

            // Flight risk ni yangilash
            $this->retentionService->increaseFlightRisk($employee, $business, 'negative_one_on_one', 5);
        }

        // 3. Keyingi uchrashuvni rejalashtirish
        $this->performanceService->scheduleNextOneOnOne($employee, $manager, $business);

        // 4. Event dispatch
        event($event);
    }

    /**
     * Feedback berilganda
     */
    public function onFeedbackGiven(User $giver, User $receiver, Business $business, string $type, string $direction, string $content, array $data = []): void
    {
        Log::info('HROrchestrator: Feedback berildi', [
            'giver_id' => $giver->id,
            'receiver_id' => $receiver->id,
            'type' => $type,
            'direction' => $direction,
        ]);

        $event = new FeedbackGiven(
            $giver,
            $receiver,
            $business,
            $type,
            $direction,
            $content,
            $data['category'] ?? null,
            $data['is_anonymous'] ?? false,
            $data['context'] ?? null
        );

        // 1. Qabul qiluvchiga xabar
        $this->alertService->createAlert(
            $business,
            'feedback_received',
            'Yangi feedback!',
            $event->isPositive()
                ? "Sizga maqtov keldi!"
                : "Sizga yangi feedback keldi",
            [
                'priority' => 'low',
                'user_id' => $receiver->id,
                'data' => [
                    'type' => $type,
                    'is_anonymous' => $data['is_anonymous'] ?? false,
                ],
            ]
        );

        // 2. Ijobiy feedback bo'lsa - engagement oshirish
        if ($event->isPositive()) {
            $this->engagementService->boostEngagement($receiver, $business, 'positive_feedback', 2);
        }

        // 3. Event dispatch
        event($event);
    }

    // ================================================
    // RECOGNITION EVENTS
    // ================================================

    /**
     * Minnatdorchilik bildirilganda
     */
    public function onRecognitionGiven(User $giver, User $receiver, Business $business, string $type, string $message, array $data = []): void
    {
        Log::info('HROrchestrator: Recognition berildi', [
            'giver_id' => $giver->id,
            'receiver_id' => $receiver->id,
            'type' => $type,
        ]);

        $event = new RecognitionGiven(
            $giver,
            $receiver,
            $business,
            $type,
            $message,
            $data['value'] ?? null,
            $data['points'] ?? null,
            $data['is_public'] ?? true
        );

        // 1. Qabul qiluvchiga xabar
        $this->alertService->createAlert(
            $business,
            'recognition_received',
            $event->getTypeLabel() . '!',
            "{$giver->name} sizga {$event->getEmoji()} berdi!",
            [
                'priority' => 'low',
                'user_id' => $receiver->id,
                'is_celebration' => true,
                'data' => [
                    'giver_id' => $giver->id,
                    'type' => $type,
                    'message' => $message,
                ],
            ]
        );

        // 2. Engagement ball oshirish
        $boostPoints = match($type) {
            RecognitionGiven::TYPE_AWARD => 10,
            RecognitionGiven::TYPE_SPOTLIGHT => 8,
            RecognitionGiven::TYPE_MILESTONE => 5,
            RecognitionGiven::TYPE_KUDOS => 2,
            default => 1,
        };
        $this->engagementService->boostEngagement($receiver, $business, 'recognition', $boostPoints);

        // 3. Flight risk kamaytirish
        $this->retentionService->decreaseFlightRisk($receiver, $business, 'recognition_received', 3);

        // 4. Event dispatch
        event($event);
    }

    // ================================================
    // RETENTION EVENTS
    // ================================================

    /**
     * Flight risk darajasi o'zgarganda
     */
    public function onFlightRiskChanged(User $employee, Business $business, string $oldLevel, string $newLevel, float $score, array $factors = []): void
    {
        Log::info('HROrchestrator: Flight risk o\'zgaradi', [
            'employee_id' => $employee->id,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'score' => $score,
        ]);

        $event = new FlightRiskLevelChanged(
            $employee,
            $business,
            $oldLevel,
            $newLevel,
            $score,
            $factors
        );

        // 1. Yuqori yoki kritik bo'lsa - zudlik bilan ogohlantirish
        if ($event->requiresImmediateAction()) {
            $this->alertService->createAlert(
                $business,
                'flight_risk_high',
                'Ketish xavfi yuqori!',
                "{$employee->name} - {$event->getLevelLabel()}. Zudlik bilan harakat kerak!",
                [
                    'priority' => 'urgent',
                    'user_id' => null, // HR va manager
                    'data' => [
                        'employee_id' => $employee->id,
                        'level' => $newLevel,
                        'score' => $score,
                        'top_factors' => $event->getTopRiskFactors(),
                        'recommended_actions' => $event->getRecommendedActions(),
                    ],
                ]
            );

            // Stay interview taklif qilish
            if ($newLevel === FlightRiskLevelChanged::LEVEL_CRITICAL) {
                $this->performanceService->scheduleStayInterview($employee, $business);
            }
        }

        // 2. Event dispatch
        event($event);
    }

    // ================================================
    // ATTENDANCE EVENTS
    // ================================================

    /**
     * Davomat naqshi o'zgarganda
     */
    public function onAttendancePatternChanged(User $employee, Business $business, string $patternType, float $severity, array $data = []): void
    {
        Log::info('HROrchestrator: Davomat naqshi aniqlandi', [
            'employee_id' => $employee->id,
            'pattern_type' => $patternType,
            'severity' => $severity,
        ]);

        $event = new AttendancePatternChanged(
            $employee,
            $business,
            $patternType,
            $severity,
            $data['pattern_data'] ?? null,
            $data['period'] ?? 'last_month'
        );

        // 1. Tashvishli naqsh bo'lsa - ogohlantirish
        if ($event->isConcerning()) {
            $this->alertService->createAlert(
                $business,
                'attendance_pattern',
                'Davomat muammosi',
                "{$employee->name} - {$event->getPatternLabel()}",
                [
                    'priority' => 'medium',
                    'user_id' => null, // HR va manager
                    'data' => [
                        'employee_id' => $employee->id,
                        'pattern_type' => $patternType,
                        'severity' => $severity,
                        'recommended_actions' => $event->getRecommendedActions(),
                    ],
                ]
            );

            // Flight risk ni yangilash
            $riskIncrease = match(true) {
                $severity >= 8 => 10,
                $severity >= 5 => 5,
                default => 2,
            };
            $this->retentionService->increaseFlightRisk($employee, $business, 'attendance_pattern', $riskIncrease);
        }

        // 2. Ijobiy o'zgarish bo'lsa - e'tirof etish
        if ($event->isPositive()) {
            $this->engagementService->boostEngagement($employee, $business, 'attendance_improved', 3);
        }

        // 3. Event dispatch
        event($event);
    }

    // ================================================
    // MILESTONE EVENTS
    // ================================================

    /**
     * Ish yilligi kelganda
     */
    public function onWorkAnniversary(User $employee, Business $business, int $years): void
    {
        Log::info('HROrchestrator: Ish yilligi', [
            'employee_id' => $employee->id,
            'years' => $years,
        ]);

        $event = new WorkAnniversary($employee, $business, $years);

        // 1. Tabriklov xabari
        $this->alertService->createAlert(
            $business,
            'work_anniversary',
            "🎉 Ish yilligi!",
            $event->getCelebrationMessage(),
            [
                'priority' => 'medium',
                'user_id' => null, // Hammaga
                'is_celebration' => true,
                'data' => [
                    'employee_id' => $employee->id,
                    'years' => $years,
                    'milestone_type' => $event->getMilestoneType(),
                ],
            ]
        );

        // 2. Milestone bo'lsa - alohida e'tirof
        if ($event->isMilestone()) {
            // Recognition yaratish
            $this->onRecognitionGiven(
                $business->owner ?? $employee, // Agar owner yo'q bo'lsa, o'zi
                $employee,
                $business,
                RecognitionGiven::TYPE_MILESTONE,
                $event->getCelebrationMessage(),
                [
                    'value' => 'loyalty',
                    'points' => $years * 10,
                    'is_public' => true,
                ]
            );
        }

        // 3. Engagement ball oshirish
        $this->engagementService->boostEngagement($employee, $business, 'work_anniversary', $years);

        // 4. Retention ball yaxshilash
        $this->retentionService->decreaseFlightRisk($employee, $business, 'work_anniversary', $years * 2);

        // 5. Event dispatch
        event($event);
    }

    // ================================================
    // HELPER METHODS
    // ================================================

    /**
     * Ketish sababini o'zbek tilida olish
     */
    protected function getReasonLabel(string $reason): string
    {
        return match($reason) {
            'voluntary' => "O'z xohishi bilan",
            'involuntary' => "Majburiy",
            'retirement' => "Pensiyaga chiqish",
            'contract_end' => "Shartnoma tugashi",
            default => "Boshqa sabab",
        };
    }

    /**
     * HR dashboard yangilash
     */
    protected function updateHRDashboard(Business $business): void
    {
        // Dashboard cache ni yangilash
        // Bu keyinroq implement qilinadi
        Log::debug('HROrchestrator: HR Dashboard yangilanishi kerak', [
            'business_id' => $business->id,
        ]);
    }
}
