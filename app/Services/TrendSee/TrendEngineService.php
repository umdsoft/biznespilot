<?php

declare(strict_types=1);

namespace App\Services\TrendSee;

use App\Models\GlobalTrend;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TrendEngineService - Global Trends Intelligence
 *
 * "Fetch Once, Serve Many" architecture.
 * Fetches search trends globally and caches for 7 days.
 *
 * Sources: Google Trends, TikTok Creative Center (via DataForSEO or mock)
 */
class TrendEngineService
{
    private const CACHE_DAYS = 7;
    private const CACHE_PREFIX = 'trends_';

    /**
     * Get trends for a niche/region (Cache-First Logic).
     *
     * @param string $niche Business niche
     * @param string $region Region code (default: UZ)
     * @param string $platform Platform: google, tiktok, instagram
     * @return array{success: bool, data: array, source: string, fetched_at: string|null}
     */
    public function getTrends(
        string $niche,
        string $region = 'UZ',
        string $platform = 'google'
    ): array {
        $cacheKey = self::CACHE_PREFIX . "{$niche}_{$region}_{$platform}";

        // Check memory cache first (short-term)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Check database (long-term)
        $trend = GlobalTrend::forNiche($niche)
            ->forRegion($region)
            ->forPlatform($platform)
            ->fresh(self::CACHE_DAYS)
            ->orderByDesc('fetched_at')
            ->first();

        if ($trend) {
            // HIT: Return cached data
            $result = [
                'success' => true,
                'data' => $this->formatTrendData($trend),
                'source' => 'database',
                'fetched_at' => $trend->fetched_at?->toDateTimeString(),
                'expires_at' => $trend->expires_at?->toDateTimeString(),
            ];

            // Store in memory cache for faster subsequent calls
            Cache::put($cacheKey, $result, now()->addHours(6));

            return $result;
        }

        // MISS: Need to fetch fresh data
        Log::info('TrendEngine: Cache miss, fetching fresh data', [
            'niche' => $niche,
            'region' => $region,
            'platform' => $platform,
        ]);

        return $this->fetchAndStoreTrends($niche, $region, $platform);
    }

    /**
     * Force refresh trends for a niche/region.
     */
    public function refresh(
        string $niche,
        string $region = 'UZ',
        string $platform = 'google'
    ): array {
        // Clear cache
        $cacheKey = self::CACHE_PREFIX . "{$niche}_{$region}_{$platform}";
        Cache::forget($cacheKey);

        return $this->fetchAndStoreTrends($niche, $region, $platform);
    }

