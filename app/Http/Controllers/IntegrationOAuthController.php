<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\Integration\GoogleOAuthService;
use App\Services\Integration\YandexOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationOAuthController extends Controller
{
    // ==================== GOOGLE ====================

    public function googleAuthUrl(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        $state = Str::random(40);
        session([
            'google_oauth_state' => $state,
            'google_oauth_business_id' => $business->id,
        ]);

        $service = new GoogleOAuthService();
        $url = $service->getAuthorizationUrl($state);

        return response()->json(['url' => $url]);
    }

    public function googleCallback(Request $request)
    {
        $state = $request->state;
        $sessionState = session('google_oauth_state');

        // CSRF tekshirish — ikkisi ham bo'sh bo'lmasligi kerak
        if (!$state || !$sessionState || $state !== $sessionState) {
            return redirect('/integrations')->with('error', 'Noto\'g\'ri so\'rov. Qaytadan urinib ko\'ring.');
        }

        if ($request->has('error')) {
            session()->forget(['google_oauth_state', 'google_oauth_business_id']);
            return redirect('/integrations')->with('error', 'Google ulanish bekor qilindi');
        }

        $businessId = session('google_oauth_business_id');
        session()->forget(['google_oauth_state', 'google_oauth_business_id']);

        if (!$businessId || !$request->code) {
            return redirect('/integrations')->with('error', 'Xatolik yuz berdi');
        }

        try {
            $service = new GoogleOAuthService();
            $tokenData = $service->exchangeCodeForToken($request->code);

            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? 3600;

            // Foydalanuvchi ma'lumotlari
            $userInfo = $service->getUserInfo($accessToken);

            // YouTube kanal
            $youtubeChannel = $service->getYouTubeChannel($accessToken);

            // GA4 properties
            $gaProperties = $service->getAnalyticsProperties($accessToken);

            // Mavjud integratsiyani yangilash yoki yangi yaratish
            Integration::updateOrCreate(
                ['business_id' => $businessId, 'type' => 'google'],
                [
                    'name' => 'Google (' . ($userInfo['email'] ?? 'connected') . ')',
                    'is_active' => true,
                    'status' => 'connected',
                    'connected_at' => now(),
                    'expires_at' => now()->addSeconds($expiresIn),
                    'credentials' => encrypt(json_encode([
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'expires_in' => $expiresIn,
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'scope' => $tokenData['scope'] ?? '',
                    ])),
                    'config' => [
                        'email' => $userInfo['email'] ?? null,
                        'name' => $userInfo['name'] ?? null,
                        'youtube_channel_id' => $youtubeChannel['id'] ?? null,
                        'youtube_channel_title' => $youtubeChannel['snippet']['title'] ?? null,
                        'youtube_subscribers' => $youtubeChannel['statistics']['subscriberCount'] ?? null,
                        'ga_properties' => $gaProperties['accountSummaries'] ?? [],
                    ],
                    'last_sync_at' => now(),
                    'last_error_at' => null,
                    'last_error_message' => null,
                ]
            );

            // Integrations cache tozalash
            \App\Http\Middleware\HandleInertiaRequests::clearIntegrationCache($businessId);

            return redirect('/integrations')->with('success', 'Google muvaffaqiyatli ulandi!');

        } catch (\Exception $e) {
            \Log::error('Google OAuth callback error', ['error' => $e->getMessage()]);
            return redirect('/integrations')->with('error', 'Google ulanishda xatolik yuz berdi. Qaytadan urinib ko\'ring.');
        }
    }

    public function googleDisconnect(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        Integration::where('business_id', $business->id)
            ->where('type', 'google')
            ->update([
                'is_active' => false,
                'status' => 'disconnected',
                'credentials' => null,
            ]);

        \App\Http\Middleware\HandleInertiaRequests::clearIntegrationCache($business->id);

        return redirect()->back()->with('success', 'Google uzildi');
    }

    // ==================== YANDEX ====================

    public function yandexAuthUrl(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        $state = Str::random(40);
        session([
            'yandex_oauth_state' => $state,
            'yandex_oauth_business_id' => $business->id,
        ]);

        $service = new YandexOAuthService();
        $url = $service->getAuthorizationUrl($state);

        return response()->json(['url' => $url]);
    }

    public function yandexCallback(Request $request)
    {
        $state = $request->state;
        $sessionState = session('yandex_oauth_state');

        if (!$state || !$sessionState || $state !== $sessionState) {
            return redirect('/integrations')->with('error', 'Noto\'g\'ri so\'rov');
        }

        if ($request->has('error')) {
            session()->forget(['yandex_oauth_state', 'yandex_oauth_business_id']);
            return redirect('/integrations')->with('error', 'Yandex ulanish bekor qilindi');
        }

        $businessId = session('yandex_oauth_business_id');
        session()->forget(['yandex_oauth_state', 'yandex_oauth_business_id']);

        if (!$businessId || !$request->code) {
            return redirect('/integrations')->with('error', 'Xatolik yuz berdi');
        }

        try {
            $service = new YandexOAuthService();
            $tokenData = $service->exchangeCodeForToken($request->code);

            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? 31536000; // Yandex: ~1 yil

            // Foydalanuvchi
            $userInfo = $service->getUserInfo($accessToken);

            // Metrika counterlari
            $counters = $service->getMetrikaCounters($accessToken);

            Integration::updateOrCreate(
                ['business_id' => $businessId, 'type' => 'yandex'],
                [
                    'name' => 'Yandex (' . ($userInfo['login'] ?? 'connected') . ')',
                    'is_active' => true,
                    'status' => 'connected',
                    'connected_at' => now(),
                    'expires_at' => now()->addSeconds($expiresIn),
                    'credentials' => encrypt(json_encode([
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'expires_in' => $expiresIn,
                    ])),
                    'config' => [
                        'login' => $userInfo['login'] ?? null,
                        'email' => $userInfo['default_email'] ?? null,
                        'name' => ($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''),
                        'metrika_counters' => array_map(fn($c) => [
                            'id' => $c['id'],
                            'name' => $c['name'],
                            'site' => $c['site'] ?? null,
                        ], $counters),
                    ],
                    'last_sync_at' => now(),
                    'last_error_at' => null,
                    'last_error_message' => null,
                ]
            );

            \App\Http\Middleware\HandleInertiaRequests::clearIntegrationCache($businessId);

            return redirect('/integrations')->with('success', 'Yandex muvaffaqiyatli ulandi!');

        } catch (\Exception $e) {
            \Log::error('Yandex OAuth callback error', ['error' => $e->getMessage()]);
            return redirect('/integrations')->with('error', 'Yandex ulanishda xatolik yuz berdi. Qaytadan urinib ko\'ring.');
        }
    }

    public function yandexDisconnect(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        Integration::where('business_id', $business->id)
            ->where('type', 'yandex')
            ->update([
                'is_active' => false,
                'status' => 'disconnected',
                'credentials' => null,
            ]);

        \App\Http\Middleware\HandleInertiaRequests::clearIntegrationCache($business->id);

        return redirect()->back()->with('success', 'Yandex uzildi');
    }

    // ==================== Helper ====================

    private function getCurrentBusiness()
    {
        $businessId = session('current_business_id');
        if (!$businessId) return null;
        return \App\Models\Business::find($businessId);
    }
}
