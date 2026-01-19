<?php

namespace App\Jobs;

use App\Models\OfferLeadAssignment;
use App\Services\OfferAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOfferToLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public OfferLeadAssignment $assignment
    ) {
        $this->onQueue('offers');
    }

    /**
     * Execute the job.
     */
    public function handle(OfferAutomationService $service): void
    {
        try {
            $success = $service->sendOfferNow($this->assignment);

            if (!$success) {
                Log::warning('Failed to send offer to lead', [
                    'assignment_id' => $this->assignment->id,
                    'channel' => $this->assignment->channel,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending offer to lead', [
                'assignment_id' => $this->assignment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendOfferToLead job failed permanently', [
            'assignment_id' => $this->assignment->id,
            'error' => $exception->getMessage(),
        ]);

        // Update assignment status to indicate failure
        $this->assignment->update([
            'metadata' => array_merge($this->assignment->metadata ?? [], [
                'send_failed' => true,
                'error' => $exception->getMessage(),
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }
}
