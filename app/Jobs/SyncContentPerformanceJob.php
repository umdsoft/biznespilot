<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\InstagramAccount;
use App\Models\InstagramContentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SyncContentPerformanceJob - Instagram Post Statistikalarini Sinxronlash
 *
 * Bu job Instagram Content Links jadvalidagi postlar uchun
 * Instagram Insights API dan statistikalarni olib keladi.
 *
 * Ishlash tartibi:
 * 1. Sinxronlash kerak bo'lgan postlarni topadi (24+ soat o'tgan)
 * 2. Har bir post uchun Instagram API dan insights oladi
 * 3. Engagement metrikalarini hisoblaydi
 * 4. Top performerlarni aniqlaydi
 *
 * Rate Limit Protection:
 * - 429 xato bo'lsa, job 60 soniyadan keyin qayta ishga tushadi
 * - Maksimum 3 marta urinish
 *
 * Scheduling: Har 6 soatda (routes/console.php da)
 *
 * @see InstagramContentLink
 */
class SyncContentPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $graphApiUrl;

    /**
     * Job ning maksimum urinishlar soni
     */
    public int $tries = 3;

    /**
     * Rate limit bo'lganda kutish vaqti (soniya)
     */
    protected const RATE_LIMIT_DELAY_SECONDS = 60;

    /**
     * API so'rovlari orasidagi kutish vaqti (rate limit uchun)
     */
    protected const API_DELAY_MS = 500;

    /**
     * Bir marta ishlatadigan maksimal postlar soni
     */
    protected const MAX_POSTS_PER_RUN = 100;

    /**
     * Top performer threshold (score >= 70)
     */
    protected const TOP_PERFORMER_THRESHOLD = 70;

    /**
     * Optional: Specific business_id for targeted sync
     */
    protected ?string $businessId;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $businessId = null)
    {
        $this->businessId = $businessId;
        $this->graphApiUrl = 'https://graph.facebook.com/' . config('services.meta.api_version', 'v24.0');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SyncContentPerformanceJob: Starting content performance sync', [
            'business_id' => $this->businessId ?? 'all',
        ]);

        $stats = [
            'total_posts' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'top_performers' => 0,
        ];

        // Sinxronlash kerak bo'lgan postlarni olish
        $query = InstagramContentLink::needsSync()
            ->with('instagramAccount')
            ->limit(self::MAX_POSTS_PER_RUN);

        if ($this->businessId) {
            $query->where('business_id', $this->businessId);
        }

        $posts = $query->get();
        $stats['total_posts'] = $posts->count();

        if ($posts->isEmpty()) {
            Log::info('SyncContentPerformanceJob: No posts need syncing');

            return;
        }

        // Accountlar bo'yicha guruhlash (token uchun)
        $postsByAccount = $posts->groupBy('instagram_account_id');

        foreach ($postsByAccount as $accountId => $accountPosts) {
            if (! $accountId) {
                $stats['skipped'] += $accountPosts->count();

                continue;
            }

            $account = InstagramAccount::find($accountId);
            if (! $account || ! $account->access_token) {
                Log::warning('SyncContentPerformanceJob: No access token for account', [
                    'account_id' => $accountId,
                ]);
                $stats['skipped'] += $accountPosts->count();

                continue;
            }

            foreach ($accountPosts as $post) {
                try {
                    $this->syncPostInsights($post, $account);
                    $stats['synced']++;

                    if ($post->is_top_performer) {
                        $stats['top_performers']++;
                    }

                } catch (\App\Exceptions\RateLimitException $e) {
                    // Rate Limit - jobni keyinroqqa qoldirish
                    Log::warning('SyncContentPerformanceJob: Rate limit reached, releasing job', [
                        'post_id' => $post->id,
                        'delay_seconds' => self::RATE_LIMIT_DELAY_SECONDS,
                        'attempt' => $this->attempts(),
                    ]);

                    // Hozirga qadar bo'lgan statistikani saqlash
                    Log::info('SyncContentPerformanceJob: Partial sync before rate limit', $stats);

                    // Jobni 60 soniyadan keyin qayta ishga tushirish
                    $this->release(self::RATE_LIMIT_DELAY_SECONDS);

                    return;

                } catch (\Exception $e) {
                    Log::error('SyncContentPerformanceJob: Failed to sync post', [
                        'post_id' => $post->id,
                        'media_id' => $post->instagram_media_id,
                        'error' => $e->getMessage(),
                    ]);

                    $post->markSyncFailed($e->getMessage());
                    $stats['failed']++;
                }

                // Rate limit uchun kutish
                usleep(self::API_DELAY_MS * 1000);
            }
        }

        // Top performerlarni yangilash
        $this->updateTopPerformers();

        Log::info('SyncContentPerformanceJob: Completed', $stats);
    }

    /**
     * Post insights ni sinxronlash
     */
    protected function syncPostInsights(InstagramContentLink $post, InstagramAccount $account): void
    {
        // Media turi bo'yicha insights fields
        $insightsFields = $this->getInsightsFieldsForMediaType($post->media_type);

        // 1. Basic metrics (public)
        $basicMetrics = $this->fetchBasicMetrics($post->instagram_media_id, $account->access_token);

        // 2. Insights metrics (business/creator account uchun)
        $insightsMetrics = $this->fetchInsightsMetrics(
            $post->instagram_media_id,
            $account->access_token,
            $insightsFields
        );

        // 3. Ma'lumotlarni birlashtirish va saqlash
        $post->update([
            'likes' => $basicMetrics['like_count'] ?? $post->likes,
            'comments' => $basicMetrics['comments_count'] ?? $post->comments,
            'caption' => $basicMetrics['caption'] ?? $post->caption,
            'permalink' => $basicMetrics['permalink'] ?? $post->permalink,
            'views' => $insightsMetrics['video_views'] ?? $insightsMetrics['impressions'] ?? $post->views,
            'reach' => $insightsMetrics['reach'] ?? $post->reach,
            'impressions' => $insightsMetrics['impressions'] ?? $post->impressions,
            'saves' => $insightsMetrics['saved'] ?? $post->saves,
            'shares' => $insightsMetrics['shares'] ?? $post->shares,
            'plays' => $insightsMetrics['plays'] ?? $insightsMetrics['video_views'] ?? $post->plays,
            'replays' => $insightsMetrics['replays'] ?? $post->replays,
        ]);

        // 4. Engagement metrikalarini hisoblash
        $followerCount = $account->followers_count ?? 0;
        $post->calculateEngagementMetrics($followerCount);

        // 5. Sync statusini yangilash
        $post->markSynced();
    }

    /**
     * Basic metrics olish (likes, comments)
     *
     * @throws \App\Exceptions\RateLimitException Rate limit bo'lganda
     * @throws \RuntimeException Boshqa xatolarda
     */
    protected function fetchBasicMetrics(string $mediaId, string $accessToken): array
    {
        $response = Http::get($this->graphApiUrl . "/{$mediaId}", [
            'fields' => 'like_count,comments_count,caption,permalink,media_type,timestamp',
            'access_token' => $accessToken,
        ]);

        // Rate Limit (429) tekshirish
        if ($response->status() === 429) {
            throw new \App\Exceptions\RateLimitException(
                'Instagram API rate limit reached',
                429
            );
        }

        if (! $response->successful()) {
            // OAuthException - rate limit sababli
            $errorCode = $response->json('error.code');
            if ($errorCode === 4 || $errorCode === 17 || $errorCode === 32) {
                throw new \App\Exceptions\RateLimitException(
                    'Instagram API rate limit: ' . ($response->json('error.message') ?? 'Unknown'),
                    429
                );
            }

            throw new \RuntimeException(
                'Failed to fetch basic metrics: ' . ($response->json('error.message') ?? 'Unknown error')
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Insights metrics olish (reach, impressions, saves)
     *
     * @throws \App\Exceptions\RateLimitException Rate limit bo'lganda
     */
    protected function fetchInsightsMetrics(string $mediaId, string $accessToken, array $metrics): array
    {
        if (empty($metrics)) {
            return [];
        }

        $response = Http::get($this->graphApiUrl . "/{$mediaId}/insights", [
            'metric' => implode(',', $metrics),
            'access_token' => $accessToken,
        ]);

        // Rate Limit (429) tekshirish
        if ($response->status() === 429) {
            throw new \App\Exceptions\RateLimitException(
                'Instagram API rate limit reached on insights',
                429
            );
        }

        // Insights API business/creator account bo'lmasa xato qaytaradi
        if (! $response->successful()) {
            // Rate limit error codes
            $errorCode = $response->json('error.code');
            if ($errorCode === 4 || $errorCode === 17 || $errorCode === 32) {
                throw new \App\Exceptions\RateLimitException(
                    'Instagram API rate limit: ' . ($response->json('error.message') ?? 'Unknown'),
                    429
                );
            }

            // Log but don't fail - basic metrics still available
            Log::debug('SyncContentPerformanceJob: Insights not available (non-business account?)', [
                'media_id' => $mediaId,
                'error' => $response->json('error.message') ?? 'Unknown',
            ]);

            return [];
        }

        $data = $response->json('data') ?? [];
        $result = [];

        foreach ($data as $insight) {
            $name = $insight['name'] ?? null;
            $value = $insight['values'][0]['value'] ?? 0;

            if ($name) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Media turi bo'yicha insights fields
     */
    protected function getInsightsFieldsForMediaType(string $mediaType): array
    {
        // Base metrics (barcha turlar uchun)
        $baseMetrics = ['reach', 'impressions', 'saved'];

        // Media type specific
        return match ($mediaType) {
            'reel', 'video' => array_merge($baseMetrics, ['plays', 'shares', 'video_views', 'total_interactions']),
            'carousel' => array_merge($baseMetrics, ['carousel_album_reach', 'carousel_album_impressions']),
            'story' => ['reach', 'impressions', 'replies', 'exits', 'taps_forward', 'taps_back'],
            default => $baseMetrics,
        };
    }

    /**
     * Top performerlarni yangilash
     *
     * Har bir biznes uchun eng yaxshi 10% postlarni top performer deb belgilaydi.
     */
    protected function updateTopPerformers(): void
    {
        // Avval barcha top performer flaglarni tozalash
        InstagramContentLink::where('is_top_performer', true)
            ->update(['is_top_performer' => false]);

        // Har bir biznes uchun top performerlarni aniqlash
        $businesses = InstagramContentLink::distinct('business_id')
            ->pluck('business_id');

        foreach ($businesses as $businessId) {
            // Performance score >= threshold bo'lgan postlarni top performer deb belgilash
            InstagramContentLink::where('business_id', $businessId)
                ->where('performance_score', '>=', self::TOP_PERFORMER_THRESHOLD)
                ->update(['is_top_performer' => true]);
        }

        Log::info('SyncContentPerformanceJob: Top performers updated');
    }

    /**
     * Yangi Instagram postni bog'lash
     *
     * Content Calendar dan yangi joylangan postni topib, bog'lash uchun ishlatiladi.
     */
    public static function linkNewPost(
        string $businessId,
        string $instagramAccountId,
        string $instagramMediaId,
        array $mediaData = [],
        ?string $contentCalendarId = null,
        ?string $contentIdeaId = null
    ): InstagramContentLink {
        return InstagramContentLink::create([
            'business_id' => $businessId,
            'instagram_account_id' => $instagramAccountId,
            'instagram_media_id' => $instagramMediaId,
            'content_calendar_id' => $contentCalendarId,
            'content_idea_id' => $contentIdeaId,
            'media_type' => $mediaData['media_type'] ?? 'post',
            'permalink' => $mediaData['permalink'] ?? null,
            'thumbnail_url' => $mediaData['thumbnail_url'] ?? null,
            'caption' => $mediaData['caption'] ?? null,
            'posted_at' => $mediaData['timestamp'] ?? now(),
            'shortcode' => $mediaData['shortcode'] ?? null,
            'sync_status' => InstagramContentLink::SYNC_STATUS_PENDING,
            'link_type' => $contentCalendarId ? InstagramContentLink::LINK_TYPE_AUTO : InstagramContentLink::LINK_TYPE_MANUAL,
            'match_method' => $contentCalendarId ? InstagramContentLink::MATCH_METHOD_EXACT : InstagramContentLink::MATCH_METHOD_MANUAL,
            'match_confidence' => $contentCalendarId ? 100.00 : null,
        ]);
    }

    /**
     * Content Calendar asosida postlarni avtomatik bog'lash
     *
     * Joylangan sanasi va caption bo'yicha matching qiladi.
     */
    public static function autoLinkPosts(string $businessId, string $instagramAccountId): int
    {
        $linkedCount = 0;

        // Bog'lanmagan Instagram postlar
        $unlinkedPosts = InstagramContentLink::where('business_id', $businessId)
            ->where('instagram_account_id', $instagramAccountId)
            ->whereNull('content_calendar_id')
            ->get();

        Log::debug('SyncContentPerformanceJob: autoLinkPosts started', [
            'business_id' => $businessId,
            'instagram_account_id' => $instagramAccountId,
            'unlinked_posts_count' => $unlinkedPosts->count(),
        ]);

        foreach ($unlinkedPosts as $post) {
            // Content Calendar da mos postni qidirish
            // 1. Sana bo'yicha (±1 kun)
            // 2. Caption o'xshashligi

            // Allaqachon bog'langan calendar IDlarini olish
            $linkedCalendarIds = InstagramContentLink::where('business_id', $businessId)
                ->whereNotNull('content_calendar_id')
                ->pluck('content_calendar_id')
                ->toArray();

            $contentCalendar = \App\Models\ContentCalendar::where('business_id', $businessId)
                ->where('status', 'published')
                ->whereBetween('scheduled_date', [
                    $post->posted_at?->subDay()?->toDateString(),
                    $post->posted_at?->addDay()?->toDateString(),
                ])
                ->whereNotIn('id', $linkedCalendarIds) // Hali bog'lanmagan
                ->get();

            Log::debug('SyncContentPerformanceJob: Content calendar query', [
                'post_id' => $post->id,
                'post_posted_at' => $post->posted_at?->toDateTimeString(),
                'date_range' => [
                    $post->posted_at?->subDay()?->toDateString(),
                    $post->posted_at?->addDay()?->toDateString(),
                ],
                'calendars_found' => $contentCalendar->count(),
            ]);

            foreach ($contentCalendar as $calendar) {
                $confidence = self::calculateMatchConfidence($post, $calendar);

                Log::debug('SyncContentPerformanceJob: Match confidence', [
                    'post_caption' => $post->caption,
                    'calendar_content' => $calendar->content ?? $calendar->content_text,
                    'confidence' => $confidence,
                ]);

                if ($confidence >= 70) {
                    $post->update([
                        'content_calendar_id' => $calendar->id,
                        'link_type' => InstagramContentLink::LINK_TYPE_AUTO,
                        'match_method' => InstagramContentLink::MATCH_METHOD_FUZZY,
                        'match_confidence' => $confidence,
                    ]);

                    $linkedCount++;
                    break;
                }
            }
        }

        Log::info('SyncContentPerformanceJob: Auto-linked posts', [
            'business_id' => $businessId,
            'linked_count' => $linkedCount,
        ]);

        return $linkedCount;
    }

    /**
     * Match confidence hisoblash
     */
    protected static function calculateMatchConfidence(InstagramContentLink $post, $calendar): float
    {
        $confidence = 0;

        // 1. Sana bo'yicha (max 40%)
        // scheduled_at (datetime) yoki scheduled_date (date only) tekshirish
        $calendarDatetime = $calendar->scheduled_at;
        $calendarDateOnly = $calendar->scheduled_date;

        if ($post->posted_at && ($calendarDatetime || $calendarDateOnly)) {
            if ($calendarDatetime) {
                // Agar scheduled_at mavjud bo'lsa, soatgacha solishtiramiz
                $hoursDiff = abs($post->posted_at->diffInHours($calendarDatetime));

                if ($hoursDiff <= 1) {
                    $confidence += 40;
                } elseif ($hoursDiff <= 6) {
                    $confidence += 30;
                } elseif ($hoursDiff <= 12) {
                    $confidence += 20;
                } elseif ($hoursDiff <= 24) {
                    $confidence += 10;
                }
            } else {
                // Faqat sana mavjud - kun bo'yicha solishtiramiz
                $postDate = $post->posted_at->toDateString();
                $calendarDate = $calendarDateOnly instanceof \Carbon\Carbon
                    ? $calendarDateOnly->toDateString()
                    : $calendarDateOnly;

                if ($postDate === $calendarDate) {
                    // Bir xil kun = maksimal ball
                    $confidence += 40;
                } else {
                    // Qo'shni kunlar
                    $daysDiff = abs($post->posted_at->diffInDays(\Carbon\Carbon::parse($calendarDate)));
                    if ($daysDiff <= 1) {
                        $confidence += 20;
                    }
                }
            }
        }

        // 2. Caption o'xshashligi (max 60%)
        // ContentCalendar: content yoki content_text; InstagramContentLink: caption
        $calendarContent = $calendar->content_text ?? $calendar->content ?? '';
        if ($post->caption && $calendarContent) {
            $similarity = 0;
            similar_text(
                mb_strtolower($post->caption),
                mb_strtolower($calendarContent),
                $similarity
            );

            $confidence += ($similarity / 100) * 60;
        }

        return round($confidence, 2);
    }
}
