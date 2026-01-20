<?php

namespace App\Services\Sales;

use App\Models\Business;
use App\Models\Lead;
use App\Models\SalesAlert;
use App\Models\SalesAlertSetting;
use App\Models\SalesKpiDailySnapshot;
use App\Models\SalesPenaltyWarning;
use App\Models\Task;
use App\Models\User;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * AlertService - Sales alertlar boshqarish
 *
 * DRY: HasKpiCalculation traitdan foydalanadi
 */
class AlertService
{
    use HasKpiCalculation;
    /**
     * Alert yaratish - barcha joydan shu method chaqiriladi
     */
    public function createAlert(
        Business $business,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): SalesAlert {
        return SalesAlert::create([
            'business_id' => $business->id,
            'user_id' => $options['user_id'] ?? null,
            'type' => $type,
            'priority' => $options['priority'] ?? 'medium',
            'title' => $title,
            'message' => $message,
            'data' => $options['data'] ?? null,
            'alertable_type' => $options['alertable_type'] ?? null,
            'alertable_id' => $options['alertable_id'] ?? null,
            'scheduled_at' => $options['scheduled_at'] ?? now(),
            'expires_at' => $options['expires_at'] ?? null,
            'channels' => $options['channels'] ?? ['app'],
        ]);
    }

