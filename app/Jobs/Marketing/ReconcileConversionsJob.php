<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\ConversionReconciliation;
use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaCampaignInsight;
use App\Models\Sale;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Meta/Google konversiyalarini haqiqiy sotuvlar bilan solishtirish
 *
 * Platformalar report qilgan konversiya sonini
 * haqiqiy sotuv bazasidagi ma'lumotlar bilan taqqoslaydi.
 */
class ReconcileConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        protected ?string $businessId = null,
        protected ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::yesterday();
    }

    public function handle(NotificationService $notificationService): void
    {
        Log::info('ReconcileConversionsJob: Starting reconciliation', [
            'date' => $this->date->format('Y-m-d'),
            'business_id' => $this->businessId ?? 'all',
        ]);

        $query = Business::where('status', 'active');

        if ($this->businessId) {
            $query->where('id', $this->businessId);
        }

        $businesses = $query->get();
        $totalReconciled = 0;
        $discrepancies = [];

        foreach ($businesses as $business) {
            $result = $this->reconcileBusiness($business);
            $totalReconciled += $result['count'];

            if ($result['has_discrepancy']) {
                $discrepancies[] = [
                    'business' => $business->name,
                    'discrepancy_percent' => $result['discrepancy_percent'],
                ];

                // Notification yuborish
                $this->sendDiscrepancyAlert($business, $result, $notificationService);
            }
        }

        Log::info('ReconcileConversionsJob: Completed', [
            'businesses_processed' => $businesses->count(),
            'total_reconciled' => $totalReconciled,
            'discrepancies_found' => count($discrepancies),
        ]);
    }

    /**
     * Bitta biznes uchun reconciliation
     */
    protected function reconcileBusiness(Business $business): array
    {
        $count = 0;
        $hasDiscrepancy = false;
        $totalDiscrepancyPercent = 0;

        // 1. Meta Ads reconciliation
        $metaResult = $this->reconcileMetaAds($business);
        $count += $metaResult['count'];
        if ($metaResult['discrepancy_percent'] > 10) {
            $hasDiscrepancy = true;
            $totalDiscrepancyPercent = max($totalDiscrepancyPercent, $metaResult['discrepancy_percent']);
        }

        // 2. Google Ads reconciliation (agar mavjud bo'lsa)
        // TODO: Google Ads integration

        return [
            'count' => $count,
            'has_discrepancy' => $hasDiscrepancy,
            'discrepancy_percent' => $totalDiscrepancyPercent,
            'meta' => $metaResult,
        ];
    }

    /**
     * Meta Ads reconciliation
     */
    protected function reconcileMetaAds(Business $business): array
    {
        $result = [
            'count' => 0,
            'platform_conversions' => 0,
            'actual_conversions' => 0,
            'discrepancy_percent' => 0,
        ];

        // Meta ad accountlarni olish
        $adAccounts = MetaAdAccount::where('business_id', $business->id)->get();

        if ($adAccounts->isEmpty()) {
            return $result;
        }

        foreach ($adAccounts as $adAccount) {
            // Kampaniyalar bo'yicha
            $campaigns = MetaCampaign::where('ad_account_id', $adAccount->id)->get();

            foreach ($campaigns as $campaign) {
                $reconciliation = $this->reconcileMetaCampaign($business, $campaign);
                if ($reconciliation) {
                    $result['count']++;
                    $result['platform_conversions'] += $reconciliation->platform_conversions;
                    $result['actual_conversions'] += $reconciliation->actual_conversions;
                }
            }
        }

        // Umumiy discrepancy hisoblash
        if ($result['platform_conversions'] > 0) {
            $result['discrepancy_percent'] = abs(
                ($result['platform_conversions'] - $result['actual_conversions'])
                / $result['platform_conversions']
            ) * 100;
        }

        return $result;
    }

    /**
     * Bitta Meta campaign uchun reconciliation
     */
    protected function reconcileMetaCampaign(Business $business, MetaCampaign $campaign): ?ConversionReconciliation
    {
        // Platform reported data
        $insight = MetaCampaignInsight::where('campaign_id', $campaign->id)
            ->whereDate('date', $this->date)
            ->first();

        if (!$insight) {
            return null;
        }

        // Platform konversiyalari (leads + purchases + messages)
        $platformConversions = ($insight->leads ?? 0)
            + ($insight->purchases ?? 0)
            + ($insight->conversions ?? 0);

        $platformValue = ($insight->conversion_value ?? 0);

        // Actual conversions from Sales
        $actualData = Sale::where('business_id', $business->id)
            ->where('campaign_id', $campaign->id)
            ->whereDate('sale_date', $this->date)
            ->selectRaw('COUNT(*) as conversions, SUM(amount) as total_value')
            ->first();

        $actualConversions = (int) ($actualData->conversions ?? 0);
        $actualValue = (float) ($actualData->total_value ?? 0);

        // Reconciliation yaratish yoki yangilash
        $reconciliation = ConversionReconciliation::updateOrCreate(
            [
                'business_id' => $business->id,
                'reconciliation_date' => $this->date,
                'platform' => 'meta',
                'platform_campaign_id' => $campaign->meta_campaign_id,
            ],
            [
                'platform_adset_id' => null,
                'platform_conversions' => $platformConversions,
                'platform_conversion_value' => $platformValue,
                'actual_conversions' => $actualConversions,
                'actual_conversion_value' => $actualValue,
                'metadata' => [
                    'campaign_name' => $campaign->name,
                    'campaign_objective' => $campaign->objective,
                    'insight_id' => $insight->id,
                ],
            ]
        );

        // Discrepancy hisoblash
        $reconciliation->calculateDiscrepancy();
        $reconciliation->save();

        return $reconciliation;
    }

    /**
     * Discrepancy alert yuborish
     */
    protected function sendDiscrepancyAlert(
        Business $business,
        array $result,
        NotificationService $notificationService
    ): void {
        try {
            $discrepancyPercent = round($result['discrepancy_percent'], 1);

            $notificationService->sendInsight(
                $business->id,
                '⚠️ Konversiya farqi aniqlandi',
                "Meta Ads report qilgan konversiyalar va haqiqiy sotuvlar o'rtasida {$discrepancyPercent}% farq bor. "
                    . "Platform: {$result['meta']['platform_conversions']} ta, Haqiqiy: {$result['meta']['actual_conversions']} ta.",
                'warning',
                [
                    'platform_conversions' => $result['meta']['platform_conversions'],
                    'actual_conversions' => $result['meta']['actual_conversions'],
                    'discrepancy_percent' => $discrepancyPercent,
                    'action_url' => '/marketing/reconciliation',
                ]
            );
        } catch (\Exception $e) {
            Log::error('ReconcileConversionsJob: Failed to send alert', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function tags(): array
    {
        return [
            'marketing',
            'reconciliation',
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
