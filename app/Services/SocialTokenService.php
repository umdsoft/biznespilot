<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Integration;
use App\Models\InstagramAccount;
use App\Notifications\TokenExpiredNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * SocialTokenService - Token Salomatligi va Yangilash Xizmati
 *
 * Bu servis Facebook/Instagram tokenlarni avtomatik monitoring qiladi:
 * - Token validligini tekshirish (/debug_token)
 * - Token amal muddatini kuzatish
 * - Eskirayotgan tokenlarni yangilash (/oauth/access_token)
 * - Admin xabarnomalarini yuborish
 *
 * SELF-HEALING: Token 7 kundan kam vaqt qolsa, avtomatik yangilanadi.
 *
 * @see https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived
 */
class SocialTokenService
{
    protected const GRAPH_API_URL = 'https://graph.facebook.com/v18.0';

    /**
     * Tokenni yangilash uchun qolgan minimal kunlar
     */
    protected const REFRESH_THRESHOLD_DAYS = 7;

    /**
     * API so'rovlari orasidagi kutish vaqti (rate limit uchun)
     */
    protected const API_DELAY_SECONDS = 2;

    /**
     * Check token health for an integration
     *
     * @param Integration $integration
     * @return array{
     *   is_valid: bool,
     *   expires_at: ?\Carbon\Carbon,
     *   days_remaining: ?int,
     *   needs_refresh: bool,
     *   error: ?string,
     *   scopes: array
     * }
     */
    public function checkTokenHealth(Integration $integration): array
    {
        $accessToken = $integration->getAccessToken();

        if (! $accessToken) {
            Log::warning('SocialTokenService: No access token found', [
                'integration_id' => $integration->id,
            ]);

            return [
                'is_valid' => false,
                'expires_at' => null,
                'days_remaining' => null,
                'needs_refresh' => false,
                'error' => 'Access token topilmadi',
                'scopes' => [],
            ];
        }

        try {
            // Facebook debug_token endpoint
            $debugResult = $this->debugToken($accessToken);

            if (! $debugResult['is_valid']) {
                // Token eskirgan - statusni yangilash
                $this->markTokenExpired($integration, $debugResult['error'] ?? 'Token expired');

                return $debugResult;
            }

            // Token hali ham valid - amal muddatini tekshirish
            $daysRemaining = $debugResult['days_remaining'];

            if ($daysRemaining !== null && $daysRemaining <= self::REFRESH_THRESHOLD_DAYS) {
                $debugResult['needs_refresh'] = true;

                Log::info('SocialTokenService: Token needs refresh', [
                    'integration_id' => $integration->id,
                    'days_remaining' => $daysRemaining,
                ]);
            }

            return $debugResult;

        } catch (\Exception $e) {
            Log::error('SocialTokenService: Token health check failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'is_valid' => false,
                'expires_at' => null,
                'days_remaining' => null,
                'needs_refresh' => false,
                'error' => $e->getMessage(),
                'scopes' => [],
            ];
        }
    }

