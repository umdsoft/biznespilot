<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ViralContent;
use App\Services\TrendSee\ContentAnalyzerService;
use App\Services\Telegram\SystemBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AnalyzeViralContentJob - Queued AI Analysis
 *
 * Analyzes viral content with AI when:
 * - Too many new items found in ViralHunterJob
 * - Background processing needed
 * - Re-analysis requested
 */
class AnalyzeViralContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    private string $contentId;
    private bool $sendAlert;

    /**
     * Create a new job instance.
     */
    public function __construct(string $contentId, bool $sendAlert = true)
    {
        $this->contentId = $contentId;
        $this->sendAlert = $sendAlert;
    }

    /**
     * Execute the job.
     */
    public function handle(ContentAnalyzerService $analyzer, SystemBotService $telegramBot): void
    {
        $content = ViralContent::find($this->contentId);

        if (!$content) {
            Log::warning('AnalyzeViralContentJob: Content not found', [
                'content_id' => $this->contentId,
            ]);
            return;
        }

        if ($content->is_processed) {
            Log::debug('AnalyzeViralContentJob: Already processed', [
                'content_id' => $this->contentId,
            ]);
            return;
        }

        Log::info('AnalyzeViralContentJob: Analyzing content', [
            'content_id' => $this->contentId,
            'platform_id' => $content->platform_id,
        ]);

        // Run AI analysis
        $analyzer->analyzeAndSave($content);

        // Check if should send alert
        if ($this->sendAlert && $content->is_super_viral && !$content->alert_sent) {
            $this->sendSuperViralAlert($content->fresh(), $telegramBot);
        }
    }

    /**
     * Send super viral alert.
     */
    private function sendSuperViralAlert(ViralContent $content, SystemBotService $telegramBot): void
    {
        $captionSummary = mb_substr($content->caption ?? '', 0, 100);
        if (mb_strlen($content->caption ?? '') > 100) {
            $captionSummary .= '...';
        }

        $hookAnalysis = $content->ai_analysis_json['hook_analysis'] ?? '';

        $message = "🔥 <b>VIRAL ALERT (TrendSee)</b>\n\n";
        $message .= "📹 <b>Mavzu:</b> {$captionSummary}\n\n";
        $message .= "👀 <b>Ko'rishlar:</b> " . number_format($content->play_count) . "\n";
        $message .= "❤️ <b>Layklar:</b> " . number_format($content->like_count) . "\n";
        $message .= "💬 <b>Izohlar:</b> " . number_format($content->comment_count) . "\n\n";

        if (!empty($hookAnalysis)) {
            $message .= "🧠 <b>AI Xulosasi:</b>\n<i>\"{$hookAnalysis}\"</i>\n\n";
        }

        if (!empty($content->permalink)) {
            $message .= "🔗 <a href=\"{$content->permalink}\">Videoni Ko'rish</a>\n";
        }

        $message .= "\n#TrendSee #ViralAlert";

        // Get admin chat IDs
        $adminChatIds = \App\Models\User::whereNotNull('telegram_chat_id')
            ->where(function ($query) {
                $query->where('is_admin', true)
                    ->orWhereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'owner']);
                    });
            })
            ->pluck('telegram_chat_id')
            ->toArray();

        foreach ($adminChatIds as $chatId) {
            try {
                $telegramBot->sendMessage($chatId, $message);
            } catch (\Exception $e) {
                Log::warning('AnalyzeViralContentJob: Failed to send alert', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $content->markAlertSent();
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeViralContentJob: Failed', [
            'content_id' => $this->contentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
