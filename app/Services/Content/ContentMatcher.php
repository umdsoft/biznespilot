<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ContentCalendar;
use App\Models\ContentPostLink;
use App\Models\InstagramAccount;
use App\Models\InstagramMedia;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelPost;
use App\Models\YoutubeChannel;
use App\Models\YoutubeVideo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Content reja item'ni tashqaridan kelgan post bilan match qiluvchi orchestrator.
 *
 * Match prioritetlari (ishonchlilik darajasi bo'yicha):
 *   1. HASHTAG     — score 1.0 (foydalanuvchi nusxa olganda hashtag saqlangan)
 *   2. WATERMARK   — score 1.0 (foydalanuvchi hashtag'ni o'chirgan, lekin invisible saqlangan)
 *   3. FUZZY       — score 0.5..0.95 (matn + vaqt + media + tag kombinatsiyasi)
 *
 * Threshold:
 *   - score >= 0.85   → avtomatik match (yuqori ishonch)
 *   - 0.6..0.85       → audit log + match qilamiz, lekin operatorga eslatma
 *   - < 0.6           → match yo'q, hech qanday item belgilanmaydi
 *
 * Maqsad: foydalanuvchi tomonidan 0 daqiqa qo'lda kiritish kerak emas.
 */
class ContentMatcher
{
    public function __construct(
        protected ContentHashtagGenerator $hashtagGenerator,
        protected ContentWatermarker $watermarker,
    ) {}

    /**
     * Auto-match threshold — pastida match yozilmaydi.
     */
    public const AUTO_MATCH_THRESHOLD = 0.6;

    /**
     * High-confidence threshold — pastida operatorga eslatma chiqariladi.
     */
    public const HIGH_CONFIDENCE_THRESHOLD = 0.85;

    /**
     * Vaqt oynasi — reja sanasi atrofida ±N kun ichida bo'lgan postlar tekshiriladi.
     */
    public const TIME_WINDOW_DAYS = 3;

    /**
     * Telegram channel post kelganda chaqiriladi.
     *
     * @return array{matched: bool, content_id: ?string, method: ?string, score: ?float}
     */
    public function matchTelegramChannelPost(TelegramChannelPost $post): array
    {
        $channel = $post->channel;
        if (! $channel || empty($channel->business_id)) {
            return $this->result(false);
        }

        $businessId = $channel->business_id;
        $text = (string) ($post->text_preview ?? '');

        // 1) HASHTAG — eng aniq, eng tez signal
        $rawMessage = is_array($post->raw_payload) ? $post->raw_payload : [];
        $entityHashtags = $this->hashtagGenerator->findInEntities($rawMessage);
        $allTextForHashtagSearch = $text;
        if (! empty($entityHashtags)) {
            $allTextForHashtagSearch .= ' ' . implode(' ', $entityHashtags);
        }
        // Telegram raw payload'dagi to'liq matn — text_preview cheklangan bo'lsa ham
        $fullText = (string) ($rawMessage['text'] ?? $rawMessage['caption'] ?? $allTextForHashtagSearch);

        $byHashtag = $this->matchByHashtag($businessId, $fullText);
        if ($byHashtag) {
            return $this->finalize($byHashtag, $post, 'hashtag', 1.0);
        }

        // 2) WATERMARK — invisible Unicode (foydalanuvchi hashtag'ni o'chirgan bo'lsa)
        $byWatermark = $this->matchByWatermark($businessId, $fullText);
        if ($byWatermark) {
            return $this->finalize($byWatermark, $post, 'watermark', 1.0);
        }

        // 3) FUZZY fallback — matn + vaqt + tag kombinatsiyasi
        $byFuzzy = $this->matchByFuzzy(
            businessId: $businessId,
            postedAt: $post->posted_at instanceof Carbon ? $post->posted_at : Carbon::parse((string) $post->posted_at),
            text: $fullText,
            contentType: (string) $post->content_type,
            platform: 'telegram',
        );

        if ($byFuzzy && $byFuzzy['score'] >= self::AUTO_MATCH_THRESHOLD) {
            return $this->finalize($byFuzzy['item'], $post, 'fuzzy', $byFuzzy['score']);
        }

        Log::info('ContentMatcher: no match for Telegram channel post', [
            'channel_id' => $channel->id,
            'post_message_id' => $post->message_id,
            'fuzzy_score' => $byFuzzy['score'] ?? 0,
        ]);

        return $this->result(false);
    }

    /**
     * Instagram media (yangi yoki yangilangan) ContentCalendar bilan match qiladi.
     *
     * @return array{matched: bool, content_id: ?string, method: ?string, score: ?float}
     */
    public function matchInstagramMedia(InstagramMedia $media): array
    {
        $account = $media->instagramAccount ?? InstagramAccount::find($media->account_id);
        if (! $account || empty($account->business_id)) {
            return $this->result(false);
        }

        $businessId = $account->business_id;
        $caption = (string) ($media->caption ?? '');

        // 1) HASHTAG (eng aniq)
        $byHashtag = $this->matchByHashtag($businessId, $caption);
        if ($byHashtag) {
            return $this->finalizeInstagram($byHashtag, $media, 'hashtag', 1.0);
        }

        // 2) WATERMARK (invisible Unicode)
        $byWatermark = $this->matchByWatermark($businessId, $caption);
        if ($byWatermark) {
            return $this->finalizeInstagram($byWatermark, $media, 'watermark', 1.0);
        }

        // 3) FUZZY fallback
        $postedAt = $media->posted_at instanceof Carbon
            ? $media->posted_at
            : Carbon::parse((string) $media->posted_at);

        $contentType = match (strtoupper((string) $media->media_type)) {
            'IMAGE' => 'photo',
            'VIDEO', 'REELS' => 'video',
            'CAROUSEL_ALBUM' => 'carousel',
            default => 'post',
        };

        $byFuzzy = $this->matchByFuzzy(
            businessId: $businessId,
            postedAt: $postedAt,
            text: $caption,
            contentType: $contentType,
            platform: 'instagram',
        );

        if ($byFuzzy && $byFuzzy['score'] >= self::AUTO_MATCH_THRESHOLD) {
            return $this->finalizeInstagram($byFuzzy['item'], $media, 'fuzzy', $byFuzzy['score']);
        }

        Log::info('ContentMatcher: no match for Instagram media', [
            'account_id' => $account->id,
            'media_id' => $media->media_id,
            'fuzzy_score' => $byFuzzy['score'] ?? 0,
        ]);

        return $this->result(false);
    }

    /**
     * YouTube video matching — title + description orqali.
     *
     * @return array{matched: bool, content_id: ?string, method: ?string, score: ?float}
     */
    public function matchYoutubeVideo(YoutubeVideo $video): array
    {
        $channel = $video->channel ?? YoutubeChannel::find($video->youtube_channel_id);
        if (! $channel || empty($channel->business_id)) {
            return $this->result(false);
        }

        $businessId = $channel->business_id;
        $haystack = trim((string) $video->title . "\n" . (string) $video->description);

        // 1) HASHTAG
        $byHashtag = $this->matchByHashtag($businessId, $haystack);
        if ($byHashtag) {
            return $this->finalizeYoutube($byHashtag, $video, 'hashtag', 1.0);
        }

        // 2) WATERMARK
        $byWatermark = $this->matchByWatermark($businessId, $haystack);
        if ($byWatermark) {
            return $this->finalizeYoutube($byWatermark, $video, 'watermark', 1.0);
        }

        // 3) FUZZY
        $postedAt = $video->published_at instanceof Carbon
            ? $video->published_at
            : Carbon::parse((string) $video->published_at);

        $contentType = $video->is_short ? 'short' : 'video';

        $byFuzzy = $this->matchByFuzzy(
            businessId: $businessId,
            postedAt: $postedAt,
            text: $haystack,
            contentType: $contentType,
            platform: 'youtube',
        );

        if ($byFuzzy && $byFuzzy['score'] >= self::AUTO_MATCH_THRESHOLD) {
            return $this->finalizeYoutube($byFuzzy['item'], $video, 'fuzzy', $byFuzzy['score']);
        }

        Log::info('ContentMatcher: no match for YouTube video', [
            'channel_id' => $channel->id,
            'video_id' => $video->video_id,
            'fuzzy_score' => $byFuzzy['score'] ?? 0,
        ]);

        return $this->result(false);
    }

    // ============================================================

    /**
     * Hashtag orqali — generatsiya qilingan #brand_topic_a3f9 ni qidirish.
     */
    protected function matchByHashtag(string $businessId, string $text): ?ContentCalendar
    {
        if ($text === '') {
            return null;
        }

        // Avval — to'liq match (auto_hashtag DB ustuni bilan):
        if (preg_match_all('/#([a-zA-Z0-9_]+)/u', $text, $m)) {
            foreach ($m[0] as $tag) {
                $candidate = ContentCalendar::where('business_id', $businessId)
                    ->where('auto_hashtag', $tag)
                    ->whereNotIn('status', ['published'])
                    ->first();
                if ($candidate) {
                    return $candidate;
                }
            }
        }

        // Fallback — agar auto_hashtag jadvalda yozilmagan eski item bo'lsa,
        // shortcode (oxirgi 4 hex) bo'yicha ID prefix match.
        if (preg_match_all('/#[a-zA-Z0-9_]+_([a-f0-9]{4})\b/iu', $text, $m)) {
            foreach ($m[1] as $shortcode) {
                $shortcode = strtolower($shortcode);
                $candidate = ContentCalendar::where('business_id', $businessId)
                    ->whereRaw('REPLACE(LOWER(id), "-", "") LIKE ?', [$shortcode . '%'])
                    ->whereNotIn('status', ['published'])
                    ->first();
                if ($candidate) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Watermark orqali — invisible Unicode'da kodlangan plan shortcode.
     */
    protected function matchByWatermark(string $businessId, string $text): ?ContentCalendar
    {
        $shortcode = $this->watermarker->extract($text);
        if ($shortcode === null || $shortcode === '') {
            return null;
        }

        return ContentCalendar::where('business_id', $businessId)
            ->whereRaw('REPLACE(LOWER(id), "-", "") LIKE ?', [$shortcode . '%'])
            ->whereNotIn('status', ['published'])
            ->first();
    }

    /**
     * Fuzzy fallback — score-based.
     *
     * Mezonlar (yig'indi 1.0):
     *   - Vaqt yaqinligi: 0.45  (TIME_WINDOW_DAYS ichida exponential decay)
     *   - Matn o'xshashligi: 0.35  (Jaccard token similarity)
     *   - Hashtag overlap: 0.15  (rejada qo'lda yozilgan hashtag'lar)
     *   - Media turi: 0.05  (text/photo/video mosligi)
     */
    protected function matchByFuzzy(
        string $businessId,
        Carbon $postedAt,
        string $text,
        string $contentType,
        string $platform,
    ): ?array {
        $candidates = ContentCalendar::where('business_id', $businessId)
            ->where('platform', $platform)
            ->whereBetween('scheduled_date', [
                $postedAt->copy()->subDays(self::TIME_WINDOW_DAYS)->toDateString(),
                $postedAt->copy()->addDays(self::TIME_WINDOW_DAYS)->toDateString(),
            ])
            ->whereNotIn('status', ['published'])
            ->limit(50)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $best = ['item' => null, 'score' => 0.0];

        foreach ($candidates as $item) {
            $score = $this->computeFuzzyScore($item, $postedAt, $text, $contentType);
            if ($score > $best['score']) {
                $best = ['item' => $item, 'score' => $score];
            }
        }

        return $best['item'] ? $best : null;
    }

    protected function computeFuzzyScore(
        ContentCalendar $item,
        Carbon $postedAt,
        string $postText,
        string $postContentType,
    ): float {
        // 1. Vaqt yaqinligi (45%)
        $scheduledTime = $item->scheduled_time ?: '12:00:00';
        $scheduledAt = Carbon::parse($item->scheduled_date->toDateString() . ' ' . $scheduledTime);
        $hoursDiff = abs($postedAt->diffInHours($scheduledAt, false));
        $maxHours = self::TIME_WINDOW_DAYS * 24;
        $timeScore = max(0.0, 1.0 - ($hoursDiff / $maxHours));

        // 2. Matn o'xshashligi (35%) — Jaccard
        $textScore = $this->jaccardSimilarity(
            (string) $item->title . ' ' . (string) ($item->content_text ?? ''),
            $postText,
        );

        // 3. Hashtag overlap (15%) — rejada qo'lda yozilgan tag'lar
        $hashtagScore = 0.0;
        $planTags = is_array($item->hashtags) ? $item->hashtags : [];
        if (! empty($planTags)) {
            preg_match_all('/#(\w+)/u', $postText, $m);
            $postTags = array_map('strtolower', $m[1] ?? []);
            $planTags = array_map(fn ($t) => strtolower(ltrim((string) $t, '#')), $planTags);
            if (! empty($planTags)) {
                $intersect = count(array_intersect($planTags, $postTags));
                $hashtagScore = $intersect / count($planTags);
            }
        }

        // 4. Media turi mosligi (5%)
        $typeScore = ($item->content_type === $postContentType) ? 1.0 : 0.0;

        return ($timeScore * 0.45) + ($textScore * 0.35) + ($hashtagScore * 0.15) + ($typeScore * 0.05);
    }

    /**
     * Jaccard similarity — tokenlar to'plami orasidagi o'xshashlik.
     */
    protected function jaccardSimilarity(string $a, string $b): float
    {
        $aTokens = $this->tokenize($a);
        $bTokens = $this->tokenize($b);
        if (empty($aTokens) && empty($bTokens)) {
            return 0.0;
        }
        $intersect = count(array_intersect($aTokens, $bTokens));
        $union = count(array_unique(array_merge($aTokens, $bTokens)));
        return $union > 0 ? $intersect / $union : 0.0;
    }

    protected function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s#]+/u', ' ', $text) ?? '';
        $tokens = preg_split('/\s+/u', $text) ?: [];
        return array_values(array_filter($tokens, fn ($t) => mb_strlen($t) >= 3));
    }

    /**
     * Match topilganda ContentCalendar va ContentPostLink yangilanadi.
     */
    protected function finalize(
        ContentCalendar $item,
        TelegramChannelPost $post,
        string $method,
        float $score,
    ): array {
        $channel = $post->channel;
        $postUrl = $this->buildTelegramPostUrl($channel, $post->message_id);

        $item->update([
            'status' => 'published',
            'published_at' => $post->posted_at,
            'external_post_id' => (string) $post->message_id,
            'post_url' => $postUrl,
            'match_method' => $method,
            'match_score' => $score,
            'matched_post_id' => (string) $post->id,
            'matched_post_text' => Str::limit((string) $post->text_preview, 1000, ''),
            'matched_at' => now(),
            // Stats avval 0 — keyin SyncContentPostLinksJob orqali to'ldiriladi
            'views' => $post->views ?? 0,
        ]);

        // ContentPostLink yaratamiz (yoki yangilaymiz)
        $this->ensurePostLink($item, $post, $postUrl);

        Log::info('ContentMatcher: matched', [
            'content_id' => $item->id,
            'method' => $method,
            'score' => $score,
            'message_id' => $post->message_id,
        ]);

        return $this->result(true, (string) $item->id, $method, $score);
    }

    /**
     * Instagram media uchun match natijasini saqlash.
     */
    protected function finalizeInstagram(
        ContentCalendar $item,
        InstagramMedia $media,
        string $method,
        float $score,
    ): array {
        $postUrl = (string) ($media->permalink ?? '');

        $item->update([
            'status' => 'published',
            'published_at' => $media->posted_at ?? now(),
            'external_post_id' => (string) $media->media_id,
            'post_url' => $postUrl,
            'match_method' => $method,
            'match_score' => $score,
            'matched_post_id' => (string) $media->id,
            'matched_post_text' => Str::limit((string) $media->caption, 1000, ''),
            'matched_at' => now(),
            'views' => $media->reach ?? 0,
            'likes' => $media->like_count ?? 0,
            'comments' => $media->comments_count ?? 0,
            'reach' => $media->reach ?? 0,
            'saves' => $media->saved ?? 0,
        ]);

        try {
            ContentPostLink::updateOrCreate(
                [
                    'business_id' => $item->business_id,
                    'platform' => 'instagram',
                    'external_id' => (string) $media->media_id,
                ],
                [
                    'external_url' => $postUrl,
                    'views' => $media->reach ?? 0,
                    'likes' => $media->like_count ?? 0,
                    'comments' => $media->comments_count ?? 0,
                    'reach' => $media->reach ?? 0,
                    'saves' => $media->saved ?? 0,
                    'sync_status' => 'pending',
                ],
            );
        } catch (\Throwable $e) {
            Log::debug('ContentPostLink (Instagram) not created', ['error' => $e->getMessage()]);
        }

        Log::info('ContentMatcher: matched Instagram media', [
            'content_id' => $item->id,
            'method' => $method,
            'score' => $score,
            'media_id' => $media->media_id,
        ]);

        return $this->result(true, (string) $item->id, $method, $score);
    }

    /**
     * YouTube video uchun match natijasini saqlash.
     */
    protected function finalizeYoutube(
        ContentCalendar $item,
        YoutubeVideo $video,
        string $method,
        float $score,
    ): array {
        $postUrl = "https://www.youtube.com/watch?v={$video->video_id}";

        $item->update([
            'status' => 'published',
            'published_at' => $video->published_at ?? now(),
            'external_post_id' => (string) $video->video_id,
            'post_url' => $postUrl,
            'match_method' => $method,
            'match_score' => $score,
            'matched_post_id' => (string) $video->id,
            'matched_post_text' => Str::limit((string) ($video->title . ' ' . $video->description), 1000, ''),
            'matched_at' => now(),
            'views' => $video->view_count ?? 0,
            'likes' => $video->like_count ?? 0,
            'comments' => $video->comment_count ?? 0,
        ]);

        try {
            ContentPostLink::updateOrCreate(
                [
                    'business_id' => $item->business_id,
                    'platform' => 'youtube',
                    'external_id' => (string) $video->video_id,
                ],
                [
                    'external_url' => $postUrl,
                    'views' => $video->view_count ?? 0,
                    'likes' => $video->like_count ?? 0,
                    'comments' => $video->comment_count ?? 0,
                    'sync_status' => 'pending',
                ],
            );
        } catch (\Throwable $e) {
            Log::debug('ContentPostLink (YouTube) not created', ['error' => $e->getMessage()]);
        }

        Log::info('ContentMatcher: matched YouTube video', [
            'content_id' => $item->id,
            'method' => $method,
            'score' => $score,
            'video_id' => $video->video_id,
        ]);

        return $this->result(true, (string) $item->id, $method, $score);
    }

    protected function ensurePostLink(ContentCalendar $item, TelegramChannelPost $post, ?string $postUrl): void
    {
        // Schema'da `content_post_id` ContentPost (alohida model) ga reference,
        // shuning uchun ContentCalendar uchun alohida link yaratish ehtiyot
        // bilan: agar `content_post_id` nullable bo'lmasa, skip qilamiz.
        try {
            ContentPostLink::updateOrCreate(
                [
                    'business_id' => $item->business_id,
                    'platform' => 'telegram',
                    'external_id' => (string) $post->message_id,
                ],
                [
                    'external_url' => $postUrl,
                    'views' => $post->views ?? 0,
                    'sync_status' => 'pending',
                ],
            );
        } catch (\Throwable $e) {
            // ContentPostLink schema ContentPost'ga FK bo'lsa — log qilib o'tib ketamiz.
            Log::debug('ContentPostLink not created (schema mismatch)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function buildTelegramPostUrl(?TelegramChannel $channel, int $messageId): ?string
    {
        if (! $channel) {
            return null;
        }
        if (! empty($channel->chat_username)) {
            $username = ltrim((string) $channel->chat_username, '@');
            return "https://t.me/{$username}/{$messageId}";
        }
        // Private channel: chat_id -100... → t.me/c/{id without -100}/...
        $chatId = (string) $channel->telegram_chat_id;
        if (str_starts_with($chatId, '-100')) {
            $internal = substr($chatId, 4);
            return "https://t.me/c/{$internal}/{$messageId}";
        }
        return null;
    }

    protected function result(bool $matched, ?string $contentId = null, ?string $method = null, ?float $score = null): array
    {
        return [
            'matched' => $matched,
            'content_id' => $contentId,
            'method' => $method,
            'score' => $score,
        ];
    }
}
