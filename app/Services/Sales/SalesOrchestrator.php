<?php

namespace App\Services\Sales;

use App\Events\LeadScoreUpdated;
use App\Events\LeadStageChanged;
use App\Events\Sales\AchievementUnlocked;
use App\Events\Sales\BonusCalculated;
use App\Events\Sales\DealClosed;
use App\Events\Sales\DealLost;
use App\Events\Sales\HotLeadDetected;
use App\Events\Sales\KpiMilestoneReached;
use App\Events\Sales\LeaderboardUpdated;
use App\Events\Sales\LeadGoneCold;
use App\Events\Sales\PenaltyApplied;
use App\Events\TaskCompleted;
use App\Models\Business;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * SalesOrchestrator - Barcha sotuv modullarini koordinatsiya qiluvchi markaziy service
 *
 * Vazifalar:
 * 1. Modullar o'rtasidagi bog'lanishni boshqarish
 * 2. Complex workflow larni koordinatsiya qilish
 * 3. Event cascade larni boshqarish
 * 4. Real-time update larni trigger qilish
 */
class SalesOrchestrator
{
    public function __construct(
        protected LeadScoringService $scoringService,
        protected AlertService $alertService,
        protected LeaderboardService $leaderboardService,
        protected AchievementService $achievementService,
        protected KpiCalculationService $kpiService,
        protected PenaltyService $penaltyService,
        protected BonusCalculationService $bonusService
    ) {}

    /**
     * ============================================
     * LEAD SCORING EVENTS
     * ============================================
     */

    /**
     * Lead score yangilanganda
     */
    public function onLeadScoreUpdated(Lead $lead, int $oldScore, int $newScore): void
    {
        Log::info('SalesOrchestrator: Lead score updated', [
            'lead_id' => $lead->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
        ]);

        $business = $lead->business;

        // Hot lead detected - 80+ ga yetdi
        if ($newScore >= 80 && $oldScore < 80) {
            $this->triggerHotLeadDetected($lead, $newScore, $oldScore);
        }

        // Lead gone cold - 40 dan pastga tushdi
        if ($newScore < 40 && $oldScore >= 40) {
            $category = $newScore < 20 ? 'frozen' : 'cold';
            $this->triggerLeadGoneCold($lead, $newScore, $oldScore, $category);
        }

        // Alert yaratish (agar kerak bo'lsa)
        if ($newScore >= 80 && $lead->assigned_to) {
            $this->alertService->createAlert(
                $business,
                'hot_lead',
                'Issiq lead!',
                "{$lead->name} - score: {$newScore}. Tezroq bog'laning!",
                [
                    'user_id' => $lead->assigned_to,
                    'priority' => 'urgent',
                    'alertable_type' => Lead::class,
                    'alertable_id' => $lead->id,
                    'data' => [
                        'lead_id' => $lead->id,
                        'lead_name' => $lead->name,
                        'score' => $newScore,
                    ],
                ]
            );
        }
    }

    /**
     * Hot lead aniqlanganda
     */
    protected function triggerHotLeadDetected(Lead $lead, int $score, int $previousScore): void
    {
        event(new HotLeadDetected($lead, $score, $previousScore));

        Log::info('SalesOrchestrator: Hot lead detected', [
            'lead_id' => $lead->id,
            'score' => $score,
        ]);

        // ROP ga ham alert
        $this->alertService->createAlert(
            $lead->business,
            'hot_lead_for_rop',
            'Yangi issiq lead!',
            "{$lead->name} ({$lead->company}) - {$score} ball. Operator: " . ($lead->assignedTo?->name ?? 'tayinlanmagan'),
            [
                'user_id' => null, // ROP va Owner uchun
                'priority' => 'high',
                'alertable_type' => Lead::class,
                'alertable_id' => $lead->id,
            ]
        );
    }

    /**
     * Lead sovuganda
     */
    protected function triggerLeadGoneCold(Lead $lead, int $score, int $previousScore, string $category): void
    {
        $daysWithoutContact = null;
        if ($lead->last_contacted_at) {
            $daysWithoutContact = $lead->last_contacted_at->diffInDays(now());
        } elseif ($lead->created_at) {
            $daysWithoutContact = $lead->created_at->diffInDays(now());
        }

        event(new LeadGoneCold($lead, $score, $previousScore, $category, $daysWithoutContact));

        Log::info('SalesOrchestrator: Lead gone cold', [
            'lead_id' => $lead->id,
            'score' => $score,
            'category' => $category,
        ]);

        // Re-engagement alert
        if ($lead->assigned_to) {
            $this->alertService->createAlert(
                $lead->business,
                'lead_cold',
                'Lead sovumoqda!',
                "{$lead->name} - {$category}. Qayta faollashtirish kerak",
                [
                    'user_id' => $lead->assigned_to,
                    'priority' => 'medium',
                    'alertable_type' => Lead::class,
                    'alertable_id' => $lead->id,
                    'data' => [
                        'lead_id' => $lead->id,
                        'score' => $score,
                        'days_without_contact' => $daysWithoutContact,
                    ],
                ]
            );
        }
    }

