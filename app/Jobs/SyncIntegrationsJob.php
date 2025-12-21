<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Integration;
use App\Models\ChannelMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncIntegrationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 600;

    public function __construct(
        public ?Business $business = null,
        public ?string $integrationType = null
    ) {}

    public function handle(): void
    {
        if ($this->business) {
            $this->syncForBusiness($this->business);
        } else {
            $this->syncForAllBusinesses();
        }
    }

    protected function syncForBusiness(Business $business): void
    {
        $query = Integration::where('business_id', $business->id)
            ->where('is_active', true)
            ->whereNotNull('access_token');

        if ($this->integrationType) {
            $query->where('type', $this->integrationType);
        }

        $integrations = $query->get();

        foreach ($integrations as $integration) {
            $this->syncIntegration($integration);
        }
    }

    protected function syncForAllBusinesses(): void
    {
        $query = Integration::where('is_active', true)
            ->whereNotNull('access_token');

        if ($this->integrationType) {
            $query->where('type', $this->integrationType);
        }

        $integrations = $query->get();

        foreach ($integrations as $integration) {
            try {
                $this->syncIntegration($integration);
            } catch (\Exception $e) {
                // Log but continue with other integrations
                continue;
            }
        }

        Log::info('Integrations sync completed', [
            'count' => $integrations->count(),
            'type' => $this->integrationType ?? 'all',
        ]);
    }

    protected function syncIntegration(Integration $integration): void
    {
        try {
            $metrics = match ($integration->type) {
                'instagram' => $this->syncInstagram($integration),
                'facebook' => $this->syncFacebook($integration),
                'telegram' => $this->syncTelegram($integration),
                'google_ads' => $this->syncGoogleAds($integration),
                'yandex_direct' => $this->syncYandexDirect($integration),
                default => null,
            };

            if ($metrics) {
                $this->saveMetrics($integration, $metrics);
            }

            $integration->update([
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

            Log::info('Integration synced', [
                'integration_id' => $integration->id,
                'type' => $integration->type,
            ]);
        } catch (\Exception $e) {
            $integration->update([
                'sync_error' => $e->getMessage(),
            ]);

            Log::error('Integration sync failed', [
                'integration_id' => $integration->id,
                'type' => $integration->type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function syncInstagram(Integration $integration): ?array
    {
        // Instagram Graph API sync
        $token = $integration->access_token;
        $accountId = $integration->external_id;

        if (!$token || !$accountId) {
            return null;
        }

        try {
            // Get account insights
            $response = Http::get("https://graph.instagram.com/{$accountId}/insights", [
                'metric' => 'impressions,reach,profile_views,follower_count',
                'period' => 'day',
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Instagram API error: ' . $response->body());
            }

            $data = $response->json();

            return [
                'impressions' => $this->extractInsightValue($data, 'impressions'),
                'reach' => $this->extractInsightValue($data, 'reach'),
                'profile_views' => $this->extractInsightValue($data, 'profile_views'),
                'followers_count' => $this->extractInsightValue($data, 'follower_count'),
            ];
        } catch (\Exception $e) {
            Log::error('Instagram sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function syncFacebook(Integration $integration): ?array
    {
        $token = $integration->access_token;
        $pageId = $integration->external_id;

        if (!$token || !$pageId) {
            return null;
        }

        try {
            $response = Http::get("https://graph.facebook.com/v18.0/{$pageId}/insights", [
                'metric' => 'page_impressions,page_engaged_users,page_fans',
                'period' => 'day',
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Facebook API error: ' . $response->body());
            }

            $data = $response->json();

            return [
                'impressions' => $this->extractInsightValue($data, 'page_impressions'),
                'engagement' => $this->extractInsightValue($data, 'page_engaged_users'),
                'followers_count' => $this->extractInsightValue($data, 'page_fans'),
            ];
        } catch (\Exception $e) {
            Log::error('Facebook sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function syncTelegram(Integration $integration): ?array
    {
        // Telegram Bot API sync for channel stats
        $token = $integration->access_token;
        $channelId = $integration->external_id;

        if (!$token || !$channelId) {
            return null;
        }

        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getChatMemberCount", [
                'chat_id' => $channelId,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Telegram API error: ' . $response->body());
            }

            $data = $response->json();

            return [
                'followers_count' => $data['result'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Telegram sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function syncGoogleAds(Integration $integration): ?array
    {
        // Google Ads API sync
        // This would require proper OAuth2 flow and Google Ads API client
        Log::info('Google Ads sync placeholder', ['integration_id' => $integration->id]);
        return null;
    }

    protected function syncYandexDirect(Integration $integration): ?array
    {
        // Yandex Direct API sync
        $token = $integration->access_token;

        if (!$token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post('https://api.direct.yandex.com/json/v5/reports', [
                'params' => [
                    'SelectionCriteria' => [
                        'DateFrom' => Carbon::yesterday()->format('Y-m-d'),
                        'DateTo' => Carbon::yesterday()->format('Y-m-d'),
                    ],
                    'FieldNames' => ['Impressions', 'Clicks', 'Cost', 'Conversions'],
                    'ReportType' => 'ACCOUNT_PERFORMANCE_REPORT',
                    'DateRangeType' => 'CUSTOM_DATE',
                    'Format' => 'TSV',
                    'IncludeVAT' => 'YES',
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Yandex Direct API error: ' . $response->body());
            }

            // Parse TSV response
            $lines = explode("\n", $response->body());
            if (count($lines) >= 2) {
                $values = str_getcsv($lines[1], "\t");
                return [
                    'impressions' => (int) ($values[0] ?? 0),
                    'clicks' => (int) ($values[1] ?? 0),
                    'ad_spend' => (float) ($values[2] ?? 0),
                    'conversions' => (int) ($values[3] ?? 0),
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Yandex Direct sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function saveMetrics(Integration $integration, array $metrics): void
    {
        $today = Carbon::today();

        // Find or create today's metric record
        $channelMetric = ChannelMetric::updateOrCreate(
            [
                'business_id' => $integration->business_id,
                'channel' => $integration->type,
                'metric_date' => $today,
            ],
            array_merge($metrics, [
                'synced_at' => now(),
            ])
        );

        Log::debug('Metrics saved', [
            'channel_metric_id' => $channelMetric->id,
            'integration_type' => $integration->type,
            'metrics' => $metrics,
        ]);
    }

    protected function extractInsightValue(array $data, string $metric): int
    {
        if (!isset($data['data'])) {
            return 0;
        }

        foreach ($data['data'] as $insight) {
            if ($insight['name'] === $metric && isset($insight['values'][0]['value'])) {
                return (int) $insight['values'][0]['value'];
            }
        }

        return 0;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncIntegrationsJob failed', [
            'business_id' => $this->business?->id,
            'type' => $this->integrationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
