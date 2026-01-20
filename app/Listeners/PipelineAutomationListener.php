<?php

namespace App\Listeners;

use App\Events\LeadActivityCreated;
use App\Events\TaskCompleted;
use App\Services\Sales\PipelineAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class PipelineAutomationListener implements ShouldQueue
{
    /**
     * Queue name
     */
    public string $queue = 'pipeline-automation';

    /**
     * Create the event listener.
     */
    public function __construct(
        private PipelineAutomationService $automationService
    ) {}

    /**
     * Handle LeadActivityCreated events.
     */
    public function handleActivityCreated(LeadActivityCreated $event): void
    {
        try {
            $this->automationService->handleActivityCreated($event->activity);
        } catch (\Exception $e) {
            Log::error('PipelineAutomationListener: Activity handling failed', [
                'activity_id' => $event->activity->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle TaskCompleted events.
     */
    public function handleTaskCompleted(TaskCompleted $event): void
    {
        try {
            $this->automationService->handleTaskCompleted($event->task);
        } catch (\Exception $e) {
            Log::error('PipelineAutomationListener: Task handling failed', [
                'task_id' => $event->task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            LeadActivityCreated::class => 'handleActivityCreated',
            TaskCompleted::class => 'handleTaskCompleted',
        ];
    }
}
