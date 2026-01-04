<?php

namespace App\Http\Controllers;

use App\Models\AdIntegration;
use App\Models\Business;
use App\Models\GoogleAdsCampaign;
use App\Models\GoogleAdsAdGroup;
use App\Models\GoogleAdsKeyword;
use App\Services\GoogleAdsSyncService;
use App\Services\GoogleAdsCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GoogleAdsCampaignController extends Controller
{
    public function __construct(
        protected GoogleAdsSyncService $syncService,
        protected GoogleAdsCampaignService $campaignService
    ) {}

    /**
     * Get campaigns list with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $integration = $this->getGoogleAdsIntegration($business->id);

        if (!$integration) {
            return response()->json([
                'campaigns' => [],
                'summary' => $this->getEmptySummary(),
                'pagination' => null,
                'error' => 'Google Ads integratsiyasi topilmadi',
            ]);
        }

        $query = GoogleAdsCampaign::where('ad_integration_id', $integration->id)
            ->where('status', '!=', 'REMOVED');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel_type')) {
            $query->where('advertising_channel_type', $request->channel_type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortField = $request->get('sort', 'total_cost');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $campaigns = $query->paginate($perPage);

        // Get summary
        $summary = $this->getCampaignsSummary($integration->id, $request);

        return response()->json([
            'campaigns' => $campaigns->items(),
            'summary' => $summary,
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    /**
     * Show campaign detail page
     */
    public function showPage(Request $request, string $id): InertiaResponse
    {
        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)
            ->findOrFail($id);

        return Inertia::render('Business/GoogleAdsCampaigns/Show', [
            'campaign' => $campaign,
            'businessId' => $business->id,
        ]);
    }

    /**
     * Get single campaign with insights
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)
            ->with(['insights' => function ($query) {
                $query->orderBy('date', 'desc')->limit(30);
            }])
            ->findOrFail($id);

        return response()->json([
            'campaign' => $campaign,
            'insights' => $campaign->insights,
        ]);
    }

    /**
     * Get filter options
     */
    public function filters(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $integration = $this->getGoogleAdsIntegration($business->id);

        if (!$integration) {
            return response()->json([
                'statuses' => [],
                'channel_types' => [],
            ]);
        }

        $statuses = GoogleAdsCampaign::where('ad_integration_id', $integration->id)
            ->distinct()
            ->pluck('status')
            ->map(fn($s) => ['value' => $s, 'label' => $this->formatStatus($s)]);

        $channelTypes = GoogleAdsCampaign::where('ad_integration_id', $integration->id)
            ->distinct()
            ->pluck('advertising_channel_type')
            ->map(fn($t) => ['value' => $t, 'label' => $this->formatChannelType($t)]);

        return response()->json([
            'statuses' => $statuses,
            'channel_types' => $channelTypes,
            'sort_options' => [
                ['value' => 'total_cost', 'label' => 'Xarajat'],
                ['value' => 'total_impressions', 'label' => 'Ko\'rishlar'],
                ['value' => 'total_clicks', 'label' => 'Kliklar'],
                ['value' => 'avg_ctr', 'label' => 'CTR'],
                ['value' => 'avg_cpc', 'label' => 'CPC'],
                ['value' => 'name', 'label' => 'Nom'],
                ['value' => 'created_at', 'label' => 'Yaratilgan sana'],
            ],
        ]);
    }

    /**
     * Sync campaigns data
     */
    public function sync(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $integration = $this->getGoogleAdsIntegration($business->id);

        if (!$integration) {
            return response()->json([
                'success' => false,
                'error' => 'Google Ads integratsiyasi topilmadi',
            ], 400);
        }

        try {
            $results = $this->syncService
                ->initialize($integration)
                ->fullSync();

            return response()->json([
                'success' => true,
                'message' => 'Sinxronlash muvaffaqiyatli',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new campaign
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'channel_type' => 'required|string|in:SEARCH,DISPLAY,VIDEO,SHOPPING,PERFORMANCE_MAX',
            'daily_budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $business = $this->getCurrentBusiness($request);
        $integration = $this->getGoogleAdsIntegration($business->id);

        if (!$integration) {
            return response()->json([
                'success' => false,
                'error' => 'Google Ads integratsiyasi topilmadi',
            ], 400);
        }

        try {
            $campaign = $this->campaignService
                ->initialize($integration)
                ->createCampaign($request->all());

            return response()->json([
                'success' => true,
                'campaign' => $campaign,
                'message' => 'Kampaniya yaratildi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update campaign
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'daily_budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)->findOrFail($id);
        $integration = $this->getGoogleAdsIntegration($business->id);

        try {
            $campaign = $this->campaignService
                ->initialize($integration)
                ->updateCampaign($campaign, $request->all());

            return response()->json([
                'success' => true,
                'campaign' => $campaign,
                'message' => 'Kampaniya yangilandi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update campaign status (pause/resume)
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:ENABLED,PAUSED',
        ]);

        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)->findOrFail($id);
        $integration = $this->getGoogleAdsIntegration($business->id);

        try {
            $success = $this->campaignService
                ->initialize($integration)
                ->updateCampaignStatus($campaign, $request->status);

            $statusLabel = $request->status === 'ENABLED' ? 'faollashtirildi' : 'to\'xtatildi';

            return response()->json([
                'success' => $success,
                'message' => "Kampaniya {$statusLabel}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete campaign
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)->findOrFail($id);
        $integration = $this->getGoogleAdsIntegration($business->id);

        try {
            $this->campaignService
                ->initialize($integration)
                ->deleteCampaign($campaign);

            return response()->json([
                'success' => true,
                'message' => 'Kampaniya o\'chirildi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get ad groups for campaign
     */
    public function getAdGroups(Request $request, string $campaignId): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)->findOrFail($campaignId);

        $adGroups = $campaign->adGroups()
            ->where('status', '!=', 'REMOVED')
            ->orderBy('total_clicks', 'desc')
            ->get();

        return response()->json([
            'ad_groups' => $adGroups,
        ]);
    }

    /**
     * Get keywords for ad group
     */
    public function getKeywords(Request $request, string $adGroupId): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adGroup = GoogleAdsAdGroup::where('business_id', $business->id)->findOrFail($adGroupId);

        $keywords = $adGroup->keywords()
            ->where('status', '!=', 'REMOVED')
            ->orderBy('total_clicks', 'desc')
            ->get();

        return response()->json([
            'keywords' => $keywords,
        ]);
    }

    /**
     * Add keywords to ad group
     */
    public function addKeywords(Request $request, string $adGroupId): JsonResponse
    {
        $request->validate([
            'keywords' => 'required|array|min:1',
            'keywords.*.text' => 'required|string|max:255',
            'keywords.*.match_type' => 'required|string|in:EXACT,PHRASE,BROAD',
        ]);

        $business = $this->getCurrentBusiness($request);
        $adGroup = GoogleAdsAdGroup::where('business_id', $business->id)->findOrFail($adGroupId);
        $integration = $this->getGoogleAdsIntegration($business->id);

        try {
            $keywords = $this->campaignService
                ->initialize($integration)
                ->addKeywords($adGroup, $request->keywords);

            return response()->json([
                'success' => true,
                'keywords' => $keywords,
                'message' => count($keywords) . ' ta kalit so\'z qo\'shildi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove keyword
     */
    public function removeKeyword(Request $request, string $keywordId): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $keyword = GoogleAdsKeyword::where('business_id', $business->id)->findOrFail($keywordId);
        $integration = $this->getGoogleAdsIntegration($business->id);

        try {
            $this->campaignService
                ->initialize($integration)
                ->removeKeyword($keyword);

            return response()->json([
                'success' => true,
                'message' => 'Kalit so\'z o\'chirildi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign insights
     */
    public function getInsights(Request $request, string $campaignId): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $campaign = GoogleAdsCampaign::where('business_id', $business->id)->findOrFail($campaignId);

        $days = $request->get('days', 30);
        $insights = $campaign->insights()
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'insights' => $insights,
        ]);
    }

    // ========== HELPER METHODS ==========

    protected function getCurrentBusiness(Request $request): Business
    {
        return $request->user()->currentBusiness ?? $request->user()->businesses()->first();
    }

    protected function getGoogleAdsIntegration(string $businessId): ?AdIntegration
    {
        return AdIntegration::where('business_id', $businessId)
            ->where('platform', 'google_ads')
            ->where('is_active', true)
            ->first();
    }

    private function getCampaignsSummary(int $integrationId, Request $request): array
    {
        $query = GoogleAdsCampaign::where('ad_integration_id', $integrationId)
            ->where('status', '!=', 'REMOVED');

        // Apply same filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('channel_type')) {
            $query->where('advertising_channel_type', $request->channel_type);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_campaigns,
            SUM(CASE WHEN status = "ENABLED" THEN 1 ELSE 0 END) as active_campaigns,
            SUM(total_cost) as total_spend,
            SUM(total_impressions) as total_impressions,
            SUM(total_clicks) as total_clicks,
            SUM(total_conversions) as total_conversions,
            AVG(avg_ctr) as avg_ctr,
            AVG(avg_cpc) as avg_cpc
        ')->first();

        return [
            'total_campaigns' => $summary->total_campaigns ?? 0,
            'active_campaigns' => $summary->active_campaigns ?? 0,
            'total_spend' => $summary->total_spend ?? 0,
            'total_impressions' => $summary->total_impressions ?? 0,
            'total_clicks' => $summary->total_clicks ?? 0,
            'total_conversions' => $summary->total_conversions ?? 0,
            'avg_ctr' => round($summary->avg_ctr ?? 0, 2),
            'avg_cpc' => round($summary->avg_cpc ?? 0, 0),
        ];
    }

    private function getEmptySummary(): array
    {
        return [
            'total_campaigns' => 0,
            'active_campaigns' => 0,
            'total_spend' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'avg_ctr' => 0,
            'avg_cpc' => 0,
        ];
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'ENABLED' => 'Faol',
            'PAUSED' => 'Pauza',
            'REMOVED' => 'O\'chirilgan',
            default => $status,
        };
    }

    private function formatChannelType(string $type): string
    {
        return match ($type) {
            'SEARCH' => 'Qidiruv',
            'DISPLAY' => 'Display',
            'VIDEO' => 'Video',
            'SHOPPING' => 'Shopping',
            'PERFORMANCE_MAX' => 'Performance Max',
            default => $type,
        };
    }
}
