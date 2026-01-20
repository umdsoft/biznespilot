<?php

namespace App\Services\Sales;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Sale;
use App\Models\SalesAlert;
use App\Models\SalesKpiDailySnapshot;
use App\Models\SalesKpiUserTarget;
use App\Models\SalesUserStreak;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class MyDayService
{
    /**
     * Foydalanuvchi uchun "My Day" ma'lumotlarini olish
     */
    public function getMyDayData(User $user, Business $business): array
    {
        return [
            'greeting' => $this->getGreeting($user),
            'date' => now()->format('l, d F'),
            'targets' => $this->getTodayTargets($user, $business),
            'hot_leads' => $this->getHotLeads($user, $business),
            'tasks' => $this->getTodayTasks($user, $business),
            'alerts' => $this->getUrgentAlerts($user, $business),
            'stats' => $this->getTodayStats($user, $business),
            'streaks' => $this->getUserStreaks($user, $business),
        ];
    }

    /**
     * Salomlashish matni
     */
    protected function getGreeting(User $user): string
    {
        $hour = now()->hour;
        $name = explode(' ', $user->name)[0];

        $greeting = match (true) {
            $hour < 12 => 'Xayrli tong',
            $hour < 17 => 'Xayrli kun',
            $hour < 21 => 'Xayrli kech',
            default => 'Xayrli tun',
        };

        return "{$greeting}, {$name}!";
    }

    /**
     * Bugungi maqsadlar
     */
    protected function getTodayTargets(User $user, Business $business): array
    {
        $snapshots = SalesKpiDailySnapshot::forBusiness($business->id)
            ->forUser($user->id)
            ->forDate(today())
            ->with('kpiSetting:id,name,kpi_type,icon')
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'name' => $snapshot->kpiSetting?->name ?? 'KPI',
                'kpi_type' => $snapshot->kpiSetting?->kpi_type,
                'icon' => $snapshot->kpiSetting?->icon ?? 'chart-bar',
                'current' => (int) $snapshot->actual_value,
                'target' => (int) $snapshot->target_value,
                'percentage' => (int) $snapshot->achievement_percent,
                'color' => $snapshot->progress_color,
            ];
        })->toArray();
    }

    /**
     * Issiq lidlar
     */
    protected function getHotLeads(User $user, Business $business): Collection
    {
        return Lead::where('business_id', $business->id)
            ->where('assigned_to', $user->id)
            ->where('score', '>=', 70)
            ->whereNull('lost_reason')
            ->whereNotIn('status', ['won', 'converted', 'lost'])
            ->with(['source:id,name'])
            ->orderByDesc('score')
            ->limit(5)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'company' => $lead->company,
                    'score' => $lead->score,
                    'score_category' => $lead->score_category,
                    'status' => $lead->status,
                    'source' => $lead->source?->name,
                    'estimated_value' => $lead->estimated_value,
                ];
            });
    }

    /**
     * Bugungi vazifalar
     */
    protected function getTodayTasks(User $user, Business $business): Collection
    {
        return Task::where('business_id', $business->id)
            ->where('assigned_to', $user->id)
            ->whereDate('due_date', today())
            ->where('status', '!=', 'completed')
            ->with('lead:id,name')
            ->orderBy('due_date')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => $task->type,
                    'due_time' => $task->due_date?->format('H:i'),
                    'is_overdue' => $task->due_date?->isPast(),
                    'lead_id' => $task->lead_id,
                    'lead_name' => $task->lead?->name,
                    'priority' => $task->priority,
                ];
            });
    }

    /**
     * Shoshilinch alertlar
     */
    protected function getUrgentAlerts(User $user, Business $business): Collection
    {
        return SalesAlert::forBusiness($business->id)
            ->forUser($user->id)
            ->unread()
            ->urgent()
            ->active()
            ->visible()
            ->limit(3)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'priority' => $alert->priority,
                    'data' => $alert->data,
                ];
            });
    }

    /**
     * Bugungi statistikalar
     */
    protected function getTodayStats(User $user, Business $business): array
    {
        $today = today();

        return [
            'calls_made' => CallLog::where('user_id', $user->id)
                ->where('business_id', $business->id)
                ->whereDate('started_at', $today)
                ->count(),
            'calls_duration' => (int) (CallLog::where('user_id', $user->id)
                ->where('business_id', $business->id)
                ->whereDate('started_at', $today)
                ->sum('duration') / 60), // minutlarda
            'tasks_completed' => Task::where('assigned_to', $user->id)
                ->where('business_id', $business->id)
                ->whereDate('completed_at', $today)
                ->count(),
            'leads_contacted' => LeadActivity::where('user_id', $user->id)
                ->whereHas('lead', fn ($q) => $q->where('business_id', $business->id))
                ->whereDate('created_at', $today)
                ->distinct('lead_id')
                ->count('lead_id'),
            // Sale modelida user_id yo'q, shuning uchun faqat business bo'yicha
            'deals_closed' => Sale::where('business_id', $business->id)
                ->whereDate('sale_date', $today)
                ->count(),
            'revenue' => Sale::where('business_id', $business->id)
                ->whereDate('sale_date', $today)
                ->sum('amount'),
        ];
    }

    /**
     * Foydalanuvchi streaklari
     */
    protected function getUserStreaks(User $user, Business $business): array
    {
        $streaks = SalesUserStreak::forBusiness($business->id)
            ->forUser($user->id)
            ->active()
            ->get();

        return $streaks->map(function ($streak) {
            return [
                'type' => $streak->streak_type,
                'type_name' => $streak->type_name,
                'current' => $streak->current_streak,
                'best' => $streak->best_streak,
                'is_at_risk' => $streak->isAtRisk(),
                'multiplier' => $streak->streak_multiplier,
                'next_milestone' => $streak->next_milestone,
                'days_to_milestone' => $streak->days_to_milestone,
            ];
        })->toArray();
    }

    /**
     * Upcoming follow-ups (yaqin kelayotgan)
     */
    public function getUpcomingFollowups(User $user, Business $business, int $limit = 5): Collection
    {
        return Lead::where('business_id', $business->id)
            ->where('assigned_to', $user->id)
            ->whereNull('lost_reason')
            ->whereNotIn('status', ['won', 'converted', 'lost'])
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                  ->orWhere('last_contacted_at', '<', now()->subDays(2));
            })
            ->orderBy('last_contacted_at')
            ->limit($limit)
            ->get()
            ->map(function ($lead) {
                $daysSinceContact = $lead->last_contacted_at
                    ? $lead->last_contacted_at->diffInDays(now())
                    : $lead->created_at->diffInDays(now());

                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'company' => $lead->company,
                    'days_since_contact' => $daysSinceContact,
                    'is_urgent' => $daysSinceContact >= 3,
                ];
            });
    }

    /**
     * Today's schedule (timeline view)
     */
    public function getTodaySchedule(User $user, Business $business): array
    {
        $tasks = Task::where('business_id', $business->id)
            ->where('assigned_to', $user->id)
            ->whereDate('due_date', today())
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->get();

        $calls = CallLog::where('user_id', $user->id)
            ->where('business_id', $business->id)
            ->whereDate('started_at', today())
            ->orderBy('started_at')
            ->get();

        $timeline = collect();

        foreach ($tasks as $task) {
            $timeline->push([
                'type' => 'task',
                'time' => $task->due_date->format('H:i'),
                'timestamp' => $task->due_date->timestamp,
                'title' => $task->title,
                'status' => $task->status,
                'id' => $task->id,
            ]);
        }

        foreach ($calls as $call) {
            $timeline->push([
                'type' => 'call',
                'time' => $call->started_at->format('H:i'),
                'timestamp' => $call->started_at->timestamp,
                'title' => "Qo'ng'iroq: " . ($call->lead?->name ?? $call->phone_number),
                'status' => $call->status,
                'duration' => $call->duration,
                'id' => $call->id,
            ]);
        }

        return $timeline->sortBy('timestamp')->values()->toArray();
    }

    /**
     * Weekly progress overview
     */
    public function getWeeklyProgress(User $user, Business $business): array
    {
        $startOfWeek = now()->startOfWeek();
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $isToday = $date->isToday();
            $isPast = $date->isPast() && ! $isToday;

            $score = 0;
            if ($isPast || $isToday) {
                $score = SalesKpiDailySnapshot::getDailyOverallScore(
                    $business->id,
                    $user->id,
                    $date
                );
            }

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('D'),
                'day_short' => mb_substr($date->locale('uz')->dayName, 0, 2),
                'is_today' => $isToday,
                'is_past' => $isPast,
                'score' => $score,
                'color' => $this->getScoreColor($score),
            ];
        }

        return [
            'days' => $days,
            'average' => round(collect($days)->where('is_past', true)->avg('score'), 1),
        ];
    }

    /**
     * Score rangini olish
     */
    protected function getScoreColor(float $score): string
    {
        return match (true) {
            $score >= 100 => 'green',
            $score >= 80 => 'blue',
            $score >= 60 => 'yellow',
            $score >= 40 => 'orange',
            $score > 0 => 'red',
            default => 'gray',
        };
    }
}
