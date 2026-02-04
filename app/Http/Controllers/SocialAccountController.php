<?php

namespace App\Http\Controllers;

use App\Exceptions\IntegrationAbuseException;
use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Jobs\SyncHistoricalDataJob;
use App\Models\Business;
use App\Models\FacebookPage;
use App\Models\InstagramAccount;
use App\Models\Integration;
use App\Models\MetaAdAccount;
use App\Services\FacebookService;
use App\Services\Integration\MetaOAuthService;
use App\Services\IntegrationGuardService;
use App\Services\SubscriptionGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * SocialAccountController - Qat'iy Bitta Akkaunt Tizimi
 *
 * QOIDALAR:
 * 1. Har bir business uchun faqat 1 ta Facebook/Instagram akkaunt (Tarif limitiga qarab)
 * 2. OAuth callback'da avtomatik saqlanmaydi - avval tanlash kerak
 * 3. Agar akkaunt ulangan bo'lsa - yangi ulanish bloklanadi
 * 4. Akkaunt tanlangandan keyin 6 oylik tarix sinxronlanadi
 */
class SocialAccountController extends Controller
{
    use HasCurrentBusiness;

    protected SubscriptionGate $gate;

    public function __construct(
        protected FacebookService $facebookService,
        protected MetaOAuthService $oauthService,
        SubscriptionGate $gate
    ) {
        $this->gate = $gate;
    }

    /**
     * Check if business already has connected account
     * Bloklash mexanizmi
     */
    public function checkExistingConnection(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $existingIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if ($existingIntegration) {
            // Get connected accounts info
            $adAccount = MetaAdAccount::where('integration_id', $existingIntegration->id)
                ->where('is_primary', true)
                ->first();

            $instagramAccount = InstagramAccount::where('integration_id', $existingIntegration->id)
                ->where('is_primary', true)
                ->first();

            return response()->json([
                'has_connection' => true,
                'message' => 'Sizda allaqachon ulangan akkaunt mavjud. Iltimos, avval uni uzing.',
                'connected_accounts' => [
                    'ad_account' => $adAccount ? [
                        'id' => $adAccount->id,
                        'name' => $adAccount->name,
                        'meta_account_id' => $adAccount->meta_account_id,
                    ] : null,
                    'instagram' => $instagramAccount ? [
                        'id' => $instagramAccount->id,
                        'username' => $instagramAccount->username,
                        'instagram_id' => $instagramAccount->instagram_id,
                    ] : null,
                ],
                'connected_at' => $existingIntegration->connected_at?->format('d.m.Y H:i'),
            ]);
        }

        return response()->json([
            'has_connection' => false,
            'message' => 'Yangi akkaunt ulashingiz mumkin',
        ]);
    }

