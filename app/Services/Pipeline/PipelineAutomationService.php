<?php

namespace App\Services\Pipeline;

use App\Events\LeadStageChanged;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineAutomationRule;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\Log;

class PipelineAutomationService
{
    /**
     * Trigger event bo'lganda chaqiriladi
     */
    public function processEvent(string $triggerType, Lead $lead, array $context = []): bool
    {
        $business = $lead->business;

        if (! $business) {
            return false;
        }

        // Bu business uchun matching rules ni topish
        $rules = PipelineAutomationRule::forBusiness($business->id)
            ->forTrigger($triggerType)
            ->active()
            ->orderByDesc('priority')
            ->get();

        if ($rules->isEmpty()) {
            return false;
        }

        foreach ($rules as $rule) {
            if ($this->shouldApplyRule($rule, $lead, $context)) {
                $result = $this->applyRule($rule, $lead, $triggerType, $context);
                if ($result) {
                    return true; // Birinchi mos kelgan rule dan keyin to'xtash
                }
            }
        }

        return false;
    }

    /**
     * Rule ni qo'llash kerakmi tekshirish
     */
    protected function shouldApplyRule(PipelineAutomationRule $rule, Lead $lead, array $context): bool
    {
        // Condition lar tekshirish
        if (! $rule->matchesConditions($context)) {
            return false;
        }

        // From stage tekshirish
        if ($rule->only_if_current_stage && $rule->from_stage_slug) {
            if ($lead->status !== $rule->from_stage_slug) {
                return false;
            }
        }

        // Allaqachon yakuniy stage da bo'lsa (won/lost)
        $currentStage = $lead->stage;
        if ($currentStage && ($currentStage->is_won || $currentStage->is_lost)) {
            // Faqat maxsus holatda o'tish mumkin
            if (! in_array($rule->trigger_type, ['lead_lost', 'sale_created'])) {
                Log::info("Lead #{$lead->id} already in final stage ({$lead->status}), skipping automation");

                return false;
            }
        }

        // Orqaga o'tishni tekshirish
        if ($rule->prevent_backward && $currentStage) {
            $toStage = PipelineStage::where('business_id', $lead->business_id)
                ->where('slug', $rule->to_stage_slug)
                ->first();

            if ($toStage && $toStage->order < $currentStage->order) {
                Log::info("Preventing backward movement for Lead #{$lead->id}: {$currentStage->slug} -> {$rule->to_stage_slug}");

                return false;
            }
        }

        return true;
    }

    /**
     * Rule ni qo'llash
     */
    protected function applyRule(PipelineAutomationRule $rule, Lead $lead, string $triggerType, array $context): bool
    {
        $oldStage = $lead->stage;
        $newStage = PipelineStage::where('business_id', $lead->business_id)
            ->where('slug', $rule->to_stage_slug)
            ->first();

        if (! $newStage) {
            Log::warning("Target stage not found: {$rule->to_stage_slug} for business {$lead->business_id}");

            return false;
        }

        // Agar bir xil stage bo'lsa - o'zgartirmaslik
        if ($lead->status === $rule->to_stage_slug) {
            return false;
        }

        return $this->changeStage($lead, $newStage, $oldStage, $triggerType, $context);
    }

    /**
     * Stage o'zgartirish
     */
    public function changeStage(
        Lead $lead,
        PipelineStage $newStage,
        ?PipelineStage $oldStage,
        string $reason,
        array $context = [],
        bool $automated = true
    ): bool {
        $oldStatus = $lead->status;

        // Stage o'zgartirish
        $lead->update([
            'status' => $newStage->slug,
            'stage_changed_at' => now(),
        ]);

        Log::info("Lead #{$lead->id} stage changed: {$oldStatus} -> {$newStage->slug} (reason: {$reason})");

        // Activity log yozish
        $this->logActivity($lead, $oldStage, $newStage, $reason, $context, $automated);

        // Event dispatch
        event(new LeadStageChanged($lead, $oldStage, $newStage, $reason, $automated));

        return true;
    }

    /**
     * Activity log yozish
     */
    protected function logActivity(
        Lead $lead,
        ?PipelineStage $oldStage,
        PipelineStage $newStage,
        string $reason,
        array $context,
        bool $automated
    ): void {
        $triggerNames = [
            'call_log_created' => 'Qo\'ng\'iroq qilindi',
            'task_created' => 'Vazifa yaratildi',
            'task_completed' => 'Vazifa bajarildi',
            'sale_created' => 'Sotuv amalga oshdi',
            'lead_lost' => 'Lead yo\'qotildi',
            'message_sent' => 'Xabar yuborildi',
            'manual' => 'Qo\'lda o\'zgartirildi',
        ];

        $description = $automated
            ? "Avtomatik: '{$oldStage?->name}' dan '{$newStage->name}' ga o'tdi"
            : "'{$oldStage?->name}' dan '{$newStage->name}' ga o'zgartirildi";

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'type' => 'stage_changed',
            'title' => $triggerNames[$reason] ?? 'Pipeline bosqichi o\'zgardi',
            'description' => $description,
            'metadata' => [
                'from_stage' => $oldStage?->slug,
                'from_stage_name' => $oldStage?->name,
                'to_stage' => $newStage->slug,
                'to_stage_name' => $newStage->name,
                'trigger' => $reason,
                'automated' => $automated,
                'context' => $context,
            ],
        ]);
    }

    /**
     * Qo'lda stage o'zgartirish
     */
    public function manualStageChange(Lead $lead, string $newStageSlug, ?string $note = null): bool
    {
        $oldStage = $lead->stage;
        $newStage = PipelineStage::where('business_id', $lead->business_id)
            ->where('slug', $newStageSlug)
            ->first();

        if (! $newStage) {
            return false;
        }

        if ($lead->status === $newStageSlug) {
            return false;
        }

        $context = $note ? ['note' => $note] : [];

        return $this->changeStage($lead, $newStage, $oldStage, 'manual', $context, false);
    }
}
