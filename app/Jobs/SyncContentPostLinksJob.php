<?php

namespace App\Jobs;

use App\Models\ContentPostLink;
use App\Models\InstagramAccount;
use App\Models\TelegramBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncContentPostLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public int $maxExceptions = 1;

    public function handle(): void
    {
        // Get links that need syncing (synced_at > 6 hours ago or null, not failed)
        $links = ContentPostLink::needsSync()
            ->whereNotNull('external_url')
            ->with('contentPost')
            ->limit(50)
            ->get();

        if ($links->isEmpty()) {
            return;
        }

        $processed = 0;

        foreach ($links as $link) {
            try {
                if (strtolower($link->platform) === 'instagram') {
                    $this->syncInstagram($link);
                } elseif (strtolower($link->platform) === 'telegram') {
                    $this->syncTelegram($link);
                }

                $processed++;
            } catch (\Exception $e) {
                Log::warning("SyncContentPostLinks: {$link->platform} link #{$link->id} xatosi: {$e->getMessage()}");
                $link->markFailed();
            }

            // Rate limiting: 500ms between requests
            usleep(500_000);
        }

        Log::info("SyncContentPostLinks: {$processed}/{$links->count()} ta link sinxronlandi");
    }

    protected function syncInstagram(ContentPostLink $link): void
    {
        $igAccount = InstagramAccount::where('business_id', $link->business_id)
            ->where('is_active', true)
            ->first();

        if (! $igAccount || ! $igAccount->access_token) {
            return;
        }

        $mediaId = $link->external_id;

        if (! $mediaId && $link->external_url) {
            $mediaId = $this->resolveInstagramMediaId($link->external_url, $igAccount->access_token);
            if ($mediaId) {
                $link->update(['external_id' => $mediaId]);
            }
        }

        if (! $mediaId) {
            return;
        }

        // Basic media info
        $response = Http::get("https://graph.facebook.com/v24.0/{$mediaId}", [
            'fields' => 'like_count,comments_count,timestamp',
            'access_token' => $igAccount->access_token,
        ]);

        if (! $response->ok()) {
            return;
        }

        $data = $response->json();
        $likes = $data['like_count'] ?? 0;
        $comments = $data['comments_count'] ?? 0;

        // Try insights for reach/saves/shares
        $reach = 0;
        $saves = 0;
        $shares = 0;

        $insightsResponse = Http::get("https://graph.facebook.com/v24.0/{$mediaId}/insights", [
            'metric' => 'reach,saved,shares',
            'access_token' => $igAccount->access_token,
        ]);

        if ($insightsResponse->ok()) {
            $insightsData = $insightsResponse->json()['data'] ?? [];
            foreach ($insightsData as $metric) {
                $value = $metric['values'][0]['value'] ?? 0;
                match ($metric['name']) {
                    'reach' => $reach = $value,
                    'saved' => $saves = $value,
                    'shares' => $shares = $value,
                    default => null,
                };
            }
        }

        $engagementRate = $reach > 0
            ? round((($likes + $comments + $saves + $shares) / $reach) * 100, 4)
            : 0;

        $link->update([
            'likes' => $likes,
            'comments' => $comments,
            'reach' => $reach,
            'saves' => $saves,
            'shares' => $shares,
            'engagement_rate' => $engagementRate,
        ]);

        $link->markSynced();
    }

    protected function syncTelegram(ContentPostLink $link): void
    {
        // Parse channel and message ID from URL: t.me/channel/123
        if (! preg_match('#t\.me/([^/]+)/(\d+)#', $link->external_url, $matches)) {
            return;
        }

        $channelUsername = $matches[1];
        $messageId = (int) $matches[2];

        $bot = TelegramBot::where('business_id', $link->business_id)
            ->where('is_active', true)
            ->first();

        if (! $bot || ! $bot->token) {
            // Can't access Telegram API without bot token — mark synced to avoid retry
            $link->markSynced();

            return;
        }

        // Telegram Bot API has limited stats — can only get view count for channel posts
        // via getMessages (not available in standard Bot API)
        // For now, just mark as synced
        $link->markSynced();
    }

    protected function resolveInstagramMediaId(string $url, string $accessToken): ?string
    {
        // oEmbed approach
        $response = Http::get('https://graph.facebook.com/v24.0/instagram_oembed', [
            'url' => $url,
            'access_token' => $accessToken,
        ]);

        if ($response->ok() && isset($response->json()['media_id'])) {
            return $response->json()['media_id'];
        }

        // Try URL lookup
        $response = Http::get('https://graph.facebook.com/v24.0/ig_hashtag_search', [
            'url' => $url,
            'access_token' => $accessToken,
        ]);

        return null;
    }
}
