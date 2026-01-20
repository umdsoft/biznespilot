<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\ContentTemplate;
use App\Models\InstagramPost;
use App\Models\TelegramBroadcast;
use App\Services\ContentAI\ContentAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportSuccessfulPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        public ?string $businessId = null,
        public float $minEngagementRate = 3.0, // Instagram uchun minimum 3% engagement
        public float $minDeliveryRate = 80.0   // Telegram uchun minimum 80% delivery
    ) {}

    public function handle(ContentAnalyzerService $analyzer): void
    {
        if ($this->businessId) {
            $this->importForBusiness($this->businessId, $analyzer);
            return;
        }

        // Barcha aktiv bizneslar uchun (Instagram yoki Telegram bor)
        $businesses = Business::where('status', 'active')
            ->where(function ($q) {
                $q->whereHas('instagramAccounts')
                    ->orWhereHas('telegramBots');
            })
            ->get();

        foreach ($businesses as $business) {
            try {
                $this->importForBusiness($business->id, $analyzer);
            } catch (\Exception $e) {
                Log::error("Failed to import posts for business {$business->id}: " . $e->getMessage());
            }
        }
    }

    protected function importForBusiness(string $businessId, ContentAnalyzerService $analyzer): void
    {
        $instagramCount = $this->importInstagramPosts($businessId, $analyzer);
        $telegramCount = $this->importTelegramBroadcasts($businessId, $analyzer);

        $total = $instagramCount + $telegramCount;
        if ($total > 0) {
            Log::info("Imported {$total} successful posts for business {$businessId}", [
                'instagram' => $instagramCount,
                'telegram' => $telegramCount,
            ]);
        }
    }

    /**
     * Import successful Instagram posts.
     */
    protected function importInstagramPosts(string $businessId, ContentAnalyzerService $analyzer): int
    {
        $instagramPosts = InstagramPost::whereHas('instagramAccount', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
            ->where('engagement_rate', '>=', $this->minEngagementRate)
            ->where('created_at', '>=', now()->subDays(90))
            ->whereNotExists(function ($q) {
                $q->select('id')
                    ->from('content_templates')
                    ->whereColumn('content_templates.source_id', 'instagram_posts.instagram_id')
                    ->where('content_templates.source_type', 'instagram');
            })
            ->orderByDesc('engagement_rate')
            ->limit(20)
            ->get();

        $importedCount = 0;

        foreach ($instagramPosts as $post) {
            try {
                $template = $this->createTemplateFromInstagram($post, $businessId);

                if ($template) {
                    $analyzer->analyzePost($template);
                    $importedCount++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to import Instagram post {$post->id}: " . $e->getMessage());
            }
        }

        return $importedCount;
    }

    /**
     * Import successful Telegram broadcasts.
     */
    protected function importTelegramBroadcasts(string $businessId, ContentAnalyzerService $analyzer): int
    {
        $broadcasts = TelegramBroadcast::where('business_id', $businessId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(90))
            ->where('total_recipients', '>=', 50) // Kamida 50 ta recipient
            ->whereRaw('(delivered_count / NULLIF(sent_count, 0) * 100) >= ?', [$this->minDeliveryRate])
            ->whereNotExists(function ($q) {
                $q->select('id')
                    ->from('content_templates')
                    ->whereColumn('content_templates.source_id', 'telegram_broadcasts.id')
                    ->where('content_templates.source_type', 'telegram');
            })
            ->orderByDesc('delivered_count')
            ->limit(15)
            ->get();

        $importedCount = 0;

        foreach ($broadcasts as $broadcast) {
            try {
                $template = $this->createTemplateFromTelegram($broadcast, $businessId);

                if ($template) {
                    $analyzer->analyzePost($template);
                    $importedCount++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to import Telegram broadcast {$broadcast->id}: " . $e->getMessage());
            }
        }

        return $importedCount;
    }

    /**
     * Create ContentTemplate from Instagram post.
     */
    protected function createTemplateFromInstagram(InstagramPost $post, string $businessId): ?ContentTemplate
    {
        if (empty($post->caption)) {
            return null;
        }

        $contentType = $this->detectInstagramContentType($post);
        $purpose = $this->detectPurpose($post->caption);

        $template = ContentTemplate::create([
            'business_id' => $businessId,
            'source_type' => 'instagram',
            'source_id' => $post->instagram_id,
            'source_url' => $post->permalink,
            'content_type' => $contentType,
            'purpose' => $purpose,
            'content' => $post->caption,
            'content_cleaned' => $this->cleanContent($post->caption),
            'hashtags' => ContentTemplate::extractHashtags($post->caption),
            'mentions' => ContentTemplate::extractMentions($post->caption),
            'media_urls' => $post->media_url ? [$post->media_url] : null,
            'likes_count' => $post->like_count ?? 0,
            'comments_count' => $post->comments_count ?? 0,
            'shares_count' => $post->shares ?? 0,
            'saves_count' => $post->saved ?? 0,
            'reach' => $post->reach ?? 0,
            'impressions' => $post->impressions ?? 0,
            'engagement_rate' => $post->engagement_rate ?? 0,
            'posted_at' => $post->timestamp ?? $post->created_at,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $template->updatePerformanceScore();

        return $template;
    }

    /**
     * Create ContentTemplate from Telegram broadcast.
     */
    protected function createTemplateFromTelegram(TelegramBroadcast $broadcast, string $businessId): ?ContentTemplate
    {
        $text = $broadcast->getContentText();
        if (empty($text)) {
            return null;
        }

        $contentType = $this->detectTelegramContentType($broadcast);
        $purpose = $this->detectPurpose($text);

        // Calculate engagement rate for Telegram
        $engagementRate = 0;
        if ($broadcast->sent_count > 0) {
            $engagementRate = ($broadcast->delivered_count / $broadcast->sent_count) * 100;
        }

        $template = ContentTemplate::create([
            'business_id' => $businessId,
            'source_type' => 'telegram',
            'source_id' => $broadcast->id,
            'source_url' => null, // Telegram broadcastlar uchun URL yo'q
            'content_type' => $contentType,
            'purpose' => $purpose,
            'content' => $text,
            'content_cleaned' => $this->cleanContent($text),
            'hashtags' => ContentTemplate::extractHashtags($text),
            'mentions' => ContentTemplate::extractMentions($text),
            'media_urls' => $this->extractTelegramMedia($broadcast),
            'likes_count' => 0, // Telegram da like yo'q
            'comments_count' => 0,
            'shares_count' => 0,
            'saves_count' => 0,
            'reach' => $broadcast->total_recipients,
            'impressions' => $broadcast->delivered_count,
            'engagement_rate' => $engagementRate,
            'posted_at' => $broadcast->started_at ?? $broadcast->created_at,
            'is_active' => true,
            'is_approved' => true,
            'metadata' => [
                'telegram_bot_id' => $broadcast->telegram_bot_id,
                'delivery_rate' => $broadcast->getDeliveryRate(),
                'blocked_rate' => $broadcast->getBlockedRate(),
                'has_keyboard' => $broadcast->hasKeyboard(),
            ],
        ]);

        $template->updatePerformanceScore();

        return $template;
    }

    /**
     * Detect content type from Instagram post.
     */
    protected function detectInstagramContentType(InstagramPost $post): string
    {
        $mediaType = strtolower($post->media_type ?? '');

        return match (true) {
            str_contains($mediaType, 'video') || str_contains($mediaType, 'reel') => 'reel',
            str_contains($mediaType, 'carousel') || str_contains($mediaType, 'album') => 'carousel',
            str_contains($mediaType, 'story') => 'story',
            default => 'post',
        };
    }

    /**
     * Detect content type from Telegram broadcast.
     */
    protected function detectTelegramContentType(TelegramBroadcast $broadcast): string
    {
        $type = $broadcast->getContentType();

        return match ($type) {
            'photo' => 'post',
            'video' => 'reel',
            'document' => 'article',
            default => 'post',
        };
    }

    /**
     * Extract media URLs from Telegram broadcast.
     */
    protected function extractTelegramMedia(TelegramBroadcast $broadcast): ?array
    {
        $content = $broadcast->content;
        if (!$content) {
            return null;
        }

        $mediaUrl = data_get($content, 'file_url') ?? data_get($content, 'media_url');
        if ($mediaUrl) {
            return [$mediaUrl];
        }

        return null;
    }

    /**
     * Detect purpose from content.
     */
    protected function detectPurpose(string $content): string
    {
        $content = mb_strtolower($content);

        $patterns = [
            'sell' => ['chegirma', 'aksiya', 'skidka', 'narx', 'sotib ol', 'buyurtma', 'bepul yetkazib', '%', 'so\'m', 'sum'],
            'educate' => ['qanday', 'nima uchun', 'bilasizmi', 'maslahat', 'yo\'l', 'usul', 'secret', 'tips'],
            'inspire' => ['motivatsiya', 'muvaffaqiyat', 'ilhom', 'dream', 'goal', 'believe', 'maqsad'],
            'announce' => ['yangilik', 'e\'lon', 'ochilish', 'yangi', 'coming soon', 'tez kunda', 'startap'],
            'entertain' => ['kulgili', 'hazil', '😂', '🤣', 'meme', 'lol'],
        ];

        foreach ($patterns as $purpose => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($content, $keyword)) {
                    return $purpose;
                }
            }
        }

        return 'engage';
    }

    /**
     * Clean content from hashtags and mentions.
     */
    protected function cleanContent(string $content): string
    {
        // Remove hashtags
        $content = preg_replace('/#\w+/u', '', $content);

        // Remove mentions
        $content = preg_replace('/@\w+/u', '', $content);

        // Remove URLs
        $content = preg_replace('/https?:\/\/\S+/i', '', $content);

        // Clean up extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}