    /**
     * Foydalanuvchi uchun alertlarni olish
     * Role ga qarab filter
     */
    public function getAlertsForUser(User $user, Business $business): Collection
    {
        $role = $this->getUserSalesRole($user, $business);

        $query = SalesAlert::forBusiness($business->id)
            ->active()
            ->visible()
            ->with(['alertable', 'user']);

        // Role based filtering
        if ($role === 'sales_operator') {
            // Operator faqat o'ziga tegishli alertlarni ko'radi
            $query->forUser($user->id);
        }
        // owner va sales_head barcha alertlarni ko'radi

        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * O'qilmagan alertlar sonini olish
     */
    public function getUnreadCount(User $user, Business $business): int
    {
        $role = $this->getUserSalesRole($user, $business);

        $query = SalesAlert::forBusiness($business->id)
            ->unread()
            ->active()
            ->visible();

        if ($role === 'sales_operator') {
            $query->forUser($user->id);
        }

        return $query->count();
    }

    /**
     * Shoshilinch alertlarni olish
     */
    public function getUrgentAlerts(User $user, Business $business, int $limit = 3): Collection
    {
        return SalesAlert::forBusiness($business->id)
            ->forUser($user->id)
            ->unread()
            ->urgent()
            ->active()
            ->visible()
            ->limit($limit)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Alert turlariga ko'ra tekshirish va yaratish
     * Scheduler orqali chaqiriladi
     */
    public function processScheduledAlerts(Business $business): void
    {
        $this->checkLeadFollowupAlerts($business);
        $this->checkKpiWarningAlerts($business);
        $this->checkPenaltyWarningAlerts($business);
        $this->checkStreakWarningAlerts($business);
    }

    /**
     * Lead follow-up alertlarini tekshirish
     */
    public function checkLeadFollowupAlerts(Business $business): void
    {
        $settings = SalesAlertSetting::getOrCreate($business->id, 'lead_followup');
        if (! $settings->is_enabled) {
            return;
        }

        $hoursBeforePenalty = $settings->getCondition('hours_before_penalty', 4);
        $penaltyHours = $settings->getCondition('penalty_hours', 24);

        $warningTime = now()->subHours($penaltyHours - $hoursBeforePenalty);

        $leads = Lead::where('business_id', $business->id)
            ->whereNotNull('assigned_to')
            ->whereNull('last_contacted_at')
            ->where('created_at', '<', $warningTime)
            ->where('created_at', '>', now()->subHours($penaltyHours))
            ->whereDoesntHave('alerts', function ($q) use ($hoursBeforePenalty) {
                $q->where('type', 'lead_followup')
                  ->where('created_at', '>', now()->subHours($hoursBeforePenalty));
            })
            ->get();

        foreach ($leads as $lead) {
            $hoursRemaining = $penaltyHours - $lead->created_at->diffInHours(now());

            $this->createAlert(
                $business,
                'lead_followup',
                'Lead javobsiz qolmoqda!',
                "{$lead->name} bilan {$hoursRemaining} soat ichida bog'lanmasangiz jarima olinadi",
                [
                    'user_id' => $lead->assigned_to,
                    'priority' => $hoursRemaining <= 2 ? 'urgent' : 'high',
                    'alertable_type' => Lead::class,
                    'alertable_id' => $lead->id,
                    'data' => [
                        'lead_id' => $lead->id,
                        'lead_name' => $lead->name,
                        'hours_remaining' => $hoursRemaining,
                    ],
                    'channels' => $settings->channels,
                ]
            );

            Log::info('AlertService: Lead followup alert created', [
                'lead_id' => $lead->id,
                'user_id' => $lead->assigned_to,
            ]);
        }
    }

    /**
     * KPI warning alertlarini tekshirish
     */
    public function checkKpiWarningAlerts(Business $business): void
    {
        $settings = SalesAlertSetting::getOrCreate($business->id, 'kpi_warning');
        if (! $settings->is_enabled) {
            return;
        }

        $threshold = $settings->getCondition('kpi_threshold', 50);
        $consecutiveDays = $settings->getCondition('consecutive_days', 3);

        // Sotuv operatorlarini olish
        $operators = $business->users()
            ->wherePivot('department', 'sales_operator')
            ->get();

        foreach ($operators as $operator) {
            $avgKpi = $this->calculateRecentKpi($operator, $business, $consecutiveDays);

            if ($avgKpi < $threshold) {
                // Oxirgi 24 soatda alert yuborilganmi tekshirish
                $recentAlert = SalesAlert::forBusiness($business->id)
                    ->where('type', 'kpi_warning')
                    ->where('data->operator_id', $operator->id)
                    ->where('created_at', '>', now()->subDay())
                    ->exists();

                if (! $recentAlert) {
                    // ROP va Owner ga alert
                    $this->createAlert(
                        $business,
                        'kpi_warning',
                        'Operator KPI past',
                        "{$operator->name} - {$consecutiveDays} kun davomida KPI {$avgKpi}%",
                        [
                            'user_id' => null, // Rahbarlarga
                            'priority' => $avgKpi < 30 ? 'urgent' : 'high',
                            'data' => [
                                'operator_id' => $operator->id,
                                'operator_name' => $operator->name,
                                'kpi_score' => $avgKpi,
                                'days' => $consecutiveDays,
                            ],
                            'channels' => $settings->channels,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Jarima ogohlantirish alertlari
     */
    public function checkPenaltyWarningAlerts(Business $business): void
    {
        $settings = SalesAlertSetting::getOrCreate($business->id, 'penalty_warning');
        if (! $settings->is_enabled) {
            return;
        }

        $hoursBefore = $settings->getCondition('hours_before', 2);

        $warnings = SalesPenaltyWarning::where('business_id', $business->id)
            ->whereIn('status', ['pending', 'warned'])
            ->where('deadline_at', '<=', now()->addHours($hoursBefore))
            ->where('deadline_at', '>', now())
            ->whereDoesntHave('alerts', function ($q) use ($hoursBefore) {
                $q->where('type', 'penalty_warning')
                  ->where('created_at', '>', now()->subHours($hoursBefore));
            })
            ->get();

        foreach ($warnings as $warning) {
            $hoursRemaining = now()->diffInHours($warning->deadline_at, false);

            $this->createAlert(
                $business,
                'penalty_warning',
                'Jarima yaqinlashmoqda!',
                "Agar {$hoursRemaining} soat ichida bajarsangiz, jarimadan qutulasiz",
                [
                    'user_id' => $warning->user_id,
                    'priority' => 'urgent',
                    'alertable_type' => SalesPenaltyWarning::class,
                    'alertable_id' => $warning->id,
                    'data' => [
                        'warning_id' => $warning->id,
                        'rule_code' => $warning->rule_code,
                        'hours_remaining' => $hoursRemaining,
                    ],
                    'channels' => $settings->channels,
                ]
            );
        }
    }

    /**
     * Streak ogohlantirish alertlari
     */
    public function checkStreakWarningAlerts(Business $business): void
    {
        $settings = SalesAlertSetting::getOrCreate($business->id, 'streak_warning');
        if (! $settings->is_enabled) {
            return;
        }

        // Bugun faoliyat qilmagan, lekin streaki bor operatorlarni topish
        $operators = $business->users()
            ->wherePivot('department', 'sales_operator')
            ->get();

        foreach ($operators as $operator) {
            $streaks = \App\Models\SalesUserStreak::forBusiness($business->id)
                ->forUser($operator->id)
                ->active()
                ->where('current_streak', '>=', 3)
                ->get();

            foreach ($streaks as $streak) {
                if ($streak->isAtRisk()) {
                    // Bugun alert yuborilganmi?
                    $alertExists = SalesAlert::forBusiness($business->id)
                        ->where('type', 'streak_warning')
                        ->where('user_id', $operator->id)
                        ->where('data->streak_type', $streak->streak_type)
                        ->whereDate('created_at', today())
                        ->exists();

                    if (! $alertExists) {
                        $this->createAlert(
                            $business,
                            'streak_warning',
                            "{$streak->current_streak} kunlik streakingiz xavf ostida!",
                            "Bugun {$streak->type_name} faoliyat qilmasangiz, streakingiz yo'qoladi",
                            [
                                'user_id' => $operator->id,
                                'priority' => $streak->current_streak >= 7 ? 'high' : 'medium',
                                'data' => [
                                    'streak_type' => $streak->streak_type,
                                    'current_streak' => $streak->current_streak,
                                ],
                                'channels' => $settings->channels,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Kunlik xulosa yaratish
     */
    public function sendDailySummary(Business $business): void
    {
        $settings = SalesAlertSetting::getOrCreate($business->id, 'daily_summary');
        if (! $settings->is_enabled) {
            return;
        }

        $operators = $business->users()
            ->wherePivot('department', 'sales_operator')
            ->get();

        foreach ($operators as $operator) {
            $todayTasks = Task::where('business_id', $business->id)
                ->where('assigned_to', $operator->id)
                ->whereDate('due_date', today())
                ->where('status', '!=', 'completed')
                ->get();

            $todayLeads = Lead::where('business_id', $business->id)
                ->where('assigned_to', $operator->id)
                ->whereNull('last_contacted_at')
                ->where('created_at', '>', now()->subDay())
                ->get();

            $this->createAlert(
                $business,
                'daily_summary',
                'Bugungi rejangiz',
                "Vazifalar: {$todayTasks->count()}, Follow-up kerak: {$todayLeads->count()}",
                [
                    'user_id' => $operator->id,
                    'priority' => 'low',
                    'scheduled_at' => today()->setTimeFromTimeString($settings->schedule_time ?? '09:00'),
                    'expires_at' => today()->setTime(12, 0),
                    'data' => [
                        'tasks_count' => $todayTasks->count(),
                        'leads_count' => $todayLeads->count(),
                        'tasks' => $todayTasks->take(5)->map(fn ($t) => [
                            'id' => $t->id,
                            'title' => $t->title,
                            'due_time' => $t->due_date?->format('H:i'),
                        ])->toArray(),
                        'leads' => $todayLeads->take(5)->map(fn ($l) => [
                            'id' => $l->id,
                            'name' => $l->name,
                        ])->toArray(),
                    ],
                    'channels' => $settings->channels,
                ]
            );
        }
    }

    /**
     * Yutuq alertini yaratish
     */
    public function createAchievementAlert(
        Business $business,
        User $user,
        string $achievementName,
        int $points,
        array $metadata = []
    ): SalesAlert {
        return $this->createAlert(
            $business,
            'achievement',
            "Tabriklaymiz! \"{$achievementName}\" yutuqini qo'lga kiritdingiz!",
            "Siz {$points} ball oldingiz",
            [
                'user_id' => $user->id,
                'priority' => 'medium',
                'data' => array_merge([
                    'achievement_name' => $achievementName,
                    'points' => $points,
                ], $metadata),
                'channels' => ['app'],
            ]
        );
    }

    /**
     * Leaderboard o'zgarish alertini yaratish
     */
    public function createLeaderboardChangeAlert(
        Business $business,
        User $user,
        int $oldPosition,
        int $newPosition
    ): ?SalesAlert {
        $settings = SalesAlertSetting::getOrCreate($business->id, 'leaderboard_change');
        if (! $settings->is_enabled) {
            return null;
        }

        $minChange = $settings->getCondition('min_position_change', 3);
        $change = $oldPosition - $newPosition;

        if (abs($change) < $minChange) {
            return null;
        }

        $isUp = $change > 0;
        $emoji = $isUp ? '📈' : '📉';
        $direction = $isUp ? 'ko\'tarildingiz' : 'tushdingiz';

        return $this->createAlert(
            $business,
            'leaderboard_change',
            "{$emoji} Reytingda {$direction}!",
            "{$oldPosition}-o'rindan {$newPosition}-o'ringa o'tdingiz",
            [
                'user_id' => $user->id,
                'priority' => $isUp ? 'medium' : 'low',
                'data' => [
                    'old_position' => $oldPosition,
                    'new_position' => $newPosition,
                    'change' => $change,
                ],
                'channels' => $settings->channels,
            ]
        );
    }

    /**
     * Oxirgi N kundagi o'rtacha KPI ni hisoblash
     */
    protected function calculateRecentKpi(User $user, Business $business, int $days): float
    {
        $snapshots = SalesKpiDailySnapshot::forBusiness($business->id)
            ->forUser($user->id)
            ->forDateRange(now()->subDays($days), now())
            ->get();

        if ($snapshots->isEmpty()) {
            return 0;
        }

        return round($snapshots->avg('achievement_percent'), 1);
    }

    /**
     * Foydalanuvchining sotuv rolini olish
     */
    protected function getUserSalesRole(User $user, Business $business): string
    {
        // Business egasimi?
        if ($business->owner_id === $user->id) {
            return 'owner';
        }

        // Pivot tabledan rolni olish
        $pivot = $user->teamBusinesses()
            ->where('businesses.id', $business->id)
            ->first()?->pivot;

        return $pivot?->department ?? 'guest';
    }

    /**
     * Alert sozlamalarini olish
     */
    public function getAlertSettings(Business $business, string $alertType): SalesAlertSetting
    {
        return SalesAlertSetting::getOrCreate($business->id, $alertType);
    }

    /**
     * Eskirgan alertlarni tozalash
     */
    public function cleanupExpiredAlerts(int $daysOld = 30): int
    {
        return SalesAlert::where('created_at', '<', now()->subDays($daysOld))
            ->whereIn('status', ['read', 'dismissed', 'actioned'])
            ->delete();
    }
}
