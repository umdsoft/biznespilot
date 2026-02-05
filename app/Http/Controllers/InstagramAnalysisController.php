<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\InstagramAccount;
use App\Models\Integration;
use App\Services\InstagramDataService;
use App\Services\InstagramInsightsService;
use App\Services\InstagramSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstagramAnalysisController extends Controller
{
    public function __construct(
        protected InstagramDataService $dataService,
        protected InstagramSyncService $syncService,
        protected InstagramInsightsService $insightsService
    ) {}

    /**
     * Determine panel type from request
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();
        if (str_contains($prefix, 'marketing')) {
            return 'marketing';
        }
        if (str_contains($prefix, 'finance')) {
            return 'finance';
        }
        if (str_contains($prefix, 'operator')) {
            return 'operator';
        }
        if (str_contains($prefix, 'saleshead')) {
            return 'saleshead';
        }
        // For /integrations route, check referer
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/marketing')) {
            return 'marketing';
        }
        if (str_contains($referer, '/finance')) {
            return 'finance';
        }
        if (str_contains($referer, '/operator')) {
            return 'operator';
        }
        if (str_contains($referer, '/saleshead')) {
            return 'saleshead';
        }

        return 'business';
    }

    /**
     * Display Instagram Analysis dashboard
     */
    public function index(Request $request): Response
    {
        $business = $this->getCurrentBusiness($request);

        // Check for instagram-specific integration first (created after OAuth if Instagram found)
        $instagramIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'instagram')
            ->first();

        // Meta Ads integration (parent that holds Instagram accounts)
        $metaIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        $instagramAccounts = collect([]);
        $selectedAccount = null;

        // Use instagram integration status for display, but get accounts from meta_ads
        $isConnected = ($instagramIntegration && $instagramIntegration->status === 'connected')
            || ($metaIntegration && $metaIntegration->status === 'connected');

        if ($isConnected && $metaIntegration) {
            $instagramAccounts = InstagramAccount::where('integration_id', $metaIntegration->id)->get();

            // First try to get saved account from business
            if ($business->instagram_account_id) {
                $selectedAccount = $instagramAccounts->firstWhere('id', $business->instagram_account_id);
            }

            // Fallback to is_primary or first account
            if (! $selectedAccount) {
                $selectedAccount = $instagramAccounts->where('is_primary', true)->first()
                    ?? $instagramAccounts->first();

                // Auto-save the first account to business
                if ($selectedAccount) {
                    $business->update(['instagram_account_id' => $selectedAccount->id]);
                }
            }
        }

        // Use instagram integration for display if available, otherwise meta_ads
        $displayIntegration = $instagramIntegration ?? $metaIntegration;

        return Inertia::render('Shared/InstagramAnalysis/Index', [
            'panelType' => $this->getPanelType($request),
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'metaIntegration' => $displayIntegration ? [
                'id' => $displayIntegration->id,
                'type' => $displayIntegration->type, // 'instagram' or 'meta_ads'
                'status' => $displayIntegration->status,
                'connected_at' => $displayIntegration->connected_at?->format('d.m.Y H:i'),
                'last_sync_at' => $displayIntegration->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
            'instagramAccounts' => $instagramAccounts->map(fn ($acc) => [
                'id' => $acc->id,
                'instagram_id' => $acc->instagram_id,
                'username' => $acc->username,
                'name' => $acc->name,
                'profile_picture_url' => $acc->profile_picture_url,
                'followers_count' => $acc->followers_count,
                'is_primary' => $acc->is_primary ?? false,
            ])->values(),
            'selectedAccount' => $selectedAccount ? [
                'id' => $selectedAccount->id,
                'instagram_id' => $selectedAccount->instagram_id,
                'username' => $selectedAccount->username,
                'name' => $selectedAccount->name,
                'profile_picture_url' => $selectedAccount->profile_picture_url,
                'followers_count' => $selectedAccount->followers_count,
                'follows_count' => $selectedAccount->follows_count,
                'media_count' => $selectedAccount->media_count,
                'last_sync_at' => $selectedAccount->last_sync_at?->format('d.m.Y H:i'),
            ] : null,
        ]);
    }

    /**
     * Get overview data
     */
    public function getOverview(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $datePreset = $request->period ?? 'last_30d';

        try {
            $data = $this->dataService->getOverview($account->id, $datePreset);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get media performance
     */
    public function getMediaPerformance(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $datePreset = $request->period ?? 'last_30d';

        try {
            $data = $this->dataService->getMediaPerformance($account->id, $datePreset);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get reels analytics
     */
    public function getReelsAnalytics(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $datePreset = $request->period ?? 'last_30d';

        try {
            $data = $this->dataService->getReelsAnalytics($account->id, $datePreset);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get engagement analytics
     */
    public function getEngagementAnalytics(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $datePreset = $request->period ?? 'last_30d';

        try {
            $data = $this->dataService->getEngagementAnalytics($account->id, $datePreset);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get audience demographics
     */
    public function getAudienceDemographics(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $data = $this->dataService->getAudienceDemographics($account->id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get hashtag performance
     */
    public function getHashtagPerformance(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $limit = (int) ($request->limit ?? 20);

        try {
            $data = $this->dataService->getHashtagPerformance($account->id, $limit);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get growth trend
     */
    public function getGrowthTrend(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $days = (int) ($request->days ?? 30);

        try {
            $data = $this->dataService->getGrowthTrend($account->id, $days);

            return response()->json(['trend' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get content comparison
     */
    public function getContentComparison(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $datePreset = $request->period ?? 'last_30d';

        try {
            $data = $this->dataService->getContentComparison($account->id, $datePreset);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get business insights - actionable recommendations
     */
    public function getBusinessInsights(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $data = $this->insightsService->getBusinessInsights($account->id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get content winners - best performing posts by category
     */
    public function getContentWinners(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $data = $this->insightsService->getContentWinners($account->id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get growth drivers - which content brings followers
     */
    public function getGrowthDrivers(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $data = $this->insightsService->analyzeGrowthDrivers($account->id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get viral analysis - what makes content go viral
     */
    public function getViralAnalysis(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $data = $this->insightsService->analyzeViralPotential($account->id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get AI insights
     */
    public function getAIInsights(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (! $account) {
            return response()->json(['success' => false, 'error' => 'Account not found']);
        }

        $datePreset = $request->period ?? 'last_30d';

        try {
            $summary = $this->dataService->getAISummary($account->id, $datePreset);
            $insights = $this->generateInsightsFromSummary($summary);

            return response()->json([
                'success' => true,
                'performance_summary' => $insights['summary'],
                'recommendations' => $insights['recommendations'],
                'content_insights' => $insights['content'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Select Instagram account as primary and save to business
     */
    public function selectAccount(Request $request): JsonResponse
    {
        $request->validate(['account_id' => 'required|uuid|exists:instagram_accounts,id']);

        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        if (! $integration) {
            return response()->json(['error' => 'Not connected'], 400);
        }

        // Verify account belongs to this integration
        $account = InstagramAccount::where('integration_id', $integration->id)
            ->where('id', $request->account_id)
            ->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        // Save selected account to business (persistent selection)
        $business->update(['instagram_account_id' => $account->id]);

        // Also set as primary for backward compatibility
        InstagramAccount::where('integration_id', $integration->id)
            ->update(['is_primary' => false]);

        $account->update(['is_primary' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Check Instagram-specific permissions on the token
     */
    public function checkPermissions(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (! $integration) {
            return response()->json([
                'connected' => false,
                'message' => 'Meta hisobingiz ulanmagan',
            ]);
        }

        $credentials = json_decode($integration->credentials, true);
        $accessToken = $credentials['access_token'] ?? null;

        if (! $accessToken) {
            return response()->json([
                'connected' => true,
                'has_permissions' => false,
                'message' => 'Access token topilmadi',
            ]);
        }

        try {
            // Check current permissions
            $response = \Http::get('https://graph.facebook.com/' . config('services.meta.api_version', 'v24.0') . '/me/permissions', [
                'access_token' => $accessToken,
            ]);

            $grantedPermissions = [];
            foreach ($response->json('data', []) as $perm) {
                if ($perm['status'] === 'granted') {
                    $grantedPermissions[] = $perm['permission'];
                }
            }

            $requiredPermissions = [
                'pages_show_list' => 'Facebook sahifalarini ko\'rish',
                'pages_read_engagement' => 'Sahifa engagement ma\'lumotlari',
                'instagram_basic' => 'Instagram akkaunti',
                'instagram_manage_insights' => 'Instagram statistikasi',
            ];

            $missingPermissions = [];
            foreach ($requiredPermissions as $perm => $label) {
                if (! in_array($perm, $grantedPermissions)) {
                    $missingPermissions[$perm] = $label;
                }
            }

            // Check if we have Facebook Pages with Instagram
            $hasInstagramAccount = false;
            if (in_array('pages_show_list', $grantedPermissions)) {
                $pagesResponse = \Http::get('https://graph.facebook.com/' . config('services.meta.api_version', 'v24.0') . '/me/accounts', [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,instagram_business_account',
                ]);

                foreach ($pagesResponse->json('data', []) as $page) {
                    if (isset($page['instagram_business_account'])) {
                        $hasInstagramAccount = true;
                        break;
                    }
                }
            }

            return response()->json([
                'connected' => true,
                'has_permissions' => empty($missingPermissions),
                'granted_permissions' => $grantedPermissions,
                'missing_permissions' => $missingPermissions,
                'has_instagram_account' => $hasInstagramAccount,
                'message' => empty($missingPermissions)
                    ? ($hasInstagramAccount ? 'Tayyor' : 'Facebook sahifangizga Instagram Business akkaunti ulang')
                    : 'Instagram uchun qo\'shimcha ruxsatlar kerak',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'connected' => true,
                'has_permissions' => false,
                'message' => 'Token tekshirishda xatolik: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger manual sync - runs synchronously for immediate feedback
     */
    public function sync(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (! $integration) {
            return response()->json(['error' => 'Meta hisobingiz ulanmagan'], 400);
        }

        $credentials = json_decode($integration->credentials, true);
        $accessToken = $credentials['access_token'] ?? null;

        if (! $accessToken) {
            return response()->json(['error' => 'Access token topilmadi'], 400);
        }

        try {
            // First check if we have required permissions
            $permResponse = \Http::get('https://graph.facebook.com/' . config('services.meta.api_version', 'v24.0') . '/me/permissions', [
                'access_token' => $accessToken,
            ]);

            $grantedPermissions = [];
            foreach ($permResponse->json('data', []) as $perm) {
                if ($perm['status'] === 'granted') {
                    $grantedPermissions[] = $perm['permission'];
                }
            }

            // Check for pages_show_list - minimum required for Instagram sync
            if (! in_array('pages_show_list', $grantedPermissions)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Instagram uchun qo\'shimcha ruxsatlar kerak. Meta Ads integratsiyasini qayta ulang va "pages_show_list", "instagram_basic" ruxsatlarini bering.',
                    'missing_permissions' => ['pages_show_list', 'instagram_basic', 'instagram_manage_insights'],
                    'needs_reconnect' => true,
                ], 400);
            }

            // Check if there are any Facebook Pages with Instagram accounts
            $pagesResponse = \Http::get('https://graph.facebook.com/' . config('services.meta.api_version', 'v24.0') . '/me/accounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,instagram_business_account',
            ]);

            $pages = $pagesResponse->json('data', []);

            if (empty($pages)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sizda hech qanday Facebook sahifasi topilmadi. Avval Facebook Business sahifasi yarating.',
                    'no_pages' => true,
                ], 400);
            }

            $hasInstagram = false;
            foreach ($pages as $page) {
                if (isset($page['instagram_business_account'])) {
                    $hasInstagram = true;
                    break;
                }
            }

            if (! $hasInstagram) {
                $pageNames = array_column($pages, 'name');

                return response()->json([
                    'success' => false,
                    'error' => 'Facebook sahifalaringizga Instagram Business akkaunti ulanmagan. Sahifalar: '.implode(', ', $pageNames),
                    'pages' => $pageNames,
                    'no_instagram' => true,
                ], 400);
            }

            // Run sync synchronously for immediate feedback
            $this->syncService->initialize($integration);
            $results = $this->syncService->fullSync();

            if (($results['accounts'] ?? 0) === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Instagram akkauntlari topilmadi. Facebook sahifangizga Instagram Business akkaunti ulang.',
                    'no_accounts' => true,
                ]);
            }

            return response()->json([
                'success' => $results['success'] ?? true,
                'message' => 'Sinxronizatsiya muvaffaqiyatli yakunlandi',
                'accounts' => $results['accounts'] ?? 0,
                'media' => $results['media'] ?? 0,
                'insights' => $results['insights'] ?? 0,
                'errors' => $results['errors'] ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Instagram sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Sinxronizatsiya xatosi: '.$e->getMessage(),
            ], 500);
        }
    }

    // ==================== HELPER METHODS ====================

    protected function getCurrentBusiness(Request $request): Business
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (! $businessId) {
            $business = auth()->user()->businesses()->first();
            if (! $business) {
                abort(400, 'Biznes tanlanmagan');
            }
            // Save to session for persistence
            session(['current_business_id' => $business->id]);

            return $business;
        }

        // Save to session when coming from request
        if ($request->has('business_id')) {
            session(['current_business_id' => $businessId]);
        }

        return Business::findOrFail($businessId);
    }

    protected function getSelectedAccount(Request $request): ?InstagramAccount
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->first();

        if (! $integration) {
            return null;
        }

        // First try to get saved account from business
        if ($business->instagram_account_id) {
            $account = InstagramAccount::where('integration_id', $integration->id)
                ->where('id', $business->instagram_account_id)
                ->first();
            if ($account) {
                return $account;
            }
        }

        // Fallback to is_primary
        $account = InstagramAccount::where('integration_id', $integration->id)
            ->where('is_primary', true)
            ->first();

        if (! $account) {
            $account = InstagramAccount::where('integration_id', $integration->id)->first();
        }

        return $account;
    }

    protected function generateInsightsFromSummary(array $data): array
    {
        $account = $data['account'] ?? [];
        $metrics = $data['metrics'] ?? [];
        $changes = $data['changes'] ?? [];
        $reels = $data['reels_summary'] ?? [];
        $bestPosting = $data['best_posting'] ?? [];

        $followers = $account['followers_count'] ?? 0;
        $engagementRate = $account['engagement_rate'] ?? 0;
        $reach = $metrics['reach'] ?? 0;
        $impressions = $metrics['impressions'] ?? 0;

        // Performance summary
        $summary = "Sizning @{$account['username']} akkauntingizda {$followers} ta follower bor. ";
        $summary .= "Oxirgi davrda {$reach} ta foydalanuvchiga yetib bordingiz. ";
        $summary .= "O'rtacha engagement rate: {$engagementRate}%.";

        if (! empty($reels)) {
            $summary .= " Reelslaringiz o'rtacha ".number_format($reels['avg_plays'] ?? 0)." marta ko'rildi.";
        }

        // Recommendations
        $recommendations = [];

        if ($engagementRate < 1) {
            $recommendations[] = "Engagement rate past (< 1%). Ko'proq savol so'rang va auditoriya bilan muloqot qiling.";
        } elseif ($engagementRate > 3) {
            $recommendations[] = 'Engagement rate yaxshi (> 3%)! Shu formatda davom eting.';
        }

        $reachChange = $changes['reach'] ?? 0;
        if ($reachChange < -10) {
            $recommendations[] = 'Reach '.abs($reachChange)."% ga kamaygan. Yangi hashtaglar va formatlarni sinab ko'ring.";
        } elseif ($reachChange > 20) {
            $recommendations[] = 'Reach '.$reachChange."% ga o'sgan! Qaysi kontent yaxshi ishlaganini tahlil qiling.";
        }

        // Best posting times
        $bestDays = $bestPosting['days'] ?? [];
        $bestHours = $bestPosting['hours'] ?? [];

        if (! empty($bestDays)) {
            $recommendations[] = 'Eng yaxshi post qilish kunlari: '.implode(', ', $bestDays).'.';
        }

        if (! empty($bestHours)) {
            $recommendations[] = 'Eng faol soatlar: '.implode(', ', array_map(fn ($h) => "{$h}:00", $bestHours)).'.';
        }

        // Top hashtags
        $topHashtags = $data['top_hashtags'] ?? [];
        if (! empty($topHashtags)) {
            $tags = array_column($topHashtags, 'hashtag');
            $recommendations[] = 'Eng samarali hashtaglar: '.implode(' ', array_slice($tags, 0, 5));
        }

        // Content insights
        $bestPost = $data['top_content']['best_post'] ?? null;
        $bestReel = $data['top_content']['best_reel'] ?? null;

        $content = '';
        if ($bestPost) {
            $content .= 'Eng yaxshi post: '.($bestPost['engagement_rate'] ?? 0).'% engagement. ';
        }
        if ($bestReel) {
            $content .= 'Eng yaxshi reel: '.number_format($bestReel['plays'] ?? 0)." ko'rish.";
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Kontentingiz yaxshi ishlayapti. Izchil post qilishni davom ettiring.';
        }

        return [
            'summary' => $summary,
            'recommendations' => $recommendations,
            'content' => $content,
        ];
    }
}
