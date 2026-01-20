<?php

namespace App\Services\Sales;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\SalesPipelineRule;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class PipelineAutomationService
{
    /**
     * Lead Activity yaratilganda pipeline bosqichini avtomatik o'zgartirish
     */
    public function handleActivityCreated(LeadActivity $activity): void
    {
        $lead = $activity->lead;

        if (! $lead) {
            return;
        }

        $this->processRulesForLead($lead, 'activity_created', [
            'activity_type' => $activity->type,
            'activity_id' => $activity->id,
        ]);
    }

    /**
     * Task bajarilganda pipeline bosqichini avtomatik o'zgartirish
     */
    public function handleTaskCompleted(Task $task): void
    {
        if (! $task->lead_id) {
            return;
        }

        $lead = $task->lead;

        if (! $lead) {
            return;
        }

        $this->processRulesForLead($lead, 'task_completed', [
            'task_type' => $task->type,
            'task_id' => $task->id,
        ]);
    }

    /**
     * Lead maydoni o'zgarganda pipeline bosqichini tekshirish
     */
    public function handleLeadFieldChanged(Lead $lead, string $fieldName, $oldValue, $newValue): void
    {
        $this->processRulesForLead($lead, 'field_changed', [
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'field_value' => $newValue,
        ]);
    }

    /**
     * Lead uchun qoidalarni qayta ishlash
     */
    protected function processRulesForLead(Lead $lead, string $triggerType, array $eventData): void
    {
        $rules = SalesPipelineRule::forBusiness($lead->business_id)
            ->active()
            ->forTrigger($triggerType)
            ->ordered()
            ->with(['fromStage', 'toStage'])
            ->get();

        foreach ($rules as $rule) {
            if ($this->shouldApplyRule($lead, $rule, $eventData)) {
                $this->applyRule($lead, $rule, $eventData);

                // Birinchi mos qoida ishlatilgandan keyin to'xtatish
                break;
            }
        }
    }

    /**
     * Qoidani qo'llash kerakmi tekshirish
     */
    protected function shouldApplyRule(Lead $lead, SalesPipelineRule $rule, array $eventData): bool
    {
        // 1. Qoida shartlari mos kelishi
        if (! $rule->matchesConditions($eventData)) {
            return false;
        }

        // 2. Agar from_stage belgilangan bo'lsa, lead o'sha bosqichda bo'lishi kerak
        if ($rule->from_stage_id && $lead->pipeline_stage_id !== $rule->from_stage_id) {
            return false;
        }

        // 3. Maqsad bosqich mavjudligini tekshirish
        if (! $rule->toStage) {
            return false;
        }

        // 4. Faqat oldinga o'tish mumkin (order bo'yicha)
        if (! $this->canTransition($lead, $rule->toStage)) {
            return false;
        }

        return true;
    }

    /**
     * Qoidani qo'llash
     */
    protected function applyRule(Lead $lead, SalesPipelineRule $rule, array $eventData): void
    {
        $fromStage = $lead->stage;
        $toStage = $rule->toStage;

        // Lead bosqichini yangilash
        $lead->update(['pipeline_stage_id' => $toStage->id]);

        // Statistikani yangilash
        $rule->incrementTriggerCount();

        // Activity log yaratish
        $this->logStageChange($lead, $fromStage, $toStage, $rule);

        Log::info('PipelineAutomationService: Lead stage changed', [
            'lead_id' => $lead->id,
            'from_stage' => $fromStage?->name,
            'to_stage' => $toStage->name,
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
        ]);
    }

    /**
     * O'tish mumkinligini tekshirish
     */
    protected function canTransition(Lead $lead, PipelineStage $toStage): bool
    {
        $currentStage = $lead->stage;

        if (! $currentStage) {
            return true; // Bosqich yo'q bo'lsa, o'tish mumkin
        }

        // Faqat oldinga o'tish mumkin (order bo'yicha)
        // Yoki "won" yoki "lost" ga o'tish har doim mumkin
        if ($toStage->is_won || $toStage->is_lost) {
            return true;
        }

        return $toStage->order > $currentStage->order;
    }

