<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private const AUTH_URL = 'https://oauth.yandex.ru/authorize';
    private const TOKEN_URL = 'https://oauth.yandex.ru/token';
    private const USERINFO_URL = 'https://login.yandex.ru/info';

    /**
     * Yandex OAuth scope'lari. Ushbu scope'lar Yandex OAuth ilova
     * settings'da ham yoqilgan bo'lishi shart (oauth.yandex.ru'da
     * ilovani yaratganda Permissions bo'limidan tanlanadi).
     */
    private const SCOPES = [
        'login:email',     // Foydalanuvchi email
        'login:info',      // Foydalanuvchi ismi va id
        'metrika:read',    // Yandex Metrika — counterlar va analytics
        'direct:api',      // Yandex Direct — reklama kampaniyalari (alohida API access kerak)
    ];

    public function __construct()
    {
        $this->clientId = config('services.yandex.client_id', '');
        $this->clientSecret = config('services.yandex.client_secret', '');
        $this->redirectUri = config('services.yandex.redirect');
    }

    /**
     * OAuth authorization URL yaratish
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => implode(' ', self::SCOPES),
            'force_confirm' => 'yes',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Authorization code ni token ga almashtirish
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('Yandex token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Yandex token olishda xatolik: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh token orqali yangi access_token olish
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            Log::error('Yandex token refresh failed', ['body' => $response->body()]);
            throw new \Exception('Yandex token yangilashda xatolik');
        }

        return $response->json();
    }

    /**
     * Foydalanuvchi ma'lumotlarini olish
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth ' . $accessToken,
        ])->get(self::USERINFO_URL, ['format' => 'json']);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Yandex Metrika counterlari ro'yxati
     */
    public function getMetrikaCounters(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth ' . $accessToken,
        ])->get('https://api-metrika.yandex.net/management/v1/counters');

        return $response->successful() ? ($response->json()['counters'] ?? []) : [];
    }

    /**
     * Yandex Direct kampaniyalari
     */
    public function getDirectCampaigns(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept-Language' => 'ru',
        ])->post('https://api.direct.yandex.com/json/v5/campaigns', [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => new \stdClass(),
                'FieldNames' => ['Id', 'Name', 'Status', 'State', 'Statistics'],
            ],
        ]);

        return $response->successful() ? ($response->json()['result']['Campaigns'] ?? []) : [];
    }
}
