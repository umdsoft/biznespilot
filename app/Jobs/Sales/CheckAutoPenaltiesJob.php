<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\SalesKpiDailySnapshot;
use App\Models\SalesPenalty;
use App\Models\SalesPenaltyRule;
use App\Models\SalesPenaltyWarning;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Avtomatik jarimalarni tekshirish
 * Har soatda ishga tushadi
 */
class CheckAutoPenaltiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        Log::info('CheckAutoPenaltiesJob started', [
            'business_id' => $this->businessId,
        ]);

        if ($this->businessId) {
            $this->processBusinessPenalties($this->businessId, $notificationService);
        } else {
            $this->processAllBusinesses($notificationService);
        }

        Log::info('CheckAutoPenaltiesJob completed');
    }

    protected function processBusinessPenalties(string $businessId, NotificationService $notificationService): void
    {
        // 1. Muddati o'tgan warninglarni jarimaga aylantirish
        $this->convertExpiredWarnings($businessId, $notificationService);

        // 2. Yangi auto-penalty triggerlarni tekshirish
        $this->checkNewPenaltyTriggers($businessId, $notificationService);
    }

    protected function processAllBusinesses(NotificationService $notificationService): void
    {
        $businesses = Business::where('status', 'active')
            ->whereHas('penaltyRules', fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        foreach ($businesses as $businessId) {
            try {
                $this->processBusinessPenalties($businessId, $notificationService);
            } catch (\Exception $e) {
                Log::error('Failed to process business penalties', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function convertExpiredWarnings(string $businessId, NotificationService $notificationService): void
    {
        $expiredWarnings = SalesPenaltyWarning::where('business_id', $businessId)
            ->whereIn('status', ['pending', 'warned'])
            ->where('auto_convert', true)
            ->where('deadline_at', '<', now())
            ->with(['user', 'penaltyRule'])
            ->get();

        $business = Business::find($businessId);

        foreach ($expiredWarnings as $warning) {
            try {
                DB::transaction(function () use ($warning, $notificationService, $business) {
                    $rule = SalesPenaltyRule::where('business_id', $warning->business_id)
                        ->where('trigger_event', $warning->rule_code)
                        ->where('is_active', true)
                        ->first();

                    if (!$rule && $warning->penalty_rule_id) {
                        $rule = SalesPenaltyRule::find($warning->penalty_rule_id);
                    }

                    if (!$rule) {
                        $warning->update(['status' => 'cancelled']);
                        return;
                    }

                    // Jarima yaratish
                    SalesPenalty::create([
                        'business_id' => $warning->business_id,
                        'user_id' => $warning->user_id,
                        'penalty_rule_id' => $rule->id,
                        'warning_id' => $warning->id,
                        'category' => $rule->category,
                        'related_type' => $warning->related_type,
                        'related_id' => $warning->related_id,
                        'penalty_amount' => $rule->penalty_amount,
                        'reason' => $rule->name,
                        'description' => $rule->description,
                        'status' => 'pending',
                        'auto_generated' => true,
                        'triggered_at' => now(),
                    ]);

                    $warning->update([
                        'status' => 'converted',
                        'converted_at' => now(),
                    ]);

                    // Notification yuborish
                    if ($warning->user && $business) {
                        $notificationService->send(
                            $business,
                            $warning->user,
                            'alert',
                            'Jarima qo\'shildi',
                            "Ogohlantirish muddati tugadi. {$rule->name} uchun {$rule->penalty_amount} so'm jarima belgilandi.",
                            [
                                'icon' => 'exclamation-triangle',
                                'action_url' => '/sales/penalties',
                                'action_text' => 'Jarimalarni ko\'rish',
                                'extra_data' => [
                                    'Jarima summasi' => number_format($rule->penalty_amount) . " so'm",
                                    'Sabab' => $rule->name,
                                ],
                            ]
                        );
                    }

                    Log::info('Warning converted to penalty', [
                        'warning_id' => $warning->id,
                        'user_id' => $warning->user_id,
                        'rule_code' => $warning->rule_code,
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Failed to convert warning to penalty', [
                    'warning_id' => $warning->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function checkNewPenaltyTriggers(string $businessId, NotificationService $notificationService): void
    {
        $this->checkLowKpiStreak($businessId, $notificationService);
        $this->checkMissedTasks($businessId, $notificationService);
        $this->checkUncontactedLeads($businessId, $notificationService);
    }

    /**
     * Ketma-ket past KPI tekshirish (3 kun yoki undan ko'p)
     */
    protected function checkLowKpiStreak(string $businessId, NotificationService $notificationService): void
    {
        $rule = SalesPenaltyRule::where('business_id', $businessId)
            ->where('trigger_event', 'low_kpi_3_days')
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            return;
        }

        $business = Business::find($businessId);
        $lowKpiThreshold = $rule->threshold ?? 50; // Default 50%
        $streakDays = $rule->streak_days ?? 3; // Default 3 kun

        // Sales operatorlarni olish
        $operators = BusinessUser::where('business_id', $businessId)
            ->whereIn('department', ['sales_operator', 'sales_head'])
            ->whereNotNull('accepted_at')
            ->with('user')
            ->get();

        foreach ($operators as $operator) {
            try {
                $userId = $operator->user_id;

                // Oxirgi X kun ichidagi overall scorelarni tekshirish
                $lowKpiDays = 0;
                $scores = [];

                for ($i = 1; $i <= $streakDays; $i++) {
                    $date = now()->subDays($i);
                    $score = SalesKpiDailySnapshot::getDailyOverallScore($businessId, $userId, $date);
                    $scores[] = $score;

                    if ($score > 0 && $score < $lowKpiThreshold) {
                        $lowKpiDays++;
                    }
                }

                // Agar ketma-ket X kun past KPI bo'lsa
                if ($lowKpiDays >= $streakDays) {
                    // Allaqachon bu oy uchun warning bormadi tekshirish
                    $existingWarning = SalesPenaltyWarning::where('business_id', $businessId)
                        ->where('user_id', $userId)
                        ->where('rule_code', 'low_kpi_3_days')
                        ->whereIn('status', ['pending', 'warned'])
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->exists();

                    if ($existingWarning) {
                        continue;
                    }

                    $warningCount = SalesPenaltyWarning::where('business_id', $businessId)
                        ->where('user_id', $userId)
                        ->where('rule_code', 'low_kpi_3_days')
                        ->whereIn('status', ['warned', 'converted'])
                        ->count();

                    $avgScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

                    $warning = SalesPenaltyWarning::create([
                        'business_id' => $businessId,
                        'penalty_rule_id' => $rule->id,
                        'rule_code' => 'low_kpi_3_days',
                        'user_id' => $userId,
                        'warning_type' => $warningCount >= 2 ? 'final' : ($warningCount >= 1 ? 'written' : 'system'),
                        'reason' => 'Ketma-ket past KPI',
                        'description' => "{$streakDays} kun ketma-ket KPI {$lowKpiThreshold}% dan past. O'rtacha ball: " . round($avgScore, 1) . "%",
                        'warning_number' => $warningCount + 1,
                        'status' => 'pending',
                        'auto_convert' => true,
                        'deadline_at' => now()->addDays(2),
                        'expires_at' => now()->addDays(30),
                    ]);

                    // Notification yuborish
                    if ($operator->user && $business) {
                        $notificationService->send(
                            $business,
                            $operator->user,
                            'kpi',
                            'Past KPI ogohlantirishi',
                            "Sizning KPI ko'rsatkichlaringiz {$streakDays} kun ketma-ket {$lowKpiThreshold}% dan past. O'rtacha ball: " . round($avgScore, 1) . "%. Iltimos 2 kun ichida natijalaringizni yaxshilang.",
                            [
                                'icon' => 'chart-bar',
                                'action_url' => '/sales/kpi',
                                'action_text' => 'KPI ni ko\'rish',
                                'extra_data' => [
                                    "O'rtacha KPI" => round($avgScore, 1) . '%',
                                    'Muddat' => '2 kun',
                                    'Ogohlantirish' => $warning->warning_level,
                                ],
                            ]
                        );
                    }

                    Log::info('Low KPI streak warning created', [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'avg_score' => $avgScore,
                        'streak_days' => $streakDays,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to check low KPI streak for user', [
                    'business_id' => $businessId,
                    'user_id' => $operator->user_id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Muddati o'tgan vazifalarni tekshirish
     */
    protected function checkMissedTasks(string $businessId, NotificationService $notificationService): void
    {
        $rule = SalesPenaltyRule::where('business_id', $businessId)
            ->where('trigger_event', 'task_overdue')
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            return;
        }

        $business = Business::find($businessId);

        // Muddati o'tgan vazifalarni topish
        $overdueTasks = DB::table('tasks')
            ->where('business_id', $businessId)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->whereNotNull('assigned_to')
            ->get();

        foreach ($overdueTasks as $task) {
            $existingWarning = SalesPenaltyWarning::where('business_id', $businessId)
                ->where('user_id', $task->assigned_to)
                ->where('rule_code', 'task_overdue')
                ->where('related_type', 'task')
                ->where('related_id', $task->id)
                ->whereIn('status', ['pending', 'warned'])
                ->exists();

            if ($existingWarning) {
                continue;
            }

            $user = User::find($task->assigned_to);
            if (!$user) {
                continue;
            }

            $warningCount = SalesPenaltyWarning::where('business_id', $businessId)
                ->where('user_id', $task->assigned_to)
                ->where('rule_code', 'task_overdue')
                ->whereIn('status', ['warned', 'converted'])
                ->count();

            SalesPenaltyWarning::create([
                'business_id' => $businessId,
                'penalty_rule_id' => $rule->id,
                'rule_code' => 'task_overdue',
                'user_id' => $task->assigned_to,
                'warning_type' => $warningCount >= 2 ? 'written' : 'system',
                'reason' => 'Muddati o\'tgan vazifa',
                'description' => "Vazifa: {$task->title}. Muddat: " . Carbon::parse($task->due_date)->format('d.m.Y'),
                'related_type' => 'task',
                'related_id' => $task->id,
                'warning_number' => $warningCount + 1,
                'status' => 'pending',
                'auto_convert' => true,
                'deadline_at' => now()->addHours(24),
                'expires_at' => now()->addDays(7),
            ]);

            if ($business) {
                $notificationService->send(
                    $business,
                    $user,
                    'task',
                    'Muddati o\'tgan vazifa',
                    "Vazifangiz muddati o'tdi: {$task->title}. Iltimos 24 soat ichida bajaring.",
                    [
                        'icon' => 'clock',
                        'action_url' => "/tasks/{$task->id}",
                        'action_text' => 'Vazifaga o\'tish',
                    ]
                );
            }
        }
    }

    /**
     * Bog'lanilmagan lidlarni tekshirish (24 soat)
     */
    protected function checkUncontactedLeads(string $businessId, NotificationService $notificationService): void
    {
        $rule = SalesPenaltyRule::where('business_id', $businessId)
            ->where('trigger_event', 'lead_not_contacted_24h')
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            return;
        }

        $business = Business::find($businessId);

        // 24 soatdan ko'p vaqt o'tgan, bog'lanilmagan lidlar
        $uncontactedLeads = DB::table('leads')
            ->where('business_id', $businessId)
            ->whereIn('status', ['new', 'assigned'])
            ->where('created_at', '<', now()->subHours(24))
            ->whereNotNull('assigned_to')
            ->whereNotExists(function ($q) {
                $q->select('id')
                    ->from('call_logs')
                    ->whereColumn('call_logs.lead_id', 'leads.id');
            })
            ->get();

        foreach ($uncontactedLeads as $lead) {
            $existingWarning = SalesPenaltyWarning::where('business_id', $businessId)
                ->where('user_id', $lead->assigned_to)
                ->where('rule_code', 'lead_not_contacted_24h')
                ->where('related_type', 'lead')
                ->where('related_id', $lead->id)
                ->whereIn('status', ['pending', 'warned'])
                ->exists();

            if ($existingWarning) {
                continue;
            }

            $user = User::find($lead->assigned_to);
            if (!$user) {
                continue;
            }

            $warningCount = SalesPenaltyWarning::where('business_id', $businessId)
                ->where('user_id', $lead->assigned_to)
                ->where('rule_code', 'lead_not_contacted_24h')
                ->whereIn('status', ['warned', 'converted'])
                ->count();

            SalesPenaltyWarning::create([
                'business_id' => $businessId,
                'penalty_rule_id' => $rule->id,
                'rule_code' => 'lead_not_contacted_24h',
                'user_id' => $lead->assigned_to,
                'warning_type' => 'system',
                'reason' => 'Lidga 24 soat ichida bog\'lanilmagan',
                'description' => "Lid: {$lead->name}. Tel: {$lead->phone}",
                'related_type' => 'lead',
                'related_id' => $lead->id,
                'warning_number' => $warningCount + 1,
                'status' => 'pending',
                'auto_convert' => true,
                'deadline_at' => now()->addHours(4),
                'expires_at' => now()->addDays(3),
            ]);

            if ($business) {
                $notificationService->send(
                    $business,
                    $user,
                    'lead',
                    'Lidga bog\'laning!',
                    "Sizga biriktirilgan lid 24 soatdan ko'proq vaqt kutmoqda: {$lead->name}. Iltimos 4 soat ichida bog'laning.",
                    [
                        'icon' => 'phone',
                        'action_url' => "/leads/{$lead->id}",
                        'action_text' => 'Lidga o\'tish',
                        'extra_data' => [
                            'Lid ismi' => $lead->name,
                            'Telefon' => $lead->phone,
                            'Kutish vaqti' => Carbon::parse($lead->created_at)->diffForHumans(),
                        ],
                    ]
                );
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAutoPenaltiesJob failed', [
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