    /**
     * Debug token using Facebook API
     *
     * @param string $inputToken Token to debug
     * @return array
     */
    protected function debugToken(string $inputToken): array
    {
        $appId = config('services.facebook.client_id');
        $appSecret = config('services.facebook.client_secret');

        if (! $appId || ! $appSecret) {
            throw new \RuntimeException('Facebook App credentials not configured');
        }

        // App Access Token yaratish
        $appAccessToken = "{$appId}|{$appSecret}";

        $response = Http::get(self::GRAPH_API_URL . '/debug_token', [
            'input_token' => $inputToken,
            'access_token' => $appAccessToken,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Debug token API error';

            return [
                'is_valid' => false,
                'expires_at' => null,
                'days_remaining' => null,
                'needs_refresh' => false,
                'error' => $error,
                'scopes' => [],
            ];
        }

        $data = $response->json('data');

        if (! $data) {
            return [
                'is_valid' => false,
                'expires_at' => null,
                'days_remaining' => null,
                'needs_refresh' => false,
                'error' => 'Invalid debug_token response',
                'scopes' => [],
            ];
        }

        $isValid = $data['is_valid'] ?? false;
        $expiresAt = isset($data['expires_at']) && $data['expires_at'] > 0
            ? \Carbon\Carbon::createFromTimestamp($data['expires_at'])
            : null;

        $daysRemaining = $expiresAt
            ? (int) now()->diffInDays($expiresAt, false)
            : null;

        // Agar expires_at 0 bo'lsa, token hech qachon tugamasligi mumkin (masalan, page token)
        if (isset($data['expires_at']) && $data['expires_at'] === 0) {
            $daysRemaining = 999; // Never expires
        }

        $error = null;
        if (! $isValid) {
            $errorData = $data['error'] ?? [];
            $error = $errorData['message'] ?? 'Token is not valid';
        }

        return [
            'is_valid' => $isValid,
            'expires_at' => $expiresAt,
            'days_remaining' => $daysRemaining,
            'needs_refresh' => false,
            'error' => $error,
            'scopes' => $data['scopes'] ?? [],
            'app_id' => $data['app_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'type' => $data['type'] ?? null,
        ];
    }

    /**
     * Refresh token for an integration
     *
     * Eskirayotgan tokenni yangi long-lived tokenga almashtiradi.
     *
     * @param Integration $integration
     * @return array{success: bool, new_expires_at: ?\Carbon\Carbon, error: ?string}
     */
    public function refreshToken(Integration $integration): array
    {
        $accessToken = $integration->getAccessToken();

        if (! $accessToken) {
            return [
                'success' => false,
                'new_expires_at' => null,
                'error' => 'Access token topilmadi',
            ];
        }

        $appId = config('services.facebook.client_id');
        $appSecret = config('services.facebook.client_secret');

        if (! $appId || ! $appSecret) {
            return [
                'success' => false,
                'new_expires_at' => null,
                'error' => 'Facebook App credentials not configured',
            ];
        }

        try {
            // Exchange for long-lived token
            $response = Http::get(self::GRAPH_API_URL . '/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $accessToken,
            ]);

            if (! $response->successful()) {
                $error = $response->json('error.message') ?? 'Token exchange failed';

                Log::error('SocialTokenService: Token refresh failed', [
                    'integration_id' => $integration->id,
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'new_expires_at' => null,
                    'error' => $error,
                ];
            }

            $data = $response->json();
            $newToken = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 5184000; // Default 60 days

            if (! $newToken) {
                return [
                    'success' => false,
                    'new_expires_at' => null,
                    'error' => 'New token not returned',
                ];
            }

            $newExpiresAt = now()->addSeconds($expiresIn);

            // Bazaga yangi tokenni saqlash
            $this->saveNewToken($integration, $newToken, $newExpiresAt);

            Log::info('SocialTokenService: Token refreshed successfully', [
                'integration_id' => $integration->id,
                'business_id' => $integration->business_id,
                'new_expires_at' => $newExpiresAt->toDateTimeString(),
                'days_valid' => round($expiresIn / 86400),
            ]);

            return [
                'success' => true,
                'new_expires_at' => $newExpiresAt,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('SocialTokenService: Token refresh exception', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'new_expires_at' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save new token to database
     */
    protected function saveNewToken(Integration $integration, string $newToken, \Carbon\Carbon $expiresAt): void
    {
        // Credentials ni yangilash
        $credentials = json_decode($integration->credentials ?? '{}', true);
        $credentials['access_token'] = $newToken;
        $credentials['refreshed_at'] = now()->toISOString();

        $integration->update([
            'credentials' => json_encode($credentials),
            'expires_at' => $expiresAt,
            'status' => 'connected',
            'last_error_message' => null,
        ]);

        // Instagram accountlarni ham yangilash
        InstagramAccount::where('integration_id', $integration->id)
            ->update(['access_token' => $newToken]);
    }

    /**
     * Mark token as expired
     */
    protected function markTokenExpired(Integration $integration, string $reason): void
    {
        $integration->update([
            'status' => 'expired',
            'last_error_at' => now(),
            'last_error_message' => "Token expired: {$reason}",
        ]);

        Log::warning('SocialTokenService: Token marked as expired', [
            'integration_id' => $integration->id,
            'business_id' => $integration->business_id,
            'reason' => $reason,
        ]);
    }

    /**
     * Send token expired notification to admin
     */
    public function notifyAdmin(Integration $integration, string $reason): void
    {
        $adminChatId = config('services.telegram.admin_chat_id');

        if (! $adminChatId) {
            Log::warning('SocialTokenService: Admin chat ID not configured for Telegram notification');

            return;
        }

        try {
            Notification::route('telegram', $adminChatId)
                ->notify(new TokenExpiredNotification($integration, $reason));

            Log::info('SocialTokenService: Admin notified about token issue', [
                'integration_id' => $integration->id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('SocialTokenService: Failed to send admin notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check and refresh token if needed
     *
     * @param Integration $integration
     * @return array{
     *   status: string,
     *   action_taken: ?string,
     *   error: ?string
     * }
     */
    public function checkAndRefreshIfNeeded(Integration $integration): array
    {
        // Rate limit uchun kutish
        sleep(self::API_DELAY_SECONDS);

        // 1. Token salomatligini tekshirish
        $health = $this->checkTokenHealth($integration);

        if (! $health['is_valid']) {
            // Token butunlay eskirgan - admin xabar berish
            $this->notifyAdmin($integration, $health['error'] ?? 'Token invalid');

            return [
                'status' => 'expired',
                'action_taken' => 'admin_notified',
                'error' => $health['error'],
            ];
        }

        // 2. Yangilash kerak bo'lsa
        if ($health['needs_refresh']) {
            $refreshResult = $this->refreshToken($integration);

            if ($refreshResult['success']) {
                return [
                    'status' => 'refreshed',
                    'action_taken' => 'token_refreshed',
                    'error' => null,
                    'new_expires_at' => $refreshResult['new_expires_at']?->toDateTimeString(),
                ];
            }

            // Yangilash muvaffaqiyatsiz - admin xabar berish
            $this->notifyAdmin($integration, $refreshResult['error'] ?? 'Refresh failed');

            return [
                'status' => 'refresh_failed',
                'action_taken' => 'admin_notified',
                'error' => $refreshResult['error'],
            ];
        }

        // 3. Token yaxshi holatda
        return [
            'status' => 'healthy',
            'action_taken' => null,
            'error' => null,
            'days_remaining' => $health['days_remaining'],
        ];
    }

    /**
     * Get all Meta/Facebook integrations
     */
    public function getMetaIntegrations(): \Illuminate\Database\Eloquent\Collection
    {
        return Integration::where('type', 'meta_ads')
            ->whereIn('status', ['connected', 'expired', 'error'])
            ->where('is_active', true)
            ->get();
    }
}
