<?php

namespace App\Services\Sales;

use App\Models\Business;
use App\Models\Lead;
use App\Models\SalesPenalty;
use App\Models\SalesPenaltyRule;
use App\Models\SalesPenaltyWarning;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenaltyService
{
    /**
     * Biznes uchun barcha avtomatik jarimalarni tekshirish
     */
    public function checkAutoPenalties(string $businessId): Collection
    {
        $rules = SalesPenaltyRule::forBusiness($businessId)
            ->active()
            ->autoTrigger()
            ->get();

        $issuedPenalties = collect();

        foreach ($rules as $rule) {
            try {
                $penalties = $this->processRule($rule, $businessId);
                $issuedPenalties = $issuedPenalties->merge($penalties);
            } catch (\Exception $e) {
                Log::error('Failed to process penalty rule', [
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Auto penalties check completed', [
            'business_id' => $businessId,
            'rules_checked' => $rules->count(),
            'penalties_issued' => $issuedPenalties->count(),
        ]);

        return $issuedPenalties;
    }

    /**
     * Bitta qoidani qayta ishlash
     */
    protected function processRule(SalesPenaltyRule $rule, string $businessId): Collection
    {
        return match ($rule->trigger_event) {
            'lead_not_contacted_24h' => $this->checkLeadNotContacted($rule, $businessId, 24),
            'lead_not_contacted_48h' => $this->checkLeadNotContacted($rule, $businessId, 48),
            'crm_not_filled' => $this->checkCrmNotFilled($rule, $businessId),
            'task_overdue' => $this->checkTaskOverdue($rule, $businessId),
            'task_overdue_3_days' => $this->checkTaskOverdue($rule, $businessId, 3),
            'no_activity_24h' => $this->checkNoActivity($rule, $businessId, 24),
            default => collect(),
        };
    }

    /**
     * Kontakt qilinmagan lidlarni tekshirish
     */
    protected function checkLeadNotContacted(
        SalesPenaltyRule $rule,
        string $businessId,
        int $hours = 24
    ): Collection {
        // Custom hours from trigger conditions
        $hours = $rule->getConditionValue('hours', $hours);
        $threshold = now()->subHours($hours);

        // Kontakt qilinmagan lidlarni topish
        $leads = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->whereNotNull('assigned_to')
            ->where('created_at', '<', $threshold)
            ->whereNull('last_contacted_at')
            ->whereDoesntHave('activities', function ($q) {
                $q->whereIn('type', ['call', 'meeting', 'email', 'message']);
            })
            ->get();

        $penalties = collect();

        foreach ($leads as $lead) {
            // Bu lid uchun bugun jarima berilanmi tekshirish
            $existingPenalty = SalesPenalty::forBusiness($businessId)
                ->where('penalty_rule_id', $rule->id)
                ->where('related_type', Lead::class)
                ->where('related_id', $lead->id)
                ->whereDate('triggered_at', today())
                ->exists();

            if ($existingPenalty) {
                continue;
            }

            $penalty = $this->issuePenaltyOrWarning(
                $rule,
                $lead->assigned_to,
                $lead,
                "Lid #{$lead->id} ({$lead->name}) {$hours} soat ichida kontakt qilinmadi"
            );

            if ($penalty) {
                $penalties->push($penalty);
            }
        }

        return $penalties;
    }

    /**
     * CRM to'ldirilmagan lidlarni tekshirish
     */
    protected function checkCrmNotFilled(SalesPenaltyRule $rule, string $businessId): Collection
    {
        $requiredFields = $rule->getConditionValue('required_fields', ['name', 'phone', 'region', 'source_id']);
        $graceHours = $rule->getConditionValue('grace_hours', 24);
        $threshold = now()->subHours($graceHours);

        $penalties = collect();

        // Kerakli maydonlar to'ldirilmagan lidlarni topish
        $leads = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->whereNotNull('assigned_to')
            ->where('created_at', '<', $threshold)
            ->get();

        foreach ($leads as $lead) {
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (empty($lead->$field)) {
                    $missingFields[] = $field;
                }
            }

            if (empty($missingFields)) {
                continue;
            }

            // Bu lid uchun bugun jarima berilanmi
            $existingPenalty = SalesPenalty::forBusiness($businessId)
                ->where('penalty_rule_id', $rule->id)
                ->where('related_type', Lead::class)
                ->where('related_id', $lead->id)
                ->whereDate('triggered_at', today())
                ->exists();

            if ($existingPenalty) {
                continue;
            }

            $penalty = $this->issuePenaltyOrWarning(
                $rule,
                $lead->assigned_to,
                $lead,
                "Lid #{$lead->id} CRM to'liq to'ldirilmagan. Kamchiliklar: ".implode(', ', $missingFields)
            );

            if ($penalty) {
                $penalties->push($penalty);
            }
        }

        return $penalties;
    }

    /**
     * Muddati o'tgan vazifalarni tekshirish
     */
    protected function checkTaskOverdue(
        SalesPenaltyRule $rule,
        string $businessId,
        int $overdueDays = 0
    ): Collection {
        $overdueDays = $rule->getConditionValue('overdue_days', $overdueDays);
        $threshold = now()->subDays($overdueDays);

        $tasks = Task::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->whereNotNull('assigned_to')
            ->where('status', 'pending')
            ->where('due_date', '<', $threshold)
            ->get();

        $penalties = collect();

        foreach ($tasks as $task) {
            // Bu task uchun bugun jarima berilanmi
            $existingPenalty = SalesPenalty::forBusiness($businessId)
                ->where('penalty_rule_id', $rule->id)
                ->where('related_type', Task::class)
                ->where('related_id', $task->id)
                ->whereDate('triggered_at', today())
                ->exists();

            if ($existingPenalty) {
                continue;
            }

            $daysOverdue = $task->due_date->diffInDays(now());

            $penalty = $this->issuePenaltyOrWarning(
                $rule,
                $task->assigned_to,
                $task,
                "Vazifa \"{$task->title}\" {$daysOverdue} kun kechiktirildi"
            );

            if ($penalty) {
                $penalties->push($penalty);
            }
        }

        return $penalties;
    }

    /**
     * Faoliyat yo'qligini tekshirish
     */
    protected function checkNoActivity(
        SalesPenaltyRule $rule,
        string $businessId,
        int $hours = 24
    ): Collection {
        $hours = $rule->getConditionValue('hours', $hours);
        $threshold = now()->subHours($hours);
        $penalties = collect();

        // Sotuv xodimlarini olish
        $salesUsers = \App\Models\BusinessUser::where('business_id', $businessId)
            ->whereIn('department', ['sales_operator', 'sales_head'])
            ->whereNotNull('accepted_at')
            ->pluck('user_id');

        foreach ($salesUsers as $userId) {
            // Oxirgi faoliyatni tekshirish
            $hasRecentCall = \App\Models\CallLog::where('business_id', $businessId)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $threshold)
                ->exists();

            if ($hasRecentCall) {
                continue;
            }

            $hasRecentTask = Task::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $threshold)
                ->exists();

            if ($hasRecentTask) {
                continue;
            }

            $hasRecentLeadActivity = Lead::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('assigned_to', $userId)
                ->where('updated_at', '>=', $threshold)
                ->exists();

            if ($hasRecentLeadActivity) {
                continue;
            }

            // Bugun uchun bu foydalanuvchiga jarima berilanmi tekshirish
            $existingPenalty = SalesPenalty::forBusiness($businessId)
                ->where('penalty_rule_id', $rule->id)
                ->where('user_id', $userId)
                ->whereDate('triggered_at', today())
                ->exists();

            if ($existingPenalty) {
                continue;
            }

            // Jarima berish
            $penalty = $this->issuePenaltyOrWarning(
                $rule,
                $userId,
                null,
                "{$hours} soat davomida hech qanday faoliyat amalga oshirilmadi"
            );

            if ($penalty) {
                $penalties->push($penalty);
            }
        }

        return $penalties;
    }

    /**
     * Jarima yoki ogohlantirish berish
     */
    protected function issuePenaltyOrWarning(
        SalesPenaltyRule $rule,
        string $userId,
        ?Model $related,
        string $description
    ): ?SalesPenalty {
        // Limit tekshirish
        $canIssue = $rule->canIssuePenalty($userId);
        if (! $canIssue['can_issue']) {
            Log::info('Penalty limit reached', [
                'rule_id' => $rule->id,
                'user_id' => $userId,
                'reason' => $canIssue['reason'],
            ]);

            return null;
        }

        // Ogohlantirish kerakmi tekshirish
        if ($rule->shouldIssueWarning($userId)) {
            $this->issueWarning($rule, $userId, $related, $description);

            return null;
        }

        // Jarima yaratish
        return $this->createPenalty($rule, $userId, $related, $description);
    }

    /**
     * Ogohlantirish yaratish
     */
    public function issueWarning(
        SalesPenaltyRule $rule,
        string $userId,
        ?Model $related,
        string $description,
        ?string $issuedBy = null
    ): SalesPenaltyWarning {
        $warningNumber = $rule->getActiveWarningsCount($userId) + 1;

        $warning = SalesPenaltyWarning::create([
            'business_id' => $rule->business_id,
            'penalty_rule_id' => $rule->id,
            'user_id' => $userId,
            'warning_type' => 'system',
            'reason' => $rule->name,
            'description' => $description,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
            'warning_number' => $warningNumber,
            'issued_by' => $issuedBy,
            'expires_at' => now()->addDays($rule->warning_validity_days),
        ]);

        Log::info('Warning issued', [
            'warning_id' => $warning->id,
            'rule_id' => $rule->id,
            'user_id' => $userId,
            'warning_number' => $warningNumber,
        ]);

        // TODO: Notification yuborish

        return $warning;
    }

    /**
     * Jarima yaratish
     */
    public function createPenalty(
        SalesPenaltyRule $rule,
        string $userId,
        ?Model $related,
        string $description,
        ?string $issuedBy = null
    ): SalesPenalty {
        $penalty = SalesPenalty::create([
            'business_id' => $rule->business_id,
            'penalty_rule_id' => $rule->id,
            'user_id' => $userId,
            'category' => $rule->category,
            'reason' => $rule->name,
            'description' => $description,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
            'trigger_data' => [
                'trigger_event' => $rule->trigger_event,
                'conditions' => $rule->trigger_conditions,
            ],
            'triggered_at' => now(),
            'penalty_amount' => $rule->calculatePenaltyAmount(),
            'status' => 'pending',
            'issued_by' => $issuedBy,
            'issued_at' => now(),
        ]);

        Log::info('Penalty issued', [
            'penalty_id' => $penalty->id,
            'rule_id' => $rule->id,
            'user_id' => $userId,
            'amount' => $penalty->penalty_amount,
        ]);

        // TODO: Notification yuborish

        return $penalty;
    }

    /**
     * Qo'lda jarima berish
     */
    public function issueManualPenalty(
        string $businessId,
        string $userId,
        string $category,
        string $reason,
        float $amount,
        ?string $description = null,
        ?Model $related = null,
        ?string $issuedBy = null
    ): SalesPenalty {
        return SalesPenalty::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'category' => $category,
            'reason' => $reason,
            'description' => $description,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
            'triggered_at' => now(),
            'penalty_amount' => $amount,
            'status' => 'pending',
            'issued_by' => $issuedBy,
            'issued_at' => now(),
        ]);
    }

    /**
     * Jarimani tasdiqlash
     */
    public function confirmPenalty(string $penaltyId, string $confirmedBy): SalesPenalty
    {
        $penalty = SalesPenalty::findOrFail($penaltyId);
        $penalty->confirm($confirmedBy);

        return $penalty->fresh();
    }

    /**
     * Shikoyat qilish
     */
    public function submitAppeal(string $penaltyId, string $reason): SalesPenalty
    {
        $penalty = SalesPenalty::findOrFail($penaltyId);

        if (! $penalty->canBeAppealed()) {
            throw new \Exception('Bu jarima uchun shikoyat qilish muddati o\'tgan yoki ruxsat yo\'q');
        }

        $penalty->submitAppeal($reason);

        Log::info('Penalty appeal submitted', [
            'penalty_id' => $penaltyId,
            'user_id' => $penalty->user_id,
        ]);

        return $penalty->fresh();
    }

    /**
     * Shikoyatni ko'rib chiqish
     */
    public function reviewAppeal(
        string $penaltyId,
        string $reviewedBy,
        string $decision,
        ?string $resolution = null
    ): SalesPenalty {
        $penalty = SalesPenalty::findOrFail($penaltyId);

        if ($penalty->status !== 'appealed') {
            throw new \Exception('Bu jarima shikoyat holatida emas');
        }

        $penalty->reviewAppeal($reviewedBy, $decision, $resolution);

        Log::info('Penalty appeal reviewed', [
            'penalty_id' => $penaltyId,
            'decision' => $decision,
            'reviewed_by' => $reviewedBy,
        ]);

        return $penalty->fresh();
    }

    /**
     * Foydalanuvchi uchun jarima summaryni olish
     */
    public function getUserPenaltySummary(string $businessId, string $userId, int $monthsBack = 3): array
    {
        $startDate = now()->subMonths($monthsBack)->startOfMonth();

        $penalties = SalesPenalty::forBusiness($businessId)
            ->forUser($userId)
            ->where('triggered_at', '>=', $startDate)
            ->get();

        $warnings = SalesPenaltyWarning::forBusiness($businessId)
            ->forUser($userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $confirmedPenalties = $penalties->where('status', 'confirmed');
        $pendingPenalties = $penalties->where('status', 'pending');
        $appealedPenalties = $penalties->whereIn('status', ['appealed', 'appeal_approved', 'appeal_rejected']);

        return [
            'total_penalties' => $penalties->count(),
            'total_warnings' => $warnings->count(),
            'confirmed_count' => $confirmedPenalties->count(),
            'pending_count' => $pendingPenalties->count(),
            'appealed_count' => $appealedPenalties->count(),
            'total_amount' => $confirmedPenalties->sum('penalty_amount'),
            'pending_amount' => $pendingPenalties->sum('penalty_amount'),
            'active_warnings' => $warnings->filter(fn ($w) => $w->isActive())->count(),
            'by_category' => $penalties->groupBy('category')->map->count(),
            'recent_penalties' => $penalties->sortByDesc('triggered_at')->take(5)->map(fn ($p) => [
                'id' => $p->id,
                'reason' => $p->reason,
                'amount' => $p->penalty_amount,
                'status' => $p->status,
                'status_label' => $p->status_label,
                'triggered_at' => $p->triggered_at->format('d.m.Y H:i'),
                'can_appeal' => $p->canBeAppealed(),
            ])->values(),
        ];
    }

    /**
     * Shikoyat kutayotgan jarimalar
     */
    public function getAwaitingAppealReview(string $businessId): Collection
    {
        return SalesPenalty::forBusiness($businessId)
            ->where('status', 'appealed')
            ->with(['user:id,name', 'penaltyRule:id,name'])
            ->orderBy('appealed_at')
            ->get();
    }
}
