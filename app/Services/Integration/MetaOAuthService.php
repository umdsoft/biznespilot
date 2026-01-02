<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaOAuthService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';
    protected string $appId;
    protected string $appSecret;

    public function __construct()
    {
        $this->appId = config('services.meta.app_id') ?? '';
        $this->appSecret = config('services.meta.app_secret') ?? '';
    }

    /**
     * OAuth URL yaratish
     */
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        // Meta Graph API v18.0 uchun scope lar
        // Reklama + Instagram tahlili uchun barcha kerakli permissions
        $scopes = [
            // Meta Ads permissions
            'ads_read',                    // Reklama ma'lumotlarini o'qish
            'ads_management',              // Reklama boshqaruvi
            'business_management',         // Biznes boshqaruvi

            // Facebook Pages permissions (Instagram uchun zarur)
            'pages_show_list',             // Facebook sahifalarini ko'rish
            'pages_read_engagement',       // Sahifa engagement ma'lumotlari

            // Instagram Business permissions
            'instagram_basic',             // Instagram akkaunt ma'lumotlari
            'instagram_manage_insights',   // Instagram statistika va tahlil
        ];

        $params = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://www.facebook.com/v18.0/dialog/oauth?{$params}";
    }

    /**
     * Authorization code ni access token ga almashtirish
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');
            Log::error('Meta OAuth token exchange failed', ['error' => $error]);
            throw new \Exception('Token exchange failed: ' . $error);
        }

        $data = $response->json();

        // Short-lived token ni long-lived ga almashtirish
        return $this->exchangeForLongLivedToken($data['access_token']);
    }

    /**
     * Long-lived token olish (60 kun)
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            Log::error('Meta long-lived token exchange failed');
            throw new \Exception('Long-lived token exchange failed');
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'token_type' => $data['token_type'] ?? 'bearer',
            'expires_in' => $data['expires_in'] ?? 5184000, // 60 days default
        ];
    }

    /**
     * Token tekshirish
     */
    public function debugToken(string $accessToken): array
    {
        $response = Http::get("{$this->baseUrl}/debug_token", [
            'input_token' => $accessToken,
            'access_token' => "{$this->appId}|{$this->appSecret}",
        ]);

        return $response->json('data', []);
    }

    /**
     * Token yangilash kerakligini tekshirish
     */
    public function shouldRefreshToken(string $accessToken): bool
    {
        $debug = $this->debugToken($accessToken);

        if (empty($debug['expires_at'])) {
            return false; // Non-expiring token
        }

        // 7 kun oldin yangilash
        return $debug['expires_at'] < (time() + 604800);
    }

    /**
     * Token valid ekanligini tekshirish
     */
    public function isTokenValid(string $accessToken): bool
    {
        try {
            $debug = $this->debugToken($accessToken);
            return !empty($debug['is_valid']) && $debug['is_valid'] === true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
