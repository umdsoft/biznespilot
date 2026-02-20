<?php

namespace App\Jobs\VideoContent;

use App\Models\VideoContentRequest;
use App\Services\CallCenter\SpeechToTextService;
use App\Services\ContentAI\ContentGeneratorService;
use App\Services\VideoContent\VideoAnalysisService;
use App\Services\VideoContent\VideoExtractorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public array $backoff;

    public function __construct(
        public VideoContentRequest $request
    ) {
        $this->tries = config('video-content.queue.tries', 2);
        $this->timeout = config('video-content.queue.timeout', 600);
        $this->backoff = config('video-content.queue.backoff', [60, 120]);
        $this->onQueue(config('video-content.queue.queue', 'video-content'));
    }

    public function handle(
        VideoExtractorService $extractor,
        SpeechToTextService $sttService,
        VideoAnalysisService $analysisService,
        ContentGeneratorService $contentService
    ): void {
        $startTime = microtime(true);
        $audioPath = null;

        Log::info('Starting video content job', [
            'request_id' => $this->request->id,
            'video_url' => $this->request->video_url,
        ]);

        try {
            // Step 1: Extract audio from video
            $this->request->markStatus('extracting');

            $extractResult = $extractor->extractAudio($this->request->video_url);
            $audioPath = $extractResult['audio_path'];
            $metadata = $extractResult['metadata'];

            $this->request->update([
                'video_title' => $metadata['title'] ?? $this->request->video_title,
                'video_duration' => $metadata['duration'] ?? null,
                'thumbnail_url' => $metadata['thumbnail'] ?? null,
            ]);

            // Step 2: Transcribe audio (DRY — SpeechToTextService qayta ishlatiladi)
            $this->request->markStatus('transcribing');

            Log::info('Transcribing video audio', ['request_id' => $this->request->id]);

            $transcriptResult = $sttService->transcribe($audioPath);

            $this->request->update([
                'transcript' => $transcriptResult['text'],
                'stt_cost' => $transcriptResult['cost'],
                'stt_model' => $transcriptResult['model'],
            ]);

            // Cleanup audio — endi kerak emas
            $extractor->cleanup($audioPath);
            $audioPath = null;

            // Step 3: Analyze transcript — kalit nuqtalarni ajratish
            $this->request->markStatus('analyzing');

            Log::info('Analyzing video transcript', ['request_id' => $this->request->id]);

            $analysisResult = $analysisService->analyze(
                $transcriptResult['text'],
                $this->request->video_title,
                $this->request->video_duration ?? 0
            );

            $this->request->update([
                'key_points' => $analysisResult['key_points'],
                'analysis_cost' => $analysisResult['cost'],
                'analysis_model' => $analysisResult['model'],
                'input_tokens' => $analysisResult['input_tokens'],
                'output_tokens' => $analysisResult['output_tokens'],
            ]);

            // Step 4: Generate content from key points
            $this->request->markStatus('generating');

            Log::info('Generating content from video', ['request_id' => $this->request->id]);

            $keyPoints = $analysisResult['key_points'];
            $topic = $keyPoints['topic'] ?? $this->request->video_title ?? 'Video kontent';

            // Build enriched prompt from key points
            $additionalPrompt = $this->buildVideoPrompt($keyPoints);

            $generation = $contentService->generate(
                $this->request->business_id,
                $this->request->user_id,
                $topic,
                $this->request->content_type,
                $this->request->purpose,
                $this->request->target_channel,
                $additionalPrompt
            );

            // Step 5: Finalize
            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            $totalCost = ($this->request->stt_cost ?? 0) + ($this->request->analysis_cost ?? 0) + ($generation->cost_usd ?? 0);

            $this->request->update([
                'total_cost' => $totalCost,
                'processing_time_ms' => $processingTime,
            ]);

            if ($generation->status === 'completed') {
                $this->request->markCompleted($generation->id);
            } else {
                $this->request->markFailed($generation->error_message ?? 'Kontent generatsiya xatosi');
            }

            Log::info('Video content job completed', [
                'request_id' => $this->request->id,
                'generation_id' => $generation->id,
                'total_cost' => $totalCost,
                'processing_time_ms' => $processingTime,
            ]);

        } catch (\Exception $e) {
            Log::error('Video content job failed', [
                'request_id' => $this->request->id,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            // Cleanup on failure
            if ($audioPath) {
                $extractor->cleanup($audioPath);
            }

            $this->request->markFailed($e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Video content job permanently failed', [
            'request_id' => $this->request->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->request->markFailed(
            "Job {$this->attempts()} urinishdan keyin xato: {$exception->getMessage()}"
        );
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function tags(): array
    {
        return [
            'video-content',
            'request:' . $this->request->id,
            'business:' . $this->request->business_id,
        ];
    }

    /**
     * Build enriched prompt from video key points
     */
    protected function buildVideoPrompt(array $keyPoints): string
    {
        $parts = [];

        $parts[] = "VIDEO TAHLILI ASOSIDA KONTENT YARATING:";

        if (! empty($keyPoints['hooks'])) {
            $hooks = implode("\n- ", $keyPoints['hooks']);
            $parts[] = "\nKUCHLI HOOK'LAR (videodan):\n- {$hooks}";
        }

        if (! empty($keyPoints['facts'])) {
            $facts = implode("\n- ", $keyPoints['facts']);
            $parts[] = "\nMUHIM FAKTLAR:\n- {$facts}";
        }

        if (! empty($keyPoints['story_elements'])) {
            $stories = implode("\n- ", $keyPoints['story_elements']);
            $parts[] = "\nHIKOYA ELEMENTLARI:\n- {$stories}";
        }

        if (! empty($keyPoints['key_messages'])) {
            $messages = implode("\n- ", $keyPoints['key_messages']);
            $parts[] = "\nASOSIY XABARLAR:\n- {$messages}";
        }

        if (! empty($keyPoints['cta'])) {
            $parts[] = "\nCTA: {$keyPoints['cta']}";
        }

        if (! empty($keyPoints['target_audience'])) {
            $parts[] = "\nMaqsadli auditoriya: {$keyPoints['target_audience']}";
        }

        if (! empty($keyPoints['content_angles'])) {
            $angles = implode("\n- ", $keyPoints['content_angles']);
            $parts[] = "\nKONTENT BURCHAKLARI:\n- {$angles}";
        }

        $parts[] = "\nYuqoridagi ma'lumotlar asosida kuchli, asl kontent yarating. Videodagi eng yaxshi g'oya va hookalardan foydalaning.";

        return implode("\n", $parts);
    }
}
