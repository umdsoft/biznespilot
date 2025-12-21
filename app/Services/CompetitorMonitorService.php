<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\CompetitorAlert;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompetitorMonitorService
{
    protected array $alertTypes = [
        'price_change' => 'Narx o\'zgarishi',
        'new_product' => 'Yangi mahsulot',
        'campaign' => 'Yangi kampaniya',
        'followers_surge' => 'Followerlar o\'sishi',
        'content_viral' => 'Viral kontent',
        'promotion' => 'Aksiya/Chegirma',
    ];

    public function checkCompetitors(Business $business): Collection
    {
        $alerts = collect();
        $competitors = Competitor::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        foreach ($competitors as $competitor) {
            $competitorAlerts = $this->monitorCompetitor($competitor);
            $alerts = $alerts->merge($competitorAlerts);
        }

        return $alerts;
    }

    public function monitorCompetitor(Competitor $competitor): Collection
    {
        $alerts = collect();

        try {
            // Check Instagram if available
            if ($competitor->instagram_handle) {
                $instagramAlerts = $this->checkInstagram($competitor);
                $alerts = $alerts->merge($instagramAlerts);
            }

            // Check Telegram if available
            if ($competitor->telegram_channel) {
                $telegramAlerts = $this->checkTelegram($competitor);
                $alerts = $alerts->merge($telegramAlerts);
            }

            // Check website if available
            if ($competitor->website_url) {
                $websiteAlerts = $this->checkWebsite($competitor);
                $alerts = $alerts->merge($websiteAlerts);
            }

            // Update last checked timestamp
            $competitor->update(['last_checked_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Competitor monitoring failed', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $alerts;
    }

    protected function checkInstagram(Competitor $competitor): Collection
    {
        $alerts = collect();

        // Get current metrics (this would use Instagram API or scraping)
        $currentMetrics = $this->fetchInstagramMetrics($competitor);

        if (!$currentMetrics) {
            return $alerts;
        }

        // Check for follower surge
        $previousFollowers = $competitor->last_followers_count ?? 0;
        $currentFollowers = $currentMetrics['followers'] ?? 0;

        if ($previousFollowers > 0) {
            $growthPercent = (($currentFollowers - $previousFollowers) / $previousFollowers) * 100;

            if ($growthPercent >= 10) {
                $alert = $this->createCompetitorAlert($competitor, [
                    'alert_type' => 'followers_surge',
                    'title' => sprintf('%s - Followerlar keskin o\'sdi', $competitor->name),
                    'description' => sprintf(
                        '%s ning Instagram followerlar soni %.1f%% o\'sdi (%s dan %s ga)',
                        $competitor->name,
                        $growthPercent,
                        number_format($previousFollowers),
                        number_format($currentFollowers)
                    ),
                    'old_value' => $previousFollowers,
                    'new_value' => $currentFollowers,
                    'change_percent' => $growthPercent,
                    'severity' => $growthPercent >= 25 ? 'high' : 'medium',
                ]);
                $alerts->push($alert);
            }
        }

        // Update competitor metrics
        $competitor->update([
            'last_followers_count' => $currentFollowers,
            'instagram_metrics' => $currentMetrics,
        ]);

        // Check for viral content
        if (isset($currentMetrics['recent_posts'])) {
            foreach ($currentMetrics['recent_posts'] as $post) {
                if ($this->isViralContent($post, $currentFollowers)) {
                    $alert = $this->createCompetitorAlert($competitor, [
                        'alert_type' => 'content_viral',
                        'title' => sprintf('%s - Viral kontent', $competitor->name),
                        'description' => sprintf(
                            '%s ning posti viral bo\'ldi: %s ta like, %s ta comment',
                            $competitor->name,
                            number_format($post['likes'] ?? 0),
                            number_format($post['comments'] ?? 0)
                        ),
                        'source_url' => $post['url'] ?? null,
                        'severity' => 'medium',
                    ]);
                    $alerts->push($alert);
                }
            }
        }

        return $alerts;
    }

    protected function checkTelegram(Competitor $competitor): Collection
    {
        $alerts = collect();

        $currentMetrics = $this->fetchTelegramMetrics($competitor);

        if (!$currentMetrics) {
            return $alerts;
        }

        // Check for subscriber surge
        $previousSubscribers = $competitor->last_telegram_subscribers ?? 0;
        $currentSubscribers = $currentMetrics['subscribers'] ?? 0;

        if ($previousSubscribers > 0) {
            $growthPercent = (($currentSubscribers - $previousSubscribers) / $previousSubscribers) * 100;

            if ($growthPercent >= 15) {
                $alert = $this->createCompetitorAlert($competitor, [
                    'alert_type' => 'followers_surge',
                    'title' => sprintf('%s - Telegram obunachilar o\'sdi', $competitor->name),
                    'description' => sprintf(
                        '%s ning Telegram obunachilar soni %.1f%% o\'sdi',
                        $competitor->name,
                        $growthPercent
                    ),
                    'old_value' => $previousSubscribers,
                    'new_value' => $currentSubscribers,
                    'change_percent' => $growthPercent,
                    'severity' => $growthPercent >= 30 ? 'high' : 'medium',
                ]);
                $alerts->push($alert);
            }
        }

        $competitor->update([
            'last_telegram_subscribers' => $currentSubscribers,
            'telegram_metrics' => $currentMetrics,
        ]);

        return $alerts;
    }

    protected function checkWebsite(Competitor $competitor): Collection
    {
        $alerts = collect();

        try {
            $pageContent = $this->fetchWebsiteContent($competitor->website_url);

            if (!$pageContent) {
                return $alerts;
            }

            // Check for price changes
            $priceAlerts = $this->detectPriceChanges($competitor, $pageContent);
            $alerts = $alerts->merge($priceAlerts);

            // Check for new products
            $productAlerts = $this->detectNewProducts($competitor, $pageContent);
            $alerts = $alerts->merge($productAlerts);

            // Check for promotions
            $promoAlerts = $this->detectPromotions($competitor, $pageContent);
            $alerts = $alerts->merge($promoAlerts);

            // Save current state for future comparison
            $competitor->update([
                'last_website_hash' => md5($pageContent),
                'last_website_check' => now(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Website check failed', [
                'competitor_id' => $competitor->id,
                'url' => $competitor->website_url,
                'error' => $e->getMessage(),
            ]);
        }

        return $alerts;
    }

    protected function detectPriceChanges(Competitor $competitor, string $content): Collection
    {
        $alerts = collect();

        // Extract prices from content using regex patterns
        $pricePattern = '/(\d{1,3}(?:[\s,]\d{3})*(?:\.\d{2})?)\s*(?:so\'m|сум|UZS|sum)/i';
        preg_match_all($pricePattern, $content, $matches);

        if (empty($matches[1])) {
            return $alerts;
        }

        $currentPrices = array_map(function ($price) {
            return (float) preg_replace('/[^\d.]/', '', $price);
        }, $matches[1]);

        $previousPrices = $competitor->tracked_prices ?? [];

        foreach ($currentPrices as $index => $currentPrice) {
            if (isset($previousPrices[$index])) {
                $previousPrice = $previousPrices[$index];
                $changePercent = (($currentPrice - $previousPrice) / $previousPrice) * 100;

                if (abs($changePercent) >= 5) {
                    $alert = $this->createCompetitorAlert($competitor, [
                        'alert_type' => 'price_change',
                        'title' => sprintf('%s - Narx %s',
                            $competitor->name,
                            $changePercent > 0 ? 'oshdi' : 'tushdi'
                        ),
                        'description' => sprintf(
                            'Narx %.1f%% %s (%s dan %s ga)',
                            abs($changePercent),
                            $changePercent > 0 ? 'oshdi' : 'tushdi',
                            number_format($previousPrice),
                            number_format($currentPrice)
                        ),
                        'old_value' => $previousPrice,
                        'new_value' => $currentPrice,
                        'change_percent' => $changePercent,
                        'severity' => abs($changePercent) >= 15 ? 'high' : 'medium',
                    ]);
                    $alerts->push($alert);
                }
            }
        }

        $competitor->update(['tracked_prices' => $currentPrices]);

        return $alerts;
    }

    protected function detectNewProducts(Competitor $competitor, string $content): Collection
    {
        $alerts = collect();

        // Look for "new", "yangi" keywords near product names
        $newProductPatterns = [
            '/yangi\s+(?:mahsulot|kolleksiya|model)/i',
            '/new\s+(?:product|collection|arrival)/i',
            '/(?:mahsulot|kolleksiya)\s+yangi/i',
        ];

        foreach ($newProductPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                // Check if we haven't already alerted about this recently
                $recentAlert = CompetitorAlert::where('competitor_id', $competitor->id)
                    ->where('alert_type', 'new_product')
                    ->where('detected_at', '>=', now()->subDays(7))
                    ->exists();

                if (!$recentAlert) {
                    $alert = $this->createCompetitorAlert($competitor, [
                        'alert_type' => 'new_product',
                        'title' => sprintf('%s - Yangi mahsulot/kolleksiya', $competitor->name),
                        'description' => sprintf('%s yangi mahsulot yoki kolleksiya e\'lon qildi', $competitor->name),
                        'source_url' => $competitor->website_url,
                        'severity' => 'medium',
                    ]);
                    $alerts->push($alert);
                    break;
                }
            }
        }

        return $alerts;
    }

    protected function detectPromotions(Competitor $competitor, string $content): Collection
    {
        $alerts = collect();

        $promoPatterns = [
            '/(\d+)%\s*(?:chegirma|skidka|off|discount)/i',
            '/(?:aksiya|акция|sale|promotion)/i',
            '/(?:chegirma|скидка)\s*(\d+)%/i',
        ];

        foreach ($promoPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $discountPercent = isset($matches[1]) ? (int) $matches[1] : null;

                $recentAlert = CompetitorAlert::where('competitor_id', $competitor->id)
                    ->where('alert_type', 'promotion')
                    ->where('detected_at', '>=', now()->subDays(3))
                    ->exists();

                if (!$recentAlert) {
                    $alert = $this->createCompetitorAlert($competitor, [
                        'alert_type' => 'promotion',
                        'title' => sprintf('%s - Aksiya/Chegirma', $competitor->name),
                        'description' => $discountPercent
                            ? sprintf('%s %d%% chegirma e\'lon qildi', $competitor->name, $discountPercent)
                            : sprintf('%s aksiya boshladi', $competitor->name),
                        'new_value' => $discountPercent,
                        'source_url' => $competitor->website_url,
                        'severity' => ($discountPercent && $discountPercent >= 30) ? 'high' : 'medium',
                    ]);
                    $alerts->push($alert);
                    break;
                }
            }
        }

        return $alerts;
    }

    public function createCompetitorAlert(Competitor $competitor, array $data): CompetitorAlert
    {
        $alert = CompetitorAlert::create(array_merge([
            'business_id' => $competitor->business_id,
            'competitor_id' => $competitor->id,
            'detected_at' => now(),
            'status' => 'new',
        ], $data));

        // Send notification
        $this->sendCompetitorAlertNotification($alert);

        return $alert;
    }

    protected function sendCompetitorAlertNotification(CompetitorAlert $alert): void
    {
        Notification::create([
            'business_id' => $alert->business_id,
            'type' => 'alert',
            'channel' => 'in_app',
            'title' => $alert->title,
            'message' => $alert->description,
            'action_url' => "/competitors/alerts/{$alert->id}",
            'action_text' => 'Ko\'rish',
            'related_type' => CompetitorAlert::class,
            'related_id' => $alert->id,
            'priority' => $alert->severity === 'high' ? 'high' : 'medium',
        ]);
    }

    protected function fetchInstagramMetrics(Competitor $competitor): ?array
    {
        // TODO: Implement Instagram API integration or scraping
        // For now, return mock data structure
        Log::info('Fetching Instagram metrics', ['competitor' => $competitor->instagram_handle]);

        return null;
    }

    protected function fetchTelegramMetrics(Competitor $competitor): ?array
    {
        // TODO: Implement Telegram API integration
        Log::info('Fetching Telegram metrics', ['competitor' => $competitor->telegram_channel]);

        return null;
    }

    protected function fetchWebsiteContent(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch website content', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function isViralContent(array $post, int $followers): bool
    {
        if ($followers <= 0) {
            return false;
        }

        $likes = $post['likes'] ?? 0;
        $comments = $post['comments'] ?? 0;
        $engagement = $likes + $comments;

        // Consider viral if engagement is > 10% of followers
        return ($engagement / $followers) > 0.10;
    }

    public function getRecentCompetitorAlerts(Business $business, int $limit = 10): Collection
    {
        return CompetitorAlert::where('business_id', $business->id)
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->with('competitor')
            ->get();
    }

    public function getCompetitorAlertStats(Business $business, int $days = 30): array
    {
        $alerts = CompetitorAlert::where('business_id', $business->id)
            ->where('detected_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total' => $alerts->count(),
            'new' => $alerts->where('status', 'new')->count(),
            'by_type' => $alerts->groupBy('alert_type')->map->count()->toArray(),
            'by_competitor' => $alerts->groupBy('competitor_id')->map->count()->toArray(),
            'by_severity' => [
                'high' => $alerts->where('severity', 'high')->count(),
                'medium' => $alerts->where('severity', 'medium')->count(),
                'low' => $alerts->where('severity', 'low')->count(),
            ],
        ];
    }

    public function takeScreenshot(string $url, string $filename): ?string
    {
        // TODO: Implement screenshot functionality using Puppeteer/Playwright
        // This would capture visual evidence of competitor changes

        $path = "screenshots/{$filename}";
        Log::info('Screenshot requested', ['url' => $url, 'path' => $path]);

        return null;
    }
}
