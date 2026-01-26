<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ViralContent;
use App\Services\TrendSee\ContentAnalyzerService;
use App\Services\External\ApifyService;
use App\Services\Telegram\SystemBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ViralHunterJob - Weekly Viral Content Hunter
 *
 * "The Hunter" - Automatically fetches and analyzes viral Instagram Reels.
 *
 * Workflow:
 * 1. Fetch from Apify (Instagram Scraper) for each configured hashtag
 * 2. Filter duplicates (by platform_id)
 * 3. Save new viral content to database
 * 4. Queue AI analysis for new items
 * 5. Send Telegram alert for super viral content (5k+ views)
 *
 * Scheduling: Weekly (Mondays at 9:00 AM)
 */
class ViralHunterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes max

    private array $hashtags;

    private const VIRAL_PLAYS_THRESHOLD = 50000;   // 50k+ plays = genuinely viral
    private const VIRAL_LIKES_THRESHOLD = 2000;   // 2k+ likes = high engagement
    private const SUPER_VIRAL_THRESHOLD = 100000; // 100k+ = mega viral

    /**
     * Create a new job instance.
     *
     * @param array|null $hashtags Custom hashtags (optional)
     */
    public function __construct(?array $hashtags = null)
    {
        $this->hashtags = $hashtags ?? config('viral_niches.general', ['businessuz', 'trenduz']);
    }

    /**
     * Execute the job.
     */
    public function handle(
        ApifyService $apify,
        ContentAnalyzerService $analyzer,
        SystemBotService $telegramBot
    ): void {
        Log::info('ViralHunterJob: Starting viral content hunt', [
            'hashtags_count' => count($this->hashtags),
            'api' => $apify->getApiName(),
        ]);

        if (!$apify->isConfigured()) {
            Log::warning('ViralHunterJob: Apify not configured, skipping');
            return;
        }

        $stats = [
            'total_fetched' => 0,
            'new_saved' => 0,
            'duplicates_skipped' => 0,
            'analyzed' => 0,
            'super_viral_alerts' => 0,
            'errors' => 0,
        ];

        foreach ($this->hashtags as $hashtag) {
            try {
                $this->processHashtag($hashtag, $apify, $analyzer, $telegramBot, $stats);

                // Delay between hashtags to respect rate limits
                sleep(3);

            } catch (\Exception $e) {
                Log::error('ViralHunterJob: Error processing hashtag', [
                    'hashtag' => $hashtag,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // Process any pending super viral alerts
        $this->sendPendingAlerts($telegramBot, $stats);

        Log::info('ViralHunterJob: Hunt completed', $stats);
    }

    /**
     * Process a single hashtag.
     */
    private function processHashtag(
        string $hashtag,
        ApifyService $apify,
        ContentAnalyzerService $analyzer,
        SystemBotService $telegramBot,
        array &$stats
    ): void {
        Log::debug('ViralHunterJob: Processing hashtag', ['hashtag' => $hashtag]);

        // Step 1: Fetch from Apify (Instagram Scraper)
        $posts = $apify->fetchHashtagFeed($hashtag);

        if (empty($posts)) {
            Log::warning('ViralHunterJob: No posts returned', ['hashtag' => $hashtag]);
            return;
        }

        Log::info('ViralHunterJob: Fetched posts', [
            'hashtag' => $hashtag,
            'count' => count($posts),
        ]);

        $stats['total_fetched'] += count($posts);

        foreach ($posts as $postData) {
            try {
                // Extract metrics from new DTO format
                $metrics = $postData['metrics'] ?? [];
                $playCount = $metrics['plays'] ?? 0;
                $likeCount = $metrics['likes'] ?? 0;
                $commentCount = $metrics['comments'] ?? 0;

                // Skip if below viral threshold (plays OR likes)
                if ($playCount < self::VIRAL_PLAYS_THRESHOLD && $likeCount < self::VIRAL_LIKES_THRESHOLD) {
                    continue;
                }

                $platformId = $postData['platform_id'] ?? null;
                if (!$platformId) {
                    continue;
                }

                // Step 2: Check for duplicates
                $existingPost = ViralContent::where('platform_id', $platformId)->first();

                if ($existingPost) {
                    // Update metrics if already exists
                    $existingPost->update([
                        'play_count' => $playCount > 0 ? $playCount : $existingPost->play_count,
                        'like_count' => $likeCount > 0 ? $likeCount : $existingPost->like_count,
                        'comment_count' => $commentCount > 0 ? $commentCount : $existingPost->comment_count,
                        'metrics_json' => $metrics ?: $existingPost->metrics_json,
                    ]);
                    $existingPost->checkSuperViral(self::SUPER_VIRAL_THRESHOLD);

                    $stats['duplicates_skipped']++;
                    continue;
                }

                // Extract music info
                $music = $postData['music'] ?? [];
                $isSuperViral = $playCount >= self::SUPER_VIRAL_THRESHOLD;

                // Step 3: Save new viral content
                $content = ViralContent::create([
                    'platform' => 'instagram',
                    'platform_id' => $platformId,
                    'platform_username' => $postData['platform_username'] ?? null,
                    'niche' => $postData['niche'] ?? $hashtag,
                    'caption' => $postData['caption'] ?? null,
                    'video_url' => $postData['video_url'] ?? null,
                    'thumbnail_url' => $postData['thumbnail_url'] ?? null,
                    'permalink' => $postData['permalink'] ?? null,
                    'play_count' => $playCount,
                    'like_count' => $likeCount,
                    'comment_count' => $commentCount,
                    'metrics_json' => $metrics,
                    'music_title' => $music['title'] ?? null,
                    'music_artist' => $music['artist'] ?? null,
                    'is_super_viral' => $isSuperViral,
                    'fetched_at' => now(),
                ]);

                $stats['new_saved']++;

                Log::info('ViralHunterJob: Saved new content', [
                    'id' => $content->id,
                    'play_count' => $playCount,
                    'niche' => $hashtag,
                ]);

                // Step 4: Analyze with AI (queue for later if too many)
                if ($stats['new_saved'] <= 10) {
                    try {
                        $analyzer->analyzeAndSave($content);
                        $stats['analyzed']++;
                    } catch (\Exception $e) {
                        Log::warning('ViralHunterJob: AI analysis failed', [
                            'content_id' => $content->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    // Queue for later analysis
                    AnalyzeViralContentJob::dispatch($content->id)->delay(now()->addMinutes(5));
                }

                // Step 5: Check for super viral alert
                if ($isSuperViral && !$content->alert_sent) {
                    $this->sendSuperViralAlert($content, $telegramBot);
                    $content->markAlertSent();
                    $stats['super_viral_alerts']++;
                }

            } catch (\Exception $e) {
                Log::error('ViralHunterJob: Error processing post', [
                    'platform_id' => $postData['platform_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }
    }

    /**
     * Send Telegram alert for super viral content.
     */
    private function sendSuperViralAlert(ViralContent $content, SystemBotService $telegramBot): void
    {
        $captionSummary = mb_substr($content->caption ?? '', 0, 100);
        if (mb_strlen($content->caption ?? '') > 100) {
            $captionSummary .= '...';
        }

        $aiSummary = $content->ai_summary ?? 'Tahlil kutilmoqda...';
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

        // Get admin users to notify
        $adminChatIds = $this->getAdminChatIds();

        foreach ($adminChatIds as $chatId) {
            try {
                $telegramBot->sendMessage($chatId, $message);
            } catch (\Exception $e) {
                Log::warning('ViralHunterJob: Failed to send alert', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send pending alerts for super viral content.
     */
    private function sendPendingAlerts(SystemBotService $telegramBot, array &$stats): void
    {
        $pendingAlerts = ViralContent::pendingAlert()
            ->where('is_processed', true)
            ->limit(5)
            ->get();

        foreach ($pendingAlerts as $content) {
            $this->sendSuperViralAlert($content, $telegramBot);
            $content->markAlertSent();
            $stats['super_viral_alerts']++;
        }
    }

    /**
     * Get admin Telegram chat IDs for alerts.
     */
    private function getAdminChatIds(): array
    {
        // Get users with telegram_chat_id who are admins or have specific role
        return \App\Models\User::whereNotNull('telegram_chat_id')
            ->where(function ($query) {
                $query->where('is_admin', true)
                    ->orWhereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'owner', 'marketing_manager']);
                    });
            })
            ->pluck('telegram_chat_id')
            ->toArray();
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ViralHunterJob: Job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