    /**
     * ============================================
     * DEAL EVENTS
     * ============================================
     */

    /**
     * Deal yopilganda (won)
     */
    public function onDealClosed(Lead $lead, float $amount, ?User $closedBy = null): void
    {
        $business = $lead->business;
        $closedBy = $closedBy ?? $lead->assignedTo;

        Log::info('SalesOrchestrator: Deal closed', [
            'lead_id' => $lead->id,
            'amount' => $amount,
            'closed_by' => $closedBy?->id,
        ]);

        // Event dispatch
        event(new DealClosed($lead, $amount, $closedBy));

        // 1. KPI yangilash
        if ($closedBy) {
            $this->updateKpiForDealClosed($business, $closedBy, $amount);
        }

        // 2. Leaderboard yangilash
        $this->updateLeaderboard($business);

        // 3. Achievement tekshirish
        if ($closedBy) {
            $this->checkDealAchievements($business, $closedBy, $amount);
        }

        // 4. Alert yaratish (jamoa uchun)
        $this->alertService->createAlert(
            $business,
            'deal_closed',
            'Deal yopildi!',
            "{$closedBy?->name} - {$lead->name} - " . number_format($amount) . " so'm",
            [
                'user_id' => null, // Hammaga
                'priority' => 'medium',
                'alertable_type' => Lead::class,
                'alertable_id' => $lead->id,
                'data' => [
                    'lead_id' => $lead->id,
                    'amount' => $amount,
                    'closed_by' => $closedBy?->id,
                ],
            ]
        );
    }

    /**
     * Deal yo'qotilganda
     */
    public function onDealLost(Lead $lead, string $lostReason, ?float $estimatedValue = null, ?User $lostBy = null): void
    {
        $business = $lead->business;
        $lostBy = $lostBy ?? $lead->assignedTo;

        Log::info('SalesOrchestrator: Deal lost', [
            'lead_id' => $lead->id,
            'lost_reason' => $lostReason,
            'estimated_value' => $estimatedValue,
        ]);

        event(new DealLost($lead, $lostReason, $estimatedValue, $lostBy));

        // KPI ga ta'sir (conversion rate tushadi)
        if ($lostBy) {
            $this->updateKpiForDealLost($business, $lostBy);
        }

        // Tahlil uchun log
        Log::channel('sales')->info('Deal lost', [
            'business_id' => $business->id,
            'lead_id' => $lead->id,
            'lead_name' => $lead->name,
            'lost_reason' => $lostReason,
            'estimated_value' => $estimatedValue,
            'user_id' => $lostBy?->id,
        ]);
    }

    /**
     * ============================================
     * TASK EVENTS
     * ============================================
     */

    /**
     * Task bajarilganda
     */
    public function onTaskCompleted(Task $task): void
    {
        $business = $task->business;
        $user = $task->assignedTo;

        Log::info('SalesOrchestrator: Task completed', [
            'task_id' => $task->id,
            'user_id' => $user?->id,
        ]);

        // 1. Agar leadga bog'liq bo'lsa, lead score ni yangilash
        if ($task->taskable_type === Lead::class && $task->taskable) {
            $this->scoringService->scoreOnTaskCompleted($task->taskable);
        }

        // 2. KPI yangilash
        if ($user) {
            $this->kpiService->incrementKpi($business->id, $user->id, 'tasks_completed');
        }

        // 3. Achievement tekshirish
        if ($user) {
            $this->checkTaskAchievements($business, $user);
        }

        // 4. Streak yangilash
        if ($user) {
            $this->achievementService->recordActivity($business->id, $user->id, 'task_completed');
        }
    }

    /**
     * ============================================
     * STAGE CHANGE EVENTS
     * ============================================
     */

