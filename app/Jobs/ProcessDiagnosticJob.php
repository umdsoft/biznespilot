<?php

namespace App\Jobs;

use App\Models\AIDiagnostic;
use App\Services\DiagnosticService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDiagnosticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AIDiagnostic $diagnostic;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(AIDiagnostic $diagnostic)
    {
        $this->diagnostic = $diagnostic;
    }

    /**
     * Execute the job.
     */
    public function handle(DiagnosticService $diagnosticService): void
    {
        Log::info('Processing diagnostic', [
            'diagnostic_id' => $this->diagnostic->id,
            'business_id' => $this->diagnostic->business_id,
        ]);

        try {
            $diagnosticService->processDiagnostic($this->diagnostic);

            Log::info('Diagnostic completed successfully', [
                'diagnostic_id' => $this->diagnostic->id,
                'overall_score' => $this->diagnostic->overall_score,
            ]);

        } catch (\Exception $e) {
            Log::error('Diagnostic processing failed', [
                'diagnostic_id' => $this->diagnostic->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Diagnostic job failed permanently', [
            'diagnostic_id' => $this->diagnostic->id,
            'error' => $exception->getMessage(),
        ]);

        $this->diagnostic->update([
            'status' => 'failed',
            'error_message' => 'Diagnostika jarayoni muvaffaqiyatsiz tugadi: ' . $exception->getMessage(),
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, then 60s, then 120s
    }
}
