<?php

namespace App\Services\Marketing;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\KpiDailyEntry;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\MarketingSpend;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cross-Module Attribution Service
 *
 * Marketing, Sotuv va Moliya modullarini avtomatik bog'lash
 * va attribution hisoblashlarini amalga oshiradi.
 */
class CrossModuleAttributionService
{
    /**
     * Source type aniqlash
     */
    public function determineSourceType(?Lead $lead, ?MarketingChannel $channel = null): string
    {
        // First check channel
        if ($channel) {
            return $this->getSourceTypeFromChannel($channel);
        }

        // Then check lead
        if ($lead) {
            if ($lead->marketingChannel) {
                return $this->getSourceTypeFromChannel($lead->marketingChannel);
            }

            if ($lead->campaign?->marketingChannel) {
                return $this->getSourceTypeFromChannel($lead->campaign->marketingChannel);
            }

            // UTM based detection
            if ($lead->utm_source) {
                return $this->getSourceTypeFromUtm($lead->utm_source, $lead->utm_medium);
            }

            // Lead source based
            if ($lead->source) {
                return $this->getSourceTypeFromLeadSource($lead->source->slug ?? $lead->source->name);
            }
        }

        return 'organic';
    }

    /**
     * Channel turidan source type olish
     */
    protected function getSourceTypeFromChannel(MarketingChannel $channel): string
    {
        $digitalTypes = ['paid_social', 'paid_search', 'display', 'video', 'programmatic', 'email', 'sms'];
        $offlineTypes = ['outdoor', 'tv', 'radio', 'print', 'events', 'direct_mail'];
        $referralTypes = ['referral', 'affiliate', 'partner'];

        if (in_array($channel->type, $digitalTypes)) {
            return 'digital';
        }

        if (in_array($channel->type, $offlineTypes)) {
            return 'offline';
        }

        if (in_array($channel->type, $referralTypes)) {
            return 'referral';
        }

        return 'organic';
    }

    /**
     * UTM dan source type aniqlash
     */
    protected function getSourceTypeFromUtm(?string $source, ?string $medium): string
    {
        $digitalSources = ['google', 'facebook', 'instagram', 'meta', 'tiktok', 'yandex', 'youtube', 'linkedin', 'twitter'];
        $digitalMediums = ['cpc', 'cpm', 'ppc', 'paid', 'display', 'banner', 'retargeting', 'remarketing'];

        if ($source && in_array(strtolower($source), $digitalSources)) {
            return 'digital';
        }

        if ($medium && in_array(strtolower($medium), $digitalMediums)) {
            return 'digital';
        }

        if ($medium && in_array(strtolower($medium), ['referral', 'affiliate'])) {
            return 'referral';
        }

        if ($medium && in_array(strtolower($medium), ['organic', 'none', '(none)'])) {
            return 'organic';
        }

        return 'digital'; // Default for UTM tagged traffic
    }

    /**
     * Lead source dan type aniqlash
     */
    protected function getSourceTypeFromLeadSource(string $slug): string
    {
        $digitalSources = ['website', 'landing_page', 'facebook', 'instagram', 'telegram', 'google', 'yandex'];
        $offlineSources = ['walk_in', 'phone', 'event', 'exhibition', 'partner_store'];
        $referralSources = ['referral', 'word_of_mouth', 'friend', 'partner'];

        $slugLower = strtolower($slug);

        foreach ($digitalSources as $digital) {
            if (str_contains($slugLower, $digital)) {
                return 'digital';
            }
        }

        foreach ($offlineSources as $offline) {
            if (str_contains($slugLower, $offline)) {
                return 'offline';
            }
        }

        foreach ($referralSources as $referral) {
            if (str_contains($slugLower, $referral)) {
                return 'referral';
            }
        }

        return 'organic';
    }

    /**
     * Lead uchun acquisition cost hisoblash
     */
    public function calculateLeadAcquisitionCost(Lead $lead): float
    {
        $cost = 0;

        // 1. Campaign dan direct cost
        if ($lead->campaign_id) {
            $campaignCost = $this->getProRataCampaignCost($lead->campaign, $lead->created_at);
            if ($campaignCost > 0) {
                $cost = $campaignCost;
            }
        }

        // 2. Marketing channel dan cost (agar campaign yo'q bo'lsa)
        if ($cost === 0 && $lead->marketing_channel_id) {
            $channelCost = $this->getProRataChannelCost(
                $lead->business_id,
                $lead->marketing_channel_id,
                $lead->created_at
            );
            if ($channelCost > 0) {
                $cost = $channelCost;
            }
        }

        // 3. Source type bo'yicha average cost
        if ($cost === 0) {
            $sourceType = $this->determineSourceType($lead);
            $cost = $this->getAverageSourceTypeCost($lead->business_id, $sourceType, $lead->created_at);
        }

        return round($cost, 2);
    }