    /**
     * Lead bosqichi o'zgarganda
     */
    public function onLeadStageChanged(Lead $lead, $oldStage, $newStage, bool $automated = false): void
    {
        Log::info('SalesOrchestrator: Lead stage changed', [
            'lead_id' => $lead->id,
            'old_stage' => $oldStage?->name,
            'new_stage' => $newStage->name,
            'automated' => $automated,
        ]);

        // 1. Lead score ni yangilash
        $this->scoringService->scoreOnStageChanged($lead);

        // 2. Won stage - deal closed
        if ($newStage->is_won && $lead->estimated_value) {
            $this->onDealClosed($lead, $lead->estimated_value);
        }

        // 3. Lost stage - deal lost
        if ($newStage->is_lost && $lead->lost_reason) {
            $this->onDealLost($lead, $lead->lost_reason, $lead->estimated_value);
        }
    }

    /**
     * ============================================
     * KPI / MILESTONE EVENTS
     * ============================================
     */

    /**
     * KPI milestone ga yetilganda
     */
    public function onKpiMilestoneReached(User $user, Business $business, string $kpiType, float $percentage): void
    {
        $milestone = match (true) {
            $percentage >= 150 => KpiMilestoneReached::MILESTONE_150,
            $percentage >= 120 => KpiMilestoneReached::MILESTONE_120,
            $percentage >= 100 => KpiMilestoneReached::MILESTONE_100,
            $percentage >= 75 => KpiMilestoneReached::MILESTONE_75,
            $percentage >= 50 => KpiMilestoneReached::MILESTONE_50,
            default => null,
        };

        if (! $milestone) {
            return;
        }

        $target = $this->kpiService->getUserTarget($business->id, $user->id, $kpiType);
        $currentValue = $this->kpiService->getCurrentValue($business->id, $user->id, $kpiType);

        Log::info('SalesOrchestrator: KPI milestone reached', [
            'user_id' => $user->id,
            'kpi_type' => $kpiType,
            'milestone' => $milestone,
            'percentage' => $percentage,
        ]);

        event(new KpiMilestoneReached(
            $user,
            $business->id,
            $kpiType,
            $milestone,
            $currentValue,
            $target,
            'monthly'
        ));

        // Achievement unlock (100%+)
        if ($percentage >= 100) {
            $this->checkKpiAchievements($business, $user, $kpiType, $percentage);
        }

        // Alert
        $this->alertService->createAlert(
            $business,
            'kpi_milestone',
            "KPI maqsadiga yetdingiz!",
            "{$kpiType} - {$percentage}% bajarildi",
            [
                'user_id' => $user->id,
                'priority' => $percentage >= 100 ? 'high' : 'medium',
                'data' => [
                    'kpi_type' => $kpiType,
                    'percentage' => $percentage,
                    'milestone' => $milestone,
                ],
            ]
        );
    }

    /**
     * ============================================
     * PENALTY / BONUS EVENTS
     * ============================================
     */

    /**
     * Jarima qo'llanilganda
     */
    public function onPenaltyApplied(User $user, Business $business, string $reason, ?float $amount, string $trigger): void
    {
        Log::info('SalesOrchestrator: Penalty applied', [
            'user_id' => $user->id,
            'reason' => $reason,
            'amount' => $amount,
            'trigger' => $trigger,
        ]);

        event(new PenaltyApplied(
            $user,
            $business->id,
            PenaltyApplied::TYPE_PENALTY,
            $reason,
            $amount,
            $trigger
        ));

        // Alert
        $this->alertService->createAlert(
            $business,
            'penalty_applied',
            'Jarima qo\'llanildi',
            $reason . ($amount ? " - " . number_format($amount) . " so'm" : ''),
            [
                'user_id' => $user->id,
                'priority' => 'high',
                'data' => [
                    'reason' => $reason,
                    'amount' => $amount,
                ],
            ]
        );
    }

    /**
     * Bonus hisoblanganida
     */
    public function onBonusCalculated(User $user, Business $business, float $amount, string $period, float $kpiScore): void
    {
        $multiplier = $this->bonusService->getMultiplier($kpiScore);
        $periodLabel = $this->getPeriodLabel($period);

        Log::info('SalesOrchestrator: Bonus calculated', [
            'user_id' => $user->id,
            'amount' => $amount,
            'period' => $period,
            'kpi_score' => $kpiScore,
        ]);

        event(new BonusCalculated(
            $user,
            $business->id,
            $amount,
            'monthly',
            $periodLabel,
            $kpiScore,
            $multiplier
        ));

        // Alert
        $this->alertService->createAlert(
            $business,
            'bonus_calculated',
            'Bonus hisoblanadi!',
            "{$periodLabel} uchun - " . number_format($amount) . " so'm",
            [
                'user_id' => $user->id,
                'priority' => 'medium',
                'data' => [
                    'amount' => $amount,
                    'kpi_score' => $kpiScore,
                    'multiplier' => $multiplier,
                ],
            ]
        );
    }

