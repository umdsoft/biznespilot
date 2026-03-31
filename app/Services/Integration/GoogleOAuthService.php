<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    // Bitta OAuth bilan 3 xizmat
    private const SCOPES = [
        'https://www.googleapis.com/auth/analytics.readonly',     // GA4
        'https://www.googleapis.com/auth/adwords',                // Google Ads
        'https://www.googleapis.com/auth/youtube.readonly',       // YouTube
        'https://www.googleapis.com/auth/userinfo.email',         // Email
    ];

    public function __construct()
    {
        $this->clientId = config('services.google.client_id', '');
        $this->clientSecret = config('services.google.client_secret', '');
        $this->redirectUri = config('services.google.redirect');
    }

    /**
     * OAuth authorization URL yaratish
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'state' => $state,
            'access_type' => 'offline',       // refresh_token olish uchun
            'prompt' => 'consent',             // har doim consent so'rash (refresh_token garantiyasi)
            'include_granted_scopes' => 'true',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Authorization code ni token ga almashtirish
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('Google token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Google token olishda xatolik: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh token orqali yangi access_token olish
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('Google token refresh failed', ['body' => $response->body()]);
            throw new \Exception('Google token yangilashda xatolik');
        }

        return $response->json();
    }

    /**
     * Foydalanuvchi ma'lumotlarini olish
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get(self::USERINFO_URL);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * GA4 propertylarini olish
     */
    public function getAnalyticsProperties(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://analyticsadmin.googleapis.com/v1beta/accountSummaries');

        return $response->successful() ? $response->json() : [];
    }

    /**
     * YouTube kanal ma'lumotlarini olish
     */
    public function getYouTubeChannel(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet,statistics,contentDetails',
                'mine' => 'true',
            ]);

        return $response->successful() ? ($response->json()['items'][0] ?? []) : [];
    }

    /**
     * Google Ads customer ID larni olish
     */
    public function getAdsCustomers(string $accessToken, ?string $developerToken = null): array
    {
        $devToken = $developerToken ?: config('services.google.developer_token');
        if (!$devToken) return [];

        $response = Http::withToken($accessToken)
            ->withHeaders(['developer-token' => $devToken])
            ->get('https://googleads.googleapis.com/v17/customers:listAccessibleCustomers');

        return $response->successful() ? $response->json() : [];
    }
}