    /**
     * Initiate OAuth flow
     * Avval mavjud ulanishni va tarif limitini tekshiradi
     */
    public function initiateOAuth(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        // TARIF LIMITI: Instagram akkaunt limitini tekshirish
        try {
            $this->gate->checkQuota($business, 'instagram_accounts');
        } catch (QuotaExceededException $e) {
            return response()->json([
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'error_code' => 'QUOTA_EXCEEDED',
                'limit_key' => 'instagram_accounts',
                'upgrade_required' => true,
            ], 403);
        } catch (\App\Exceptions\NoActiveSubscriptionException $e) {
            return response()->json([
                'error' => 'no_subscription',
                'message' => $e->getMessage(),
                'error_code' => 'NO_ACTIVE_SUBSCRIPTION',
                'upgrade_required' => true,
            ], 402);
        }

        // A QADAM: Tekshirish - agar akkaunt bor bo'lsa bloklash
        $existingIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if ($existingIntegration) {
            return response()->json([
                'error' => 'blocked',
                'message' => 'Sizda allaqachon ulangan akkaunt mavjud. Yangi akkaunt ulash uchun avval mavjud akkaunтni uzing.',
            ], 403);
        }

        // Generate OAuth URL
        $state = bin2hex(random_bytes(16));

        session([
            'meta_oauth_state' => $state,
            'meta_oauth_business_id' => $business->id,
            'meta_oauth_panel_type' => 'business',
        ]);

        $redirectUri = route('integrations.social.oauth.callback');

        $scopes = [
            'ads_read',
            'ads_management',
            'business_management',
            'instagram_basic',
            'instagram_manage_insights',
            'instagram_content_publish',
            'instagram_manage_comments',
            'instagram_manage_messages',
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_metadata',
            'read_insights',
        ];

        $apiVersion = config('services.meta.api_version', 'v21.0');
        $authUrl = 'https://www.facebook.com/' . $apiVersion . '/dialog/oauth?' . http_build_query([
            'client_id' => config('services.facebook.client_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
        ]);

        return response()->json([
            'auth_url' => $authUrl,
        ]);
    }

    /**
     * Handle OAuth callback - B QADAM: Tanlovchi
     * Token olish va mavjud akkauntlarni ko'rsatish
     *
     * MUHIM: OAuth state parametri CSRF hujumlaridan himoya qiladi
     */
    public function handleCallback(Request $request)
    {
        Log::info('=== Social OAuth Callback Started ===', [
            'has_code' => $request->has('code'),
            'has_error' => $request->has('error'),
            'has_state' => $request->has('state'),
        ]);

        // CSRF HIMOYASI: State parametrini tekshirish
        $sessionState = session('meta_oauth_state');
        $requestState = $request->input('state');

        if (!$sessionState || !$requestState || !hash_equals($sessionState, $requestState)) {
            Log::warning('Social OAuth: State mismatch - possible CSRF attack', [
                'session_state_exists' => !empty($sessionState),
                'request_state_exists' => !empty($requestState),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Session ma'lumotlarini tozalash
            session()->forget(['meta_oauth_state', 'meta_oauth_business_id', 'meta_oauth_panel_type']);

            return redirect()->route('business.settings.index')
                ->with('error', 'Xavfsizlik tekshiruvi muvaffaqiyatsiz. Iltimos, qayta urinib ko\'ring.');
        }

        $businessId = session('meta_oauth_business_id');

        if (!$businessId) {
            return redirect()->route('business.settings.index')
                ->with('error', 'Sessiya muddati tugagan. Qayta urinib ko\'ring.');
        }

        if ($request->has('error')) {
            Log::error('Social OAuth: Error from Facebook', [
                'error' => $request->error,
                'description' => $request->error_description,
            ]);

            // Xatolik bo'lganda ham state ni tozalash
            session()->forget(['meta_oauth_state']);

            return redirect()->route('business.settings.index')
                ->with('error', $request->error_description ?? 'OAuth xatolik');
        }

        if (!$request->has('code')) {
            return redirect()->route('business.settings.index')
                ->with('error', 'Authorization code olinmadi');
        }

        try {
            // State tekshiruvidan o'tdi - endi tozalash mumkin
            session()->forget(['meta_oauth_state']);

            // Exchange code for token
            $redirectUri = route('integrations.social.oauth.callback');
            $tokenData = $this->oauthService->exchangeCodeForToken($request->code, $redirectUri);

            if (empty($tokenData['access_token'])) {
                throw new \Exception('Access token olinmadi');
            }

            // Store token temporarily in session for account selection
            session([
                'meta_temp_token' => $tokenData['access_token'],
                'meta_temp_token_expires' => $tokenData['expires_in'] ?? 5184000,
            ]);

            // Redirect to account selection page
            return redirect()->route('business.settings.social.select-accounts');

        } catch (\Exception $e) {
            Log::error('Social OAuth: Exception', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('business.settings.index')
                ->with('error', 'Ulanishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Show account selection page
     * Mavjud Ad Account va Instagram akkauntlarni ko'rsatish
     */
    public function showAccountSelection(Request $request)
    {
        $business = $this->getCurrentBusiness($request);
        $accessToken = session('meta_temp_token');

        if (!$accessToken) {
            return redirect()->route('business.settings.index')
                ->with('error', 'Sessiya muddati tugagan. Qayta ulanishni boshlang.');
        }

        try {
            // B QADAM: Mavjud akkauntlarni olish
            $availableAccounts = $this->facebookService->getAvailableAccounts($accessToken);

            return Inertia::render('Business/Settings/SelectSocialAccounts', [
                'adAccounts' => $availableAccounts['ad_accounts'],
                'instagramAccounts' => $availableAccounts['instagram_accounts'],
                'facebookPages' => $availableAccounts['facebook_pages'],
                'currentBusiness' => [
                    'id' => $business->id,
                    'name' => $business->name,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Social OAuth: Failed to get available accounts', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('business.settings.index')
                ->with('error', 'Akkauntlarni olishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Save selected accounts - C QADAM: Saqlovchi
     * Tanlangan akkauntni saqlash va tarix sinxronlashni boshlash
     */
    public function saveSelectedAccounts(Request $request): JsonResponse
    {
        $request->validate([
            'selected_ad_account_id' => 'nullable|string',
            'selected_instagram_id' => 'nullable|string',
            'selected_page_id' => 'nullable|string',
        ]);

        $business = $this->getCurrentBusiness($request);
        $accessToken = session('meta_temp_token');
        $tokenExpires = session('meta_temp_token_expires', 5184000);

        if (!$accessToken) {
            return response()->json([
                'error' => 'Sessiya muddati tugagan. Qayta ulanishni boshlang.',
            ], 400);
        }

        if (!$request->selected_ad_account_id && !$request->selected_instagram_id) {
            return response()->json([
                'error' => 'Kamida bitta akkaunt tanlashingiz kerak.',
            ], 400);
        }

        try {
            // Yana bir marta tekshirish - qulf
            $existingIntegration = Integration::where('business_id', $business->id)
                ->where('type', 'meta_ads')
                ->where('status', 'connected')
                ->first();

            if ($existingIntegration) {
                return response()->json([
                    'error' => 'Sizda allaqachon ulangan akkaunt mavjud.',
                ], 403);
            }

            // Exchange for long-lived token
            $longLivedToken = $this->facebookService->exchangeForLongLivedToken($accessToken);
            $finalToken = $longLivedToken ?? $accessToken;

            // Create integration record
            $integration = Integration::create([
                'business_id' => $business->id,
                'type' => 'meta_ads',
                'name' => 'Meta Ads & Instagram',
                'is_active' => true,
                'status' => 'connected',
                'credentials' => json_encode([
                    'access_token' => $finalToken,
                    'token_type' => 'bearer',
                ]),
                'connected_at' => now(),
                'expires_at' => now()->addSeconds($tokenExpires),
            ]);

            $savedAccounts = [];

            // Save selected Ad Account (faqat 1 ta)
            if ($request->selected_ad_account_id) {
                $adAccountData = $this->facebookService->getAdAccountDetails(
                    $finalToken,
                    $request->selected_ad_account_id
                );

                if ($adAccountData) {
                    $adAccount = MetaAdAccount::create([
                        'business_id' => $business->id,
                        'integration_id' => $integration->id,
                        'meta_account_id' => $adAccountData['id'],
                        'account_id' => $adAccountData['id'],
                        'name' => $adAccountData['name'],
                        'currency' => $adAccountData['currency'] ?? 'USD',
                        'timezone' => $adAccountData['timezone_name'] ?? 'Asia/Tashkent',
                        'is_primary' => true,
                        'account_status' => 1, // 1 = active
                    ]);

                    $savedAccounts['ad_account'] = $adAccount;
                }
            }

            // Save selected Instagram Account (faqat 1 ta)
            if ($request->selected_instagram_id) {
                // Anti-abuse tekshiruvi: Instagram akkaunt global unikalligi
                try {
                    app(IntegrationGuardService::class)->checkInstagramAccount(
                        $request->selected_instagram_id,
                        $business
                    );
                } catch (IntegrationAbuseException $e) {
                    return response()->json([
                        'error' => $e->getMessage(),
                        'error_code' => 'INTEGRATION_ABUSE',
                        'abuse_type' => $e->getAbuseType(),
                        'upgrade_required' => $e->getAbuseType() === 'trial_abuse',
                    ], 403);
                }

                $igAccountData = $this->facebookService->getInstagramAccountDetails(
                    $finalToken,
                    $request->selected_instagram_id
                );

                if ($igAccountData) {
                    $instagramAccount = InstagramAccount::create([
                        'business_id' => $business->id,
                        'integration_id' => $integration->id,
                        'instagram_id' => $igAccountData['id'],
                        'username' => $igAccountData['username'],
                        'name' => $igAccountData['name'] ?? $igAccountData['username'],
                        'profile_picture_url' => $igAccountData['profile_picture_url'] ?? null,
                        'followers_count' => $igAccountData['followers_count'] ?? 0,
                        'follows_count' => $igAccountData['follows_count'] ?? 0,
                        'media_count' => $igAccountData['media_count'] ?? 0,
                        'biography' => $igAccountData['biography'] ?? null,
                        'website' => $igAccountData['website'] ?? null,
                        'is_primary' => true,
                        'is_active' => true,
                        'access_token' => $finalToken,
                    ]);

                    $savedAccounts['instagram'] = $instagramAccount;

                    // Link to business for quick access
                    $business->update(['instagram_account_id' => $instagramAccount->id]);
                }
            }

            // Save Facebook Page if selected
            if ($request->selected_page_id) {
                $pageData = $this->facebookService->getPageDetails(
                    $finalToken,
                    $request->selected_page_id
                );

                if ($pageData) {
                    $facebookPage = FacebookPage::create([
                        'business_id' => $business->id,
                        'integration_id' => $integration->id,
                        'facebook_page_id' => $pageData['id'],
                        'page_name' => $pageData['name'],
                        'page_username' => $pageData['username'] ?? null,
                        'category' => $pageData['category'] ?? null,
                        'fan_count' => $pageData['fan_count'] ?? 0,
                        'access_token' => $pageData['access_token'] ?? $finalToken,
                        'is_active' => true,
                    ]);

                    $savedAccounts['facebook_page'] = $facebookPage;
                }
            }

            // Clear temporary session data
            session()->forget(['meta_temp_token', 'meta_temp_token_expires', 'meta_oauth_state', 'meta_oauth_business_id']);

            // TRIGGER: Tarixiy ma'lumotlarni sinxronlash (6 oy)
            SyncHistoricalDataJob::dispatch($integration->id, 6)
                ->onQueue('default');

            Log::info('Social accounts saved successfully', [
                'business_id' => $business->id,
                'integration_id' => $integration->id,
                'accounts' => array_keys($savedAccounts),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Akkauntlar muvaffaqiyatli ulandi! Ma\'lumotlar sinxronlanmoqda...',
                'integration_id' => $integration->id,
                'saved_accounts' => $savedAccounts,
            ]);

        } catch (\Exception $e) {
            Log::error('Social OAuth: Failed to save accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Saqlashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect social accounts
     * Akkauntni uzish
     */
    public function disconnect(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        if (!$integration) {
            return response()->json([
                'error' => 'Ulangan akkaunt topilmadi',
            ], 404);
        }

        try {
            // Delete related records
            MetaAdAccount::where('integration_id', $integration->id)->delete();
            InstagramAccount::where('integration_id', $integration->id)->delete();
            FacebookPage::where('integration_id', $integration->id)->delete();

            // Clear business instagram link
            $business->update(['instagram_account_id' => null]);

            // Delete integration
            $integration->delete();

            Log::info('Social accounts disconnected', [
                'business_id' => $business->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Akkaunt muvaffaqiyatli uzildi',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to disconnect social accounts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Uzishda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }
}