    /**
     * Campaign uchun pro-rata cost (1 lead uchun)
     */
    protected function getProRataCampaignCost(Campaign $campaign, Carbon $date): float
    {
        // Shu kampaniya uchun shu kun/hafta/oy uchun sarflangan xarajat
        $spend = MarketingSpend::where('campaign_id', $campaign->id)
            ->whereDate('spend_date', '<=', $date)
            ->whereDate('spend_date', '>=', $date->copy()->subDays(7))
            ->sum('amount');

        if ($spend <= 0) {
            return 0;
        }

        // Shu davrda kelgan lidlar soni
        $leadsCount = Lead::where('campaign_id', $campaign->id)
            ->whereDate('created_at', '<=', $date)
            ->whereDate('created_at', '>=', $date->copy()->subDays(7))
            ->count();

        if ($leadsCount <= 0) {
            return 0;
        }

        return $spend / $leadsCount;
    }

    /**
     * Channel uchun pro-rata cost
     */
    protected function getProRataChannelCost(string $businessId, string $channelId, Carbon $date): float
    {
        $spend = MarketingSpend::where('business_id', $businessId)
            ->where('channel_id', $channelId)
            ->whereDate('spend_date', '<=', $date)
            ->whereDate('spend_date', '>=', $date->copy()->subDays(7))
            ->sum('amount');

        if ($spend <= 0) {
            return 0;
        }

        $leadsCount = Lead::where('business_id', $businessId)
            ->where('marketing_channel_id', $channelId)
            ->whereDate('created_at', '<=', $date)
            ->whereDate('created_at', '>=', $date->copy()->subDays(7))
            ->count();

        if ($leadsCount <= 0) {
            return 0;
        }

        return $spend / $leadsCount;
    }

    /**
     * Source type bo'yicha o'rtacha cost
     */
    protected function getAverageSourceTypeCost(string $businessId, string $sourceType, Carbon $date): float
    {
        // KPI daily entry dan olish
        $entry = KpiDailyEntry::where('business_id', $businessId)
            ->whereDate('date', $date->copy()->subDay())
            ->first();

        if (!$entry) {
            return 0;
        }

        $spend = match ($sourceType) {
            'digital' => $entry->spend_digital ?? 0,
            'offline' => $entry->spend_offline ?? 0,
            default => 0,
        };

        $leads = match ($sourceType) {
            'digital' => $entry->leads_digital ?? 0,
            'offline' => $entry->leads_offline ?? 0,
            'referral' => $entry->leads_referral ?? 0,
            'organic' => $entry->leads_organic ?? 0,
            default => 0,
        };

        if ($leads <= 0 || $spend <= 0) {
            return 0;
        }

        return $spend / $leads;
    }

    /**
     * Sale ga attribution qo'shish
     */
    public function attributeSale(Sale $sale): array
    {
        $attribution = [
            'source_type' => 'organic',
            'acquisition_cost' => 0,
            'attributed_spend' => 0,
        ];

        // Lead dan olish
        if ($sale->lead_id && $sale->lead) {
            $lead = $sale->lead;
            $attribution['source_type'] = $lead->acquisition_source_type ?? $this->determineSourceType($lead);
            $attribution['acquisition_cost'] = $lead->acquisition_cost ?? $this->calculateLeadAcquisitionCost($lead);

            // Attribution data ni yangilash
            $attributionData = $sale->attribution_data ?? [];
            $attributionData['lead_source'] = $lead->source?->name;
            $attributionData['campaign_name'] = $lead->campaign?->name;
            $attributionData['channel_name'] = $lead->marketingChannel?->name;
            $attributionData['utm'] = $lead->getUtmArray();
            $sale->attribution_data = $attributionData;
        }
        // Direct campaign dan
        elseif ($sale->campaign_id && $sale->campaign) {
            $attribution['source_type'] = $this->determineSourceType(null, $sale->campaign->marketingChannel);
            $attribution['attributed_spend'] = $this->getProRataCampaignCost($sale->campaign, $sale->created_at);
        }
        // Marketing channel dan
        elseif ($sale->marketing_channel_id && $sale->marketingChannel) {
            $attribution['source_type'] = $this->getSourceTypeFromChannel($sale->marketingChannel);
            $attribution['attributed_spend'] = $this->getProRataChannelCost(
                $sale->business_id,
                $sale->marketing_channel_id,
                $sale->created_at
            );
        }

        return $attribution;
    }

