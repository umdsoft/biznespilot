<?php

namespace App\Observers;

use App\Events\Sales\DealClosed;
use App\Events\Sales\DealLost;
use App\Events\LeadWon;
use App\Jobs\Sales\UpdateUserKpiSnapshotJob;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\SalesPenaltyWarning;
use App\Services\Marketing\LeadAttributionService;
use App\Services\Marketing\LeadQualificationService;
use App\Services\Marketing\LeadToSaleService;
use App\Services\Pipeline\PipelineAutomationService;
use App\Services\Sales\AchievementService;
use App\Services\Sales\LeaderboardService;
use App\Services\Sales\LeadScoringService;
use App\Services\Sales\SalesOrchestrator;
use App\Services\Marketing\CrossModuleAttributionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LeadObserver
{
    /**
     * Lead yaratilganda
     */
    public function created(Lead $lead): void
    {
        // Yangi lid qo'shilganda - 24 soat ichida bog'lanish kerak warning
        if ($lead->assigned_to) {
            $this->scheduleContactWarning($lead);
        }

        // First touch tracking (Marketing Attribution)
        if (!$lead->first_touch_at) {
            $lead->first_touch_at = now();
            $lead->first_touch_source = $lead->utm_source ?? $lead->source?->name ?? 'direct';
            $lead->saveQuietly();
        }

        // Lead score ni hisoblash
        $this->calculateAndUpdateScore($lead);

        // Auto-qualification tekshirish (score asosida)
        $this->checkAutoQualification($lead);

        // Acquisition cost hisoblash (async)
        $this->calculateAcquisitionCost($lead);

        Log::info('LeadObserver: Lead created', [
            'lead_id' => $lead->id,
            'business_id' => $lead->business_id,
            'assigned_to' => $lead->assigned_to,
            'score' => $lead->score,
            'campaign_id' => $lead->campaign_id,
            'channel_id' => $lead->marketing_channel_id,
        ]);
    }

    /**
     * Lead yangilanganda
     */
    public function updated(Lead $lead): void
    {
        // Status o'zgargan bo'lsa
        if ($lead->isDirty('status')) {
            $this->handleStatusChange($lead);
        }

        // Tayinlangan odam o'zgargan bo'lsa
        if ($lead->isDirty('assigned_to')) {
            $this->handleAssignmentChange($lead);
        }

        // Oxirgi aloqa vaqti yangilangan bo'lsa
        if ($lead->isDirty('last_contacted_at')) {
            $this->handleContactMade($lead);
        }

        // Lost reason qo'shilgan bo'lsa - pipeline automation
        if ($lead->isDirty('lost_reason') && $lead->lost_reason) {
            $this->processPipelineAutomation($lead, 'lead_lost');
        }

        // Scoring ga ta'sir qiluvchi maydonlar o'zgarganda score qayta hisoblash
        $scoringFields = ['phone', 'email', 'company', 'estimated_value', 'source_id', 'lost_reason', 'last_contacted_at'];
        if ($lead->wasChanged($scoringFields)) {
            $this->calculateAndUpdateScore($lead);
        }
    }

    /**
     * Status o'zgarishini qayta ishlash
     */
    protected function handleStatusChange(Lead $lead): void
    {
        $oldStatus = $lead->getOriginal('status');
        $newStatus = $lead->status;

        Log::info('LeadObserver: Status changed', [
            'lead_id' => $lead->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // Won stage ga o'tgan bo'lsa
        $wonStage = PipelineStage::where('business_id', $lead->business_id)
            ->where('is_won', true)
            ->first();

        if ($wonStage && $newStatus === $wonStage->slug) {
            $this->handleLeadConverted($lead);
        }

        // Lost stage ga o'tgan bo'lsa
        $lostStage = PipelineStage::where('business_id', $lead->business_id)
            ->where('is_lost', true)
            ->first();

        if ($lostStage && $newStatus === $lostStage->slug) {
            $this->handleLeadLost($lead);
        }
    }

    /**
     * Lid sotuvga o'tganda
     */
    protected function handleLeadConverted(Lead $lead): void
    {
        if (!$lead->assigned_to) {
            return;
        }

        Log::info('LeadObserver: Lead converted to sale', [
            'lead_id' => $lead->id,
            'user_id' => $lead->assigned_to,
            'value' => $lead->estimated_value,
        ]);

        // 0. MARKETING ATTRIBUTION: Avtomatik Sale yaratish
        $sale = null;
        try {
            $leadToSaleService = app(LeadToSaleService::class);

            // Faqat yangi sale yaratish (agar mavjud bo'lmasa)
            if (!$lead->sale) {
                $sale = $leadToSaleService->convertToSale($lead, [
                    'closed_by' => $lead->assigned_to,
                ]);

                Log::info('LeadObserver: Sale created from lead', [
                    'lead_id' => $lead->id,
                    'sale_id' => $sale->id,
                    'amount' => $sale->amount,
                    'campaign_id' => $sale->campaign_id,
                    'channel_id' => $sale->marketing_channel_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to create sale from lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        // DealClosed event dispatch
        event(new DealClosed(
            $lead,
            $lead->estimated_value ?? 0,
            $lead->assignedTo
        ));

        // 1. KPI snapshotni yangilash (async)
        UpdateUserKpiSnapshotJob::dispatch(
            $lead->business_id,
            $lead->assigned_to,
            Carbon::today()
        );

        // 2. Leaderboardni yangilash
        try {
            app(LeaderboardService::class)->updateUserScore(
                $lead->business_id,
                $lead->assigned_to,
                'lead_converted',
                $lead->estimated_value ?? 0
            );
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to update leaderboard', [
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Achievement tekshirish
        try {
            app(AchievementService::class)->checkAndAwardAchievements(
                $lead->business_id,
                $lead->assigned_to,
                'lead_converted'
            );
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to check achievements', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lid yo'qotilganda
     */
    protected function handleLeadLost(Lead $lead): void
    {
        if (!$lead->assigned_to) {
            return;
        }

        Log::info('LeadObserver: Lead lost', [
            'lead_id' => $lead->id,
            'user_id' => $lead->assigned_to,
            'lost_reason' => $lead->lost_reason,
        ]);

        // DealLost event dispatch
        event(new DealLost(
            $lead,
            $lead->lost_reason ?? 'Noma\'lum sabab',
            $lead->estimated_value,
            $lead->assignedTo
        ));

        // KPI snapshotni yangilash
        UpdateUserKpiSnapshotJob::dispatch(
            $lead->business_id,
            $lead->assigned_to,
            Carbon::today()
        );
    }

    /**
     * Tayinlash o'zgarishini qayta ishlash
     */
    protected function handleAssignmentChange(Lead $lead): void
    {
        $oldAssignee = $lead->getOriginal('assigned_to');
        $newAssignee = $lead->assigned_to;

        Log::info('LeadObserver: Assignment changed', [
            'lead_id' => $lead->id,
            'old_assignee' => $oldAssignee,
            'new_assignee' => $newAssignee,
        ]);

        // Eski tayinlangan odamning KPIsini yangilash
        if ($oldAssignee) {
            UpdateUserKpiSnapshotJob::dispatch(
                $lead->business_id,
                $oldAssignee,
                Carbon::today()
            );
        }

        // Yangi tayinlangan odamning KPIsini yangilash
        if ($newAssignee) {
            UpdateUserKpiSnapshotJob::dispatch(
                $lead->business_id,
                $newAssignee,
                Carbon::today()
            );

            // Yangi tayinlangan odam uchun 24 soatlik ogohlantirish
            $this->scheduleContactWarning($lead);
        }
    }

    /**
     * Lid bilan bog'lanilganda
     */
    protected function handleContactMade(Lead $lead): void
    {
        if (!$lead->assigned_to) {
            return;
        }

        // Warning larni bekor qilish
        SalesPenaltyWarning::where('business_id', $lead->business_id)
            ->where('user_id', $lead->assigned_to)
            ->where('related_type', 'lead')
            ->where('related_id', $lead->id)
            ->whereIn('status', ['pending', 'warned'])
            ->update(['status' => 'resolved']);

        // Touch rate KPIsini yangilash
        UpdateUserKpiSnapshotJob::dispatch(
            $lead->business_id,
            $lead->assigned_to,
            Carbon::today()
        );
    }

    /**
     * 24 soatlik bog'lanish ogohlantirishini yaratish
     */
    protected function scheduleContactWarning(Lead $lead): void
    {
        if (!$lead->assigned_to || $lead->last_contacted_at) {
            return;
        }

        try {
            // Mavjud warning bormi tekshirish
            $exists = SalesPenaltyWarning::where('business_id', $lead->business_id)
                ->where('user_id', $lead->assigned_to)
                ->where('related_type', 'lead')
                ->where('related_id', $lead->id)
                ->whereIn('status', ['pending', 'warned'])
                ->exists();

            if ($exists) {
                return;
            }

            SalesPenaltyWarning::create([
                'business_id' => $lead->business_id,
                'user_id' => $lead->assigned_to,
                'rule_code' => 'lead_not_contacted_24h',
                'related_type' => 'lead',
                'related_id' => $lead->id,
                'deadline_at' => Carbon::now()->addHours(24),
                'status' => 'pending',
                'auto_convert' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to create contact warning', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lead score ni hisoblash va yangilash
     */
    protected function calculateAndUpdateScore(Lead $lead): void
    {
        try {
            app(LeadScoringService::class)->updateLeadScore($lead);
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to calculate score', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pipeline avtomatizatsiya
     */
    protected function processPipelineAutomation(Lead $lead, string $triggerType): void
    {
        try {
            app(PipelineAutomationService::class)->processEvent(
                $triggerType,
                $lead,
                [
                    'lost_reason' => $lead->lost_reason,
                ]
            );
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to process pipeline automation', [
                'lead_id' => $lead->id,
                'trigger' => $triggerType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Avtomatik qualification tekshirish (score asosida)
     */
    protected function checkAutoQualification(Lead $lead): void
    {
        try {
            app(LeadQualificationService::class)->autoQualify($lead);
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to auto-qualify lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Acquisition cost va source type hisoblash
     */
    protected function calculateAcquisitionCost(Lead $lead): void
    {
        try {
            $attributionService = app(CrossModuleAttributionService::class);

            // Source type aniqlash
            $sourceType = $attributionService->determineSourceType($lead);

            // Acquisition cost hisoblash
            $cost = $attributionService->calculateLeadAcquisitionCost($lead);

            // Quietly update to avoid infinite loop
            $lead->updateQuietly([
                'acquisition_source_type' => $sourceType,
                'acquisition_cost' => $cost,
            ]);

            Log::debug('LeadObserver: Acquisition cost calculated', [
                'lead_id' => $lead->id,
                'source_type' => $sourceType,
                'cost' => $cost,
            ]);
        } catch (\Exception $e) {
            Log::error('LeadObserver: Failed to calculate acquisition cost', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
