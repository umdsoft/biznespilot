<?php

namespace App\Services\Sales;

use App\Models\BusinessUser;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\SalesAchievementDefinition;
use App\Models\SalesKpiDailySnapshot;
use App\Models\SalesLeaderboardEntry;
use App\Models\SalesUserAchievement;
use App\Models\SalesUserPoints;
use App\Models\SalesUserStreak;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    /**
     * Foydalanuvchi uchun yutuqlarni tekshirish
     */
    public function checkAchievements(string $businessId, string $userId): Collection
    {
        $earnedAchievements = collect();

        // Faol yutuqlarni olish
        $achievements = SalesAchievementDefinition::forBusiness($businessId)
            ->active()
            ->get();

        foreach ($achievements as $achievement) {
            try {
                $earned = $this->checkSingleAchievement($achievement, $businessId, $userId);
                if ($earned) {
                    $earnedAchievements->push($earned);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to check achievement', [
                    'achievement_id' => $achievement->id,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($earnedAchievements->isNotEmpty()) {
            Log::info('Achievements earned', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'count' => $earnedAchievements->count(),
            ]);
        }

        return $earnedAchievements;
    }

    /**
     * Bitta yutuqni tekshirish
     */
    protected function checkSingleAchievement(
        SalesAchievementDefinition $achievement,
        string $businessId,
        string $userId
    ): ?SalesUserAchievement {
        // Allaqachon olinganmi tekshirish
        if (! $achievement->is_repeatable && $achievement->isEarnedByUser($userId)) {
            return null;
        }

        // Joriy qiymatni olish
        $currentValue = $this->getMetricValue($achievement, $businessId, $userId);

        // Maqsadga yetganmi tekshirish
        if ($currentValue < $achievement->target_value) {
            return null;
        }

        // Qo'shimcha shartlarni tekshirish
        if (! $this->checkConditions($achievement, $businessId, $userId)) {
            return null;
        }

        // Yutuq berish
        return SalesUserAchievement::awardAchievement(
            $businessId,
            $userId,
            $achievement,
            $currentValue,
            ['checked_at' => now()->toIso8601String()]
        );
    }

    /**
     * Metrik qiymatini olish
     */
    protected function getMetricValue(
        SalesAchievementDefinition $achievement,
        string $businessId,
        string $userId
    ): float {
        $metric = $achievement->metric;
        $triggerType = $achievement->trigger_type;

        return match ($triggerType) {
            'threshold' => $this->getThresholdValue($metric, $businessId, $userId),
            'cumulative' => $this->getCumulativeValue($metric, $businessId, $userId),
            'streak' => $this->getStreakValue($metric, $businessId, $userId),
            'milestone' => $this->getMilestoneValue($metric, $businessId, $userId),
            default => 0,
        };
    }

    /**
     * Bir kunlik qiymat (threshold)
     */
    protected function getThresholdValue(string $metric, string $businessId, string $userId): float
    {
        $today = now()->startOfDay();

        return match ($metric) {
            'leads_converted' => Lead::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->whereDate('converted_at', $today)
                ->count(),

            'calls_made' => CallLog::where('business_id', $businessId)
                ->where('user_id', $userId)
                ->whereDate('created_at', $today)
                ->count(),

            'tasks_completed' => Task::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->count(),

            'kpi_score' => SalesKpiDailySnapshot::forBusiness($businessId)
                ->forUser($userId)
                ->whereDate('date', $today)
                ->avg('score') ?? 0,

            'gold_medals' => SalesLeaderboardEntry::forBusiness($businessId)
                ->forUser($userId)
                ->whereDate('created_at', $today)
                ->where('medal', 'gold')
                ->count(),

            default => 0,
        };
    }

    /**
     * Jami qiymat (cumulative)
     */
    protected function getCumulativeValue(string $metric, string $businessId, string $userId): float
    {
        return match ($metric) {
            'leads_converted' => Lead::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->whereNotNull('converted_at')
                ->count(),

            'revenue' => Lead::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->whereNotNull('converted_at')
                ->sum('deal_value') ?? 0,

            'calls_made' => CallLog::where('business_id', $businessId)
                ->where('user_id', $userId)
                ->count(),

            'tasks_completed' => Task::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('status', 'completed')
                ->count(),

            'first_place_count' => SalesLeaderboardEntry::forBusiness($businessId)
                ->forUser($userId)
                ->where('rank', 1)
                ->count(),

            'gold_medals' => SalesUserPoints::forBusiness($businessId)
                ->forUser($userId)
                ->value('gold_medals') ?? 0,

            default => 0,
        };
    }

    /**
     * Streak qiymati
     */
    protected function getStreakValue(string $metric, string $businessId, string $userId): float
    {
        if ($metric === 'streak_days') {
            // daily_target streak ni tekshirish
            $streak = SalesUserStreak::forBusiness($businessId)
                ->forUser($userId)
                ->forType('daily_target')
                ->first();

            return $streak?->current_streak ?? 0;
        }

        return 0;
    }

    /**
     * Milestone qiymati
     */
    protected function getMilestoneValue(string $metric, string $businessId, string $userId): float
    {
        // Milestone va cumulative bir xil logika
        return $this->getCumulativeValue($metric, $businessId, $userId);
    }

    /**
     * Qo'shimcha shartlarni tekshirish
     */
    protected function checkConditions(
        SalesAchievementDefinition $achievement,
        string $businessId,
        string $userId
    ): bool {
        $conditions = $achievement->conditions;

        if (empty($conditions)) {
            return true;
        }

        // Shartlarni tekshirish
        foreach ($conditions as $key => $value) {
            if (! $this->checkCondition($key, $value, $businessId, $userId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bitta shartni tekshirish
     */
    protected function checkCondition(string $key, $value, string $businessId, string $userId): bool
    {
        return match ($key) {
            'min_days_active' => $this->getUserDaysActive($businessId, $userId) >= $value,
            'min_level' => SalesUserPoints::forBusiness($businessId)
                ->forUser($userId)
                ->value('level') >= $value,
            'period_type' => true, // Davrni tekshirish kerak bo'lsa
            default => true,
        };
    }

    /**
     * Foydalanuvchi faol kunlari
     */
    protected function getUserDaysActive(string $businessId, string $userId): int
    {
        return SalesKpiDailySnapshot::forBusiness($businessId)
            ->forUser($userId)
            ->distinct('date')
            ->count('date');
    }

    /**
     * Biznes uchun barcha foydalanuvchilar yutuqlarini tekshirish
     */
    public function checkAllUsersAchievements(string $businessId): array
    {
        $userIds = BusinessUser::where('business_id', $businessId)
            ->whereIn('department', ['sales_operator', 'sales_head'])
            ->whereNotNull('accepted_at')
            ->pluck('user_id');

        $results = [];

        foreach ($userIds as $userId) {
            try {
                $earned = $this->checkAchievements($businessId, $userId);
                $results[$userId] = $earned->count();
            } catch (\Exception $e) {
                Log::error('Failed to check user achievements', [
                    'business_id' => $businessId,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $results[$userId] = 0;
            }
        }

        return $results;
    }

    /**
     * Foydalanuvchi yutuqlari sahifasi uchun ma'lumot
     */
    public function getUserAchievementsPage(string $businessId, string $userId): array
    {
        // Barcha yutuqlarni olish
        $allAchievements = SalesAchievementDefinition::forBusiness($businessId)
            ->active()
            ->public()
            ->ordered()
            ->get();

        // Foydalanuvchi yutuqlari
        $userAchievements = SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->with('achievement')
            ->get()
            ->keyBy('achievement_id');

        // Kategoriyalar bo'yicha guruhlash
        $byCategory = [];
        foreach (SalesAchievementDefinition::CATEGORIES as $code => $name) {
            $categoryAchievements = $allAchievements->filter(fn ($a) => $a->category === $code);

            $byCategory[$code] = [
                'name' => $name,
                'achievements' => $categoryAchievements->map(function ($achievement) use ($userAchievements, $businessId, $userId) {
                    $userAchievement = $userAchievements->get($achievement->id);
                    $currentValue = $this->getMetricValue($achievement, $businessId, $userId);
                    $progress = $achievement->getUserProgress($userId, $currentValue);

                    return [
                        'id' => $achievement->id,
                        'code' => $achievement->code,
                        'name' => $achievement->name,
                        'description' => $achievement->description,
                        'icon' => $achievement->icon,
                        'tier' => $achievement->tier,
                        'tier_info' => $achievement->tier_info,
                        'points' => $achievement->points,
                        'earned' => $userAchievement !== null,
                        'earned_at' => $userAchievement?->earned_at,
                        'times_earned' => $userAchievement?->times_earned ?? 0,
                        'progress' => $progress,
                        'is_pinned' => $userAchievement?->is_pinned ?? false,
                    ];
                })->values(),
                'earned_count' => $categoryAchievements->filter(fn ($a) => $userAchievements->has($a->id))->count(),
                'total_count' => $categoryAchievements->count(),
            ];
        }

        // Umumiy statistika
        $stats = SalesUserAchievement::getUserStats($businessId, $userId);

        // Ko'rilmagan yutuqlar
        $unseenAchievements = SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->unseen()
            ->with('achievement')
            ->get();

        return [
            'by_category' => $byCategory,
            'stats' => $stats,
            'unseen' => $unseenAchievements,
            'total_earned' => $userAchievements->count(),
            'total_available' => $allAchievements->count(),
        ];
    }

    /**
     * Foydalanuvchi progressini olish (dashboard uchun)
     */
    public function getUserProgressSummary(string $businessId, string $userId): array
    {
        // Keyingi olinishi mumkin bo'lgan yutuqlar
        $upcomingAchievements = SalesAchievementDefinition::forBusiness($businessId)
            ->active()
            ->public()
            ->whereDoesntHave('userAchievements', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy('target_value')
            ->limit(5)
            ->get()
            ->map(function ($achievement) use ($businessId, $userId) {
                $currentValue = $this->getMetricValue($achievement, $businessId, $userId);
                $progress = $achievement->getUserProgress($userId, $currentValue);

                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'tier' => $achievement->tier,
                    'points' => $achievement->points,
                    'progress' => $progress,
                ];
            })
            ->sortByDesc(fn ($a) => $a['progress']['percent'])
            ->take(3)
            ->values();

        // So'nggi olingan yutuqlar
        $recentAchievements = SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->with('achievement')
            ->recent(3)
            ->get();

        // Umumiy progress
        $totalAvailable = SalesAchievementDefinition::forBusiness($businessId)
            ->active()
            ->count();
        $totalEarned = SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->count();

        return [
            'upcoming' => $upcomingAchievements,
            'recent' => $recentAchievements,
            'total_earned' => $totalEarned,
            'total_available' => $totalAvailable,
            'completion_percent' => $totalAvailable > 0
                ? round(($totalEarned / $totalAvailable) * 100, 1)
                : 0,
        ];
    }

    /**
     * Yutuqni ko'rilgan deb belgilash
     */
    public function markAsSeen(string $achievementId): void
    {
        $userAchievement = SalesUserAchievement::findOrFail($achievementId);
        $userAchievement->markAsSeen();
    }

    /**
     * Barcha ko'rilmaganlarni ko'rilgan deb belgilash
     */
    public function markAllAsSeen(string $businessId, string $userId): int
    {
        return SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->unseen()
            ->update([
                'is_seen' => true,
                'seen_at' => now(),
            ]);
    }

    /**
     * Yutuqni pin/unpin qilish
     */
    public function togglePin(string $achievementId): bool
    {
        $userAchievement = SalesUserAchievement::findOrFail($achievementId);

        // Maksimum 3 ta pinned yutuq
        if (! $userAchievement->is_pinned) {
            $pinnedCount = SalesUserAchievement::forBusiness($userAchievement->business_id)
                ->forUser($userAchievement->user_id)
                ->pinned()
                ->count();

            if ($pinnedCount >= 3) {
                return false;
            }
        }

        $userAchievement->togglePin();

        return true;
    }

    /**
     * Biznes uchun tizim yutuqlarini sozlash
     */
    public function setupSystemAchievements(string $businessId): void
    {
        SalesAchievementDefinition::createSystemAchievements($businessId);
    }

    /**
     * Streak yangilash (kunlik maqsadga yetganda)
     */
    public function updateStreak(string $businessId, string $userId, string $streakType): void
    {
        $streak = SalesUserStreak::getOrCreate($businessId, $userId, $streakType);
        $streak->incrementStreak();

        // Streak yutuqlarini tekshirish
        $this->checkAchievements($businessId, $userId);
    }

    /**
     * Yutuqlarni tekshirish va berish (Observer lardan chaqiriladi)
     *
     * @param string $businessId
     * @param string $userId
     * @param string $trigger - lead_converted, task_completed, call_completed, etc.
     * @return bool - kamida bitta yutuq berilganmi
     */
    public function checkAndAwardAchievements(
        string $businessId,
        string $userId,
        string $trigger
    ): bool {
        try {
            // Triggerlarga mos yutuqlarni tekshirish
            $earnedAchievements = $this->checkAchievements($businessId, $userId);

            // Trigger-specific yutuqlarni tekshirish
            $triggerAchievements = $this->checkTriggerAchievements($businessId, $userId, $trigger);

            $totalEarned = $earnedAchievements->count() + $triggerAchievements->count();

            if ($totalEarned > 0) {
                Log::info('Achievements awarded via trigger', [
                    'business_id' => $businessId,
                    'user_id' => $userId,
                    'trigger' => $trigger,
                    'count' => $totalEarned,
                ]);
            }

            return $totalEarned > 0;
        } catch (\Exception $e) {
            Log::error('Failed to check and award achievements', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Trigger asosida maxsus yutuqlarni tekshirish
     */
    protected function checkTriggerAchievements(
        string $businessId,
        string $userId,
        string $trigger
    ): Collection {
        $earned = collect();

        // Trigger turiga qarab qo'shimcha tekshirishlar
        switch ($trigger) {
            case 'lead_converted':
                $earned = $this->checkLeadConversionAchievements($businessId, $userId);
                break;

            case 'task_completed':
                $earned = $this->checkTaskAchievements($businessId, $userId);
                break;

            case 'call_completed':
                $earned = $this->checkCallAchievements($businessId, $userId);
                break;

            case 'daily_calls_10':
            case 'daily_calls_25':
            case 'daily_calls_50':
            case 'daily_calls_100':
                $earned = $this->checkDailyCallMilestone($businessId, $userId, $trigger);
                break;
        }

        return $earned;
    }

    /**
     * Lead conversion yutuqlarini tekshirish
     */
    protected function checkLeadConversionAchievements(string $businessId, string $userId): Collection
    {
        $earned = collect();

        // Birinchi lead conversion
        $totalConverted = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereNotNull('converted_at')
            ->count();

        // Milestone lar: 1, 10, 50, 100, 500, 1000
        $milestones = [1 => 'first_conversion', 10 => 'ten_conversions', 50 => 'fifty_conversions',
            100 => 'hundred_conversions', 500 => 'five_hundred_conversions', 1000 => 'thousand_conversions'];

        foreach ($milestones as $count => $code) {
            if ($totalConverted >= $count) {
                $achievement = SalesAchievementDefinition::forBusiness($businessId)
                    ->where('code', $code)
                    ->first();

                if ($achievement && !$achievement->isEarnedByUser($userId)) {
                    $userAchievement = SalesUserAchievement::awardAchievement(
                        $businessId,
                        $userId,
                        $achievement,
                        $totalConverted,
                        ['trigger' => 'lead_converted']
                    );

                    if ($userAchievement) {
                        $earned->push($userAchievement);
                    }
                }
            }
        }

        return $earned;
    }

    /**
     * Task yutuqlarini tekshirish
     */
    protected function checkTaskAchievements(string $businessId, string $userId): Collection
    {
        return collect(); // Asosiy checkAchievements da tekshiriladi
    }

    /**
     * Call yutuqlarini tekshirish
     */
    protected function checkCallAchievements(string $businessId, string $userId): Collection
    {
        return collect(); // Asosiy checkAchievements da tekshiriladi
    }

    /**
     * Kunlik qo'ng'iroq milestonelarini tekshirish
     */
    protected function checkDailyCallMilestone(string $businessId, string $userId, string $trigger): Collection
    {
        // trigger format: daily_calls_N
        return collect(); // Milestone-specific achievements
    }

    /**
     * Kun oxirida streak larni qayta ishlash (CheckAchievementsJob dan chaqiriladi)
     */
    public function processEndOfDayStreaks(string $businessId, string $userId): void
    {
        try {
            // Kunlik maqsadga yetganmi tekshirish
            $todayKpi = SalesKpiDailySnapshot::forBusiness($businessId)
                ->forUser($userId)
                ->whereDate('snapshot_date', today())
                ->avg('achievement_percent');

            // Agar kunlik maqsadning 80%+ ga yetgan bo'lsa streak davom etadi
            if ($todayKpi && $todayKpi >= 80) {
                $this->updateStreak($businessId, $userId, 'daily_target');
            } else {
                // Streak to'xtaydi
                $streak = SalesUserStreak::forBusiness($businessId)
                    ->forUser($userId)
                    ->forType('daily_target')
                    ->first();

                if ($streak) {
                    $streak->reset();
                }
            }

            // Boshqa streak turlarini tekshirish
            $this->checkActivityStreaks($businessId, $userId);
        } catch (\Exception $e) {
            Log::error('Failed to process end of day streaks', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Faoliyat streak larini tekshirish
     */
    protected function checkActivityStreaks(string $businessId, string $userId): void
    {
        // Qo'ng'iroq streak
        $todayCalls = CallLog::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->whereIn('status', [CallLog::STATUS_ANSWERED, CallLog::STATUS_COMPLETED])
            ->whereDate('started_at', today())
            ->count();

        if ($todayCalls >= 10) {
            $this->updateStreak($businessId, $userId, 'calls_made');
        }

        // Vazifa streak
        $todayTasks = Task::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();

        if ($todayTasks >= 5) {
            $this->updateStreak($businessId, $userId, 'tasks_completed');
        }
    }

    // ==================== ORCHESTRATOR HELPER METODLARI ====================

    /**
     * Yutuqni ochish (SalesOrchestrator dan chaqiriladi)
     */
    public function unlock(string $businessId, string $userId, string $achievementCode): ?SalesUserAchievement
    {
        $achievement = SalesAchievementDefinition::forBusiness($businessId)
            ->where('code', $achievementCode)
            ->active()
            ->first();

        if (! $achievement) {
            Log::debug('AchievementService: Achievement not found for unlock', [
                'business_id' => $businessId,
                'code' => $achievementCode,
            ]);

            return null;
        }

        // Allaqachon olinganmi tekshirish
        if (! $achievement->is_repeatable && $achievement->isEarnedByUser($userId)) {
            return null;
        }

        $currentValue = $this->getMetricValue($achievement, $businessId, $userId);

        $userAchievement = SalesUserAchievement::awardAchievement(
            $businessId,
            $userId,
            $achievement,
            $currentValue,
            ['unlocked_by' => 'orchestrator', 'unlocked_at' => now()->toIso8601String()]
        );

        if ($userAchievement) {
            Log::info('AchievementService: Achievement unlocked', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'achievement_code' => $achievementCode,
            ]);
        }

        return $userAchievement;
    }

    /**
     * Faoliyatni qayd etish (streak uchun)
     */
    public function recordActivity(string $businessId, string $userId, string $activityType): void
    {
        $streakType = match ($activityType) {
            'task_completed' => 'tasks_completed',
            'call_made', 'call_completed' => 'calls_made',
            'lead_converted' => 'leads_converted',
            default => null,
        };

        if ($streakType) {
            // Bugungi faoliyatni tekshirish
            $this->checkActivityStreaks($businessId, $userId);
        }

        Log::debug('AchievementService: Activity recorded', [
            'business_id' => $businessId,
            'user_id' => $userId,
            'activity_type' => $activityType,
        ]);
    }
}
