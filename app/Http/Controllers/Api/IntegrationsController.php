<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\InstagramAccount;
use App\Models\FacebookPage;
use App\Models\TelegramBot;
use App\Models\WhatsAppAccount;
use App\Models\PosSystem;
use App\Models\GoogleAnalyticsAccount;
use App\Models\YandexMetricaAccount;
use App\Models\GoogleAdsAccount;
use App\Models\YandexDirectAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntegrationsController extends Controller
{
    /**
     * Get integration status for a business
     */
    public function getStatus(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
        ]);

        $businessId = $request->input('business_id');
        $business = Business::findOrFail($businessId);

        // Check each integration status
        $integrations = [
            'instagram' => $this->getInstagramStatus($business),
            'facebook' => $this->getFacebookStatus($business),
            'telegram' => $this->getTelegramStatus($business),
            'whatsapp' => $this->getWhatsAppStatus($business),
            'pos' => $this->getPosStatus($business),
            'google_analytics' => $this->getGoogleAnalyticsStatus($business),
            'yandex_metrica' => $this->getYandexMetricaStatus($business),
            'google_ads' => $this->getGoogleAdsStatus($business),
            'yandex_direct' => $this->getYandexDirectStatus($business),
            'email' => $this->getEmailMarketingStatus($business),
        ];

        return response()->json([
            'success' => true,
            'data' => $integrations,
        ]);
    }

    /**
     * Disconnect an integration
     */
    public function disconnect(Request $request, string $integrationId): JsonResponse
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
        ]);

        $businessId = $request->input('business_id');
        $business = Business::findOrFail($businessId);

        try {
            switch ($integrationId) {
                case 'instagram':
                    $this->disconnectInstagram($business);
                    break;
                case 'facebook':
                    $this->disconnectFacebook($business);
                    break;
                case 'telegram':
                    $this->disconnectTelegram($business);
                    break;
                case 'whatsapp':
                    $this->disconnectWhatsApp($business);
                    break;
                case 'pos':
                    $this->disconnectPos($business);
                    break;
                case 'google_analytics':
                    $this->disconnectGoogleAnalytics($business);
                    break;
                case 'yandex_metrica':
                    $this->disconnectYandexMetrica($business);
                    break;
                case 'google_ads':
                    $this->disconnectGoogleAds($business);
                    break;
                case 'yandex_direct':
                    $this->disconnectYandexDirect($business);
                    break;
                case 'email':
                    // Placeholder for future integrations
                    throw new \Exception("Integration '{$integrationId}' disconnect not yet implemented");
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unknown integration ID',
                    ], 400);
            }

            Log::info("Integration disconnected", [
                'business_id' => $businessId,
                'integration' => $integrationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration disconnected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to disconnect integration", [
                'business_id' => $businessId,
                'integration' => $integrationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync an integration manually
     */
    public function sync(Request $request, string $integrationId): JsonResponse
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
        ]);

        $businessId = $request->input('business_id');
        $business = Business::findOrFail($businessId);

        try {
            $syncResult = null;

            switch ($integrationId) {
                case 'instagram':
                    $syncResult = $this->syncInstagram($business);
                    break;
                case 'facebook':
                    $syncResult = $this->syncFacebook($business);
                    break;
                case 'telegram':
                    $syncResult = $this->syncTelegram($business);
                    break;
                case 'whatsapp':
                    $syncResult = $this->syncWhatsApp($business);
                    break;
                case 'pos':
                    $syncResult = $this->syncPos($business);
                    break;
                case 'google_analytics':
                    $syncResult = $this->syncGoogleAnalytics($business);
                    break;
                case 'yandex_metrica':
                    $syncResult = $this->syncYandexMetrica($business);
                    break;
                case 'google_ads':
                    $syncResult = $this->syncGoogleAds($business);
                    break;
                case 'yandex_direct':
                    $syncResult = $this->syncYandexDirect($business);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Sync not available for this integration',
                    ], 400);
            }

            Log::info("Integration synced", [
                'business_id' => $businessId,
                'integration' => $integrationId,
                'result' => $syncResult,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration synced successfully',
                'data' => $syncResult,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync integration", [
                'business_id' => $businessId,
                'integration' => $integrationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Instagram ====================

    private function getInstagramStatus(Business $business): array
    {
        $account = InstagramAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $account->is_active,
            'connectedAt' => $account->created_at?->toIso8601String(),
            'lastSync' => $account->last_synced_at?->toIso8601String(),
            'username' => $account->username,
            'stats' => [
                ['label' => 'Followers', 'value' => $account->followers_count ?? 0],
                ['label' => 'Posts', 'value' => $account->posts_count ?? 0],
                ['label' => 'Engagement', 'value' => $account->engagement_rate ? round($account->engagement_rate, 1) . '%' : '0%'],
            ],
        ];
    }

    private function disconnectInstagram(Business $business): void
    {
        InstagramAccount::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncInstagram(Business $business): array
    {
        $account = InstagramAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Update last synced timestamp
        $account->last_synced_at = now();
        $account->save();

        // TODO: Trigger Instagram sync job here
        // dispatch(new SyncInstagramDataJob($account));

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Facebook ====================

    private function getFacebookStatus(Business $business): array
    {
        $page = FacebookPage::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $page->is_active,
            'connectedAt' => $page->created_at?->toIso8601String(),
            'lastSync' => $page->last_synced_at?->toIso8601String(),
            'pageName' => $page->page_name,
            'stats' => [
                ['label' => 'Fans', 'value' => $page->fan_count ?? 0],
                ['label' => 'Posts', 'value' => $page->posts_count ?? 0],
                ['label' => 'Reach', 'value' => $page->page_impressions ?? 0],
            ],
        ];
    }

    private function disconnectFacebook(Business $business): void
    {
        FacebookPage::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncFacebook(Business $business): array
    {
        $page = FacebookPage::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $page->last_synced_at = now();
        $page->save();

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Telegram ====================

    private function getTelegramStatus(Business $business): array
    {
        $bot = TelegramBot::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$bot) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $bot->is_active,
            'connectedAt' => $bot->created_at?->toIso8601String(),
            'lastSync' => $bot->last_synced_at?->toIso8601String(),
            'botName' => $bot->bot_name,
            'stats' => [
                ['label' => 'Users', 'value' => $bot->total_users ?? 0],
                ['label' => 'Messages', 'value' => $bot->total_messages ?? 0],
                ['label' => 'Active', 'value' => $bot->active_users ?? 0],
            ],
        ];
    }

    private function disconnectTelegram(Business $business): void
    {
        TelegramBot::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'bot_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncTelegram(Business $business): array
    {
        $bot = TelegramBot::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $bot->last_synced_at = now();
        $bot->save();

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== WhatsApp ====================

    private function getWhatsAppStatus(Business $business): array
    {
        $account = WhatsAppAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $account->is_active,
            'connectedAt' => $account->created_at?->toIso8601String(),
            'lastSync' => $account->last_synced_at?->toIso8601String(),
            'phoneNumber' => $account->phone_number,
            'stats' => [
                ['label' => 'Contacts', 'value' => $account->total_contacts ?? 0],
                ['label' => 'Messages', 'value' => $account->total_messages ?? 0],
                ['label' => 'Delivered', 'value' => $account->delivered_rate ? round($account->delivered_rate, 1) . '%' : '0%'],
            ],
        ];
    }

    private function disconnectWhatsApp(Business $business): void
    {
        WhatsAppAccount::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncWhatsApp(Business $business): array
    {
        $account = WhatsAppAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $account->last_synced_at = now();
        $account->save();

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== POS System ====================

    private function getPosStatus(Business $business): array
    {
        $pos = PosSystem::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$pos) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $pos->is_active,
            'connectedAt' => $pos->created_at?->toIso8601String(),
            'lastSync' => $pos->last_synced_at?->toIso8601String(),
            'systemName' => $pos->system_name,
            'stats' => [
                ['label' => 'Sales', 'value' => $pos->total_sales ?? 0],
                ['label' => 'Transactions', 'value' => $pos->total_transactions ?? 0],
                ['label' => 'Revenue', 'value' => $pos->total_revenue ? number_format($pos->total_revenue) . ' so\'m' : '0'],
            ],
        ];
    }

    private function disconnectPos(Business $business): void
    {
        PosSystem::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'api_key' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncPos(Business $business): array
    {
        $pos = PosSystem::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $pos->last_synced_at = now();
        $pos->save();

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Google Analytics ====================

    private function getGoogleAnalyticsStatus(Business $business): array
    {
        $account = GoogleAnalyticsAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $account->is_active,
            'connectedAt' => $account->created_at?->toIso8601String(),
            'lastSync' => $account->last_synced_at?->toIso8601String(),
            'propertyName' => $account->property_name,
            'websiteUrl' => $account->website_url,
            'stats' => [
                ['label' => 'Users', 'value' => $account->total_users ?? 0],
                ['label' => 'Sessions', 'value' => $account->sessions ?? 0],
                ['label' => 'Conv. Rate', 'value' => $account->conversion_rate ? round($account->conversion_rate, 1) . '%' : '0%'],
            ],
        ];
    }

    private function disconnectGoogleAnalytics(Business $business): void
    {
        GoogleAnalyticsAccount::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'refresh_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncGoogleAnalytics(Business $business): array
    {
        $account = GoogleAnalyticsAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $account->last_synced_at = now();
        $account->save();

        // TODO: Trigger Google Analytics sync job here
        // dispatch(new SyncGoogleAnalyticsJob($account));

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Yandex.Metrica ====================

    private function getYandexMetricaStatus(Business $business): array
    {
        $account = YandexMetricaAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $account->is_active,
            'connectedAt' => $account->created_at?->toIso8601String(),
            'lastSync' => $account->last_synced_at?->toIso8601String(),
            'counterName' => $account->counter_name,
            'websiteUrl' => $account->website_url,
            'stats' => [
                ['label' => 'Visitors', 'value' => $account->visitors ?? 0],
                ['label' => 'Visits', 'value' => $account->visits ?? 0],
                ['label' => 'Conv. Rate', 'value' => $account->conversion_rate ? round($account->conversion_rate, 1) . '%' : '0%'],
            ],
        ];
    }

    private function disconnectYandexMetrica(Business $business): void
    {
        YandexMetricaAccount::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'refresh_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncYandexMetrica(Business $business): array
    {
        $account = YandexMetricaAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $account->last_synced_at = now();
        $account->save();

        // TODO: Trigger Yandex.Metrica sync job here
        // dispatch(new SyncYandexMetricaJob($account));

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Google Ads ====================

    private function getGoogleAdsStatus(Business $business): array
    {
        $account = GoogleAdsAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $account->is_active,
            'connectedAt' => $account->created_at?->toIso8601String(),
            'lastSync' => $account->last_synced_at?->toIso8601String(),
            'accountName' => $account->account_name,
            'stats' => [
                ['label' => 'Campaigns', 'value' => $account->active_campaigns ?? 0],
                ['label' => 'Clicks', 'value' => $account->clicks ?? 0],
                ['label' => 'ROAS', 'value' => $account->roas ? round($account->roas, 2) . 'x' : '0x'],
            ],
        ];
    }

    private function disconnectGoogleAds(Business $business): void
    {
        GoogleAdsAccount::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'refresh_token' => null,
                'developer_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncGoogleAds(Business $business): array
    {
        $account = GoogleAdsAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $account->last_synced_at = now();
        $account->save();

        // TODO: Trigger Google Ads sync job here
        // dispatch(new SyncGoogleAdsJob($account));

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Yandex.Direct ====================

    private function getYandexDirectStatus(Business $business): array
    {
        $account = YandexDirectAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return [
                'isConnected' => false,
                'isActive' => false,
            ];
        }

        return [
            'isConnected' => true,
            'isActive' => $account->is_active,
            'connectedAt' => $account->created_at?->toIso8601String(),
            'lastSync' => $account->last_synced_at?->toIso8601String(),
            'accountName' => $account->account_name,
            'stats' => [
                ['label' => 'Campaigns', 'value' => $account->active_campaigns ?? 0],
                ['label' => 'Clicks', 'value' => $account->clicks ?? 0],
                ['label' => 'ROI', 'value' => $account->roi ? round($account->roi, 1) . '%' : '0%'],
            ],
        ];
    }

    private function disconnectYandexDirect(Business $business): void
    {
        YandexDirectAccount::where('business_id', $business->id)
            ->update([
                'is_active' => false,
                'access_token' => null,
                'refresh_token' => null,
                'disconnected_at' => now(),
            ]);
    }

    private function syncYandexDirect(Business $business): array
    {
        $account = YandexDirectAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $account->last_synced_at = now();
        $account->save();

        // TODO: Trigger Yandex.Direct sync job here
        // dispatch(new SyncYandexDirectJob($account));

        return [
            'synced_at' => now()->toIso8601String(),
            'status' => 'queued',
        ];
    }

    // ==================== Email Marketing (Placeholder) ====================

    private function getEmailMarketingStatus(Business $business): array
    {
        // TODO: Implement Email Marketing integration
        return [
            'isConnected' => false,
            'isActive' => false,
        ];
    }
}
