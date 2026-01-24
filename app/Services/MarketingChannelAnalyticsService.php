<?php

namespace App\Services;

use App\Models\Business;
use App\Models\FacebookPage;
use App\Models\InstagramAccount;
use App\Models\Lead;
use App\Models\TelegramBot;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Cache;

class MarketingChannelAnalyticsService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Get all connected channels with analytics
     */
    public function getConnectedChannels(Business $business): array
    {
        $cacheKey = "marketing_channel_analytics_{$business->id}";

        return Cache::remember($cacheKey, 300, function () use ($business) {
            $channels = [];

            // Instagram Accounts
            try {
                $instagramAccounts = InstagramAccount::where('business_id', $business->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($instagramAccounts as $account) {
                    $channels[] = $this->formatInstagramChannel($account, $business);
                }
            } catch (\Exception $e) {
                // Table doesn't exist or other error - skip silently
            }

            // Facebook Pages
            try {
                $facebookPages = FacebookPage::where('business_id', $business->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($facebookPages as $page) {
                    $channels[] = $this->formatFacebookChannel($page, $business);
                }
            } catch (\Exception $e) {
                // Table doesn't exist or other error - skip silently
            }

            // Telegram Bots
            try {
                $telegramBots = TelegramBot::where('business_id', $business->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($telegramBots as $bot) {
                    $channels[] = $this->formatTelegramChannel($bot, $business);
                }
            } catch (\Exception $e) {
                // Table doesn't exist or other error - skip silently
            }

            // Calculate effectiveness scores
            $channels = $this->calculateEffectivenessScores($channels, $business);

            // Sort by effectiveness score
            usort($channels, fn ($a, $b) => $b['effectiveness_score'] <=> $a['effectiveness_score']);

            return $channels;
        });
    }

    /**
     * Format Instagram account as channel
     */
    protected function formatInstagramChannel(InstagramAccount $account, Business $business): array
    {
        $leadsFromChannel = 0;
        try {
            $leadsFromChannel = Lead::where('business_id', $business->id)
                ->where(function ($q) {
                    $q->where('utm_source', 'like', '%instagram%')
                      ->orWhere('first_touch_source', 'like', '%instagram%');
                })
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        } catch (\Exception $e) {
            $leadsFromChannel = 0;
        }

        $reach = 0;
        try {
            if (method_exists($account, 'dailyInsights')) {
                $reach = $account->dailyInsights()
                    ->where('insight_date', '>=', now()->subDays(30))
                    ->sum('reach') ?? 0;
            }
        } catch (\Exception $e) {
            $reach = 0;
        }

        return [
            'id' => $account->id,
            'type' => 'integration',
            'platform' => 'Instagram',
            'platform_icon' => 'instagram',
            'name' => '@' . $account->username,
            'profile_url' => "https://instagram.com/{$account->username}",
            'profile_picture' => $account->profile_picture_url ?? null,
            'is_connected' => true,
            'last_synced_at' => $account->last_synced_at?->diffForHumans(),
            'metrics' => [
                'followers' => $account->followers_count ?? 0,
                'following' => $account->follows_count ?? 0,
                'posts' => $account->media_count ?? 0,
                'engagement_rate' => $account->engagement_rate ?? 0,
                'reach_30d' => $reach,
                'leads_30d' => $leadsFromChannel,
            ],
            'growth' => $this->calculateInstagramGrowth($account),
            'effectiveness_score' => 0,
        ];
    }

    /**
     * Format Facebook page as channel
     */
    protected function formatFacebookChannel(FacebookPage $page, Business $business): array
    {
        $leadsFromChannel = 0;
        try {
            $leadsFromChannel = Lead::where('business_id', $business->id)
                ->where(function ($q) {
                    $q->where('utm_source', 'like', '%facebook%')
                      ->orWhere('first_touch_source', 'like', '%facebook%');
                })
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        } catch (\Exception $e) {
            $leadsFromChannel = 0;
        }

        return [
            'id' => $page->id,
            'type' => 'integration',
            'platform' => 'Facebook',
            'platform_icon' => 'facebook',
            'name' => $page->page_name ?? $page->name ?? 'Facebook Page',
            'profile_url' => $page->page_username ? "https://facebook.com/{$page->page_username}" : null,
            'profile_picture' => $page->profile_picture_url ?? null,
            'is_connected' => true,
            'last_synced_at' => $page->last_synced_at?->diffForHumans(),
            'metrics' => [
                'followers' => $page->fan_count ?? $page->followers_count ?? 0,
                'posts' => $page->posts_count ?? 0,
                'impressions' => $page->page_impressions ?? 0,
                'engaged_users' => $page->page_engaged_users ?? 0,
                'engagement_rate' => $page->engagement_rate ?? 0,
                'leads_30d' => $leadsFromChannel,
            ],
            'growth' => null,
            'effectiveness_score' => 0,
        ];
    }

    /**
     * Format Telegram bot as channel
     */
    protected function formatTelegramChannel(TelegramBot $bot, Business $business): array
    {
        $totalUsers = 0;
        $activeUsers = 0;
        try {
            $totalUsers = TelegramUser::where('telegram_bot_id', $bot->id)->count();
            $activeUsers = TelegramUser::where('telegram_bot_id', $bot->id)
                ->where('last_activity_at', '>=', now()->subDays(30))
                ->count();
        } catch (\Exception $e) {
            // Table doesn't exist
        }

        $leadsFromChannel = 0;
        try {
            $leadsFromChannel = Lead::where('business_id', $business->id)
                ->where(function ($q) {
                    $q->where('utm_source', 'like', '%telegram%')
                      ->orWhere('first_touch_source', 'like', '%telegram%');
                })
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        } catch (\Exception $e) {
            $leadsFromChannel = 0;
        }

        $engagementRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;

        $funnelsCount = 0;
        try {
            $funnelsCount = $bot->funnels()->count();
        } catch (\Exception $e) {
            // Funnels relation doesn't exist
        }

        return [
            'id' => $bot->id,
            'type' => 'integration',
            'platform' => 'Telegram',
            'platform_icon' => 'telegram',
            'name' => '@' . $bot->bot_username,
            'profile_url' => "https://t.me/{$bot->bot_username}",
            'profile_picture' => null,
            'is_connected' => true,
            'last_synced_at' => null,
            'metrics' => [
                'followers' => $totalUsers,
                'active_users' => $activeUsers,
                'engagement_rate' => $engagementRate,
                'funnels' => $funnelsCount,
                'leads_30d' => $leadsFromChannel,
            ],
            'growth' => $this->calculateTelegramGrowth($bot),
            'effectiveness_score' => 0,
        ];
    }

    /**
     * Calculate Instagram growth
     */
    protected function calculateInstagramGrowth(InstagramAccount $account): ?array
    {
        try {
            if (!method_exists($account, 'dailyInsights')) {
                return null;
            }

            $insights = $account->dailyInsights()
                ->where('insight_date', '>=', now()->subDays(30))
                ->orderBy('insight_date')
                ->get();

            if ($insights->count() < 2) {
                return null;
            }

            $firstFollowers = $insights->first()->follower_count ?? 0;
            $lastFollowers = $insights->last()->follower_count ?? 0;

            $change = $lastFollowers - $firstFollowers;
            $changePercent = $firstFollowers > 0 ? round(($change / $firstFollowers) * 100, 1) : 0;

            return [
                'period' => '30d',
                'change' => $change,
                'change_percent' => $changePercent,
                'trend' => $change >= 0 ? 'up' : 'down',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate Telegram bot growth
     */
    protected function calculateTelegramGrowth(TelegramBot $bot): ?array
    {
        try {
            $newUsers = TelegramUser::where('telegram_bot_id', $bot->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $totalUsers = TelegramUser::where('telegram_bot_id', $bot->id)->count();
            $changePercent = $totalUsers > 0 ? round(($newUsers / $totalUsers) * 100, 1) : 0;

            return [
                'period' => '30d',
                'change' => $newUsers,
                'change_percent' => $changePercent,
                'trend' => $newUsers > 0 ? 'up' : 'stable',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate effectiveness scores for all channels
     */
    protected function calculateEffectivenessScores(array $channels, Business $business): array
    {
        $totalLeads = 0;
        foreach ($channels as $channel) {
            $totalLeads += $channel['metrics']['leads_30d'] ?? 0;
        }

        foreach ($channels as &$channel) {
            $score = 0;

            // Engagement score (40%)
            $engagementRate = $channel['metrics']['engagement_rate'] ?? 0;
            if ($engagementRate >= 5) {
                $score += 40;
            } elseif ($engagementRate >= 3) {
                $score += 30;
            } elseif ($engagementRate >= 1) {
                $score += 20;
            } elseif ($engagementRate > 0) {
                $score += 10;
            }

            // Lead generation score (35%)
            $leads = $channel['metrics']['leads_30d'] ?? 0;
            if ($totalLeads > 0) {
                $leadShare = ($leads / $totalLeads) * 100;
                if ($leadShare >= 40) {
                    $score += 35;
                } elseif ($leadShare >= 25) {
                    $score += 28;
                } elseif ($leadShare >= 10) {
                    $score += 20;
                } elseif ($leadShare > 0) {
                    $score += 10;
                }
            }

            // Growth score (25%)
            $growth = $channel['growth'];
            if ($growth) {
                $changePercent = $growth['change_percent'] ?? 0;
                if ($changePercent >= 10) {
                    $score += 25;
                } elseif ($changePercent >= 5) {
                    $score += 20;
                } elseif ($changePercent >= 0) {
                    $score += 15;
                } elseif ($changePercent >= -5) {
                    $score += 10;
                } else {
                    $score += 5;
                }
            } else {
                $score += 10;
            }

            $channel['effectiveness_score'] = $score;
            $channel['effectiveness_label'] = $this->getEffectivenessLabel($score);
            $channel['effectiveness_color'] = $this->getEffectivenessColor($score);
        }

        return $channels;
    }

    protected function getEffectivenessLabel(int $score): string
    {
        if ($score >= 80) return "A'lo";
        if ($score >= 60) return 'Yaxshi';
        if ($score >= 40) return "O'rtacha";
        if ($score >= 20) return 'Past';
        return 'Juda past';
    }

    protected function getEffectivenessColor(int $score): string
    {
        if ($score >= 80) return 'green';
        if ($score >= 60) return 'blue';
        if ($score >= 40) return 'yellow';
        if ($score >= 20) return 'orange';
        return 'red';
    }

    /**
     * Get overall channel statistics
     */
    public function getOverallStats(array $channels): array
    {
        $totalFollowers = 0;
        $totalLeads = 0;
        $totalEngagement = 0;
        $engagementCount = 0;

        foreach ($channels as $channel) {
            $metrics = $channel['metrics'];
            $totalFollowers += $metrics['followers'] ?? 0;
            $totalLeads += $metrics['leads_30d'] ?? 0;

            if (isset($metrics['engagement_rate']) && $metrics['engagement_rate'] > 0) {
                $totalEngagement += $metrics['engagement_rate'];
                $engagementCount++;
            }
        }

        $avgEngagement = $engagementCount > 0 ? round($totalEngagement / $engagementCount, 1) : 0;
        $avgEffectiveness = count($channels) > 0
            ? round(array_sum(array_column($channels, 'effectiveness_score')) / count($channels))
            : 0;

        return [
            'total_channels' => count($channels),
            'total_followers' => $totalFollowers,
            'total_leads_30d' => $totalLeads,
            'avg_engagement' => $avgEngagement,
            'avg_effectiveness' => $avgEffectiveness,
            'best_channel' => $channels[0] ?? null,
        ];
    }

    /**
     * Get AI recommendations for channels
     */
    public function getAIRecommendations(Business $business, array $channels, array $stats): array
    {
        if (empty($channels)) {
            return $this->getDefaultRecommendations();
        }

        // Generate basic recommendations
        return $this->generateBasicRecommendations($channels, $stats);
    }

    /**
     * Generate basic recommendations
     */
    protected function generateBasicRecommendations(array $channels, array $stats): array
    {
        $recommendations = [];

        // Best performing channel
        if (!empty($channels)) {
            $best = $channels[0];
            if ($best['effectiveness_score'] >= 60) {
                $recommendations[] = [
                    'type' => 'success',
                    'icon' => 'trophy',
                    'title' => "{$best['platform']} eng samarali kanal",
                    'description' => "{$best['effectiveness_score']}% samaradorlik. Bu kanalga ko'proq e'tibor bering!",
                    'priority' => 'high',
                ];
            }
        }

        // Low engagement warning
        if ($stats['avg_engagement'] < 2 && $stats['avg_engagement'] > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'icon' => 'chart-bar',
                'title' => "Engagement pastroq",
                'description' => "O'rtacha {$stats['avg_engagement']}%. Stories, reels va interaktiv kontent qo'shing.",
                'priority' => 'high',
            ];
        }

        // Few leads suggestion
        if ($stats['total_leads_30d'] < 10) {
            $recommendations[] = [
                'type' => 'action',
                'icon' => 'user-plus',
                'title' => 'Lead generatsiyasini kuchaytiring',
                'description' => "30 kunda {$stats['total_leads_30d']} ta lead. Bio'ga link, CTA va maxsus takliflar qo'shing.",
                'priority' => 'high',
            ];
        }

        // Multi-channel suggestion
        if (count($channels) < 3) {
            $existingPlatforms = array_column($channels, 'platform');
            $suggested = null;

            if (!in_array('Instagram', $existingPlatforms)) {
                $suggested = 'Instagram';
            } elseif (!in_array('Telegram', $existingPlatforms)) {
                $suggested = 'Telegram';
            } elseif (!in_array('Facebook', $existingPlatforms)) {
                $suggested = 'Facebook';
            }

            if ($suggested) {
                $recommendations[] = [
                    'type' => 'info',
                    'icon' => 'plus-circle',
                    'title' => "{$suggested} ulashni o'ylab ko'ring",
                    'description' => "Ko'p kanallilik marketingda samaradorlikni oshiradi.",
                    'priority' => 'medium',
                ];
            }
        }

        // Growth tip
        $hasGrowingChannel = false;
        foreach ($channels as $channel) {
            if (isset($channel['growth']['change_percent']) && $channel['growth']['change_percent'] > 5) {
                $hasGrowingChannel = true;
                break;
            }
        }

        if (!$hasGrowingChannel && !empty($channels)) {
            $recommendations[] = [
                'type' => 'tip',
                'icon' => 'lightbulb',
                'title' => "O'sish strategiyasi",
                'description' => "Haftalik post rejasi tuzing. Doimiylik = o'sish.",
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }

    /**
     * Get default recommendations when no channels connected
     */
    protected function getDefaultRecommendations(): array
    {
        return [
            [
                'type' => 'action',
                'icon' => 'link',
                'title' => 'Birinchi kanalni ulang',
                'description' => "Integratsiyalar bo'limidan Instagram, Telegram yoki boshqa kanallarni ulang.",
                'priority' => 'high',
            ],
            [
                'type' => 'info',
                'icon' => 'instagram',
                'title' => "Instagram tavsiya qilinadi",
                'description' => "O'zbekistonda eng ko'p auditoriya Instagram'da. Birinchi navbatda uni ulang.",
                'priority' => 'high',
            ],
            [
                'type' => 'info',
                'icon' => 'telegram',
                'title' => 'Telegram bot yarating',
                'description' => 'Mijozlar bilan 24/7 avtomatik aloqa qilish imkoniyati.',
                'priority' => 'medium',
            ],
        ];
    }

    /**
     * Get available platforms for connection
     */
    public function getAvailablePlatforms(Business $business): array
    {
        $connected = [];

        try {
            if (InstagramAccount::where('business_id', $business->id)->where('is_active', true)->exists()) {
                $connected[] = 'instagram';
            }
        } catch (\Exception $e) {
            // Table doesn't exist
        }

        try {
            if (FacebookPage::where('business_id', $business->id)->where('is_active', true)->exists()) {
                $connected[] = 'facebook';
            }
        } catch (\Exception $e) {
            // Table doesn't exist
        }

        try {
            if (TelegramBot::where('business_id', $business->id)->where('is_active', true)->exists()) {
                $connected[] = 'telegram';
            }
        } catch (\Exception $e) {
            // Table doesn't exist
        }

        return [
            [
                'id' => 'instagram',
                'name' => 'Instagram',
                'icon' => 'instagram',
                'color' => 'from-purple-600 via-pink-500 to-orange-400',
                'is_connected' => in_array('instagram', $connected),
                'connect_url' => '/integrations/instagram',
                'description' => 'Postlar, stories, reels analitikasi',
            ],
            [
                'id' => 'facebook',
                'name' => 'Facebook',
                'icon' => 'facebook',
                'color' => 'bg-blue-600',
                'is_connected' => in_array('facebook', $connected),
                'connect_url' => '/integrations/facebook',
                'description' => 'Sahifa va reklama analitikasi',
            ],
            [
                'id' => 'telegram',
                'name' => 'Telegram',
                'icon' => 'telegram',
                'color' => 'bg-sky-500',
                'is_connected' => in_array('telegram', $connected),
                'connect_url' => '/integrations/telegram',
                'description' => 'Bot statistikasi va funnellar',
            ],
        ];
    }

    /**
     * Clear cache for business
     */
    public function clearCache(Business $business): void
    {
        Cache::forget("marketing_channel_analytics_{$business->id}");
    }
}
