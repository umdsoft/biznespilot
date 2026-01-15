<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Integration;
use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaInsight;
use App\Services\TargetAnalysisService;
use App\Services\Integration\MetaAdsService;
use App\Services\Integration\MetaOAuthService;
use App\Services\MetaDataService;
use App\Services\MetaSyncService;
use App\Services\InstagramSyncService;
use App\Jobs\SyncMetaInsightsJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TargetAnalysisController extends Controller
{
    use HasCurrentBusiness;
    public function __construct(
        protected TargetAnalysisService $analysisService,
        protected MetaAdsService $metaService,
        protected MetaOAuthService $oauthService,
        protected MetaDataService $metaDataService,
        protected MetaSyncService $metaSyncService,
        protected InstagramSyncService $instagramSyncService
    ) {}

    /**
     * Get panel type from request
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) return 'marketing';
        if (str_contains($prefix, 'finance')) return 'finance';
        if (str_contains($prefix, 'operator')) return 'operator';
        if (str_contains($prefix, 'saleshead')) return 'saleshead';

        return 'business';
    }

    /**
     * Display target analysis dashboard
     * OPTIMIZED: Only sends minimal data on initial load
     * Heavy data is loaded lazily via API calls
     */
    public function index(Request $request): Response
    {
        $panelType = $this->getPanelType($request);
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return Inertia::render('Shared/TargetAnalysis/Index', [
                'error' => 'Biznes tanlanmagan',
                'analysis' => null,
                'panelType' => $panelType,
            ]);
        }

        // OPTIMIZATION: Don't load heavy analysis data on initial page load
        // It will be loaded via API call after page mounts
        // $analysis = $this->analysisService->getTargetAnalysis($business);

        // Meta Ads integration data - lightweight query
        $metaIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        $metaAdAccounts = collect([]);
        $selectedMetaAccount = null;
        $tokenStatus = null;

        if ($metaIntegration && in_array($metaIntegration->status, ['connected', 'expired'])) {
            // For expired status, check if token might still work (don't block user)
            if ($metaIntegration->status === 'expired') {
                // Try to validate - maybe token was refreshed or still valid
                $tokenStatus = $this->validateAndRefreshMetaToken($metaIntegration, false);
                if ($tokenStatus['valid']) {
                    // Token is actually valid - restore connected status
                    $metaIntegration->update(['status' => 'connected']);
                    $metaIntegration->refresh();
                }
            } else {
                // Connected status - do light validation (don't call Meta API on every page load)
                $tokenStatus = $this->validateAndRefreshMetaToken($metaIntegration, true);
            }

            // Load accounts regardless - let actual API calls determine if token works
            if ($metaIntegration->status === 'connected' || ($tokenStatus['valid'] ?? false)) {
                $metaAdAccounts = MetaAdAccount::where('integration_id', $metaIntegration->id)->get();
                $selectedMetaAccount = $metaAdAccounts->where('is_primary', true)->first()
                    ?? $metaAdAccounts->first();
            }
        }

        return Inertia::render('Shared/TargetAnalysis/Index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
            ],
            // LAZY LOAD: Analysis data will be fetched via API
            'analysis' => null,
            'lazyLoad' => true, // Flag to tell frontend to fetch data
            'lastUpdated' => now()->format('d.m.Y H:i'),
            'panelType' => $panelType,
            // Meta Ads data
            'metaIntegration' => $metaIntegration ? [
                'id' => $metaIntegration->id,
                'status' => $metaIntegration->status,
                'connected_at' => $metaIntegration->connected_at?->format('d.m.Y H:i'),
                'expires_at' => $metaIntegration->expires_at?->format('d.m.Y H:i'),
                'last_sync_at' => $metaIntegration->last_sync_at?->format('d.m.Y H:i'),
                'token_status' => $tokenStatus,
                'days_until_expiry' => $metaIntegration->expires_at
                    ? max(0, now()->diffInDays($metaIntegration->expires_at, false))
                    : null,
            ] : null,
            'metaAdAccounts' => $metaAdAccounts->map(fn($acc) => [
                'id' => $acc->id,
                'meta_account_id' => $acc->meta_account_id,
                'name' => $acc->name,
                'currency' => $acc->currency,
                'timezone' => $acc->timezone,
                'is_primary' => $acc->is_primary ?? false,
            ])->values(),
            'selectedMetaAccount' => $selectedMetaAccount ? [
                'id' => $selectedMetaAccount->id,
                'meta_account_id' => $selectedMetaAccount->meta_account_id,
                'name' => $selectedMetaAccount->name,
                'currency' => $selectedMetaAccount->currency,
                'timezone' => $selectedMetaAccount->timezone,
                'last_sync_at' => $selectedMetaAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
        ]);
    }

    /**
     * Get analysis data as JSON (for API calls)
     */
    public function getAnalysisData(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $analysis = $this->analysisService->getTargetAnalysis($business);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get Dream Buyer match analysis
     */
    public function getDreamBuyerMatch(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $matchAnalysis = $this->analysisService->analyzeDreamBuyerMatch($business);

        return response()->json([
            'success' => true,
            'data' => $matchAnalysis,
        ]);
    }

    /**
     * Get customer segmentation data
     */
    public function getSegments(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $segments = $this->analysisService->getCustomerSegments($business);

        return response()->json([
            'success' => true,
            'data' => $segments,
        ]);
    }

    /**
     * Get growth trends
     */
    public function getGrowthTrends(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $months = $request->input('months', 6);
        $trends = $this->analysisService->getGrowthTrends($business, $months);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Regenerate AI insights
     */
    public function regenerateInsights(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        // Get full analysis which includes AI insights
        $analysis = $this->analysisService->getTargetAnalysis($business);

        return response()->json([
            'success' => true,
            'insights' => $analysis['ai_insights'] ?? [],
        ]);
    }

    /**
     * Get churn risk customers
     */
    public function getChurnRisk(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $churnRisk = $this->analysisService->getChurnRiskAnalysis($business);

        return response()->json([
            'success' => true,
            'data' => $churnRisk,
        ]);
    }

    /**
     * Export analysis data
     */
    public function export(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $analysis = $this->analysisService->exportAnalysis($business);

        $filename = "target-analysis-{$business->slug}-" . now()->format('Y-m-d') . ".json";

        return response()->json($analysis)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get top performing customers
     */
    public function getTopPerformers(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes tanlanmagan'], 400);
        }

        $limit = $request->input('limit', 10);
        $topPerformers = $this->analysisService->getTopPerformingCustomers($business, $limit);

        return response()->json([
            'success' => true,
            'data' => $topPerformers,
        ]);
    }

    // ==================== META ADS METHODS ====================

    /**
     * Get Meta OAuth URL
     */
    public function getMetaAuthUrl(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        // Determine panel type from referer URL
        $referer = $request->headers->get('referer', '');
        $panelType = 'business';
        if (str_contains($referer, '/marketing')) {
            $panelType = 'marketing';
        } elseif (str_contains($referer, '/finance')) {
            $panelType = 'finance';
        } elseif (str_contains($referer, '/operator')) {
            $panelType = 'operator';
        } elseif (str_contains($referer, '/saleshead')) {
            $panelType = 'saleshead';
        }

        $state = Str::random(40);
        session([
            'meta_oauth_state' => $state,
            'meta_oauth_business_id' => $business->id,
            'meta_oauth_panel_type' => $panelType,
        ]);

        // Use shared integrations callback route
        $redirectUri = route('integrations.meta.callback');
        $authUrl = $this->oauthService->getAuthorizationUrl($redirectUri, $state);

        return response()->json(['url' => $authUrl]);
    }

    /**
     * Handle Meta OAuth Callback
     */
    public function handleMetaCallback(Request $request)
    {
        // Get panel type from session (set in getMetaAuthUrl)
        $panelType = session('meta_oauth_panel_type', 'business');

        \Log::info('=== Meta OAuth Callback Started ===', [
            'all_params' => $request->all(),
            'state' => $request->state,
            'session_state' => session('meta_oauth_state'),
            'business_id' => session('meta_oauth_business_id'),
            'panel_type' => $panelType,
            'current_business_id' => session('current_business_id'),
            'has_code' => $request->has('code'),
            'has_error' => $request->has('error'),
        ]);

        // Get business ID - try multiple sources
        $businessId = session('meta_oauth_business_id')
            ?? session('current_business_id')
            ?? auth()->user()?->businesses()->first()?->id;

        \Log::info('Meta OAuth: Business ID resolved', ['business_id' => $businessId]);

        // Helper function to get redirect route based on panel
        $getRedirectRoute = function($route, $params = []) use ($panelType, $businessId) {
            // For marketing panel, redirect to facebook-analysis page
            if ($panelType === 'marketing') {
                return redirect()->route('marketing.facebook-analysis')
                    ->with($params);
            }
            return redirect()->route('business.target-analysis.index', ['business_id' => $businessId])
                ->with($params);
        };

        if (!$businessId) {
            \Log::error('Meta OAuth: No business found');
            return $getRedirectRoute('index', ['error' => 'Biznes topilmadi. Iltimos, qayta urinib ko\'ring.']);
        }

        // Check for error from Facebook
        if ($request->has('error')) {
            \Log::error('Meta OAuth: Error from Facebook', [
                'error' => $request->error,
                'description' => $request->error_description,
            ]);
            return $getRedirectRoute('index', ['error' => $request->error_description ?? 'OAuth xatolik']);
        }

        // Ensure we have code
        if (!$request->has('code')) {
            \Log::error('Meta OAuth: No code received');
            return $getRedirectRoute('index', ['error' => 'Authorization code olinmadi']);
        }

        try {
            // Use shared integrations callback route
            $redirectUri = route('integrations.meta.callback');
            \Log::info('Meta OAuth: Exchanging code for token', ['redirect_uri' => $redirectUri]);

            $tokenData = $this->oauthService->exchangeCodeForToken($request->code, $redirectUri);
            \Log::info('Meta OAuth: Token received successfully', [
                'expires_in' => $tokenData['expires_in'] ?? 'unknown',
                'has_access_token' => !empty($tokenData['access_token']),
            ]);

            // Create/Update integration - ONLY save to DB, no sync yet
            // Note: credentials field is cast as 'encrypted', so we pass JSON string
            // Laravel will encrypt it automatically
            $integration = Integration::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'type' => 'meta_ads',
                ],
                [
                    'name' => 'Meta Ads',
                    'is_active' => true,
                    'status' => 'connected',
                    'credentials' => json_encode([
                        'access_token' => $tokenData['access_token'],
                        'token_type' => $tokenData['token_type'] ?? 'bearer',
                    ]),
                    'connected_at' => now(),
                    'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 5184000),
                ]
            );

            \Log::info('Meta OAuth: Integration saved to DB', [
                'integration_id' => $integration->id,
                'business_id' => $businessId,
                'status' => $integration->status,
            ]);

            // Dispatch FULL sync job in background (ad accounts, instagram, campaigns, insights)
            // This avoids timeout issues during OAuth callback
            \App\Jobs\SyncMetaInsightsJob::dispatch($businessId, true)->onQueue('default');
            \Log::info('Meta OAuth: Full sync job dispatched to background', [
                'business_id' => $businessId,
                'integration_id' => $integration->id,
            ]);

            // Clear OAuth session data
            session()->forget(['meta_oauth_state', 'meta_oauth_business_id', 'meta_oauth_panel_type']);

            $successMessage = 'Meta Ads muvaffaqiyatli ulandi! Ma\'lumotlar sinxronlanmoqda...';

            return $getRedirectRoute('index', ['success' => $successMessage]);

        } catch (\Exception $e) {
            \Log::error('Meta OAuth: Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $getRedirectRoute('index', ['error' => 'Ulanishda xatolik: ' . $e->getMessage()]);
        }
    }

    /**
     * Disconnect Meta integration
     */
    public function disconnectMeta(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        if ($integration) {
            MetaAdAccount::where('integration_id', $integration->id)->delete();
            $integration->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Meta Ads disconnected',
        ]);
    }

    /**
     * Validate Meta token and refresh if needed
     * Returns token status information
     *
     * @param Integration $integration
     * @param bool $lightValidation If true, only check stored expires_at without calling Meta API
     * @return array
     */
    protected function validateAndRefreshMetaToken(Integration $integration, bool $lightValidation = false): array
    {
        $accessToken = $integration->getAccessToken();

        if (!$accessToken) {
            \Log::warning('Meta token validation: No access token found', [
                'integration_id' => $integration->id,
            ]);
            return [
                'valid' => false,
                'message' => 'Access token topilmadi',
                'needs_reconnect' => true,
            ];
        }

        try {
            // Check if token is about to expire based on stored expires_at
            $expiresAt = $integration->expires_at;
            $now = now();

            // If expires_at is stored and valid
            if ($expiresAt) {
                // Token already expired
                if ($expiresAt->isPast()) {
                    \Log::info('Meta token validation: Token expired', [
                        'integration_id' => $integration->id,
                        'expired_at' => $expiresAt->toDateTimeString(),
                    ]);
                    return [
                        'valid' => false,
                        'message' => 'Token muddati tugagan. Qayta ulaning.',
                        'needs_reconnect' => true,
                        'expired_at' => $expiresAt->format('d.m.Y H:i'),
                    ];
                }

                $daysUntilExpiry = $now->diffInDays($expiresAt, false);

                // Light validation - don't make API calls, just check stored expiry
                if ($lightValidation) {
                    return [
                        'valid' => true,
                        'message' => 'Token faol',
                        'days_until_expiry' => $daysUntilExpiry,
                    ];
                }

                // Token expiring within 7 days - try to refresh
                if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
                    \Log::info('Meta token validation: Token expiring soon, attempting refresh', [
                        'integration_id' => $integration->id,
                        'days_until_expiry' => $daysUntilExpiry,
                    ]);

                    $refreshResult = $this->attemptTokenRefresh($integration, $accessToken);
                    if ($refreshResult['refreshed']) {
                        return [
                            'valid' => true,
                            'message' => 'Token yangilandi',
                            'refreshed' => true,
                            'new_expiry' => $refreshResult['new_expiry'],
                        ];
                    }
                }

                // Token is valid and not expiring soon
                return [
                    'valid' => true,
                    'message' => 'Token faol',
                    'days_until_expiry' => $daysUntilExpiry,
                ];
            }

            // No expires_at stored
            // For light validation, assume token is valid (don't call Meta API)
            if ($lightValidation) {
                return [
                    'valid' => true,
                    'message' => 'Token faol (tekshirilmagan)',
                ];
            }

            // Full validation - validate with Meta API
            $isValid = $this->oauthService->isTokenValid($accessToken);

            if (!$isValid) {
                \Log::warning('Meta token validation: Token invalid per Meta API', [
                    'integration_id' => $integration->id,
                ]);
                return [
                    'valid' => false,
                    'message' => 'Token yaroqsiz. Qayta ulaning.',
                    'needs_reconnect' => true,
                ];
            }

            // Token is valid, get debug info to store expiry
            $debugInfo = $this->oauthService->debugToken($accessToken);
            if (!empty($debugInfo['expires_at'])) {
                $integration->update([
                    'expires_at' => \Carbon\Carbon::createFromTimestamp($debugInfo['expires_at']),
                ]);
            }

            return [
                'valid' => true,
                'message' => 'Token faol',
            ];

        } catch (\Exception $e) {
            \Log::error('Meta token validation error', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            // On error, assume token is still valid to avoid blocking user
            // Let actual API calls handle the error
            return [
                'valid' => true,
                'message' => 'Token tekshirishda xatolik, ammo davom etamiz',
                'warning' => $e->getMessage(),
            ];
        }
    }

    /**
     * Attempt to refresh Meta access token
     */
    protected function attemptTokenRefresh(Integration $integration, string $currentToken): array
    {
        try {
            // Exchange current token for a new long-lived token
            $newTokenData = $this->oauthService->exchangeForLongLivedToken($currentToken);

            if (!empty($newTokenData['access_token'])) {
                // Update integration with new token
                $credentials = json_decode($integration->credentials, true) ?? [];
                $credentials['access_token'] = $newTokenData['access_token'];

                $newExpiresAt = now()->addSeconds($newTokenData['expires_in'] ?? 5184000);

                $integration->update([
                    'credentials' => json_encode($credentials),
                    'expires_at' => $newExpiresAt,
                ]);

                \Log::info('Meta token refresh successful', [
                    'integration_id' => $integration->id,
                    'new_expires_at' => $newExpiresAt->toDateTimeString(),
                ]);

                return [
                    'refreshed' => true,
                    'new_expiry' => $newExpiresAt->format('d.m.Y H:i'),
                ];
            }

            return ['refreshed' => false];

        } catch (\Exception $e) {
            \Log::warning('Meta token refresh failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return ['refreshed' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync Meta data manually (Load Data button) - Quick sync
     */
    public function syncMeta(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return response()->json([
                'success' => false,
                'error' => 'Meta Ads ulanmagan'
            ], 400);
        }

        try {
            // Use MetaSyncService for proper sync
            $this->metaSyncService->initialize($integration);
            $results = $this->metaSyncService->fullSync();

            // Update last sync time
            $integration->update(['last_sync_at' => now()]);

            // Build success message
            $message = "{$results['accounts']} ta hisob, {$results['campaigns']} ta kampaniya, {$results['insights']} ta insight sinxronlandi.";

            if (!empty($results['errors'])) {
                $message .= " Ba'zi xatoliklar: " . implode('; ', array_slice($results['errors'], 0, 2));
            }

            return response()->json([
                'success' => $results['success'],
                'message' => $message,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta Sync Error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Yuklashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh Meta data - Full sync (includes demographics & placements)
     */
    public function refreshMeta(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return response()->json([
                'success' => false,
                'error' => 'Meta Ads ulanmagan'
            ], 400);
        }

        try {
            // Use MetaSyncService for full sync
            $this->metaSyncService->initialize($integration);
            $results = $this->metaSyncService->fullSync();

            // Update last sync time
            $integration->update(['last_sync_at' => now()]);

            // Build success message
            $message = "To'liq sinxronlash tugadi: {$results['accounts']} ta hisob, {$results['campaigns']} ta kampaniya, {$results['insights']} ta insight.";

            if (!empty($results['errors'])) {
                $message .= " Xatoliklar: " . implode('; ', array_slice($results['errors'], 0, 2));
            }

            return response()->json([
                'success' => $results['success'],
                'message' => $message,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta Full Sync Error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'To\'liq sinxronlashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Select Meta ad account as primary and delete others
     * Each business can only have ONE ad account
     */
    public function selectMetaAccount(Request $request): JsonResponse
    {
        $request->validate(['account_id' => 'required|string']);

        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        if (!$integration) {
            return response()->json(['error' => 'Not connected'], 400);
        }

        // Get the selected account
        $selectedAccount = MetaAdAccount::where('integration_id', $integration->id)
            ->where('meta_account_id', $request->account_id)
            ->first();

        if (!$selectedAccount) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        // Delete all OTHER accounts (each business can only have one)
        MetaAdAccount::where('integration_id', $integration->id)
            ->where('meta_account_id', '!=', $request->account_id)
            ->delete();

        // Set selected as primary
        $selectedAccount->update(['is_primary' => true]);

        // Dispatch background sync job to sync campaigns and insights
        SyncMetaInsightsJob::dispatch($business->id, true);

        return response()->json([
            'success' => true,
            'message' => 'Hisob tanlandi. Ma\'lumotlar orqada sinxronlanmoqda...'
        ]);
    }

    /**
     * Get Meta overview data (from local database)
     */
    public function getMetaOverview(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->period ?? 'last_30d';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'current' => [],
                    'change' => [],
                    'message' => 'Meta Ad hesob tanlanmagan. Avval "Ma\'lumotlarni yuklash" tugmasini bosing.',
                ]);
            }

            // Check if we have any data
            if (!$this->metaDataService->hasData($adAccount->id)) {
                return response()->json([
                    'success' => false,
                    'current' => [],
                    'change' => [],
                    'message' => 'Hali ma\'lumot yuklanmagan. "Yangilash" tugmasini bosib ma\'lumotlarni yuklang.',
                ]);
            }

            $data = $this->metaDataService->getOverview($adAccount->id, $datePreset);
            return response()->json(array_merge(['success' => true], $data));
        } catch (\Exception $e) {
            \Log::error('Meta Overview Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'current' => [],
                'change' => [],
            ], 500);
        }
    }

    /**
     * Get Meta campaigns (from local database)
     */
    public function getMetaCampaigns(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->period ?? 'last_30d';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'campaigns' => [],
                    'message' => 'Meta Ad hesob tanlanmagan.',
                ]);
            }

            $campaigns = $this->metaDataService->getCampaigns($adAccount->id, $datePreset);
            return response()->json([
                'success' => true,
                'campaigns' => $campaigns,
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta Campaigns Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'campaigns' => [],
            ], 500);
        }
    }

    /**
     * Get Meta demographics (from local database)
     */
    public function getMetaDemographics(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->period ?? 'last_30d';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'age' => [],
                    'gender' => [],
                    'message' => 'Meta Ad hesob tanlanmagan.',
                ]);
            }

            $data = $this->metaDataService->getDemographics($adAccount->id, $datePreset);
            return response()->json(['success' => true, ...$data]);
        } catch (\Exception $e) {
            \Log::error('Meta Demographics Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'age' => [],
                'gender' => [],
            ], 500);
        }
    }

    /**
     * Get Meta placements (from local database)
     */
    public function getMetaPlacements(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->period ?? 'last_30d';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'platforms' => [],
                    'positions' => [],
                    'message' => 'Meta Ad hesob tanlanmagan.',
                ]);
            }

            $data = $this->metaDataService->getPlacements($adAccount->id, $datePreset);
            return response()->json(['success' => true, ...$data]);
        } catch (\Exception $e) {
            \Log::error('Meta Placements Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'platforms' => [],
                'positions' => [],
            ], 500);
        }
    }

    /**
     * Get Meta objectives analytics (leads, messages, sales breakdown)
     */
    public function getMetaObjectives(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->date_preset ?? 'maximum';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'objectives' => [],
                    'message' => 'Meta Ad hesob tanlanmagan.',
                ]);
            }

            $objectives = $this->metaDataService->getObjectivesAnalytics($adAccount->id, $datePreset);
            return response()->json([
                'success' => true,
                'objectives' => $objectives,
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta Objectives Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'objectives' => [],
            ], 500);
        }
    }

    /**
     * Get Meta audience analytics (age, gender, platform performance)
     */
    public function getMetaAudience(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->date_preset ?? 'maximum';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'audience' => [],
                    'message' => 'Meta Ad hesob tanlanmagan.',
                ]);
            }

            $audience = $this->metaDataService->getAudienceAnalytics($adAccount->id, $datePreset);
            return response()->json([
                'success' => true,
                'audience' => $audience,
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta Audience Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'audience' => [],
            ], 500);
        }
    }

    /**
     * Get Meta daily trend (from local database)
     */
    public function getMetaTrend(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $days = (int) ($request->days ?? 30);

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'trend' => [],
                    'message' => 'Meta Ad hesob tanlanmagan.',
                ]);
            }

            $trend = $this->metaDataService->getTrend($adAccount->id, $days);
            return response()->json([
                'success' => true,
                'trend' => $trend,
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta Trend Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trend' => [],
            ], 500);
        }
    }

    /**
     * Get Meta AI insights (from local database)
     */
    public function getMetaAIInsights(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $datePreset = $request->period ?? 'last_30d';

            $adAccount = $this->getSelectedMetaAccount($business->id);
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Meta Ad hesob tanlanmagan.',
                    'performance_summary' => '',
                    'recommendations' => [],
                    'audience_insights' => '',
                ]);
            }

            $summaryData = $this->metaDataService->getAISummary($adAccount->id, $datePreset);
            $insights = $this->generateMetaInsightsFromLocal($summaryData);

            return response()->json([
                'success' => true,
                'performance_summary' => $insights['summary'],
                'recommendations' => $insights['recommendations'],
                'audience_insights' => $insights['audience'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Meta AI Insights Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'performance_summary' => '',
                'recommendations' => [],
                'audience_insights' => '',
            ], 500);
        }
    }

    // ==================== HELPER METHODS ====================

    protected function getMetaIntegration(string $businessId): ?Integration
    {
        return Integration::where('business_id', $businessId)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();
    }

    protected function getSelectedMetaAccountId(string $businessId): ?string
    {
        $account = $this->getSelectedMetaAccount($businessId);
        return $account ? str_replace('act_', '', $account->meta_account_id) : null;
    }

    protected function getSelectedMetaAccount(string $businessId): ?MetaAdAccount
    {
        $integration = Integration::where('business_id', $businessId)
            ->where('type', 'meta_ads')
            ->first();

        if (!$integration) {
            return null;
        }

        $account = MetaAdAccount::where('integration_id', $integration->id)
            ->where('is_primary', true)
            ->first();

        if (!$account) {
            $account = MetaAdAccount::where('integration_id', $integration->id)->first();
        }

        return $account;
    }

    protected function setupMetaService(Integration $integration): void
    {
        $token = $integration->getAccessToken();
        if (!$token) {
            throw new \Exception('Access token not found');
        }
        $this->metaService->setAccessToken($token);
    }

    protected function syncMetaAdAccounts(Integration $integration): void
    {
        $this->setupMetaService($integration);

        $accounts = $this->metaService->getAdAccounts();
        $accountsData = $accounts['data'] ?? [];

        // Find account with highest spend to set as primary
        $maxSpendAccountId = null;
        $maxSpend = 0;
        foreach ($accountsData as $account) {
            $spent = floatval($account['amount_spent'] ?? 0);
            if ($spent > $maxSpend) {
                $maxSpend = $spent;
                $maxSpendAccountId = $account['id'];
            }
        }

        // Check if there's already a primary account
        $hasPrimary = MetaAdAccount::where('integration_id', $integration->id)
            ->where('is_primary', true)
            ->exists();

        foreach ($accountsData as $index => $account) {
            // Only active accounts (status 1)
            $isActive = ($account['account_status'] ?? 1) == 1;

            // Determine primary: keep existing, or set highest spend, or first active
            $isPrimary = false;
            if (!$hasPrimary) {
                if ($maxSpendAccountId && $account['id'] === $maxSpendAccountId) {
                    $isPrimary = true;
                } elseif (!$maxSpendAccountId && $isActive && $index === 0) {
                    $isPrimary = true;
                }
            }

            // account_id is the clean ID without act_ prefix (legacy column)
            $cleanAccountId = str_replace('act_', '', $account['id']);

            MetaAdAccount::updateOrCreate(
                [
                    'integration_id' => $integration->id,
                    'meta_account_id' => $account['id'],
                ],
                [
                    'business_id' => $integration->business_id,
                    'account_id' => $cleanAccountId,
                    'name' => $account['name'],
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone_name'] ?? null,
                    'account_status' => $account['account_status'] ?? 1,
                    'amount_spent' => floatval($account['amount_spent'] ?? 0) / 100,
                    'is_primary' => $isPrimary,
                    'last_sync_at' => now(),
                ]
            );
        }
    }

    protected function getPreviousDatePreset(string $datePreset): string
    {
        return match ($datePreset) {
            'last_7d' => 'last_14d',
            'last_14d' => 'last_28d',
            'last_30d' => 'last_60d',
            default => 'last_30d',
        };
    }

    protected function calculateChange(array $current, array $previous): array
    {
        $metrics = ['spend', 'impressions', 'reach', 'clicks', 'ctr', 'cpc', 'cpm'];
        $changes = [];

        foreach ($metrics as $metric) {
            $currentVal = floatval($current[$metric] ?? 0);
            $previousVal = floatval($previous[$metric] ?? 0);

            if ($previousVal > 0) {
                $changes[$metric] = round((($currentVal - $previousVal) / $previousVal) * 100, 1);
            } else {
                $changes[$metric] = $currentVal > 0 ? 100 : 0;
            }
        }

        return $changes;
    }

    protected function generateMetaInsights(array $overview, array $demographics, array $placements): array
    {
        $spend = (float) ($overview['spend'] ?? 0);
        $impressions = (int) ($overview['impressions'] ?? 0);
        $clicks = (int) ($overview['clicks'] ?? 0);
        $ctr = (float) ($overview['ctr'] ?? 0);
        $cpc = (float) ($overview['cpc'] ?? 0);
        $reach = (int) ($overview['reach'] ?? 0);

        // Performance summary
        $summary = "Tanlangan davrda " . number_format($spend, 2) . " USD sarflandi. ";
        $summary .= number_format($impressions) . " ko'rish va " . number_format($clicks) . " klik olindi. ";
        $summary .= "CTR " . number_format($ctr, 2) . "%, CPC " . number_format($cpc, 2) . " USD.";

        // Recommendations
        $recommendations = [];

        if ($ctr < 1) {
            $recommendations[] = "CTR past (< 1%). Reklama kreativlarini yangilang va targeting sozlamalarini qayta ko'rib chiqing.";
        } elseif ($ctr > 3) {
            $recommendations[] = "CTR yaxshi (> 3%). Ushbu kreativlarni boshqa kampaniyalarda ham sinab ko'ring.";
        }

        if ($cpc > 2) {
            $recommendations[] = "CPC yuqori. Auditoriyani kengaytiring yoki bid strategiyasini o'zgartiring.";
        }

        if ($reach > 0 && $impressions / $reach > 3) {
            $recommendations[] = "Frequency yuqori. Yangi auditoriya segmentlarini sinab ko'ring.";
        }

        // Demographics insights
        $topAgeGroups = collect($demographics)
            ->sortByDesc(fn($d) => (float) ($d['spend'] ?? 0))
            ->take(2)
            ->pluck('age')
            ->filter()
            ->implode(', ');

        $audience = "Eng faol auditoriya: ";
        if ($topAgeGroups) {
            $audience .= $topAgeGroups . " yosh guruhi. ";
        }
        $audience .= "Bu segmentga ko'proq e'tibor qarating.";

        if (empty($recommendations)) {
            $recommendations[] = "Kampaniyalar yaxshi ishlayapti. Budjetni asta-sekin oshirishni ko'rib chiqing.";
        }

        return [
            'summary' => $summary,
            'recommendations' => $recommendations,
            'audience' => $audience,
        ];
    }

    /**
     * Generate insights from local database summary
     */
    protected function generateMetaInsightsFromLocal(array $data): array
    {
        $overview = $data['overview'] ?? [];
        $spend = (float) ($overview['spend'] ?? 0);
        $impressions = (int) ($overview['impressions'] ?? 0);
        $clicks = (int) ($overview['clicks'] ?? 0);
        $ctr = (float) ($overview['ctr'] ?? 0);
        $cpc = (float) ($overview['cpc'] ?? 0);
        $reach = (int) ($overview['reach'] ?? 0);
        $conversions = (int) ($overview['conversions'] ?? 0);
        $roas = (float) ($overview['roas'] ?? 0);

        // Performance summary
        $summary = "Tanlangan davrda " . number_format($spend, 2) . " USD sarflandi. ";
        $summary .= number_format($impressions) . " ko'rish va " . number_format($clicks) . " klik olindi. ";
        $summary .= "CTR " . number_format($ctr, 2) . "%, CPC " . number_format($cpc, 2) . " USD.";

        if ($conversions > 0) {
            $summary .= " " . number_format($conversions) . " ta konversiya, ROAS: " . number_format($roas, 2) . "x.";
        }

        // Recommendations
        $recommendations = [];

        if ($ctr < 1) {
            $recommendations[] = "CTR past (< 1%). Reklama kreativlarini yangilang va targeting sozlamalarini qayta ko'rib chiqing.";
        } elseif ($ctr > 3) {
            $recommendations[] = "CTR yaxshi (> 3%). Ushbu kreativlarni boshqa kampaniyalarda ham sinab ko'ring.";
        }

        if ($cpc > 2) {
            $recommendations[] = "CPC yuqori. Auditoriyani kengaytiring yoki bid strategiyasini o'zgartiring.";
        }

        if ($reach > 0 && $impressions / $reach > 3) {
            $recommendations[] = "Frequency yuqori (" . number_format($impressions / $reach, 1) . "). Yangi auditoriya segmentlarini sinab ko'ring.";
        }

        // Check worst campaigns
        $worstCampaigns = $data['worst_campaigns'] ?? [];
        if (!empty($worstCampaigns)) {
            $campaignNames = collect($worstCampaigns)->pluck('name')->take(2)->implode(', ');
            $recommendations[] = "Past samaradorlikdagi kampaniyalar: {$campaignNames}. Ularni optimallashtiring yoki to'xtating.";
        }

        if ($roas > 0 && $roas < 1) {
            $recommendations[] = "ROAS 1 dan past. Konversiya optimizatsiyasini yoqing yoki auditoriyani qayta ko'rib chiqing.";
        } elseif ($roas > 3) {
            $recommendations[] = "ROAS yaxshi ({$roas}x). Budjetni oshirishni ko'rib chiqing.";
        }

        // Demographics insights
        $demographics = $data['demographics'] ?? [];
        $ageData = $demographics['age'] ?? [];

        $topAgeGroups = collect($ageData)
            ->sortByDesc('percentage')
            ->take(2)
            ->pluck('label')
            ->filter()
            ->implode(', ');

        $audience = "Eng faol auditoriya: ";
        if ($topAgeGroups) {
            $audience .= $topAgeGroups . " yosh guruhi. ";
        }

        // Platform insights
        $placements = $data['placements'] ?? [];
        $platforms = $placements['platforms'] ?? [];
        $topPlatform = collect($platforms)->sortByDesc('percentage')->first();

        if ($topPlatform) {
            $audience .= $topPlatform['label'] . " platformasi eng samarali (" . $topPlatform['percentage'] . "%). ";
        }

        $audience .= "Bu segmentga ko'proq e'tibor qarating.";

        if (empty($recommendations)) {
            $recommendations[] = "Kampaniyalar yaxshi ishlayapti. Budjetni asta-sekin oshirishni ko'rib chiqing.";
        }

        return [
            'summary' => $summary,
            'recommendations' => $recommendations,
            'audience' => $audience,
        ];
    }

    // ==================== CAMPAIGN MANAGEMENT ====================

    /**
     * Update campaign status (toggle on/off)
     */
    public function updateCampaignStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'campaign_id' => 'required|string',
                'status' => 'required|in:ACTIVE,PAUSED',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            // Get campaign from local DB
            $campaign = MetaCampaign::withoutGlobalScope('business')
                ->where('meta_campaign_id', $request->campaign_id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kampaniya topilmadi.',
                ], 404);
            }

            // Update via Meta API
            $result = $this->metaService->updateCampaignStatus($request->campaign_id, $request->status);

            // Update local DB
            $campaign->update([
                'status' => $request->status,
                'effective_status' => $request->status,
            ]);

            \Log::info('Campaign status updated', [
                'campaign_id' => $request->campaign_id,
                'new_status' => $request->status,
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->status === 'ACTIVE' ? 'Kampaniya yoqildi' : 'Kampaniya to\'xtatildi',
                'campaign' => [
                    'id' => $campaign->id,
                    'meta_campaign_id' => $campaign->meta_campaign_id,
                    'status' => $request->status,
                    'effective_status' => $request->status,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Campaign status update error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update campaign budget
     */
    public function updateCampaignBudget(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'campaign_id' => 'required|string',
                'budget' => 'required|numeric|min:1',
                'budget_type' => 'in:daily,lifetime',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            $campaign = MetaCampaign::withoutGlobalScope('business')
                ->where('meta_campaign_id', $request->campaign_id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kampaniya topilmadi.',
                ], 404);
            }

            $budgetType = $request->budget_type ?? 'daily';

            // Update via Meta API
            $result = $this->metaService->updateCampaignBudget(
                $request->campaign_id,
                $request->budget,
                $budgetType
            );

            // Update local DB
            if ($budgetType === 'daily') {
                $campaign->update(['daily_budget' => $request->budget]);
            } else {
                $campaign->update(['lifetime_budget' => $request->budget]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Byudjet yangilandi',
                'campaign' => [
                    'id' => $campaign->id,
                    'meta_campaign_id' => $campaign->meta_campaign_id,
                    'daily_budget' => $campaign->daily_budget,
                    'lifetime_budget' => $campaign->lifetime_budget,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Campaign budget update error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch update campaign statuses
     */
    public function batchUpdateCampaigns(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'campaign_ids' => 'required|array|min:1',
                'campaign_ids.*' => 'string',
                'status' => 'required|in:ACTIVE,PAUSED',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            $results = $this->metaService->batchUpdateCampaignStatus(
                $request->campaign_ids,
                $request->status
            );

            // Update local DB
            $successCount = 0;
            foreach ($request->campaign_ids as $campaignId) {
                if (!isset($results[$campaignId]['error'])) {
                    MetaCampaign::withoutGlobalScope('business')
                        ->where('meta_campaign_id', $campaignId)
                        ->update([
                            'status' => $request->status,
                            'effective_status' => $request->status,
                        ]);
                    $successCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$successCount} ta kampaniya yangilandi",
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error('Batch campaign update error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign management capabilities
     */
    public function getCampaignManagementInfo(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            $capabilities = [
                'can_manage' => false,
                'permissions' => [],
                'limitations' => [],
            ];

            if (!$integration) {
                $capabilities['limitations'][] = 'Meta integratsiyasi topilmadi';
                return response()->json(['success' => true, 'capabilities' => $capabilities]);
            }

            // Check token validity
            $isValid = $this->oauthService->isTokenValid($integration->getAccessToken());
            if (!$isValid) {
                $capabilities['limitations'][] = 'Access token muddati tugagan. Qayta ulaning.';
                return response()->json(['success' => true, 'capabilities' => $capabilities]);
            }

            // Token debug info
            $tokenInfo = $this->oauthService->debugToken($integration->getAccessToken());
            $scopes = $tokenInfo['scopes'] ?? [];

            $capabilities['can_manage'] = in_array('ads_management', $scopes);
            $capabilities['permissions'] = [
                'ads_read' => in_array('ads_read', $scopes),
                'ads_management' => in_array('ads_management', $scopes),
                'business_management' => in_array('business_management', $scopes),
            ];

            if (!$capabilities['can_manage']) {
                $capabilities['limitations'][] = 'ads_management ruxsati yo\'q. Qayta ulaning.';
            }

            $capabilities['available_actions'] = [
                'toggle_campaign' => $capabilities['can_manage'],
                'update_budget' => $capabilities['can_manage'],
                'batch_update' => $capabilities['can_manage'],
            ];

            return response()->json([
                'success' => true,
                'capabilities' => $capabilities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'capabilities' => ['can_manage' => false],
            ], 500);
        }
    }

    // ==================== CAMPAIGN CREATION ====================

    /**
     * Get campaign creation options (objectives, pages, etc.)
     */
    public function getCampaignCreationOptions(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            // Get available objectives
            $objectives = $this->metaService->getAvailableObjectives();

            // Get Facebook pages
            $pagesResponse = $this->metaService->getPages();
            $pages = collect($pagesResponse['data'] ?? [])->map(function ($page) {
                return [
                    'id' => $page['id'],
                    'name' => $page['name'],
                    'category' => $page['category'] ?? '',
                    'picture' => $page['picture']['data']['url'] ?? null,
                ];
            })->values()->all();

            // Get ad accounts
            $adAccount = $this->getSelectedMetaAccount($business->id);

            return response()->json([
                'success' => true,
                'objectives' => $objectives,
                'pages' => $pages,
                'ad_account' => $adAccount ? [
                    'id' => $adAccount->meta_account_id,
                    'name' => $adAccount->name,
                    'currency' => $adAccount->currency,
                ] : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get campaign creation options error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ma\'lumotlarni yuklashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get optimization goals for objective
     */
    public function getOptimizationGoals(Request $request): JsonResponse
    {
        try {
            $objective = $request->objective;
            if (!$objective) {
                return response()->json([
                    'success' => false,
                    'message' => 'Objective parametri kerak.',
                ], 400);
            }

            $goals = $this->metaService->getOptimizationGoals($objective);

            return response()->json([
                'success' => true,
                'goals' => $goals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search interests for targeting
     */
    public function searchInterests(Request $request): JsonResponse
    {
        try {
            $query = $request->q;
            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'interests' => [],
                ]);
            }

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);
            $response = $this->metaService->searchInterests($query);

            $interests = collect($response['data'] ?? [])->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'audience_size' => $item['audience_size'] ?? 0,
                    'path' => $item['path'] ?? [],
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'interests' => $interests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'interests' => [],
            ], 500);
        }
    }

    /**
     * Search locations for targeting
     */
    public function searchLocations(Request $request): JsonResponse
    {
        try {
            $query = $request->q;
            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'locations' => [],
                ]);
            }

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);
            $response = $this->metaService->searchLocations($query);

            $locations = collect($response['data'] ?? [])->map(function ($item) {
                return [
                    'key' => $item['key'],
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'country_code' => $item['country_code'] ?? '',
                    'country_name' => $item['country_name'] ?? '',
                    'region' => $item['region'] ?? '',
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'locations' => $locations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'locations' => [],
            ], 500);
        }
    }

    /**
     * Get reach estimate
     */
    public function getReachEstimate(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            $targeting = $request->targeting ?? [
                'geo_locations' => ['countries' => ['UZ']],
            ];

            $response = $this->metaService->getReachEstimate($adAccount->meta_account_id, $targeting);

            return response()->json([
                'success' => true,
                'users' => $response['data']['users'] ?? 0,
                'users_lower_bound' => $response['data']['users_lower_bound'] ?? 0,
                'users_upper_bound' => $response['data']['users_upper_bound'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'users_lower_bound' => 0,
                'users_upper_bound' => 0,
            ], 500);
        }
    }

    /**
     * Search behaviors for targeting
     */
    public function searchBehaviors(Request $request): JsonResponse
    {
        try {
            $query = $request->q;
            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);
            $response = $this->metaService->searchBehaviors($query);

            $behaviors = collect($response['data'] ?? [])->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'audience_size_lower_bound' => $item['audience_size_lower_bound'] ?? 0,
                    'audience_size_upper_bound' => $item['audience_size_upper_bound'] ?? 0,
                    'path' => $item['path'] ?? [],
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'data' => $behaviors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Browse interests by category
     */
    public function browseInterests(Request $request): JsonResponse
    {
        try {
            $category = $request->category;

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            // Get targeting browse for category
            $response = $this->metaService->getTargetingCategories($adAccount->meta_account_id);

            $interests = collect($response['data'] ?? [])->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'audience_size_lower_bound' => $item['audience_size_lower_bound'] ?? 0,
                    'type' => $item['type'] ?? 'interests',
                    'path' => $item['path'] ?? [],
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'data' => $interests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Create a new campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'objective' => 'required|string',
                'daily_budget' => 'nullable|numeric|min:1',
                'lifetime_budget' => 'nullable|numeric|min:1',
                'status' => 'in:ACTIVE,PAUSED',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            // Create campaign via Meta API
            $result = $this->metaService->createCampaign($adAccount->meta_account_id, [
                'name' => $request->name,
                'objective' => $request->objective,
                'daily_budget' => $request->daily_budget,
                'lifetime_budget' => $request->lifetime_budget,
                'status' => $request->status ?? 'PAUSED',
                'special_ad_categories' => $request->special_ad_categories ?? [],
            ]);

            $metaCampaignId = $result['id'] ?? null;

            if (!$metaCampaignId) {
                \Log::error('Campaign creation failed - no ID returned', ['result' => $result]);
                return response()->json([
                    'success' => false,
                    'message' => 'Kampaniya yaratishda xatolik: Meta javob bermadi',
                    'details' => $result,
                ], 500);
            }

            // Save to local database
            $campaign = MetaCampaign::create([
                'ad_account_id' => $adAccount->id,
                'business_id' => $business->id,
                'meta_campaign_id' => $metaCampaignId,
                'name' => $request->name,
                'objective' => $request->objective,
                'status' => $request->status ?? 'PAUSED',
                'effective_status' => $request->status ?? 'PAUSED',
                'daily_budget' => $request->daily_budget,
                'lifetime_budget' => $request->lifetime_budget,
                'created_time' => now(),
            ]);

            \Log::info('Campaign created', [
                'campaign_id' => $campaign->id,
                'meta_campaign_id' => $metaCampaignId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kampaniya muvaffaqiyatli yaratildi',
                'campaign' => [
                    'id' => $campaign->id,
                    'meta_campaign_id' => $metaCampaignId,
                    'name' => $campaign->name,
                    'objective' => $campaign->objective,
                    'status' => $campaign->status,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Create campaign error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new AdSet
     */
    public function createAdSet(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'campaign_id' => 'required|string',
                'name' => 'required|string|max:255',
                'daily_budget' => 'nullable|numeric|min:1',
                'optimization_goal' => 'required|string',
                'billing_event' => 'required|string',
                'targeting' => 'required|array',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            // Create AdSet via Meta API
            $result = $this->metaService->createAdSet($adAccount->meta_account_id, $request->campaign_id, [
                'name' => $request->name,
                'daily_budget' => $request->daily_budget,
                'lifetime_budget' => $request->lifetime_budget,
                'optimization_goal' => $request->optimization_goal,
                'billing_event' => $request->billing_event,
                'targeting' => $request->targeting,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status ?? 'PAUSED',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AdSet muvaffaqiyatli yaratildi',
                'adset' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Create AdSet error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wizard options for full ad creation
     */
    public function getWizardOptions(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            // Get pages
            $pagesResponse = $this->metaService->getPages();
            $pages = collect($pagesResponse['data'] ?? [])->map(fn($page) => [
                'id' => $page['id'],
                'name' => $page['name'],
                'picture' => $page['picture']['data']['url'] ?? null,
            ])->values()->all();

            return response()->json([
                'success' => true,
                'objectives' => $this->metaService->getAvailableObjectives(),
                'countries' => $this->metaService->getCountries(),
                'call_to_actions' => $this->metaService->getCallToActionTypes(),
                'pages' => $pages,
                'ad_account' => [
                    'id' => $adAccount->meta_account_id,
                    'name' => $adAccount->name,
                    'currency' => $adAccount->currency ?? 'USD',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get wizard options error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload image for ad creative
     */
    public function uploadAdImage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required|string', // base64 encoded image
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            $result = $this->metaService->uploadImageBase64($adAccount->meta_account_id, $request->image);

            // Get image hash from response
            $images = $result['images'] ?? [];
            $imageHash = null;
            foreach ($images as $name => $data) {
                $imageHash = $data['hash'] ?? null;
                break;
            }

            return response()->json([
                'success' => true,
                'image_hash' => $imageHash,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Upload ad image error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Rasm yuklashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lead forms for lead campaigns
     */
    public function getLeadForms(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                    'data' => [],
                ], 400);
            }

            $this->setupMetaService($integration);

            // Get pages first
            $pagesResponse = $this->metaService->getPages();
            $pages = $pagesResponse['data'] ?? [];

            $leadForms = [];

            // For each page, get lead forms
            foreach ($pages as $page) {
                try {
                    $pageId = $page['id'];
                    $pageAccessToken = $page['access_token'] ?? null;

                    if (!$pageAccessToken) {
                        continue;
                    }

                    // Fetch lead forms from page using Meta service
                    $forms = $this->metaService->getPageLeadForms($pageId, $pageAccessToken);

                    foreach ($forms as $form) {
                        if (($form['status'] ?? '') === 'ACTIVE') {
                            $leadForms[] = [
                                'id' => $form['id'],
                                'name' => $form['name'] ?? 'Nomsiz forma',
                                'page_id' => $pageId,
                                'page_name' => $page['name'] ?? '',
                                'leads_count' => $form['leads_count'] ?? 0,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error fetching lead forms for page', [
                        'page_id' => $page['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $leadForms,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get lead forms error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Create ad with creative
     */
    public function createAd(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'adset_id' => 'required|string',
                'page_id' => 'required|string',
                'name' => 'required|string|max:255',
                'primary_text' => 'required|string',
                'headline' => 'nullable|string|max:255',
                'link' => 'required|url',
                'call_to_action' => 'required|string',
                'image_hash' => 'nullable|string',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);

            $result = $this->metaService->createAdWithCreative(
                $adAccount->meta_account_id,
                $request->adset_id,
                $request->page_id,
                [
                    'ad_name' => $request->name,
                    'primary_text' => $request->primary_text,
                    'headline' => $request->headline,
                    'description' => $request->description,
                    'link' => $request->link,
                    'call_to_action' => $request->call_to_action,
                    'image_hash' => $request->image_hash,
                ],
                $request->status ?? 'PAUSED'
            );

            return response()->json([
                'success' => true,
                'message' => 'Reklama muvaffaqiyatli yaratildi',
                'ad' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Create ad error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create full ad (Campaign + AdSet + Ad) in one wizard step
     */
    public function createFullAd(Request $request): JsonResponse
    {
        try {
            $request->validate([
                // Campaign
                'campaign_name' => 'required|string|max:255',
                'objective' => 'required|string',
                // AdSet
                'adset_name' => 'required|string|max:255',
                'daily_budget' => 'required|numeric|min:1',
                'optimization_goal' => 'required|string',
                'targeting' => 'required|array',
                // Ad
                'ad_name' => 'required|string|max:255',
                'page_id' => 'required|string',
                'primary_text' => 'required|string',
                'link' => 'required|url',
                'call_to_action' => 'required|string',
            ]);

            $business = $this->getCurrentBusiness($request);
            $integration = $this->getMetaIntegration($business->id);
            $adAccount = $this->getSelectedMetaAccount($business->id);

            if (!$integration || !$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta integratsiyasi topilmadi.',
                ], 400);
            }

            $this->setupMetaService($integration);
            $status = $request->status ?? 'PAUSED';

            // Step 1: Create Campaign
            $campaignResult = $this->metaService->createCampaign($adAccount->meta_account_id, [
                'name' => $request->campaign_name,
                'objective' => $request->objective,
                'status' => $status,
                'special_ad_categories' => [],
            ]);

            $campaignId = $campaignResult['id'] ?? null;
            if (!$campaignId) {
                throw new \Exception('Kampaniya yaratishda xatolik');
            }

            // Save campaign to DB
            $campaign = MetaCampaign::create([
                'ad_account_id' => $adAccount->id,
                'business_id' => $business->id,
                'meta_campaign_id' => $campaignId,
                'name' => $request->campaign_name,
                'objective' => $request->objective,
                'status' => $status,
            ]);

            // Step 2: Create AdSet
            $billingEvent = $this->getBillingEventForGoal($request->optimization_goal);
            $adSetResult = $this->metaService->createAdSet($adAccount->meta_account_id, $campaignId, [
                'name' => $request->adset_name,
                'daily_budget' => $request->daily_budget,
                'optimization_goal' => $request->optimization_goal,
                'billing_event' => $billingEvent,
                'targeting' => $request->targeting,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $status,
            ]);

            $adSetId = $adSetResult['id'] ?? null;
            if (!$adSetId) {
                throw new \Exception('AdSet yaratishda xatolik');
            }

            // Save adset to DB
            $adSet = MetaAdSet::create([
                'ad_account_id' => $adAccount->id,
                'campaign_id' => $campaign->id,
                'meta_campaign_id' => $campaignId,
                'business_id' => $business->id,
                'meta_adset_id' => $adSetId,
                'name' => $request->adset_name,
                'status' => $status,
                'optimization_goal' => $request->optimization_goal,
                'billing_event' => $billingEvent,
                'daily_budget' => $request->daily_budget,
                'targeting' => $request->targeting,
            ]);

            // Step 3: Create Ad with Creative
            $adResult = $this->metaService->createAdWithCreative(
                $adAccount->meta_account_id,
                $adSetId,
                $request->page_id,
                [
                    'ad_name' => $request->ad_name,
                    'primary_text' => $request->primary_text,
                    'headline' => $request->headline,
                    'description' => $request->description,
                    'link' => $request->link,
                    'call_to_action' => $request->call_to_action,
                    'image_hash' => $request->image_hash,
                ],
                $status
            );

            $adId = $adResult['id'] ?? null;

            // Save ad to DB
            if ($adId) {
                MetaAd::create([
                    'ad_account_id' => $adAccount->id,
                    'adset_id' => $adSet->id,
                    'campaign_id' => $campaign->id,
                    'meta_adset_id' => $adSetId,
                    'meta_campaign_id' => $campaignId,
                    'business_id' => $business->id,
                    'meta_ad_id' => $adId,
                    'name' => $request->ad_name,
                    'status' => $status,
                    'creative_body' => $request->primary_text,
                    'creative_title' => $request->headline,
                    'creative_link_url' => $request->link,
                    'creative_call_to_action' => $request->call_to_action,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reklama to\'liq yaratildi!',
                'data' => [
                    'campaign_id' => $campaignId,
                    'adset_id' => $adSetId,
                    'ad_id' => $adId,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Create full ad error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get billing event for optimization goal
     */
    private function getBillingEventForGoal(string $goal): string
    {
        return match ($goal) {
            'LINK_CLICKS' => 'LINK_CLICKS',
            default => 'IMPRESSIONS',
        };
    }
}
