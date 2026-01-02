<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_notifications' => true,
                'browser_notifications' => true,
                'marketing_emails' => false,
                'preferred_ai_model' => 'gpt-4',
                'ai_creativity_level' => 7,
                'theme' => 'light',
                'language' => 'uz',
            ]
        );

        return Inertia::render('Business/Settings/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'settings' => [
                'email_notifications' => $settings->email_notifications,
                'browser_notifications' => $settings->browser_notifications,
                'marketing_emails' => $settings->marketing_emails,
                'preferred_ai_model' => $settings->preferred_ai_model,
                'ai_creativity_level' => $settings->ai_creativity_level,
                'theme' => $settings->theme,
                'language' => $settings->language,
                'has_openai_key' => !empty($settings->openai_api_key),
                'has_claude_key' => !empty($settings->claude_api_key),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profil yangilandi!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Joriy parol noto\'g\'ri.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Parol yangilandi!');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['boolean'],
            'browser_notifications' => ['boolean'],
            'marketing_emails' => ['boolean'],
            'preferred_ai_model' => ['string', 'in:gpt-4,gpt-3.5-turbo,claude-3-opus,claude-3-sonnet'],
            'ai_creativity_level' => ['integer', 'min:1', 'max:10'],
            'theme' => ['string', 'in:light,dark,auto'],
            'language' => ['string', 'in:uz,ru,en'],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);
        $settings->update($validated);

        return redirect()->back()->with('success', 'Sozlamalar yangilandi!');
    }

    public function updateApiKeys(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'claude_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);

        if ($request->filled('openai_api_key')) {
            $settings->setOpenAIKey($validated['openai_api_key']);
        }

        if ($request->filled('claude_api_key')) {
            $settings->setClaudeKey($validated['claude_api_key']);
        }

        $settings->save();

        return redirect()->back()->with('success', 'API kalitlari yangilandi!');
    }

    public function deleteApiKey(Request $request)
    {
        $validated = $request->validate([
            'key_type' => ['required', 'string', 'in:openai,claude'],
        ]);

        $settings = UserSetting::where('user_id', Auth::id())->first();

        if ($settings) {
            if ($validated['key_type'] === 'openai') {
                $settings->openai_api_key = null;
            } elseif ($validated['key_type'] === 'claude') {
                $settings->claude_api_key = null;
            }
            $settings->save();
        }

        return redirect()->back()->with('success', 'API kaliti o\'chirildi!');
    }

    /**
     * WhatsApp Integration Settings
     *
     * @return \Inertia\Response
     */
    public function whatsapp()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznесni tanlang yoki yarating.');
        }

        return Inertia::render('Business/Settings/WhatsApp', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    /**
     * WhatsApp AI Configuration Settings
     *
     * @return \Inertia\Response
     */
    public function whatsappAI()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznесni tanlang yoki yarating.');
        }

        return Inertia::render('Business/Settings/WhatsAppAI', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    /**
     * Instagram AI Configuration Settings
     *
     * @return \Inertia\Response
     */
    public function instagramAI()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznесni tanlang yoki yarating.');
        }

        return Inertia::render('Business/Settings/InstagramAI', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    /**
     * Google Ads Integration Settings
     */
    public function googleAds()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznesni tanlang yoki yarating.');
        }

        // Get integration status
        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'google_ads')
            ->first();

        return Inertia::render('Business/Settings/GoogleAds', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
            'integration' => $integration ? [
                'id' => $integration->id,
                'is_connected' => $integration->is_active,
                'account_name' => $integration->account_name,
                'account_id' => $integration->account_id,
                'connected_at' => $integration->created_at,
                'last_synced_at' => $integration->last_synced_at,
            ] : null,
            'oauthUrl' => $this->getGoogleAdsOAuthUrl(),
        ]);
    }

    public function connectGoogleAds(Request $request)
    {
        // Redirect to Google OAuth
        return redirect($this->getGoogleAdsOAuthUrl());
    }

    public function disconnectGoogleAds(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if ($currentBusiness) {
            $currentBusiness->adIntegrations()
                ->where('platform', 'google_ads')
                ->delete();
        }

        return redirect()->back()->with('success', 'Google Ads integratsiyasi o\'chirildi!');
    }

    public function googleAdsCallback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error || !$code) {
            return redirect()->route('business.settings.google-ads')
                ->with('error', 'Google Ads bilan ulanish bekor qilindi: ' . ($error ?? 'kod topilmadi'));
        }

        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.settings.google-ads')
                ->with('error', 'Biznes topilmadi.');
        }

        try {
            // Exchange code for tokens
            $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('business.settings.google-ads.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                return redirect()->route('business.settings.google-ads')
                    ->with('error', 'Token olishda xatolik: ' . $tokenResponse->body());
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            $refreshToken = $tokens['refresh_token'] ?? null;
            $expiresIn = $tokens['expires_in'] ?? 3600;

            if (!$accessToken) {
                return redirect()->route('business.settings.google-ads')
                    ->with('error', 'Access token olinmadi.');
            }

            // Get Google user info for account name
            $userInfoResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            $accountName = null;
            $accountId = null;

            if ($userInfoResponse->successful()) {
                $userInfo = $userInfoResponse->json();
                $accountName = $userInfo['name'] ?? $userInfo['email'] ?? null;
                $accountId = $userInfo['id'] ?? null;
            }

            // Save or update integration
            $currentBusiness->adIntegrations()->updateOrCreate(
                ['platform' => 'google_ads'],
                [
                    'account_id' => $accountId,
                    'account_name' => $accountName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'sync_status' => 'completed',
                ]
            );

            return redirect()->route('business.settings.google-ads')
                ->with('success', 'Google Ads muvaffaqiyatli ulandi!' . ($accountName ? " Hisob: {$accountName}" : ''));

        } catch (\Exception $e) {
            return redirect()->route('business.settings.google-ads')
                ->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    private function getGoogleAdsOAuthUrl()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = route('business.settings.google-ads.callback');
        $scope = urlencode('https://www.googleapis.com/auth/adwords');

        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/adwords',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }

    /**
     * Yandex Direct Integration Settings
     */
    public function yandexDirect()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznesni tanlang yoki yarating.');
        }

        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'yandex_direct')
            ->first();

        return Inertia::render('Business/Settings/YandexDirect', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
            'integration' => $integration ? [
                'id' => $integration->id,
                'is_connected' => $integration->is_active,
                'account_name' => $integration->account_name,
                'account_id' => $integration->account_id,
                'connected_at' => $integration->created_at,
                'last_synced_at' => $integration->last_synced_at,
            ] : null,
            'oauthUrl' => $this->getYandexDirectOAuthUrl(),
        ]);
    }

    public function connectYandexDirect(Request $request)
    {
        return redirect($this->getYandexDirectOAuthUrl());
    }

    public function disconnectYandexDirect(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if ($currentBusiness) {
            $currentBusiness->adIntegrations()
                ->where('platform', 'yandex_direct')
                ->delete();
        }

        return redirect()->back()->with('success', 'Yandex Direct integratsiyasi o\'chirildi!');
    }

    public function yandexDirectCallback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error || !$code) {
            return redirect()->route('business.settings.yandex-direct')
                ->with('error', 'Yandex Direct bilan ulanish bekor qilindi: ' . ($error ?? 'kod topilmadi'));
        }

        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.settings.yandex-direct')
                ->with('error', 'Biznes topilmadi.');
        }

        try {
            // Exchange code for tokens
            $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth.yandex.ru/token', [
                'code' => $code,
                'client_id' => config('services.yandex.client_id'),
                'client_secret' => config('services.yandex.client_secret'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                return redirect()->route('business.settings.yandex-direct')
                    ->with('error', 'Token olishda xatolik: ' . $tokenResponse->body());
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            $refreshToken = $tokens['refresh_token'] ?? null;
            $expiresIn = $tokens['expires_in'] ?? 31536000; // Yandex tokens last ~1 year

            if (!$accessToken) {
                return redirect()->route('business.settings.yandex-direct')
                    ->with('error', 'Access token olinmadi.');
            }

            // Get Yandex user info
            $userInfoResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'OAuth ' . $accessToken,
            ])->get('https://login.yandex.ru/info');

            $accountName = null;
            $accountId = null;

            if ($userInfoResponse->successful()) {
                $userInfo = $userInfoResponse->json();
                $accountName = $userInfo['display_name'] ?? $userInfo['login'] ?? null;
                $accountId = $userInfo['id'] ?? null;
            }

            // Save or update integration
            $currentBusiness->adIntegrations()->updateOrCreate(
                ['platform' => 'yandex_direct'],
                [
                    'account_id' => $accountId,
                    'account_name' => $accountName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'sync_status' => 'completed',
                ]
            );

            return redirect()->route('business.settings.yandex-direct')
                ->with('success', 'Yandex Direct muvaffaqiyatli ulandi!' . ($accountName ? " Hisob: {$accountName}" : ''));

        } catch (\Exception $e) {
            return redirect()->route('business.settings.yandex-direct')
                ->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    private function getYandexDirectOAuthUrl()
    {
        $clientId = config('services.yandex.client_id');
        $redirectUri = route('business.settings.yandex-direct.callback');

        return "https://oauth.yandex.ru/authorize?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
        ]);
    }

    /**
     * YouTube Analytics Integration Settings
     */
    public function youtube()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes tanlanmagan. Iltimos, biznesni tanlang yoki yarating.');
        }

        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'youtube')
            ->first();

        return Inertia::render('Business/Settings/YouTube', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
            'integration' => $integration ? [
                'id' => $integration->id,
                'is_connected' => $integration->is_active,
                'channel_name' => $integration->account_name,
                'channel_id' => $integration->account_id,
                'connected_at' => $integration->created_at,
                'last_synced_at' => $integration->last_synced_at,
            ] : null,
            'oauthUrl' => $this->getYouTubeOAuthUrl(),
        ]);
    }

    public function connectYoutube(Request $request)
    {
        return redirect($this->getYouTubeOAuthUrl());
    }

    public function disconnectYoutube(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if ($currentBusiness) {
            $currentBusiness->adIntegrations()
                ->where('platform', 'youtube')
                ->delete();
        }

        return redirect()->back()->with('success', 'YouTube integratsiyasi o\'chirildi!');
    }

    public function youtubeCallback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error || !$code) {
            return redirect()->route('business.settings.youtube')
                ->with('error', 'YouTube bilan ulanish bekor qilindi: ' . ($error ?? 'kod topilmadi'));
        }

        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.settings.youtube')
                ->with('error', 'Biznes topilmadi.');
        }

        try {
            // Exchange code for tokens
            $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('business.settings.youtube.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                return redirect()->route('business.settings.youtube')
                    ->with('error', 'Token olishda xatolik: ' . $tokenResponse->body());
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            $refreshToken = $tokens['refresh_token'] ?? null;
            $expiresIn = $tokens['expires_in'] ?? 3600;

            if (!$accessToken) {
                return redirect()->route('business.settings.youtube')
                    ->with('error', 'Access token olinmadi.');
            }

            // Get YouTube channel info
            $channelResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics',
                    'mine' => 'true',
                ]);

            $channelName = null;
            $channelId = null;

            if ($channelResponse->successful()) {
                $channelData = $channelResponse->json();
                if (!empty($channelData['items'][0])) {
                    $channel = $channelData['items'][0];
                    $channelId = $channel['id'] ?? null;
                    $channelName = $channel['snippet']['title'] ?? null;
                }
            }

            // Save or update integration
            $currentBusiness->adIntegrations()->updateOrCreate(
                ['platform' => 'youtube'],
                [
                    'account_id' => $channelId,
                    'account_name' => $channelName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'sync_status' => 'completed',
                ]
            );

            return redirect()->route('business.settings.youtube')
                ->with('success', 'YouTube muvaffaqiyatli ulandi!' . ($channelName ? " Kanal: {$channelName}" : ''));

        } catch (\Exception $e) {
            return redirect()->route('business.settings.youtube')
                ->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    private function getYouTubeOAuthUrl()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = route('business.settings.youtube.callback');

        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.readonly https://www.googleapis.com/auth/yt-analytics.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }
}