    /**
     * ============================================
     * GAMIFICATION EVENTS
     * ============================================
     */

    /**
     * Achievement ochilganda
     */
    public function onAchievementUnlocked(User $user, Business $business, array $achievement): void
    {
        Log::info('SalesOrchestrator: Achievement unlocked', [
            'user_id' => $user->id,
            'achievement' => $achievement['code'],
        ]);

        event(new AchievementUnlocked(
            $user,
            $business->id,
            $achievement['code'],
            $achievement['name'],
            $achievement['description'],
            $achievement['icon'] ?? 'trophy',
            $achievement['points'] ?? 0,
            $achievement['tier'] ?? null
        ));

        // Alert
        $this->alertService->createAchievementAlert(
            $business,
            $user,
            $achievement['name'],
            $achievement['points'] ?? 0,
            ['code' => $achievement['code']]
        );
    }

    /**
     * Leaderboard yangilanganda
     */
    public function onLeaderboardUpdated(Business $business, string $type, array $topPerformers): void
    {
        event(new LeaderboardUpdated(
            $business->id,
            $type,
            $topPerformers
        ));

        Log::debug('SalesOrchestrator: Leaderboard updated', [
            'business_id' => $business->id,
            'type' => $type,
        ]);
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    protected function updateKpiForDealClosed(Business $business, User $user, float $amount): void
    {
        $this->kpiService->incrementKpi($business->id, $user->id, 'leads_converted');
        $this->kpiService->incrementKpi($business->id, $user->id, 'revenue', $amount);
        $this->kpiService->incrementKpi($business->id, $user->id, 'deals_count');
    }

    protected function updateKpiForDealLost(Business $business, User $user): void
    {
        // Conversion rate ni qayta hisoblash kerak bo'ladi
        $this->kpiService->recalculateConversionRate($business->id, $user->id);
    }

    protected function updateLeaderboard(Business $business): void
    {
        $topPerformers = $this->leaderboardService->calculateDaily($business->id);
        $this->onLeaderboardUpdated($business, LeaderboardUpdated::TYPE_DAILY, $topPerformers);
    }

    protected function checkDealAchievements(Business $business, User $user, float $amount): void
    {
        // First deal
        $dealsCount = $this->kpiService->getCurrentValue($business->id, $user->id, 'deals_count');

        if ($dealsCount === 1) {
            $this->achievementService->unlock($business->id, $user->id, 'first_sale');
        }

        // Big deal
        if ($amount >= 50000000) { // 50M+
            $this->achievementService->unlock($business->id, $user->id, 'big_deal');
        }

        // 10 deals
        if ($dealsCount === 10) {
            $this->achievementService->unlock($business->id, $user->id, 'deals_10');
        }
    }

    protected function checkTaskAchievements(Business $business, User $user): void
    {
        $tasksCount = $this->kpiService->getCurrentValue($business->id, $user->id, 'tasks_completed');

        if ($tasksCount === 10) {
            $this->achievementService->unlock($business->id, $user->id, 'tasks_10');
        }

        if ($tasksCount === 50) {
            $this->achievementService->unlock($business->id, $user->id, 'tasks_50');
        }

        if ($tasksCount === 100) {
            $this->achievementService->unlock($business->id, $user->id, 'tasks_100');
        }
    }

    protected function checkKpiAchievements(Business $business, User $user, string $kpiType, float $percentage): void
    {
        if ($percentage >= 100) {
            $this->achievementService->unlock($business->id, $user->id, "kpi_{$kpiType}_100");
        }

        if ($percentage >= 120) {
            $this->achievementService->unlock($business->id, $user->id, "kpi_{$kpiType}_120");
        }

        if ($percentage >= 150) {
            $this->achievementService->unlock($business->id, $user->id, "kpi_{$kpiType}_150");
        }
    }

    protected function getPeriodLabel(string $period): string
    {
        return match ($period) {
            'monthly' => now()->format('Y-m'),
            'quarterly' => 'Q' . ceil(now()->month / 3) . '-' . now()->year,
            'yearly' => now()->format('Y'),
            default => $period,
        };
    }
}
