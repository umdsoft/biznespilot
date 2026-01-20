<?php

namespace App\Listeners\Sales;

use App\Events\LeadActivityCreated;
use App\Events\LeadScoreUpdated;
use App\Events\LeadStageChanged;
use App\Events\TaskCompleted;
use App\Services\Sales\SalesOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * SalesIntegrationListener - Barcha sotuv eventlarini birlashtiruvchi listener
 *
 * Bu listener SalesOrchestrator ga yo'naltiradi va
 * cross-module integratsiyani ta'minlaydi
 */
class SalesIntegrationListener implements ShouldQueue
{
    public string $queue = 'sales';

    public function __construct(
        protected SalesOrchestrator $orchestrator
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            LeadScoreUpdated::class => 'handleLeadScoreUpdated',
            LeadStageChanged::class => 'handleLeadStageChanged',
            TaskCompleted::class => 'handleTaskCompleted',
            LeadActivityCreated::class => 'handleLeadActivityCreated',
        ];
    }

    /**
     * Lead score yangilanganda
     */
    public function handleLeadScoreUpdated(LeadScoreUpdated $event): void
    {
        try {
            $this->orchestrator->onLeadScoreUpdated(
                $event->lead,
                $event->oldScore,
                $event->newScore
            );
        } catch (\Exception $e) {
            Log::error('SalesIntegrationListener: Failed to handle LeadScoreUpdated', [
                'lead_id' => $event->lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lead bosqichi o'zgarganda
     */
    public function handleLeadStageChanged(LeadStageChanged $event): void
    {
        try {
            $this->orchestrator->onLeadStageChanged(
                $event->lead,
                $event->oldStage,
                $event->newStage,
                $event->automated
            );
        } catch (\Exception $e) {
            Log::error('SalesIntegrationListener: Failed to handle LeadStageChanged', [
                'lead_id' => $event->lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Task bajarilganda
     */
    public function handleTaskCompleted(TaskCompleted $event): void
    {
        try {
            $this->orchestrator->onTaskCompleted($event->task);
        } catch (\Exception $e) {
            Log::error('SalesIntegrationListener: Failed to handle TaskCompleted', [
                'task_id' => $event->task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lead activity yaratilganda
     */
    public function handleLeadActivityCreated(LeadActivityCreated $event): void
    {
        try {
            // Lead score ni yangilash
            $lead = $event->activity->lead;
            if ($lead) {
                app(\App\Services\Sales\LeadScoringService::class)->scoreOnActivity($lead);
            }
        } catch (\Exception $e) {
            Log::error('SalesIntegrationListener: Failed to handle LeadActivityCreated', [
                'activity_id' => $event->activity->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
