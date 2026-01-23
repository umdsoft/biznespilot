<?php

namespace App\Jobs\CallCenter;

use App\Models\CallAnalysis;
use App\Models\CallLog;
use App\Services\CallCenter\AudioProcessingService;
use App\Services\CallCenter\CallAnalysisService;
use App\Services\CallCenter\OperatorStatsService;
use App\Services\CallCenter\SpeechToTextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCallAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CallLog $callLog
    ) {
        $this->tries = config('call-center.queue.tries', 3);
        $this->timeout = config('call-center.queue.timeout', 300);
        $this->backoff = config('call-center.queue.backoff', [30, 60, 120]);
        $this->onQueue(config('call-center.queue.queue', 'call-center'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        AudioProcessingService $audioService,
        SpeechToTextService $sttService,
        CallAnalysisService $analysisService,
        OperatorStatsService $statsService
    ): void {
        $startTime = microtime(true);

        Log::info('Starting call analysis job', [
            'call_log_id' => $this->callLog->id,
            'recording_url' => $this->callLog->recording_url,
        ]);

        // Initialize analysis record
        $analysis = CallAnalysis::create([
            'call_log_id' => $this->callLog->id,
        ]);

        $tempAudioPath = null;

        try {
            // Step 1: Download and process audio
            $this->callLog->markAsTranscribing();

            Log::info('Downloading and processing audio', ['call_log_id' => $this->callLog->id]);

            $audioResult = $audioService->processAudio($this->callLog->recording_url);
            $tempAudioPath = $audioResult['storage_path'];

            $analysis->update(['temp_audio_path' => $tempAudioPath]);

            // Step 2: Transcribe audio
            Log::info('Transcribing audio', ['call_log_id' => $this->callLog->id]);

            $localPath = $audioService->getLocalPath($tempAudioPath);
            $transcriptResult = $sttService->transcribe($localPath);

            // Clean up local temp file
            @unlink($localPath);

            $analysis->update([
                'transcript' => $transcriptResult['text'],
                'stt_cost' => $transcriptResult['cost'],
                'stt_model' => $transcriptResult['model'],
            ]);

            // Step 3: Analyze transcript
            $this->callLog->markAsAnalyzing();

            Log::info('Analyzing transcript', ['call_log_id' => $this->callLog->id]);

            $operatorName = $this->callLog->user?->name ?? 'Operator';
            $analysisResult = $analysisService->analyze(
                $transcriptResult['text'],
                $operatorName,
                $this->callLog->duration
            );

            // Step 4: Save analysis results
            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            $analysis->update([
                'overall_score' => $analysisResult['overall_score'],
                'stage_scores' => $analysisResult['stage_scores'],
                'anti_patterns' => $analysisResult['anti_patterns'],
                'recommendations' => $analysisResult['recommendations'],
                'strengths' => $analysisResult['strengths'],
                'weaknesses' => $analysisResult['weaknesses'],
                'formatted_transcript' => $analysisResult['formatted_transcript'] ?? null,
                'analysis_cost' => $analysisResult['cost'],
                'input_tokens' => $analysisResult['input_tokens'],
                'output_tokens' => $analysisResult['output_tokens'],
                'analysis_model' => $analysisResult['model'],
                'processing_time_ms' => $processingTime,
            ]);

            // Step 5: Clean up temporary audio
            if ($tempAudioPath) {
                $audioService->deleteFromStorage($tempAudioPath);
                $analysis->update(['temp_audio_path' => null]);
            }

            // Mark as completed
            $this->callLog->markAnalysisCompleted();

            // Step 6: Update operator statistics
            try {
                $statsService->updateStatsAfterAnalysis($this->callLog);
                Log::info('Operator stats updated', ['user_id' => $this->callLog->user_id]);
            } catch (\Exception $statsError) {
                // Don't fail the job if stats update fails
                Log::warning('Failed to update operator stats', [
                    'call_log_id' => $this->callLog->id,
                    'error' => $statsError->getMessage(),
                ]);
            }

            Log::info('Call analysis completed successfully', [
                'call_log_id' => $this->callLog->id,
                'overall_score' => $analysisResult['overall_score'],
                'total_cost' => $analysis->total_cost,
                'processing_time_ms' => $processingTime,
            ]);

        } catch (\Exception $e) {
            Log::error('Call analysis job failed', [
                'call_log_id' => $this->callLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up on failure
            if ($tempAudioPath) {
                try {
                    $audioService->deleteFromStorage($tempAudioPath);
                } catch (\Exception $cleanupError) {
                    Log::warning('Failed to clean up temp audio', [
                        'path' => $tempAudioPath,
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            // Mark as failed
            $this->callLog->markAnalysisFailed($e->getMessage());

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Call analysis job permanently failed', [
            'call_log_id' => $this->callLog->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->callLog->markAnalysisFailed(
            "Job failed after {$this->attempts()} attempts: {$exception->getMessage()}"
        );
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(1);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'call-analysis',
            'call_log:'.$this->callLog->id,
            'business:'.$this->callLog->business_id,
        ];
    }
}
