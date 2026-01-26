<?php

namespace App\Listeners\Marketing;

use App\Events\LeadWon;
use App\Events\LeadScoreUpdated;
use App\Events\LeadQualificationChanged;
use App\Events\Sales\DealClosed;
use App\Events\Sales\DealLost;
use App\Jobs\Marketing\CalculateMarketingKpiSnapshotsJob;
use App\Models\MarketingKpiSnapshot;
use App\Services\Marketing\MarketingKpiCalculatorService;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * MarketingIntegrationListener - Sales eventlarini tinglab Marketing KPI yangilash
 *
 * Bu listener Sales va Marketing modullarini bog'laydi:
 * - LeadWon → Marketing Revenue va Won Count yangilash
 * - DealClosed → Marketing Attribution tracking
 * - LeadQualificationChanged → MQL/SQL countlarni yangilash
 *
 * DRY: HasKpiCalculation traitdan foydalanadi
 */
class MarketingIntegrationListener implements ShouldQueue
{
    use HasKpiCalculation;
    public string $queue = 'marketing-kpi';

    public function __construct(
        protected MarketingKpiCalculatorService $kpiCalculator
    ) {}

    /**
     * Subscribe to events.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            LeadWon::class => 'handleLeadWon',
            DealClosed::class => 'handleDealClosed',
            DealLost::class => 'handleDealLost',
            LeadQualificationChanged::class => 'handleQualificationChanged',
        ];
    }

    /**
     * Lead won bo'lganda - Marketing Revenue va Won Count yangilash.
     */
    public function handleLeadWon(LeadWon $event): void
    {
        $lead = $event->lead;
        $sale = $event->sale;

        // Attribution bor bo'lsa - Marketing KPI ni yangilash
        if (!$event->hasAttribution()) {
            Log::debug('MarketingIntegrationListener: LeadWon without attribution, skipping', [
                'lead_id' => $lead->id,
                'sale_id' => $sale->id,
            ]);
            return;
        }

        Log::info('MarketingIntegrationListener: Processing LeadWon with attribution', [
            'lead_id' => $lead->id,
            'sale_id' => $sale->id,
            'campaign_id' => $event->campaignId,
            'channel_id' => $event->channelId,
            'revenue' => $event->revenue,
        ]);

        try {
            // Real-time KPI increment (won count, revenue)
            $this->incrementMarketingKpi(
                $lead->business_id,
                $event->channelId,
                $event->campaignId,
                [
                    'won_count' => 1,
                    'total_revenue' => $event->revenue,
                ]
            );

            // Full recalculation trigger (async)
            CalculateMarketingKpiSnapshotsJob::dispatch(
                $lead->business_id,
                'daily',
                Carbon::today()
            )->delay(now()->addMinutes(5)); // 5 daqiqadan keyin

        } catch (\Exception $e) {
            Log::error('MarketingIntegrationListener: Failed to process LeadWon', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * DealClosed eventni qayta ishlash (Sales moduldan).
     */
    public function handleDealClosed(DealClosed $event): void
    {
        $lead = $event->lead;

        // Agar lead marketing attribution bor bo'lsa
        if (!$lead->campaign_id && !$lead->marketing_channel_id) {
            return;
        }

        Log::info('MarketingIntegrationListener: Processing DealClosed', [
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'channel_id' => $lead->marketing_channel_id,
            'value' => $event->value,
        ]);

        try {
            $this->incrementMarketingKpi(
                $lead->business_id,
                $lead->marketing_channel_id,
                $lead->campaign_id,
                [
                    'won_count' => 1,
                    'total_revenue' => $event->value,
                ]
            );
        } catch (\Exception $e) {
            Log::error('MarketingIntegrationListener: Failed to process DealClosed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * DealLost eventni qayta ishlash - Lost count va wasted spend yangilash.
     *
     * Bu metod marketing ROI ni to'g'ri hisoblash uchun muhim:
     * - Qaysi kanal/kampaniya ko'proq yo'qotadi
     * - Wasted marketing spend tracking
     */
    public function handleDealLost(DealLost $event): void
    {
        $lead = $event->lead;

        // Agar lead marketing attribution bor bo'lsa
        if (!$lead->campaign_id && !$lead->marketing_channel_id) {
            Log::debug('MarketingIntegrationListener: DealLost without attribution, skipping KPI update', [
                'lead_id' => $lead->id,
            ]);
            return;
        }

        Log::info('MarketingIntegrationListener: Processing DealLost', [
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'channel_id' => $lead->marketing_channel_id,
            'estimated_value' => $event->estimatedValue,
            'lost_reason' => $event->lostReason,
        ]);

        try {
            // Lost count increment qilish
            $this->incrementMarketingKpi(
                $lead->business_id,
                $lead->marketing_channel_id,
                $lead->campaign_id,
                [
                    'lost_count' => 1,
                ]
            );

            // Full recalculation trigger (conversion rate, loss rate hisoblash uchun)
            CalculateMarketingKpiSnapshotsJob::dispatch(
                $lead->business_id,
                'daily',
                Carbon::today()
            )->delay(now()->addMinutes(5));

            Log::info('MarketingIntegrationListener: DealLost KPI updated', [
                'lead_id' => $lead->id,
                'channel_id' => $lead->marketing_channel_id,
                'campaign_id' => $lead->campaign_id,
            ]);

        } catch (\Exception $e) {
            Log::error('MarketingIntegrationListener: Failed to process DealLost', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lead qualification o'zgarganda - MQL/SQL countlarni yangilash.
     */
    public function handleQualificationChanged(LeadQualificationChanged $event): void
    {
        $lead = $event->lead;

        // Attribution bor bo'lsa
        if (!$lead->campaign_id && !$lead->marketing_channel_id) {
            return;
        }

        Log::info('MarketingIntegrationListener: Processing QualificationChanged', [
            'lead_id' => $lead->id,
            'from_status' => $event->fromStatus,
            'to_status' => $event->toStatus,
        ]);

        try {
            $increments = [];

            // MQL ga o'tdi
            if ($event->becameMql()) {
                $increments['mql_count'] = 1;
            }

            // SQL ga o'tdi
            if ($event->becameSql()) {
                $increments['sql_count'] = 1;
                // Agar to'g'ridan-to'g'ri SQL ga o'tgan bo'lsa (MQL ni skip qilgan)
                if ($event->fromStatus === 'new') {
                    $increments['mql_count'] = 1;
                }
            }

            if (!empty($increments)) {
                $this->incrementMarketingKpi(
                    $lead->business_id,
                    $lead->marketing_channel_id,
                    $lead->campaign_id,
                    $increments
                );
            }
        } catch (\Exception $e) {
            Log::error('MarketingIntegrationListener: Failed to process QualificationChanged', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Marketing KPI snapshotni increment qilish.
     */
    protected function incrementMarketingKpi(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        array $increments
    ): void {
        $today = Carbon::today();

        // Overall snapshot (no filter)
        $this->incrementSnapshot($businessId, $today, null, null, $increments);

        // Channel-specific snapshot
        if ($channelId) {
            $this->incrementSnapshot($businessId, $today, $channelId, null, $increments);
        }

        // Campaign-specific snapshot
        if ($campaignId) {
            $this->incrementSnapshot($businessId, $today, null, $campaignId, $increments);
        }

        // Channel + Campaign specific
        if ($channelId && $campaignId) {
            $this->incrementSnapshot($businessId, $today, $channelId, $campaignId, $increments);
        }
    }

    /**
     * Bitta snapshotni increment qilish.
     */
    protected function incrementSnapshot(
        string $businessId,
        Carbon $date,
        ?string $channelId,
        ?string $campaignId,
        array $increments
    ): void {
        $snapshot = MarketingKpiSnapshot::firstOrCreate(
            [
                'business_id' => $businessId,
                'date' => $date->toDateString(),
                'period_type' => 'daily',
                'channel_id' => $channelId,
                'campaign_id' => $campaignId,
            ],
            [
                'leads_count' => 0,
                'mql_count' => 0,
                'sql_count' => 0,
                'won_count' => 0,
                'lost_count' => 0,
                'total_spend' => 0,
                'total_revenue' => 0,
                'cpl' => 0,
                'cpmql' => 0,
                'cpsql' => 0,
                'cac' => 0,
                'roas' => 0,
                'roi' => 0,
            ]
        );

        foreach ($increments as $field => $value) {
            $snapshot->increment($field, $value);
        }

        // Recalculate derived metrics
        $this->recalculateDerivedMetrics($snapshot);
    }

    /**
     * Derived metrics (CPL, ROAS, ROI, CAC) ni qayta hisoblash.
     */
    protected function recalculateDerivedMetrics(MarketingKpiSnapshot $snapshot): void
    {
        $snapshot->refresh();

        $updates = [];

        // CPL - Cost Per Lead
        if ($snapshot->leads_count > 0 && $snapshot->total_spend > 0) {
            $updates['cpl'] = round($snapshot->total_spend / $snapshot->leads_count, 2);
        }

        // CPMQL - Cost Per MQL
        if ($snapshot->mql_count > 0 && $snapshot->total_spend > 0) {
            $updates['cpmql'] = round($snapshot->total_spend / $snapshot->mql_count, 2);
        }

        // CPSQL - Cost Per SQL
        if ($snapshot->sql_count > 0 && $snapshot->total_spend > 0) {
            $updates['cpsql'] = round($snapshot->total_spend / $snapshot->sql_count, 2);
        }

        // CAC - Customer Acquisition Cost
        if ($snapshot->won_count > 0 && $snapshot->total_spend > 0) {
            $updates['cac'] = round($snapshot->total_spend / $snapshot->won_count, 2);
        }

        // ROAS - Return On Ad Spend
        if ($snapshot->total_spend > 0) {
            $updates['roas'] = round($snapshot->total_revenue / $snapshot->total_spend, 4);
        }

        // ROI - Return On Investment (%)
        if ($snapshot->total_spend > 0) {
            $updates['roi'] = round(
                (($snapshot->total_revenue - $snapshot->total_spend) / $snapshot->total_spend) * 100,
                2
            );
        }

        // Conversion rates
        if ($snapshot->leads_count > 0) {
            $updates['lead_to_mql_rate'] = round(($snapshot->mql_count / $snapshot->leads_count) * 100, 2);
            $updates['overall_conversion_rate'] = round(($snapshot->won_count / $snapshot->leads_count) * 100, 2);
        }

        if ($snapshot->mql_count > 0) {
            $updates['mql_to_sql_rate'] = round(($snapshot->sql_count / $snapshot->mql_count) * 100, 2);
        }

        if ($snapshot->sql_count > 0) {
            $updates['sql_to_won_rate'] = round(($snapshot->won_count / $snapshot->sql_count) * 100, 2);
        }

        // Loss rate - Yo'qotilgan lidlar foizi (Black Box metrikasi)
        if ($snapshot->leads_count > 0) {
            $updates['loss_rate'] = round(($snapshot->lost_count / $snapshot->leads_count) * 100, 2);
        }

        // Win/Loss ratio
        $totalDecided = $snapshot->won_count + $snapshot->lost_count;
        if ($totalDecided > 0) {
            $updates['win_rate'] = round(($snapshot->won_count / $totalDecided) * 100, 2);
        }

        if (!empty($updates)) {
            $snapshot->update($updates);
        }
    }
}
