<?php

namespace App\Http\Controllers;

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
use App\Jobs\SyncMetaInsightsJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TargetAnalysisController extends Controller
{
    public function __construct(
        protected TargetAnalysisService $analysisService,
        protected MetaAdsService $metaService,
        protected MetaOAuthService $oauthService,
        protected MetaDataService $metaDataService,
        protected MetaSyncService $metaSyncService
    ) {}

    /**
     * Display target analysis dashboard
     */
    public function index(Request $request): Response
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (!$businessId) {
            return Inertia::render('Business/TargetAnalysis/Index', [
                'error' => 'Biznes tanlanmagan',
                'analysis' => null,
            ]);
        }

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Sizda bu biznesga kirish huquqi yo\'q');
        }

        $analysis = $this->analysisService->getTargetAnalysis($business);

        // Meta Ads integration data
        $metaIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        $metaAdAccounts = collect([]);
        $selectedMetaAccount = null;

        if ($metaIntegration && $metaIntegration->status === 'connected') {
            $metaAdAccounts = MetaAdAccount::where('integration_id', $metaIntegration->id)->get();
            $selectedMetaAccount = $metaAdAccounts->where('is_primary', true)->first()
                ?? $metaAdAccounts->first();
        }

        return Inertia::render('Business/TargetAnalysis/Index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
            ],
            'analysis' => $analysis,
            'lastUpdated' => now()->format('d.m.Y H:i'),
            // Meta Ads data
            'metaIntegration' => $metaIntegration ? [
                'id' => $metaIntegration->id,
                'status' => $metaIntegration->status,
                'connected_at' => $metaIntegration->connected_at?->format('d.m.Y H:i'),
                'expires_at' => $metaIntegration->expires_at?->format('d.m.Y H:i'),
                'last_sync_at' => $metaIntegration->last_sync_at?->format('d.m.Y H:i'),
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
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 400);
        }

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $months = $request->input('months', 6);

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
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
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $limit = $request->input('limit', 10);

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

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

        $state = Str::random(40);
        session(['meta_oauth_state' => $state, 'meta_oauth_business_id' => $business->id]);

        $redirectUri = route('business.target-analysis.meta.callback');
        $authUrl = $this->oauthService->getAuthorizationUrl($redirectUri, $state);

        return response()->json(['url' => $authUrl]);
    }

    /**
     * Handle Meta OAuth Callback
     */
    public function handleMetaCallback(Request $request)
    {
        \Log::info('=== Meta OAuth Callback Started ===', [
            'all_params' => $request->all(),
            'state' => $request->state,
            'session_state' => session('meta_oauth_state'),
            'business_id' => session('meta_oauth_business_id'),
            'current_business_id' => session('current_business_id'),
            'has_code' => $request->has('code'),
            'has_error' => $request->has('error'),
        ]);

        // Get business ID - try multiple sources
        $businessId = session('meta_oauth_business_id')
            ?? session('current_business_id')
            ?? auth()->user()?->businesses()->first()?->id;

        \Log::info('Meta OAuth: Business ID resolved', ['business_id' => $businessId]);

        if (!$businessId) {
            \Log::error('Meta OAuth: No business found');
            return redirect()->route('business.target-analysis.index')
                ->with('error', 'Biznes topilmadi. Iltimos, qayta urinib ko\'ring.');
        }

        // Check for error from Facebook
        if ($request->has('error')) {
            \Log::error('Meta OAuth: Error from Facebook', [
                'error' => $request->error,
                'description' => $request->error_description,
            ]);
            return redirect()->route('business.target-analysis.index', ['business_id' => $businessId])
                ->with('error', $request->error_description ?? 'OAuth xatolik');
        }

        // Ensure we have code
        if (!$request->has('code')) {
            \Log::error('Meta OAuth: No code received');
            return redirect()->route('business.target-analysis.index', ['business_id' => $businessId])
                ->with('error', 'Authorization code olinmadi');
        }

        try {
            $redirectUri = route('business.target-analysis.meta.callback');
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

            // Clear OAuth session data
            session()->forget(['meta_oauth_state', 'meta_oauth_business_id']);

            return redirect()->route('business.target-analysis.index', ['business_id' => $businessId])
                ->with('success', 'Meta Ads muvaffaqiyatli ulandi! "Ma\'lumotlarni yuklash" tugmasini bosib ma\'lumotlarni oling.');

        } catch (\Exception $e) {
            \Log::error('Meta OAuth: Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->route('business.target-analysis.index', ['business_id' => $businessId])
                ->with('error', 'Ulanishda xatolik: ' . $e->getMessage());
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
     * Select Meta ad account as primary
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

        // Reset all
        MetaAdAccount::where('integration_id', $integration->id)
            ->update(['is_primary' => false]);

        // Set selected as primary
        MetaAdAccount::where('integration_id', $integration->id)
            ->where('meta_account_id', $request->account_id)
            ->update(['is_primary' => true]);

        return response()->json(['success' => true]);
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

    protected function getCurrentBusiness(Request $request): Business
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (!$businessId) {
            $business = auth()->user()->businesses()->first();
            if (!$business) {
                abort(400, 'Biznes tanlanmagan');
            }
            return $business;
        }

        return Business::findOrFail($businessId);
    }

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

            MetaAdAccount::updateOrCreate(
                [
                    'integration_id' => $integration->id,
                    'meta_account_id' => $account['id'],
                ],
                [
                    'business_id' => $integration->business_id,
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
}
