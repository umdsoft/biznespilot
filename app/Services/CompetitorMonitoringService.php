<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorActivity;
use App\Models\CompetitorAlert;
use App\Models\CompetitorMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompetitorMonitoringService
{
    /**
     * Monitor a single competitor
     */
    public function monitorCompetitor(Competitor $competitor): array
    {
        $results = [
            'success' => false,
            'metrics_collected' => false,
            'activities_found' => 0,
            'alerts_created' => 0,
            'errors' => [],
        ];

        try {
            // Collect metrics from available platforms
            $metrics = $this->collectMetrics($competitor);

            if ($metrics) {
                $this->saveMetrics($competitor, $metrics);
                $results['metrics_collected'] = true;

                // Check for alerts based on metrics
                $alerts = $this->checkForAlerts($competitor, $metrics);
                $results['alerts_created'] = count($alerts);
            }

            // Scan for new activities
            $activities = $this->scanActivities($competitor);
            $results['activities_found'] = count($activities);

            // Update last checked timestamp
            $competitor->update(['last_checked_at' => now()]);

            $results['success'] = true;

        } catch (\Exception $e) {
            Log::error('Competitor monitoring error', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Collect metrics from all available platforms
     */
    protected function collectMetrics(Competitor $competitor): array
    {
        $metrics = [];

        // Instagram metrics
        if ($competitor->instagram_handle) {
            $instagramData = $this->getInstagramMetrics($competitor->instagram_handle);
            if ($instagramData) {
                $metrics = array_merge($metrics, $instagramData);
            }
        }

        // Telegram metrics
        if ($competitor->telegram_handle) {
            $telegramData = $this->getTelegramMetrics($competitor->telegram_handle);
            if ($telegramData) {
                $metrics = array_merge($metrics, $telegramData);
            }
        }

        // Facebook metrics
        if ($competitor->facebook_page) {
            $facebookData = $this->getFacebookMetrics($competitor->facebook_page);
            if ($facebookData) {
                $metrics = array_merge($metrics, $facebookData);
            }
        }

        return $metrics;
    }

    /**
     * Get Instagram metrics
     * NOTE: This is a placeholder. In production, you would:
     * 1. Use Instagram Graph API (requires business account)
     * 2. Use third-party scraping service
     * 3. Manual data entry
     */
    protected function getInstagramMetrics(string $handle): ?array
    {
        // Placeholder - would integrate with Instagram API or scraping service
        // For now, return null to indicate manual entry needed

        Log::info('Instagram metrics check', ['handle' => $handle]);

        // Example of what real implementation might return:
        // return [
        //     'instagram_followers' => 15000,
        //     'instagram_following' => 500,
        //     'instagram_posts' => 234,
        //     'instagram_engagement_rate' => 3.5,
        //     'instagram_avg_likes' => 450,
        //     'instagram_avg_comments' => 25,
        // ];

        return null;
    }

    /**
     * Get Telegram metrics
     * NOTE: Can use Telegram API for public channels
     */
    protected function getTelegramMetrics(string $handle): ?array
    {
        try {
            // Telegram provides public channel info via web preview
            // This is a simplified example
            $handle = str_replace('@', '', $handle);

            // In production, integrate with Telegram Bot API or web scraping
            Log::info('Telegram metrics check', ['handle' => $handle]);

            return null;

        } catch (\Exception $e) {
            Log::error('Telegram metrics error', [
                'handle' => $handle,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Facebook metrics
     */
    protected function getFacebookMetrics(string $pageId): ?array
    {
        // Placeholder - would integrate with Facebook Graph API
        Log::info('Facebook metrics check', ['page_id' => $pageId]);

        return null;
    }

    /**
     * Save metrics to database
     */
    protected function saveMetrics(Competitor $competitor, array $metrics): void
    {
        $metric = CompetitorMetric::updateOrCreate(
            [
                'competitor_id' => $competitor->id,
                'date' => today(),
            ],
            array_merge($metrics, [
                'data_source' => 'api',
            ])
        );

        // Calculate growth rates
        $metric->calculateGrowthRates();
    }

    /**
     * Scan for new activities
     */
    protected function scanActivities(Competitor $competitor): array
    {
        $activities = [];

        // Scan Instagram posts
        if ($competitor->instagram_handle) {
            $instagramPosts = $this->scanInstagramPosts($competitor);
            $activities = array_merge($activities, $instagramPosts);
        }

        // Scan Telegram posts
        if ($competitor->telegram_handle) {
            $telegramPosts = $this->scanTelegramPosts($competitor);
            $activities = array_merge($activities, $telegramPosts);
        }

        return $activities;
    }

    /**
     * Scan Instagram posts
     */
    protected function scanInstagramPosts(Competitor $competitor): array
    {
        // Placeholder for Instagram post scanning
        // Would integrate with Instagram API or scraping service

        return [];
    }

    /**
     * Scan Telegram posts
     */
    protected function scanTelegramPosts(Competitor $competitor): array
    {
        // Placeholder for Telegram post scanning
        // Can use Telegram API for public channels

        return [];
    }

    /**
     * Check for alerts based on metrics
     */
    protected function checkForAlerts(Competitor $competitor, array $metrics): array
    {
        $alerts = [];

        // Get previous metrics
        $previousMetric = CompetitorMetric::where('competitor_id', $competitor->id)
            ->where('date', '<', today())
            ->orderBy('date', 'desc')
            ->first();

        if (!$previousMetric) {
            return $alerts;
        }

        // Check for follower surge (>10% growth)
        if (isset($metrics['instagram_followers']) && $previousMetric->instagram_followers) {
            $growthRate = (($metrics['instagram_followers'] - $previousMetric->instagram_followers) / $previousMetric->instagram_followers) * 100;

            if ($growthRate >= 10) {
                $alert = $this->createAlert($competitor, [
                    'type' => 'follower_surge',
                    'severity' => 'high',
                    'title' => "{$competitor->name} - Follower Surge Detected",
                    'message' => "Instagram followers grew by {$growthRate}% in one day ({$previousMetric->instagram_followers} → {$metrics['instagram_followers']})",
                    'data' => [
                        'growth_rate' => round($growthRate, 2),
                        'previous_count' => $previousMetric->instagram_followers,
                        'current_count' => $metrics['instagram_followers'],
                    ],
                ]);

                $alerts[] = $alert;
            }
        }

        // Check for engagement spike
        if (isset($metrics['instagram_engagement_rate']) && $previousMetric->instagram_engagement_rate) {
            $engagementGrowth = (($metrics['instagram_engagement_rate'] - $previousMetric->instagram_engagement_rate) / $previousMetric->instagram_engagement_rate) * 100;

            if ($engagementGrowth >= 50) { // 50% increase in engagement
                $alert = $this->createAlert($competitor, [
                    'type' => 'engagement_spike',
                    'severity' => 'medium',
                    'title' => "{$competitor->name} - Engagement Spike",
                    'message' => "Engagement rate increased by {$engagementGrowth}% ({$previousMetric->instagram_engagement_rate}% → {$metrics['instagram_engagement_rate']}%)",
                    'data' => [
                        'growth_rate' => round($engagementGrowth, 2),
                        'previous_rate' => $previousMetric->instagram_engagement_rate,
                        'current_rate' => $metrics['instagram_engagement_rate'],
                    ],
                ]);

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Create an alert
     */
    protected function createAlert(Competitor $competitor, array $data): CompetitorAlert
    {
        return CompetitorAlert::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'business_id' => $competitor->business_id,
        ]));
    }

    /**
     * Manual metric entry
     */
    public function recordManualMetrics(Competitor $competitor, array $metrics, ?Carbon $date = null): CompetitorMetric
    {
        $metric = CompetitorMetric::updateOrCreate(
            [
                'competitor_id' => $competitor->id,
                'date' => $date ?? today(),
            ],
            array_merge($metrics, [
                'data_source' => 'manual',
            ])
        );

        // Calculate growth rates
        $metric->calculateGrowthRates();

        // Check for alerts
        $this->checkForAlerts($competitor, $metrics);

        return $metric;
    }

    /**
     * Monitor all active competitors for a business
     */
    public function monitorAllCompetitors($businessId): array
    {
        $results = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        $competitors = Competitor::where('business_id', $businessId)
            ->where('status', 'active')
            ->where('auto_monitor', true)
            ->get();

        $results['total'] = $competitors->count();

        foreach ($competitors as $competitor) {
            $result = $this->monitorCompetitor($competitor);

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }

            $results['details'][$competitor->id] = $result;
        }

        return $results;
    }
}
