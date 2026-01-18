<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorAd;
use App\Models\CompetitorAdStat;
use App\Models\CompetitorAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdLibraryService
{
    protected string $adLibraryUrl = 'https://www.facebook.com/ads/library/';

    protected string $apiUrl = 'https://graph.facebook.com/v23.0/ads_archive';

    protected int $cacheTTL = 3600; // 1 hour

    // Supported countries for Ad Library API (EU + Brazil + some others)
    protected array $supportedCountries = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'BR', 'US', 'GB',
        'CA', 'AU', 'IN', 'MX', 'AR', 'CL', 'CO', 'PE', 'UA', 'TR',
    ];

    /**
     * Search for competitor ads in Meta Ad Library
     */
    public function searchCompetitorAds(Competitor $competitor): array
    {
        $results = [
            'success' => false,
            'new_ads' => 0,
            'updated_ads' => 0,
            'errors' => [],
        ];

        try {
            // Try to find Facebook page ID
            $pageId = $this->findFacebookPageId($competitor);

            if (! $pageId && ! $competitor->facebook_page) {
                $results['errors'][] = 'Facebook page not found';

                return $results;
            }

            // Search ads by page
            $ads = $this->fetchAdsFromLibrary($pageId ?? $competitor->facebook_page, $competitor->name);

            foreach ($ads as $adData) {
                $result = $this->saveOrUpdateAd($competitor, $adData);
                if ($result === 'new') {
                    $results['new_ads']++;
                } elseif ($result === 'updated') {
                    $results['updated_ads']++;
                }
            }

            // Update ad stats
            $this->updateAdStats($competitor);

            // Check for alerts
            $this->checkForAdAlerts($competitor);

            $results['success'] = true;

        } catch (\Exception $e) {
            Log::error('Meta Ad Library search error', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Find Facebook Page ID from page name or URL
     */
    protected function findFacebookPageId(Competitor $competitor): ?string
    {
        if (! $competitor->facebook_page) {
            return null;
        }

        // If it's already an ID (numeric), return it
        if (is_numeric($competitor->facebook_page)) {
            return $competitor->facebook_page;
        }

        // Try to extract from URL
        $pageHandle = $competitor->facebook_page;
        $pageHandle = preg_replace('#^https?://(www\.)?facebook\.com/#i', '', $pageHandle);
        $pageHandle = rtrim($pageHandle, '/');
        $pageHandle = explode('?', $pageHandle)[0];

        return $pageHandle;
    }

    /**
     * Fetch ads from Meta Ad Library
     * Tries API first, falls back to web scraping
     */
    protected function fetchAdsFromLibrary(string $pageId, string $searchName): array
    {
        $cacheKey = "meta_ads_{$pageId}";

        if ($cached = Cache::get($cacheKey)) {
            Log::debug('Using cached Meta ads', ['page_id' => $pageId]);

            return $cached;
        }

        $ads = [];

        try {
            // Method 1: Try Meta Ad Library API (requires META_AD_LIBRARY_TOKEN in .env)
            // API works best for EU/US/BR countries
            $searchTerm = $searchName ?: $pageId;
            $ads = $this->fetchViaAPI($searchTerm);

            Log::info('Meta Ad Library API attempt', [
                'search' => $searchTerm,
                'found' => count($ads),
            ]);

            // Method 2: If API returns empty, try web scraping (limited)
            if (empty($ads)) {
                $ads = $this->scrapeAdLibrary($pageId, $searchName);
            }

            if (! empty($ads)) {
                Cache::put($cacheKey, $ads, $this->cacheTTL);
            }

        } catch (\Exception $e) {
            Log::warning('Ad Library fetch failed', ['error' => $e->getMessage()]);
        }

        return $ads;
    }

    /**
     * Fetch ads via Meta Graph API
     * Requires access_token with ads_read permission
     */
    protected function fetchViaAPI(string $pageId, ?string $accessToken = null): array
    {
        $ads = [];

        // Get access token from config or parameter
        $token = $accessToken ?? config('services.meta.ad_library_token');

        if (! $token) {
            Log::info('No Meta Ad Library access token configured');

            return $ads;
        }

        try {
            // Build API request
            $response = Http::timeout(30)->get($this->apiUrl, [
                'access_token' => $token,
                'search_terms' => $pageId,
                'ad_type' => 'ALL',
                'ad_active_status' => 'ACTIVE',
                'ad_reached_countries' => json_encode(['US', 'GB', 'DE', 'TR', 'UA']), // Try multiple countries
                'fields' => implode(',', [
                    'id',
                    'ad_creation_time',
                    'ad_creative_bodies',
                    'ad_creative_link_captions',
                    'ad_creative_link_descriptions',
                    'ad_creative_link_titles',
                    'ad_delivery_start_time',
                    'ad_delivery_stop_time',
                    'ad_snapshot_url',
                    'page_id',
                    'page_name',
                    'publisher_platforms',
                    'bylines',
                    'currency',
                    'spend',
                    'impressions',
                ]),
                'limit' => 25,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $adData) {
                        $ads[] = $this->transformAPIAdData($adData);
                    }

                    Log::info('Meta Ad Library API success', [
                        'search' => $pageId,
                        'ads_found' => count($ads),
                    ]);
                }
            } else {
                Log::warning('Meta Ad Library API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Meta Ad Library API exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return $ads;
    }

    /**
     * Transform API response to our ad format
     */
    protected function transformAPIAdData(array $adData): array
    {
        $headline = $adData['ad_creative_link_titles'][0] ?? null;
        $bodyText = $adData['ad_creative_bodies'][0] ?? null;
        $cta = $adData['ad_creative_link_captions'][0] ?? null;

        // Determine media type from creative
        $mediaType = 'image'; // Default
        if (isset($adData['ad_snapshot_url'])) {
            $snapshotUrl = $adData['ad_snapshot_url'];
            if (str_contains($snapshotUrl, 'video')) {
                $mediaType = 'video';
            }
        }

        // Determine platforms
        $platforms = $adData['publisher_platforms'] ?? ['facebook'];

        return [
            'ad_id' => $adData['id'] ?? uniqid('meta_'),
            'page_id' => $adData['page_id'] ?? null,
            'page_name' => $adData['page_name'] ?? null,
            'platform' => in_array('instagram', $platforms) ? 'instagram' : 'facebook',
            'headline' => $headline,
            'body_text' => $bodyText,
            'call_to_action' => $cta,
            'destination_url' => $adData['ad_creative_link_descriptions'][0] ?? null,
            'media_type' => $mediaType,
            'thumbnail_url' => $adData['ad_snapshot_url'] ?? null,
            'started_at' => isset($adData['ad_delivery_start_time'])
                ? Carbon::parse($adData['ad_delivery_start_time'])
                : now(),
            'is_active' => ! isset($adData['ad_delivery_stop_time']),
            'spend' => $adData['spend'] ?? null,
            'impressions' => $adData['impressions'] ?? null,
            'raw_data' => $adData,
        ];
    }

    /**
     * Scrape Meta Ad Library web page
     */
    protected function scrapeAdLibrary(string $pageId, string $searchName): array
    {
        $ads = [];

        try {
            // Build Ad Library URL
            $url = 'https://www.facebook.com/ads/library/?active_status=active&ad_type=all&country=UZ&q='.urlencode($searchName);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(30)->get($url);

            if (! $response->successful()) {
                return $ads;
            }

            $html = $response->body();

            // Extract ad data from HTML
            // Note: Facebook's HTML is heavily obfuscated, this is a simplified extraction
            $ads = $this->parseAdLibraryHTML($html, $pageId);

        } catch (\Exception $e) {
            Log::warning('Ad Library scraping failed', ['error' => $e->getMessage()]);
        }

        return $ads;
    }

    /**
     * Parse Ad Library HTML to extract ad data
     */
    protected function parseAdLibraryHTML(string $html, string $pageId): array
    {
        $ads = [];

        // Look for JSON data embedded in page
        if (preg_match_all('/"adArchiveID":"(\d+)"/', $html, $matches)) {
            foreach ($matches[1] as $adId) {
                $ads[] = [
                    'ad_id' => $adId,
                    'page_id' => $pageId,
                    'platform' => 'facebook',
                    'is_active' => true,
                ];
            }
        }

        // Try to extract more details from structured data
        if (preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $html, $match)) {
            $jsonData = json_decode($match[1], true);
            // Process structured data if available
        }

        return $ads;
    }

    /**
     * Save or update ad in database
     */
    protected function saveOrUpdateAd(Competitor $competitor, array $adData): string
    {
        $existingAd = CompetitorAd::where('competitor_id', $competitor->id)
            ->where('platform', $adData['platform'] ?? 'facebook')
            ->where('ad_id', $adData['ad_id'])
            ->first();

        if ($existingAd) {
            // Update existing ad
            $existingAd->update([
                'is_active' => $adData['is_active'] ?? true,
                'days_running' => $existingAd->calculateDaysRunning(),
                'headline' => $adData['headline'] ?? $existingAd->headline,
                'body_text' => $adData['body_text'] ?? $existingAd->body_text,
            ]);

            return 'updated';
        }

        // Create new ad
        CompetitorAd::create([
            'competitor_id' => $competitor->id,
            'platform' => $adData['platform'] ?? 'facebook',
            'ad_id' => $adData['ad_id'],
            'page_id' => $adData['page_id'] ?? null,
            'page_name' => $adData['page_name'] ?? $competitor->name,
            'headline' => $adData['headline'] ?? null,
            'body_text' => $adData['body_text'] ?? null,
            'call_to_action' => $adData['call_to_action'] ?? null,
            'destination_url' => $adData['destination_url'] ?? null,
            'media_type' => $adData['media_type'] ?? null,
            'media_urls' => $adData['media_urls'] ?? null,
            'thumbnail_url' => $adData['thumbnail_url'] ?? null,
            'ad_status' => 'active',
            'started_at' => $adData['started_at'] ?? now(),
            'is_active' => true,
            'targeting_countries' => ['UZ'],
            'raw_data' => $adData,
        ]);

        return 'new';
    }

    /**
     * Update daily ad statistics
     */
    protected function updateAdStats(Competitor $competitor): void
    {
        $platforms = ['facebook', 'instagram'];

        foreach ($platforms as $platform) {
            $activeAds = $competitor->ads()
                ->where('platform', $platform)
                ->where('is_active', true)
                ->get();

            if ($activeAds->isEmpty()) {
                continue;
            }

            // Find longest running ad
            $longestRunning = $activeAds->sortByDesc('days_running')->first();

            // Count by media type
            $imageAds = $activeAds->where('media_type', 'image')->count();
            $videoAds = $activeAds->where('media_type', 'video')->count();
            $carouselAds = $activeAds->where('media_type', 'carousel')->count();

            // Get yesterday's stats for comparison
            $yesterdayStats = CompetitorAdStat::where('competitor_id', $competitor->id)
                ->where('platform', $platform)
                ->where('stat_date', today()->subDay())
                ->first();

            $newAds = $yesterdayStats
                ? $activeAds->count() - $yesterdayStats->total_active_ads + ($yesterdayStats->stopped_ads ?? 0)
                : $activeAds->count();

            CompetitorAdStat::updateOrCreate(
                [
                    'competitor_id' => $competitor->id,
                    'platform' => $platform,
                    'stat_date' => today(),
                ],
                [
                    'total_active_ads' => $activeAds->count(),
                    'new_ads' => max(0, $newAds),
                    'stopped_ads' => 0,
                    'image_ads_count' => $imageAds,
                    'video_ads_count' => $videoAds,
                    'carousel_ads_count' => $carouselAds,
                    'longest_running_ad_id' => $longestRunning?->id,
                    'longest_running_days' => $longestRunning?->days_running ?? 0,
                ]
            );
        }
    }

    /**
     * Check for ad-related alerts
     */
    protected function checkForAdAlerts(Competitor $competitor): void
    {
        // Alert if competitor started many new ads (campaign launch)
        $todayStats = CompetitorAdStat::where('competitor_id', $competitor->id)
            ->where('stat_date', today())
            ->first();

        if ($todayStats && $todayStats->new_ads >= 5) {
            $this->createAlert($competitor, [
                'type' => 'ad_campaign_launch',
                'severity' => 'high',
                'title' => "{$competitor->name} - Yangi reklama kampaniyasi",
                'message' => "Raqobatchi {$todayStats->new_ads} ta yangi reklama boshladi. Bu katta kampaniya bo'lishi mumkin.",
                'data' => [
                    'new_ads_count' => $todayStats->new_ads,
                    'total_active' => $todayStats->total_active_ads,
                ],
            ]);
        }

        // Alert for long-running successful ads (worth studying)
        $longRunningAds = $competitor->ads()
            ->where('is_active', true)
            ->where('days_running', '>=', 60)
            ->whereDate('created_at', today())
            ->get();

        foreach ($longRunningAds as $ad) {
            $this->createAlert($competitor, [
                'type' => 'successful_ad_detected',
                'severity' => 'medium',
                'title' => "{$competitor->name} - Muvaffaqiyatli reklama",
                'message' => "Reklama {$ad->days_running} kundan beri faol. Bu samarali kreativ bo'lishi mumkin.",
                'data' => [
                    'ad_id' => $ad->id,
                    'days_running' => $ad->days_running,
                ],
            ]);
        }
    }

    /**
     * Create alert
     */
    protected function createAlert(Competitor $competitor, array $data): CompetitorAlert
    {
        return CompetitorAlert::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'business_id' => $competitor->business_id,
        ]));
    }

    /**
     * Get ad insights for competitor
     */
    public function getAdInsights(Competitor $competitor, int $days = 30): array
    {
        $ads = $competitor->ads()
            ->where('started_at', '>=', now()->subDays($days))
            ->get();

        $activeAds = $competitor->ads()->active()->get();

        if ($ads->isEmpty() && $activeAds->isEmpty()) {
            return [
                'total_ads' => 0,
                'active_ads' => 0,
                'avg_ad_lifespan' => 0,
                'ad_types' => [],
                'cta_distribution' => [],
            ];
        }

        // Ad types distribution
        $adTypes = $activeAds->groupBy('media_type')->map->count();

        // CTA distribution
        $ctaDistribution = $activeAds->groupBy('call_to_action')->map->count();

        // Average ad lifespan
        $avgLifespan = $ads->filter(fn ($a) => ! $a->is_active)->avg('days_running') ?? 0;

        return [
            'total_ads' => $ads->count(),
            'active_ads' => $activeAds->count(),
            'new_ads_this_period' => $ads->count(),
            'avg_ad_lifespan' => round($avgLifespan, 1),
            'longest_running' => $activeAds->max('days_running'),
            'ad_types' => $adTypes->toArray(),
            'cta_distribution' => $ctaDistribution->toArray(),
            'video_ads_percent' => $activeAds->count() > 0
                ? round(($activeAds->where('media_type', 'video')->count() / $activeAds->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Manual ad entry
     */
    public function addManualAd(Competitor $competitor, array $data): CompetitorAd
    {
        return CompetitorAd::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'ad_status' => 'active',
            'is_active' => true,
            'started_at' => $data['started_at'] ?? now(),
        ]));
    }
}
