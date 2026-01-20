<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\GoogleAdsCampaign;
use App\Models\MetaCampaign;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Kampaniya samaradorligini tekshirish va ogohlantirish
 * Har 4 soatda ishga tushadi
 */
class CheckCampaignPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        Log::info('CheckCampaignPerformanceJob started', [
            'business_id' => $this->businessId,
        ]);

        if ($this->businessId) {
            $this->processBusinessCampaigns($this->businessId, $notificationService);
        } else {
            $this->processAllBusinesses($notificationService);
        }

        Log::info('CheckCampaignPerformanceJob completed');
    }

    protected function processAllBusinesses(NotificationService $notificationService): void
    {
        $businesses = Business::where('status', 'active')
            ->where(function ($q) {
                $q->whereHas('metaAdAccounts')
                    ->orWhereHas('googleAdsAccounts');
            })
            ->get();

        foreach ($businesses as $business) {
            try {
                $this->processBusinessCampaigns($business->id, $notificationService);
            } catch (\Exception $e) {
                Log::error('Failed to check campaigns for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processBusinessCampaigns(string $businessId, NotificationService $notificationService): void
    {
        $business = Business::find($businessId);
        if (!$business) {
            return;
        }

        $alerts = [];

        // Meta (Facebook/Instagram) kampaniyalarni tekshirish
        $metaAlerts = $this->checkMetaCampaigns($businessId);
        $alerts = array_merge($alerts, $metaAlerts);

        // Google Ads kampaniyalarni tekshirish
        $googleAlerts = $this->checkGoogleAdsCampaigns($businessId);
        $alerts = array_merge($alerts, $googleAlerts);

        // Alertlarni notification sifatida yuborish
        foreach ($alerts as $alert) {
            $notificationService->send(
                $business,
                null,
                'alert',
                $alert['title'],
                $alert['message'],
                [
                    'icon' => 'chart-bar',
                    'action_url' => $alert['action_url'],
                    'action_text' => 'Ko\'rish',
                    'extra_data' => $alert['data'] ?? [],
                    'notify_all_users' => true,
                ]
            );
        }

        if (count($alerts) > 0) {
            Log::info('Campaign performance alerts sent', [
                'business_id' => $businessId,
                'alerts_count' => count($alerts),
            ]);
        }
    }

    protected function checkMetaCampaigns(string $businessId): array
    {
        $alerts = [];

        $campaigns = MetaCampaign::where('business_id', $businessId)
            ->where('effective_status', 'ACTIVE')
            ->where('total_spend', '>', 0)
            ->get();

        foreach ($campaigns as $campaign) {
            // 1. Budget tez sarflanmoqda (80% dan ortiq)
            if ($campaign->daily_budget > 0 && $campaign->budget_remaining !== null) {
                $budgetUsed = $campaign->daily_budget - $campaign->budget_remaining;
                $usedPercent = ($budgetUsed / $campaign->daily_budget) * 100;

                if ($usedPercent >= 80 && now()->hour < 18) {
                    $alerts[] = [
                        'title' => 'Budget tez sarflanmoqda',
                        'message' => "Meta kampaniya \"{$campaign->name}\" budjetining {$usedPercent}% sarflandi. Kun yakunigacha budget yetmasligi mumkin.",
                        'action_url' => '/marketing/meta/campaigns/' . $campaign->id,
                        'data' => [
                            'Kampaniya' => $campaign->name,
                            'Sarflangan' => round($usedPercent) . '%',
                            'Kunlik budget' => number_format($campaign->daily_budget) . " so'm",
                        ],
                    ];
                }
            }

            // 2. Cost per result juda yuqori (o'rtachadan 2x)
            $primaryResult = $campaign->getPrimaryResult();
            if ($primaryResult['cost'] > 0) {
                // Shu biznes uchun o'rtacha CPRni hisoblash
                $avgCpr = MetaCampaign::where('business_id', $businessId)
                    ->where('objective', $campaign->objective)
                    ->where('id', '!=', $campaign->id)
                    ->where('total_spend', '>', 0)
                    ->avg('cost_per_lead') ?? $primaryResult['cost'];

                if ($primaryResult['cost'] > $avgCpr * 2 && $avgCpr > 0) {
                    $alerts[] = [
                        'title' => 'Yuqori xarajat per natija',
                        'message' => "Meta kampaniya \"{$campaign->name}\" da har bir {$primaryResult['label']} uchun xarajat o'rtachadan 2 baravar yuqori.",
                        'action_url' => '/marketing/meta/campaigns/' . $campaign->id,
                        'data' => [
                            'Kampaniya' => $campaign->name,
                            "Joriy CPR" => number_format($primaryResult['cost']) . " so'm",
                            "O'rtacha CPR" => number_format($avgCpr) . " so'm",
                        ],
                    ];
                }
            }

            // 3. CTR past (0.5% dan kam)
            if ($campaign->total_impressions > 1000 && $campaign->avg_ctr < 0.5) {
                $alerts[] = [
                    'title' => 'Past CTR',
                    'message' => "Meta kampaniya \"{$campaign->name}\" CTR 0.5% dan past. Kreativ yoki targetingni ko'rib chiqing.",
                    'action_url' => '/marketing/meta/campaigns/' . $campaign->id,
                    'data' => [
                        'Kampaniya' => $campaign->name,
                        'CTR' => round($campaign->avg_ctr, 2) . '%',
                        'Impressions' => number_format($campaign->total_impressions),
                    ],
                ];
            }

            // 4. 3 kundan ko'p result yo'q
            if ($campaign->total_spend > 50000 && ($campaign->total_leads + $campaign->total_purchases + $campaign->total_messages) === 0) {
                $alerts[] = [
                    'title' => 'Natija yo\'q',
                    'message' => "Meta kampaniya \"{$campaign->name}\" 50,000 so'mdan ko'p sarfladi, lekin hech qanday natija yo'q. Kampaniyani to'xtatishni ko'rib chiqing.",
                    'action_url' => '/marketing/meta/campaigns/' . $campaign->id,
                    'data' => [
                        'Kampaniya' => $campaign->name,
                        'Sarflangan' => number_format($campaign->total_spend) . " so'm",
                        'Natijalar' => '0',
                    ],
                ];
            }
        }

        return $alerts;
    }

    protected function checkGoogleAdsCampaigns(string $businessId): array
    {
        $alerts = [];

        $campaigns = GoogleAdsCampaign::where('business_id', $businessId)
            ->where('status', 'ENABLED')
            ->where('total_cost', '>', 0)
            ->get();

        foreach ($campaigns as $campaign) {
            // 1. Budget 80% sarflangan
            if ($campaign->budget > 0) {
                $usedPercent = ($campaign->total_cost / $campaign->budget) * 100;

                if ($usedPercent >= 80 && now()->hour < 18) {
                    $alerts[] = [
                        'title' => 'Budget tez sarflanmoqda',
                        'message' => "Google Ads kampaniya \"{$campaign->name}\" budjetining {$usedPercent}% sarflandi.",
                        'action_url' => '/marketing/google-ads/campaigns/' . $campaign->id,
                        'data' => [
                            'Kampaniya' => $campaign->name,
                            'Sarflangan' => round($usedPercent) . '%',
                        ],
                    ];
                }
            }

            // 2. CPC juda yuqori
            if ($campaign->total_clicks > 0) {
                $cpc = $campaign->total_cost / $campaign->total_clicks;

                $avgCpc = GoogleAdsCampaign::where('business_id', $businessId)
                    ->where('id', '!=', $campaign->id)
                    ->where('total_clicks', '>', 0)
                    ->selectRaw('AVG(total_cost / total_clicks) as avg_cpc')
                    ->value('avg_cpc') ?? $cpc;

                if ($cpc > $avgCpc * 2 && $avgCpc > 0) {
                    $alerts[] = [
                        'title' => 'Yuqori CPC',
                        'message' => "Google Ads kampaniya \"{$campaign->name}\" CPC o'rtachadan 2 baravar yuqori.",
                        'action_url' => '/marketing/google-ads/campaigns/' . $campaign->id,
                        'data' => [
                            'Kampaniya' => $campaign->name,
                            'Joriy CPC' => number_format($cpc) . " so'm",
                            "O'rtacha CPC" => number_format($avgCpc) . " so'm",
                        ],
                    ];
                }
            }

            // 3. CTR past (1% dan kam search uchun)
            if ($campaign->total_impressions > 1000 && $campaign->total_clicks > 0) {
                $ctr = ($campaign->total_clicks / $campaign->total_impressions) * 100;

                if ($ctr < 1 && $campaign->campaign_type === 'SEARCH') {
                    $alerts[] = [
                        'title' => 'Past CTR',
                        'message' => "Google Ads kampaniya \"{$campaign->name}\" CTR 1% dan past. Kalit so'zlar va reklamalarni ko'rib chiqing.",
                        'action_url' => '/marketing/google-ads/campaigns/' . $campaign->id,
                        'data' => [
                            'Kampaniya' => $campaign->name,
                            'CTR' => round($ctr, 2) . '%',
                        ],
                    ];
                }
            }

            // 4. Konversiya yo'q, ko'p sarflangan
            if ($campaign->total_cost > 100000 && $campaign->total_conversions === 0) {
                $alerts[] = [
                    'title' => 'Konversiya yo\'q',
                    'message' => "Google Ads kampaniya \"{$campaign->name}\" 100,000 so'mdan ko'p sarfladi, lekin konversiya yo'q.",
                    'action_url' => '/marketing/google-ads/campaigns/' . $campaign->id,
                    'data' => [
                        'Kampaniya' => $campaign->name,
                        'Sarflangan' => number_format($campaign->total_cost) . " so'm",
                    ],
                ];
            }
        }

        return $alerts;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckCampaignPerformanceJob failed', [
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
