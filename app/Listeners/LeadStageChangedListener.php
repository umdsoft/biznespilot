<?php

namespace App\Listeners;

use App\Events\LeadStageChanged;
use App\Jobs\Sales\UpdateUserKpiSnapshotJob;
use App\Services\Sales\AchievementService;
use App\Services\Sales\LeaderboardService;
use App\Services\Sales\LostOpportunityService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LeadStageChangedListener implements ShouldQueue
{
    public string $queue = 'kpi';

    public function __construct(
        protected LostOpportunityService $lostOpportunityService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(LeadStageChanged $event): void
    {
        $lead = $event->lead;
        $newStage = $event->newStage;
        $automated = $event->automated;

        Log::info('LeadStageChangedListener: Processing stage change', [
            'lead_id' => $lead->id,
            'new_stage' => $newStage->slug,
            'is_won' => $newStage->is_won,
            'automated' => $automated,
        ]);

        // Agar "won" bosqichiga o'tgan bo'lsa - KPI yangilash
        if ($newStage->is_won) {
            $this->handleLeadWon($lead);
        }

        // Agar "lost" bosqichiga o'tgan bo'lsa
        if ($newStage->is_lost) {
            $this->handleLeadLost($lead);
        }
    }

    /**
     * Lead "won" holatiga o'tganda
     */
    protected function handleLeadWon($lead): void
    {
        // 1. KPI Snapshot yangilash
        if ($lead->assigned_to) {
            try {
                UpdateUserKpiSnapshotJob::dispatch(
                    $lead->business_id,
                    $lead->assigned_to,
                    Carbon::now()
                );
            } catch (\Exception $e) {
                Log::error('LeadStageChangedListener: Failed to dispatch KPI job', [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Achievement tekshirish
        if ($lead->assigned_to) {
            try {
                app(AchievementService::class)->checkAndAwardAchievements(
                    $lead->business_id,
                    $lead->assigned_to
                );
            } catch (\Exception $e) {
                Log::error('LeadStageChangedListener: Failed to check achievements', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Leaderboard yangilash
        if ($lead->assigned_to) {
            try {
                app(LeaderboardService::class)->updateUserScore(
                    $lead->business_id,
                    $lead->assigned_to,
                    'leads_converted'
                );
            } catch (\Exception $e) {
                Log::error('LeadStageChangedListener: Failed to update leaderboard', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('LeadStageChangedListener: Lead won processed', [
            'lead_id' => $lead->id,
            'assigned_to' => $lead->assigned_to,
        ]);
    }

    /**
     * Lead "lost" holatiga o'tganda
     *
     * LostOpportunity yaratish va Marketing Attribution tracking.
     */
    protected function handleLeadLost($lead): void
    {
        Log::info('LeadStageChangedListener: Processing lead lost', [
            'lead_id' => $lead->id,
            'lost_reason' => $lead->lost_reason,
            'estimated_value' => $lead->estimated_value,
        ]);

        try {
            // LostOpportunity yaratish (attribution bilan)
            $lostOpportunity = $this->lostOpportunityService->trackLostLead(
                lead: $lead,
                lostReason: $lead->lost_reason ?? 'other',
                lostReasonDetails: $lead->lost_reason_details,
                lostBy: $lead->assignedTo,
                lostToCompetitor: null // Lead modelda competitor ma'lumoti bo'lsa qo'shish mumkin
            );

            Log::info('LeadStageChangedListener: Lost opportunity created', [
                'lead_id' => $lead->id,
                'lost_opportunity_id' => $lostOpportunity->id,
                'estimated_value' => $lostOpportunity->estimated_value,
                'campaign_id' => $lostOpportunity->campaign_id,
            ]);

            // KPI Snapshot yangilash - yo'qotilgan lidlar soni
            if ($lead->assigned_to) {
                try {
                    UpdateUserKpiSnapshotJob::dispatch(
                        $lead->business_id,
                        $lead->assigned_to,
                        Carbon::now()
                    );
                } catch (\Exception $e) {
                    Log::error('LeadStageChangedListener: Failed to dispatch lost KPI job', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('LeadStageChangedListener: Failed to track lost opportunity', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
