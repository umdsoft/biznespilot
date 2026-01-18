<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Integration;
use App\Models\MetaAd;
use App\Models\MetaAdAccount;
use App\Models\MetaAdSet;
use App\Models\MetaCampaign;
use App\Models\MetaCampaignInsight;
use App\Services\MetaSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MetaCampaignController extends Controller
{
    public function __construct(
        protected MetaSyncService $syncService
    ) {}

    /**
     * Get campaigns list with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        \Log::info('MetaCampaignController::index', [
            'business_id' => $business->id,
            'ad_account_id' => $adAccount?->id,
            'ad_account_name' => $adAccount?->name,
            'request_business_id' => $request->input('business_id'),
            'session_business_id' => session('current_business_id'),
        ]);

        if (! $adAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Meta Ad hesob tanlanmagan',
                'data' => [],
                'summary' => $this->getEmptySummary(),
                'pagination' => $this->getEmptyPagination(),
            ]);
        }

        $query = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id);

        \Log::info('MetaCampaignController: Campaign query count', [
            'count' => (clone $query)->count(),
        ]);

        // Apply filters
        if ($request->filled('status')) {
            $statuses = \is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('effective_status', $statuses);
        }

        if ($request->filled('objective')) {
            $query->where('objective', $request->objective);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Date filter for insights
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Filter campaigns that have insights in date range
            $query->whereHas('insights', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo]);
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'total_spend');
        $sortDir = $request->get('direction', 'desc');

        $allowedSorts = ['total_spend', 'total_impressions', 'total_clicks', 'avg_ctr', 'avg_cpc', 'name', 'created_time', 'effective_status'];
        if (\in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('total_spend', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);
        $campaigns = $query->paginate($perPage);

        // Get summary stats
        $summary = $this->getCampaignsSummary($adAccount->id, $request);

        return response()->json([
            'success' => true,
            'data' => $campaigns->items(),
            'summary' => $summary,
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
                'from' => $campaigns->firstItem(),
                'to' => $campaigns->lastItem(),
            ],
        ]);
    }

    /**
     * Show create ad page (Inertia)
     */
    public function createPage(Request $request): InertiaResponse|\Illuminate\Http\RedirectResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        if (! $adAccount) {
            return redirect()->route('business.target-analysis.index')
                ->with('error', 'Meta Ad hesob tanlanmagan');
        }

        return Inertia::render('Business/MetaCampaigns/Create', [
            'businessId' => $business->id,
            'adAccountId' => $adAccount->id,
            'currency' => $adAccount->currency ?? 'USD',
        ]);
    }

    /**
     * Show campaign detail page (Inertia)
     */
    public function showPage(Request $request, string $id): InertiaResponse|\Illuminate\Http\RedirectResponse
    {
        \Log::info('MetaCampaignController::showPage called', ['id' => $id]);

        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        \Log::info('showPage: Got business and account', [
            'business_id' => $business->id,
            'ad_account_id' => $adAccount?->id,
        ]);

        if (! $adAccount) {
            \Log::warning('showPage: No ad account found');

            return redirect()->route('business.target-analysis.index')
                ->with('error', 'Meta Ad hesob tanlanmagan');
        }

        $campaign = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id)
            ->where('id', $id)
            ->first();

        \Log::info('showPage: Campaign query', [
            'campaign_found' => $campaign ? true : false,
            'campaign_name' => $campaign?->name,
        ]);

        if (! $campaign) {
            \Log::warning('showPage: Campaign not found');

            return redirect()->route('business.target-analysis.index')
                ->with('error', 'Kampaniya topilmadi');
        }

        return Inertia::render('Business/MetaCampaigns/Show', [
            'campaign' => $campaign,
            'currency' => $adAccount->currency ?? 'USD',
            'businessId' => $business->id,
        ]);
    }

    /**
     * Get single campaign with insights
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        if (! $adAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Meta Ad hesob tanlanmagan',
            ], 400);
        }

        $campaign = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id)
            ->where('id', $id)
            ->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Kampaniya topilmadi',
            ], 404);
        }

        // Get daily insights (last 30 days)
        $insights = MetaCampaignInsight::where('campaign_id', $campaign->id)
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'insights' => $insights,
            ],
        ]);
    }

    /**
     * Get filter options
     */
    public function filters(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        if (! $adAccount) {
            return response()->json([
                'success' => true,
                'data' => [
                    'statuses' => [],
                    'objectives' => [],
                    'sort_options' => $this->getSortOptions(),
                ],
            ]);
        }

        // Get unique statuses
        $statuses = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id)
            ->whereNotNull('effective_status')
            ->distinct()
            ->pluck('effective_status')
            ->map(fn ($s) => [
                'value' => $s,
                'label' => $this->formatStatus($s),
            ]);

        // Get unique objectives
        $objectives = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id)
            ->whereNotNull('objective')
            ->distinct()
            ->pluck('objective')
            ->map(fn ($o) => [
                'value' => $o,
                'label' => $this->formatObjective($o),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => $statuses,
                'objectives' => $objectives,
                'sort_options' => $this->getSortOptions(),
            ],
        ]);
    }

    /**
     * Trigger sync for campaigns
     */
    public function sync(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);

        $integration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (! $integration) {
            return response()->json([
                'success' => false,
                'message' => 'Meta Ads ulanmagan',
            ], 400);
        }

        try {
            $this->syncService->initialize($integration);
            $results = $this->syncService->fullSync();

            return response()->json([
                'success' => $results['success'],
                'message' => "Sinxronlash tugadi: {$results['campaigns']} ta kampaniya, {$results['insights']} ta insight.",
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sinxronlashda xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaigns summary stats
     */
    private function getCampaignsSummary(string $adAccountId, Request $request): array
    {
        $query = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId);

        // Apply same filters as main query
        if ($request->filled('status')) {
            $statuses = \is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('effective_status', $statuses);
        }

        if ($request->filled('objective')) {
            $query->where('objective', $request->objective);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_campaigns,
            COUNT(CASE WHEN effective_status = "ACTIVE" THEN 1 END) as active_campaigns,
            COALESCE(SUM(total_spend), 0) as total_spend,
            COALESCE(SUM(total_impressions), 0) as total_impressions,
            COALESCE(SUM(total_reach), 0) as total_reach,
            COALESCE(SUM(total_clicks), 0) as total_clicks,
            COALESCE(AVG(avg_ctr), 0) as avg_ctr,
            COALESCE(AVG(avg_cpc), 0) as avg_cpc
        ')->first();

        return [
            'total_campaigns' => (int) ($summary->total_campaigns ?? 0),
            'active_campaigns' => (int) ($summary->active_campaigns ?? 0),
            'total_spend' => round((float) ($summary->total_spend ?? 0), 2),
            'total_impressions' => (int) ($summary->total_impressions ?? 0),
            'total_reach' => (int) ($summary->total_reach ?? 0),
            'total_clicks' => (int) ($summary->total_clicks ?? 0),
            'avg_ctr' => round((float) ($summary->avg_ctr ?? 0), 2),
            'avg_cpc' => round((float) ($summary->avg_cpc ?? 0), 2),
        ];
    }

    /**
     * Get empty summary for when no account is selected
     */
    private function getEmptySummary(): array
    {
        return [
            'total_campaigns' => 0,
            'active_campaigns' => 0,
            'total_spend' => 0,
            'total_impressions' => 0,
            'total_reach' => 0,
            'total_clicks' => 0,
            'avg_ctr' => 0,
            'avg_cpc' => 0,
        ];
    }

    /**
     * Get empty pagination
     */
    private function getEmptyPagination(): array
    {
        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => 0,
            'from' => null,
            'to' => null,
        ];
    }

    /**
     * Get sort options
     */
    private function getSortOptions(): array
    {
        return [
            ['value' => 'total_spend', 'label' => 'Sarflangan (ko\'p → kam)'],
            ['value' => 'total_impressions', 'label' => 'Ko\'rishlar'],
            ['value' => 'total_clicks', 'label' => 'Kliklar'],
            ['value' => 'avg_ctr', 'label' => 'CTR'],
            ['value' => 'avg_cpc', 'label' => 'CPC'],
            ['value' => 'name', 'label' => 'Nomi (A-Z)'],
            ['value' => 'created_time', 'label' => 'Yaratilgan sana'],
        ];
    }

    /**
     * Format status label
     */
    private function formatStatus(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'Faol',
            'PAUSED' => 'Pauza',
            'DELETED' => 'O\'chirilgan',
            'ARCHIVED' => 'Arxivlangan',
            'IN_PROCESS' => 'Jarayonda',
            'WITH_ISSUES' => 'Muammoli',
            'CAMPAIGN_PAUSED' => 'Kampaniya pauzada',
            'ADSET_PAUSED' => 'AdSet pauzada',
            'PENDING_REVIEW' => 'Ko\'rib chiqilmoqda',
            'DISAPPROVED' => 'Rad etilgan',
            default => $status,
        };
    }

    /**
     * Format objective label
     */
    private function formatObjective(string $objective): string
    {
        return match ($objective) {
            'OUTCOME_AWARENESS' => 'Xabardorlik',
            'OUTCOME_ENGAGEMENT' => 'Engagement',
            'OUTCOME_LEADS' => 'Lidlar',
            'OUTCOME_SALES' => 'Sotuvlar',
            'OUTCOME_TRAFFIC' => 'Trafik',
            'OUTCOME_APP_PROMOTION' => 'App Promotion',
            'LINK_CLICKS' => 'Link Clicks',
            'POST_ENGAGEMENT' => 'Post Engagement',
            'PAGE_LIKES' => 'Page Likes',
            'CONVERSIONS' => 'Konversiyalar',
            'MESSAGES' => 'Xabarlar',
            'VIDEO_VIEWS' => 'Video Views',
            default => ucfirst(strtolower(str_replace('_', ' ', $objective))),
        };
    }

    /**
     * Get current business
     */
    protected function getCurrentBusiness(Request $request): Business
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (! $businessId) {
            $business = auth()->user()->businesses()->first();
            if (! $business) {
                abort(400, 'Biznes tanlanmagan');
            }

            return $business;
        }

        return Business::findOrFail($businessId);
    }

    /**
     * Get selected Meta account
     */
    protected function getSelectedMetaAccount(string $businessId): ?MetaAdAccount
    {
        $integration = Integration::where('business_id', $businessId)
            ->where('type', 'meta_ads')
            ->first();

        if (! $integration) {
            return null;
        }

        $account = MetaAdAccount::where('integration_id', $integration->id)
            ->where('is_primary', true)
            ->first();

        if (! $account) {
            $account = MetaAdAccount::where('integration_id', $integration->id)->first();
        }

        return $account;
    }

    /**
     * Get ad sets for a campaign
     */
    public function getAdSets(Request $request, string $campaignId): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        if (! $adAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Meta Ad hesob tanlanmagan',
                'data' => [],
            ]);
        }

        $campaign = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id)
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Kampaniya topilmadi',
                'data' => [],
            ], 404);
        }

        $adSets = MetaAdSet::withoutGlobalScope('business')
            ->where('campaign_id', $campaign->id)
            ->orderBy('name')
            ->get()
            ->map(function ($adSet) {
                // Build targeting summary
                $targeting = $adSet->targeting ?? [];
                $parts = [];

                if (! empty($targeting['age_min']) || ! empty($targeting['age_max'])) {
                    $min = $targeting['age_min'] ?? 18;
                    $max = $targeting['age_max'] ?? 65;
                    $parts[] = "{$min}-{$max} yosh";
                }

                if (! empty($targeting['genders'])) {
                    $genderLabels = collect($targeting['genders'])->map(fn ($g) => $g == 1 ? 'Erkak' : 'Ayol')->implode(', ');
                    $parts[] = $genderLabels;
                }

                if (! empty($targeting['geo_locations']['countries'])) {
                    $parts[] = implode(', ', $targeting['geo_locations']['countries']);
                }

                return [
                    'id' => $adSet->id,
                    'meta_adset_id' => $adSet->meta_adset_id,
                    'name' => $adSet->name,
                    'status' => $adSet->status,
                    'effective_status' => $adSet->effective_status,
                    'optimization_goal' => $adSet->optimization_goal,
                    'daily_budget' => $adSet->daily_budget,
                    'lifetime_budget' => $adSet->lifetime_budget,
                    'bid_amount' => $adSet->bid_amount,
                    'bid_strategy' => $adSet->bid_strategy,
                    'billing_event' => $adSet->billing_event,
                    'start_time' => $adSet->start_time,
                    'end_time' => $adSet->end_time,
                    'targeting_summary' => implode(' • ', $parts) ?: null,
                    'total_spend' => (float) ($adSet->total_spend ?? 0),
                    'total_impressions' => (int) ($adSet->total_impressions ?? 0),
                    'total_reach' => (int) ($adSet->total_reach ?? 0),
                    'total_clicks' => (int) ($adSet->total_clicks ?? 0),
                    'total_conversions' => (int) ($adSet->total_conversions ?? 0),
                    'avg_cpc' => (float) ($adSet->avg_cpc ?? 0),
                    'avg_cpm' => (float) ($adSet->avg_cpm ?? 0),
                    'avg_ctr' => (float) ($adSet->avg_ctr ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $adSets,
        ]);
    }

    /**
     * Get ads for a campaign
     */
    public function getAds(Request $request, string $campaignId): JsonResponse
    {
        $business = $this->getCurrentBusiness($request);
        $adAccount = $this->getSelectedMetaAccount($business->id);

        if (! $adAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Meta Ad hesob tanlanmagan',
                'data' => [],
            ]);
        }

        $campaign = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccount->id)
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Kampaniya topilmadi',
                'data' => [],
            ], 404);
        }

        $ads = MetaAd::withoutGlobalScope('business')
            ->where('campaign_id', $campaign->id)
            ->orderBy('name')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'meta_ad_id' => $ad->meta_ad_id,
                    'name' => $ad->name,
                    'status' => $ad->status,
                    'effective_status' => $ad->effective_status,
                    'thumbnail_url' => $ad->creative_thumbnail_url,
                    'body' => $ad->creative_body,
                    'title' => $ad->creative_title,
                    'link_url' => $ad->creative_link_url,
                    'call_to_action' => $ad->creative_call_to_action,
                    'creative_id' => $ad->creative_id,
                    'creative_data' => $ad->creative_data,
                    'total_spend' => (float) ($ad->total_spend ?? 0),
                    'total_impressions' => (int) ($ad->total_impressions ?? 0),
                    'total_reach' => (int) ($ad->total_reach ?? 0),
                    'total_clicks' => (int) ($ad->total_clicks ?? 0),
                    'total_conversions' => (int) ($ad->total_conversions ?? 0),
                    'avg_cpc' => (float) ($ad->avg_cpc ?? 0),
                    'avg_cpm' => (float) ($ad->avg_cpm ?? 0),
                    'avg_ctr' => (float) ($ad->avg_ctr ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }
}