    /**
     * Refresh all configured niches for a region.
     */
    public function refreshAllNiches(string $region = 'UZ', string $platform = 'google'): array
    {
        $results = [];
        $niches = array_keys(GlobalTrend::getAvailableNiches());

        foreach ($niches as $niche) {
            try {
                $results[$niche] = $this->refresh($niche, $region, $platform);

                // Delay between requests to avoid rate limits
                usleep(500000); // 0.5 second
            } catch (\Exception $e) {
                Log::error('TrendEngine: Failed to refresh niche', [
                    'niche' => $niche,
                    'error' => $e->getMessage(),
                ]);
                $results[$niche] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Fetch fresh trends from external API and store in DB.
     */
    private function fetchAndStoreTrends(
        string $niche,
        string $region,
        string $platform
    ): array {
        try {
            // Try to fetch from external API
            $apiData = $this->fetchFromExternalApi($niche, $region, $platform);

            if (!$apiData['success']) {
                // Use mock data as fallback
                $apiData = $this->getMockTrendsData($niche, $region, $platform);
            }

            // Store in database
            $trend = GlobalTrend::updateOrCreate(
                [
                    'niche' => $niche,
                    'region_code' => $region,
                    'platform' => $platform,
                    'trend_date' => now()->toDateString(),
                ],
                [
                    'data_json' => $apiData['data'],
                    'top_keywords' => $apiData['top_keywords'] ?? [],
                    'rising_keywords' => $apiData['rising_keywords'] ?? [],
                    'total_keywords' => count($apiData['top_keywords'] ?? []),
                    'data_source' => $apiData['source'] ?? 'mock',
                    'api_cost' => $apiData['cost'] ?? 0,
                    'is_processed' => true,
                    'fetched_at' => now(),
                    'expires_at' => now()->addDays(self::CACHE_DAYS),
                ]
            );

            $result = [
                'success' => true,
                'data' => $this->formatTrendData($trend),
                'source' => $apiData['source'] ?? 'mock',
                'fetched_at' => $trend->fetched_at->toDateTimeString(),
                'expires_at' => $trend->expires_at->toDateTimeString(),
            ];

            // Store in memory cache
            $cacheKey = self::CACHE_PREFIX . "{$niche}_{$region}_{$platform}";
            Cache::put($cacheKey, $result, now()->addHours(6));

            Log::info('TrendEngine: Stored fresh trends', [
                'niche' => $niche,
                'region' => $region,
                'keywords_count' => $trend->total_keywords,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('TrendEngine: Failed to fetch trends', [
                'niche' => $niche,
                'region' => $region,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => [],
                'source' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch from external API (DataForSEO, SerpAPI, etc).
     * This is a stub - implement with actual API when ready.
     */
    private function fetchFromExternalApi(
        string $niche,
        string $region,
        string $platform
    ): array {
        $apiKey = config('services.dataforseo.key');

        if (empty($apiKey)) {
            // No API configured, use mock
            return ['success' => false];
        }

        // Example: DataForSEO Google Trends API
        // This is a placeholder - implement actual API call
        try {
            $response = Http::withBasicAuth(
                config('services.dataforseo.login'),
                config('services.dataforseo.password')
            )
                ->timeout(30)
                ->post('https://api.dataforseo.com/v3/keywords_data/google_trends/explore/live', [
                    [
                        'keywords' => [$this->getNicheKeyword($niche)],
                        'location_code' => $this->getLocationCode($region),
                        'language_code' => 'uz',
                        'time_range' => 'past_7_days',
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // Parse and return structured data
                return [
                    'success' => true,
                    'data' => $data,
                    'top_keywords' => $this->extractTopKeywords($data),
                    'rising_keywords' => $this->extractRisingKeywords($data),
                    'source' => 'dataforseo',
                    'cost' => 0.005, // Approximate cost
                ];
            }
        } catch (\Exception $e) {
            Log::warning('TrendEngine: External API call failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return ['success' => false];
    }

    /**
     * Get mock trends data for development/fallback.
     */
    private function getMockTrendsData(
        string $niche,
        string $region,
        string $platform
    ): array {
        $mockKeywords = $this->getMockKeywordsForNiche($niche);

        return [
            'success' => true,
            'data' => [
                'niche' => $niche,
                'region' => $region,
                'platform' => $platform,
                'generated_at' => now()->toDateTimeString(),
            ],
            'top_keywords' => $mockKeywords['top'],
            'rising_keywords' => $mockKeywords['rising'],
            'source' => 'mock',
            'cost' => 0,
        ];
    }

    /**
     * Get mock keywords for niche.
     */
    private function getMockKeywordsForNiche(string $niche): array
    {
        $keywords = [
            'business' => [
                'top' => [
                    ['keyword' => 'biznes boshlamoq', 'volume' => 12000, 'trend' => 'stable'],
                    ['keyword' => 'onlayn savdo', 'volume' => 8500, 'trend' => 'up'],
                    ['keyword' => 'startup g\'oyalar', 'volume' => 6200, 'trend' => 'up'],
                    ['keyword' => 'kichik biznes', 'volume' => 5800, 'trend' => 'stable'],
                    ['keyword' => 'tadbirkorlik', 'volume' => 4500, 'trend' => 'stable'],
                    ['keyword' => 'biznes reja', 'volume' => 4200, 'trend' => 'up'],
                    ['keyword' => 'dropshipping', 'volume' => 3800, 'trend' => 'up'],
                    ['keyword' => 'franchise', 'volume' => 2900, 'trend' => 'stable'],
                    ['keyword' => 'investitsiya', 'volume' => 2500, 'trend' => 'up'],
                    ['keyword' => 'marketing strategiya', 'volume' => 2200, 'trend' => 'stable'],
                ],
                'rising' => [
                    ['keyword' => 'AI biznes', 'growth' => 450, 'trend' => 'breakout'],
                    ['keyword' => 'telegram bot biznes', 'growth' => 280, 'trend' => 'up'],
                    ['keyword' => 'onlayn kurs sotish', 'growth' => 220, 'trend' => 'up'],
                    ['keyword' => 'SMM xizmatlari', 'growth' => 180, 'trend' => 'up'],
                    ['keyword' => 'marketplace sotuvchi', 'growth' => 150, 'trend' => 'up'],
                ],
            ],
            'fashion' => [
                'top' => [
                    ['keyword' => 'moda trendlari 2026', 'volume' => 15000, 'trend' => 'up'],
                    ['keyword' => 'ayollar kiyimi', 'volume' => 12000, 'trend' => 'stable'],
                    ['keyword' => 'erkaklar kiyimi', 'volume' => 8000, 'trend' => 'stable'],
                    ['keyword' => 'poyabzal', 'volume' => 6500, 'trend' => 'stable'],
                    ['keyword' => 'aksessuarlar', 'volume' => 4200, 'trend' => 'up'],
                ],
                'rising' => [
                    ['keyword' => 'sustainable fashion', 'growth' => 320, 'trend' => 'breakout'],
                    ['keyword' => 'vintage kiyimlar', 'growth' => 180, 'trend' => 'up'],
                    ['keyword' => 'streetwear', 'growth' => 150, 'trend' => 'up'],
                ],
            ],
            'food' => [
                'top' => [
                    ['keyword' => 'restoran toshkent', 'volume' => 18000, 'trend' => 'stable'],
                    ['keyword' => 'yetkazib berish', 'volume' => 14000, 'trend' => 'up'],
                    ['keyword' => 'milliy taomlar', 'volume' => 9500, 'trend' => 'stable'],
                    ['keyword' => 'kafe', 'volume' => 7200, 'trend' => 'stable'],
                    ['keyword' => 'sushi', 'volume' => 5800, 'trend' => 'up'],
                ],
                'rising' => [
                    ['keyword' => 'healthy food', 'growth' => 280, 'trend' => 'up'],
                    ['keyword' => 'cloud kitchen', 'growth' => 220, 'trend' => 'breakout'],
                    ['keyword' => 'vegan taomlar', 'growth' => 150, 'trend' => 'up'],
                ],
            ],
        ];

        return $keywords[$niche] ?? [
            'top' => [
                ['keyword' => $niche . ' toshkent', 'volume' => 5000, 'trend' => 'stable'],
                ['keyword' => $niche . ' xizmatlari', 'volume' => 3500, 'trend' => 'stable'],
                ['keyword' => 'eng yaxshi ' . $niche, 'volume' => 2800, 'trend' => 'up'],
            ],
            'rising' => [
                ['keyword' => $niche . ' 2026', 'growth' => 150, 'trend' => 'up'],
            ],
        ];
    }

    /**
     * Format trend data for response.
     */
    private function formatTrendData(GlobalTrend $trend): array
    {
        return [
            'id' => $trend->id,
            'niche' => $trend->niche,
            'region_code' => $trend->region_code,
            'platform' => $trend->platform,
            'trend_date' => $trend->trend_date->format('Y-m-d'),
            'top_keywords' => $trend->top_keywords ?? [],
            'rising_keywords' => $trend->rising_keywords ?? [],
            'total_keywords' => $trend->total_keywords,
            'data_source' => $trend->data_source,
            'is_fresh' => $trend->is_fresh,
        ];
    }

    /**
     * Get main keyword for niche.
     */
    private function getNicheKeyword(string $niche): string
    {
        $keywords = [
            'business' => 'biznes',
            'fashion' => 'moda',
            'food' => 'restoran',
            'beauty' => 'go\'zallik',
            'tech' => 'texnologiya',
            'education' => 'ta\'lim',
            'travel' => 'sayohat',
            'fitness' => 'sport',
            'real_estate' => 'kvartira',
            'auto' => 'avtomobil',
        ];

        return $keywords[$niche] ?? $niche;
    }

    /**
     * Get location code for region.
     */
    private function getLocationCode(string $region): int
    {
        $codes = [
            'UZ' => 2860, // Uzbekistan
            'KZ' => 2398, // Kazakhstan
            'RU' => 2643, // Russia
            'US' => 2840, // USA
        ];

        return $codes[$region] ?? 2860;
    }

    /**
     * Extract top keywords from API response.
     */
    private function extractTopKeywords(array $data): array
    {
        // Implement based on actual API response structure
        return [];
    }

    /**
     * Extract rising keywords from API response.
     */
    private function extractRisingKeywords(array $data): array
    {
        // Implement based on actual API response structure
        return [];
    }
}