    /**
     * Customer ga acquisition data qo'shish
     */
    public function attributeCustomer(Customer $customer): array
    {
        $attribution = [
            'first_acquisition_source' => null,
            'first_acquisition_source_type' => 'organic',
            'first_acquisition_channel_id' => null,
            'first_campaign_id' => null,
            'total_acquisition_cost' => 0,
        ];

        // Lead dan olish
        if ($customer->lead_id && $customer->lead) {
            $lead = $customer->lead;

            $attribution['first_acquisition_source'] = $lead->source?->name
                ?? $lead->utm_source
                ?? $lead->marketingChannel?->name
                ?? 'Unknown';

            $attribution['first_acquisition_source_type'] = $lead->acquisition_source_type
                ?? $this->determineSourceType($lead);

            $attribution['first_acquisition_channel_id'] = $lead->marketing_channel_id;
            $attribution['first_campaign_id'] = $lead->campaign_id;
            $attribution['total_acquisition_cost'] = $lead->acquisition_cost ?? 0;
        }

        return $attribution;
    }

    /**
     * KPI Daily Entry ga revenue by source qo'shish
     */
    public function updateKpiDailyRevenue(string $businessId, Carbon $date): void
    {
        $entry = KpiDailyEntry::firstOrCreate(
            ['business_id' => $businessId, 'date' => $date->format('Y-m-d')],
            ['source' => 'integration']
        );

        // Revenue by source type
        $revenueBySource = Sale::where('business_id', $businessId)
            ->whereDate('sale_date', $date)
            ->selectRaw("
                attribution_source_type,
                SUM(amount) as total_revenue,
                SUM(COALESCE(profit, amount - COALESCE(cost, 0))) as total_profit
            ")
            ->groupBy('attribution_source_type')
            ->get()
            ->keyBy('attribution_source_type');

        $entry->revenue_digital = $revenueBySource->get('digital')?->total_revenue ?? 0;
        $entry->revenue_offline = $revenueBySource->get('offline')?->total_revenue ?? 0;
        $entry->revenue_referral = $revenueBySource->get('referral')?->total_revenue ?? 0;
        $entry->revenue_organic = $revenueBySource->get('organic')?->total_revenue ?? 0;

        // Profit total
        $entry->profit_total = $revenueBySource->sum('total_profit');

        // ROAS hisoblash
        if ($entry->spend_digital > 0) {
            $entry->roas_digital = $entry->revenue_digital / $entry->spend_digital;
        }

        if ($entry->spend_offline > 0) {
            $entry->roas_offline = $entry->revenue_offline / $entry->spend_offline;
        }

        if ($entry->spend_total > 0) {
            $entry->roas_total = $entry->revenue_total / $entry->spend_total;
            $entry->roi_total = $entry->profit_total / $entry->spend_total;
        }

        $entry->save();

        Log::info('KPI Daily Revenue updated', [
            'business_id' => $businessId,
            'date' => $date->format('Y-m-d'),
            'revenue_digital' => $entry->revenue_digital,
            'revenue_offline' => $entry->revenue_offline,
            'roas_total' => $entry->roas_total,
        ]);
    }

    /**
     * Customer churn risk hisoblash
     */
    public function calculateChurnRisk(Customer $customer): array
    {
        $riskScore = 0;
        $factors = [];

        // 1. Oxirgi xariddan beri o'tgan vaqt
        $daysSinceLastPurchase = $customer->last_purchase_at
            ? $customer->last_purchase_at->diffInDays(now())
            : 999;

        if ($daysSinceLastPurchase > 180) {
            $riskScore += 40;
            $factors[] = '180 kundan ko\'p xaridsiz';
        } elseif ($daysSinceLastPurchase > 90) {
            $riskScore += 25;
            $factors[] = '90-180 kun xaridsiz';
        } elseif ($daysSinceLastPurchase > 60) {
            $riskScore += 15;
            $factors[] = '60-90 kun xaridsiz';
        } elseif ($daysSinceLastPurchase > 30) {
            $riskScore += 5;
            $factors[] = '30-60 kun xaridsiz';
        }

        // 2. Xarid chastotasi o'zgarishi
        if ($customer->purchase_frequency_days && $customer->purchase_frequency_days > 0) {
            $expectedDays = $customer->purchase_frequency_days * 1.5;
            if ($daysSinceLastPurchase > $expectedDays) {
                $riskScore += 20;
                $factors[] = 'Xarid chastotasi pasaygan';
            }
        }

        // 3. Umumiy xaridlar soni
        if ($customer->orders_count <= 1) {
            $riskScore += 15;
            $factors[] = 'Faqat 1 ta xarid';
        }

        // 4. O'rtacha chek pasayishi (last order vs average)
        // Bu yerda qo'shimcha tekshirish kerak bo'lsa qo'shiladi

        // 5. Oxirgi faollik
        if ($customer->last_activity_at) {
            $daysSinceActivity = $customer->last_activity_at->diffInDays(now());
            if ($daysSinceActivity > 30) {
                $riskScore += 10;
                $factors[] = '30 kundan ko\'p faoliyatsiz';
            }
        }

        // Risk level
        $riskLevel = match (true) {
            $riskScore >= 70 => 'critical',
            $riskScore >= 50 => 'high',
            $riskScore >= 30 => 'medium',
            default => 'low',
        };

        return [
            'score' => min($riskScore, 100),
            'level' => $riskLevel,
            'factors' => $factors,
            'days_since_last_purchase' => $daysSinceLastPurchase,
        ];
    }

    /**
     * Customer CLV (Lifetime Value) hisoblash
     */
    public function calculateCustomerLtv(Customer $customer): float
    {
        // Simple CLV: Average Order Value * Purchase Frequency * Customer Lifespan
        $avgOrderValue = (float) ($customer->average_order_value ?? 0);
        $ordersCount = (int) ($customer->orders_count ?? 0);

        if ($avgOrderValue <= 0 || $ordersCount <= 0) {
            return 0;
        }

        // Agar 1 dan ortiq xarid bo'lsa, purchase frequency hisoblash
        if ($ordersCount > 1 && $customer->first_purchase_at && $customer->last_purchase_at) {
            $monthsActive = max(1, $customer->first_purchase_at->diffInMonths($customer->last_purchase_at));
            $monthlyFrequency = $ordersCount / $monthsActive;

            // 24 oylik projected LTV
            $projectedMonths = 24;
            return round($avgOrderValue * $monthlyFrequency * $projectedMonths, 2);
        }

        // Agar bitta xarid bo'lsa, faqat shu qiymatni qaytarish
        return round($avgOrderValue * 2, 2); // 2x multiplier for potential
    }

    /**
     * Batch process: Barcha leadlar uchun acquisition cost hisoblash
     */
    public function batchCalculateLeadCosts(string $businessId, ?Carbon $fromDate = null): int
    {
        $query = Lead::where('business_id', $businessId)
            ->whereNull('acquisition_cost');

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        $updated = 0;

        $query->chunk(100, function ($leads) use (&$updated) {
            foreach ($leads as $lead) {
                $cost = $this->calculateLeadAcquisitionCost($lead);
                $sourceType = $this->determineSourceType($lead);

                $lead->update([
                    'acquisition_cost' => $cost,
                    'acquisition_source_type' => $sourceType,
                ]);

                $updated++;
            }
        });

        return $updated;
    }

    /**
     * Batch process: Barcha saleslar uchun attribution
     */
    public function batchAttributeSales(string $businessId, ?Carbon $fromDate = null): int
    {
        $query = Sale::where('business_id', $businessId)
            ->whereNull('attribution_source_type');

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        $updated = 0;

        $query->chunk(100, function ($sales) use (&$updated) {
            foreach ($sales as $sale) {
                $attribution = $this->attributeSale($sale);

                $sale->update([
                    'acquisition_cost' => $attribution['acquisition_cost'],
                    'attribution_source_type' => $attribution['source_type'],
                    'attributed_spend' => $attribution['attributed_spend'],
                ]);

                $updated++;
            }
        });

        return $updated;
    }

    /**
     * Batch process: Barcha customerlar uchun attribution
     */
    public function batchAttributeCustomers(string $businessId): int
    {
        $updated = 0;

        Customer::where('business_id', $businessId)
            ->whereNull('first_acquisition_source')
            ->chunk(100, function ($customers) use (&$updated) {
                foreach ($customers as $customer) {
                    $attribution = $this->attributeCustomer($customer);
                    $churn = $this->calculateChurnRisk($customer);
                    $ltv = $this->calculateCustomerLtv($customer);

                    $customer->update([
                        'first_acquisition_source' => $attribution['first_acquisition_source'],
                        'first_acquisition_source_type' => $attribution['first_acquisition_source_type'],
                        'first_acquisition_channel_id' => $attribution['first_acquisition_channel_id'],
                        'first_campaign_id' => $attribution['first_campaign_id'],
                        'total_acquisition_cost' => $attribution['total_acquisition_cost'],
                        'churn_risk_score' => $churn['score'],
                        'churn_risk_level' => $churn['level'],
                        'days_since_last_purchase' => $churn['days_since_last_purchase'],
                        'lifetime_value' => $ltv,
                    ]);

                    $updated++;
                }
            });

        return $updated;
    }
}