    /**
     * Stage o'zgarishini log qilish
     */
    protected function logStageChange(Lead $lead, ?PipelineStage $fromStage, PipelineStage $toStage, SalesPipelineRule $rule): void
    {
        LeadActivity::create([
            'lead_id' => $lead->id,
            'business_id' => $lead->business_id,
            'user_id' => null, // System
            'type' => 'stage_change',
            'title' => 'Avtomatik bosqich o\'zgarishi',
            'description' => ($fromStage?->name ?? 'Boshlang\'ich') . ' → ' . $toStage->name,
            'metadata' => [
                'from_stage_id' => $fromStage?->id,
                'from_stage_name' => $fromStage?->name,
                'to_stage_id' => $toStage->id,
                'to_stage_name' => $toStage->name,
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'automated' => true,
            ],
        ]);
    }

    /**
     * Pipeline bottleneck (tiqilib qolgan joylar) ni aniqlash
     */
    public function detectBottlenecks(string $businessId): array
    {
        $stages = PipelineStage::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $bottlenecks = [];

        foreach ($stages as $stage) {
            // Har bir bosqichda necha kun o'rtacha turganini hisoblash
            $avgDays = Lead::where('business_id', $businessId)
                ->where('pipeline_stage_id', $stage->id)
                ->whereNotNull('updated_at')
                ->avg(\DB::raw('DATEDIFF(NOW(), updated_at)'));

            $leadsCount = Lead::where('business_id', $businessId)
                ->where('pipeline_stage_id', $stage->id)
                ->count();

            // Agar o'rtacha 7 kundan ko'p tursa yoki lidlar soni ko'p bo'lsa
            if ($avgDays > 7 || $leadsCount > 20) {
                $bottlenecks[] = [
                    'stage_id' => $stage->id,
                    'stage_name' => $stage->name,
                    'avg_days' => round($avgDays, 1),
                    'leads_count' => $leadsCount,
                    'severity' => $avgDays > 14 || $leadsCount > 50 ? 'high' : 'medium',
                    'recommendation' => $this->getBottleneckRecommendation($stage, $avgDays, $leadsCount),
                ];
            }
        }

        return $bottlenecks;
    }

    /**
     * Bottleneck uchun tavsiya
     */
    protected function getBottleneckRecommendation(PipelineStage $stage, float $avgDays, int $leadsCount): string
    {
        if ($avgDays > 14) {
            return "Bu bosqichda lidlar juda uzoq vaqt turmoqda. Jarayonni tezlashtirish yoki avtomatik eslatmalar qo'shish tavsiya etiladi.";
        }

        if ($leadsCount > 50) {
            return "Bu bosqichda juda ko'p lid to'plangan. Qo'shimcha resurs ajratish yoki lidlarni qayta taqsimlash kerak.";
        }

        if ($leadsCount > 20) {
            return "Bu bosqichda lidlar soni o'sib bormoqda. E'tibor qaratish tavsiya etiladi.";
        }

        return "Jarayon normal ishlayapti, lekin monitoring davom ettirilsin.";
    }

    /**
     * Pipeline conversion rate ni hisoblash
     */
    public function getConversionRates(string $businessId, int $days = 30): array
    {
        $stages = PipelineStage::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $rates = [];
        $startDate = now()->subDays($days);

        foreach ($stages as $index => $stage) {
            if ($index === 0) {
                continue; // Birinchi bosqich uchun conversion yo'q
            }

            $previousStage = $stages[$index - 1];

            // Oldingi bosqichdan o'tganlar soni
            $fromPrevious = Lead::where('business_id', $businessId)
                ->where('created_at', '>=', $startDate)
                ->whereHas('activities', function ($q) use ($previousStage) {
                    $q->where('type', 'stage_change')
                      ->where('metadata->from_stage_id', $previousStage->id);
                })
                ->count();

            // Hozirgi bosqichga yetganlar
            $toCurrent = Lead::where('business_id', $businessId)
                ->where('created_at', '>=', $startDate)
                ->whereHas('activities', function ($q) use ($stage) {
                    $q->where('type', 'stage_change')
                      ->where('metadata->to_stage_id', $stage->id);
                })
                ->count();

            $rate = $fromPrevious > 0 ? round(($toCurrent / $fromPrevious) * 100, 1) : 0;

            $rates[] = [
                'from_stage' => $previousStage->name,
                'to_stage' => $stage->name,
                'from_count' => $fromPrevious,
                'to_count' => $toCurrent,
                'conversion_rate' => $rate,
            ];
        }

        return $rates;
    }
}
