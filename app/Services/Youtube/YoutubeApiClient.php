<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Models\YoutubeChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YouTube Data API v3 client.
 *
 * Asosiy operatsiyalar:
 *   - listUploads(channel)             — kanaldagi yangi videolarni olish
 *   - fetchVideosBatch(videoIds)       — bir nechta video uchun statistikalar
 *   - refreshAccessToken(channel)      — OAuth refresh_token orqali yangilash
 *
 * Quota: 10,000 units/day default.
 *  - playlistItems.list = 1 unit
 *  - videos.list (50 ID) = 1 unit
 *  - videos.insert = 1,600 units (ko'p — direct upload uchun)
 */
class YoutubeApiClient
{
    private const BASE_URL = 'https://www.googleapis.com/youtube/v3';

    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Kanaldagi yangi videolarni uploads playlist orqali olish.
     *
     * @return array<int, array> raw playlistItems API javob `items` qismi
     */
    public function listUploads(YoutubeChannel $channel, int $maxResults = 50): array
    {
        if (empty($channel->uploads_playlist_id)) {
            return [];
        }

        $token = $this->ensureToken($channel);
        if (! $token) {
            return [];
        }

        $response = Http::withToken($token)
            ->timeout(30)
            ->get(self::BASE_URL . '/playlistItems', [
                'playlistId' => $channel->uploads_playlist_id,
                'part' => 'snippet,contentDetails',
                'maxResults' => min($maxResults, 50),
            ]);

        if (! $response->successful()) {
            Log::warning('YoutubeApiClient: listUploads failed', [
                'channel_id' => $channel->channel_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return [];
        }

        return $response->json('items', []) ?? [];
    }

    /**
     * Bir nechta video uchun statistikalarni bitta so'rovda olish (eng tejamli).
     *
     * @param  string[] $videoIds  YouTube video ID'lari
     * @return array<string, array>  video_id => statistics
     */
    public function fetchVideosBatch(YoutubeChannel $channel, array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $token = $this->ensureToken($channel);
        if (! $token) {
            return [];
        }

        $chunks = array_chunk(array_unique($videoIds), 50);
        $stats = [];

        foreach ($chunks as $chunk) {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get(self::BASE_URL . '/videos', [
                    'id' => implode(',', $chunk),
                    'part' => 'statistics,status,contentDetails',
                ]);

            if (! $response->successful()) {
                Log::warning('YoutubeApiClient: fetchVideosBatch failed', [
                    'status' => $response->status(),
                    'count' => count($chunk),
                ]);
                continue;
            }

            foreach ($response->json('items', []) ?? [] as $item) {
                $videoId = (string) ($item['id'] ?? '');
                if ($videoId !== '') {
                    $stats[$videoId] = $item;
                }
            }
        }

        return $stats;
    }

    /**
     * OAuth access_token muddati tugagan bo'lsa refresh_token bilan yangilash.
     */
    public function refreshAccessToken(YoutubeChannel $channel): ?string
    {
        if (empty($channel->refresh_token)) {
            return null;
        }

        $clientId = config('services.youtube.client_id');
        $clientSecret = config('services.youtube.client_secret');
        if (! $clientId || ! $clientSecret) {
            Log::error('YoutubeApiClient: OAuth credentials not configured');
            return null;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post(self::OAUTH_TOKEN_URL, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $channel->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            Log::warning('YoutubeApiClient: refresh failed', [
                'channel_id' => $channel->channel_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return null;
        }

        $data = $response->json();
        $token = (string) ($data['access_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if ($token !== '') {
            $channel->update([
                'access_token' => $token,
                'token_expires_at' => now()->addSeconds($expiresIn - 60), // 60s safety
            ]);
        }

        return $token ?: null;
    }

    /**
     * Token mavjud va aktiv bo'lishini ta'minlaydi.
     */
    protected function ensureToken(YoutubeChannel $channel): ?string
    {
        if (empty($channel->access_token)) {
            return $this->refreshAccessToken($channel);
        }
        if ($channel->isTokenExpired()) {
            return $this->refreshAccessToken($channel);
        }
        return $channel->access_token;
    }
}
