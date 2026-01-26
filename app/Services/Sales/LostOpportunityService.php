<?php

namespace App\Services\Sales;

use App\Events\Sales\DealLost;
use App\Models\Lead;
use App\Models\LostOpportunity;
use App\Models\User;
use App\Services\Marketing\CrossModuleAttributionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LostOpportunityService - Yo'qotilgan imkoniyatlarni kuzatish.
 *
 * "Black Box" konsepsiyasining asosi: Qancha pul yo'qotilganini kuzatish.
 * - Marketing Attribution: Qaysi kampaniya/kanal ko'proq yo'qotadi
 * - Financial Impact: Qancha daromad yo'qotildi
 * - Recovery Tracking: Qayta urinib ko'rish imkoniyatlari
 */
class LostOpportunityService
{
    public function __construct(
        private CrossModuleAttributionService $attributionService
    ) {}

    /**
     * Lead dan LostOpportunity yaratish.
     *
     * Bu metod LeadStageChangedListener tomonidan chaqiriladi
     * qachonki Lead "lost" bosqichiga o'tganda.
     */
    public function trackLostLead(
        Lead $lead,
        string $lostReason,
        ?string $lostReasonDetails = null,
        ?User $lostBy = null,
        ?string $lostToCompetitor = null
    ): LostOpportunity {
        // Agar allaqachon track qilingan bo'lsa
        $existing = LostOpportunity::where('lead_id', $lead->id)
            ->whereNull('recovered_at')
            ->first();

        if ($existing) {
            Log::warning('LostOpportunityService: Lead already tracked as lost', [
                'lead_id' => $lead->id,
                'lost_opportunity_id' => $existing->id,
            ]);
            return $existing;
        }

        return DB::transaction(function () use ($lead, $lostReason, $lostReasonDetails, $lostBy, $lostToCompetitor) {
            // Acquisition cost hisoblash (agar mavjud bo'lmasa)
            $acquisitionCost = $lead->acquisition_cost;
            if (!$acquisitionCost && $lead->hasAttribution()) {
                $acquisitionCost = $this->attributionService->calculateLeadAcquisitionCost($lead);
            }

            // Attribution source type aniqlash
            $sourceType = $this->determineSourceType($lead);

            // Pipeline stage aniqlash
            $lostStage = $lead->status ?? $lead->stage?->slug;

            $lostOpportunity = LostOpportunity::create([
                'business_id' => $lead->business_id,
                'lead_id' => $lead->id,
                'lost_by' => $lostBy?->id ?? auth()->id(),
                'assigned_to' => $lead->assigned_to,
                // Marketing Attribution (Lead dan meros)
                'campaign_id' => $lead->campaign_id,
                'marketing_channel_id' => $lead->marketing_channel_id,
                'source_id' => $lead->source_id,
                'utm_source' => $lead->utm_source,
                'utm_medium' => $lead->utm_medium,
                'utm_campaign' => $lead->utm_campaign,
                'utm_content' => $lead->utm_content,
                'utm_term' => $lead->utm_term,
                'attribution_source_type' => $sourceType,
                // Financial
                'estimated_value' => $lead->estimated_value ?? 0,
                'acquisition_cost' => $acquisitionCost ?? 0,
                'currency' => 'UZS',
                // Loss tracking
                'lost_reason' => $lostReason,
                'lost_reason_details' => $lostReasonDetails ?? $lead->lost_reason_details,
                'lost_stage' => $lostStage,
                'lost_at' => now(),
                // Competitor
                'lost_to_competitor' => $lostToCompetitor,
                // Recovery
                'is_recoverable' => $this->determineRecoverability($lostReason),
            ]);

            // DealLost event dispatch (KPI va boshqa listenerlar uchun)
            event(new DealLost(
                lead: $lead,
                lostReason: $lostReason,
                estimatedValue: (float) ($lead->estimated_value ?? 0),
                lostBy: $lostBy,
                notes: $lostReasonDetails
            ));

            Log::info('LostOpportunityService: Lost opportunity tracked', [
                'lost_opportunity_id' => $lostOpportunity->id,
                'lead_id' => $lead->id,
                'lost_reason' => $lostReason,
                'estimated_value' => $lostOpportunity->estimated_value,
                'acquisition_cost' => $lostOpportunity->acquisition_cost,
                'campaign_id' => $lostOpportunity->campaign_id,
                'channel_id' => $lostOpportunity->marketing_channel_id,
            ]);

            return $lostOpportunity;
        });
    }

    /**
     * Attribution source type aniqlash.
     */
    private function determineSourceType(Lead $lead): ?string
    {
        // Agar channel mavjud bo'lsa - undan olish
        if ($lead->marketingChannel) {
            return $this->attributionService->determineSourceType($lead, $lead->marketingChannel);
        }

        // UTM source orqali aniqlash
        if ($lead->utm_source) {
            $source = strtolower($lead->utm_source);

            $digitalSources = ['facebook', 'instagram', 'google', 'tiktok', 'linkedin', 'telegram_ads'];
            if (in_array($source, $digitalSources)) {
                return 'digital';
            }

            if (in_array($source, ['referral', 'word_of_mouth'])) {
                return 'referral';
            }

            if (in_array($source, ['organic', 'seo', 'blog'])) {
                return 'organic';
            }

            if (in_array($source, ['event', 'exhibition', 'flyer', 'billboard'])) {
                return 'offline';
            }
        }

        // Lead source orqali
        if ($lead->source) {
            return $this->mapLeadSourceToType($lead->source->name);
        }

        return 'direct';
    }

    /**
     * Lead source ni type ga map qilish.
     */
    private function mapLeadSourceToType(string $sourceName): string
    {
        $sourceName = strtolower($sourceName);

        if (str_contains($sourceName, 'facebook') || str_contains($sourceName, 'instagram') || str_contains($sourceName, 'google')) {
            return 'digital';
        }

        if (str_contains($sourceName, 'referral') || str_contains($sourceName, 'tavsiya')) {
            return 'referral';
        }

        if (str_contains($sourceName, 'organic') || str_contains($sourceName, 'seo')) {
            return 'organic';
        }

        if (str_contains($sourceName, 'event') || str_contains($sourceName, 'offline')) {
            return 'offline';
        }

        return 'direct';
    }

    /**
     * Sabab bo'yicha recovery imkoniyatini aniqlash.
     */
    private function determineRecoverability(string $reason): bool
    {
        // Bu sabablarda recovery imkoniyati past
        $nonRecoverableReasons = [
            'wrong_contact',
            'no_need',
            'low_quality',
        ];

        return !in_array($reason, $nonRecoverableReasons);
    }

    // ==========================================
    // ANALYTICS METHODS
    // ==========================================

    /**
     * Yo'qotilgan imkoniyatlar statistikasi.
     */
    public function getLostStats(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): array {
        $opportunities = LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->get();

        $totalValue = $opportunities->sum('estimated_value');
        $totalWasted = $opportunities->sum('acquisition_cost');

        return [
            'total_count' => $opportunities->count(),
            'total_estimated_value' => $totalValue,
            'total_acquisition_cost' => $totalWasted,
            'total_loss' => $totalValue + $totalWasted,
            'average_deal_size' => $opportunities->count() > 0
                ? round($totalValue / $opportunities->count(), 2)
                : 0,
            'recoverable_count' => $opportunities->where('is_recoverable', true)->count(),
            'recovered_count' => $opportunities->whereNotNull('recovered_at')->count(),
        ];
    }

    /**
     * Sabab bo'yicha statistika.
     */
    public function getStatsByReason(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->selectRaw('
                lost_reason,
                COUNT(*) as count,
                SUM(estimated_value) as total_value,
                SUM(acquisition_cost) as total_wasted,
                AVG(estimated_value) as avg_value
            ')
            ->groupBy('lost_reason')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                $item->reason_label = LostOpportunity::LOST_REASONS[$item->lost_reason] ?? $item->lost_reason;
                return $item;
            });
    }

    /**
     * Marketing kanal bo'yicha yo'qotish statistikasi.
     */
    public function getStatsByChannel(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->whereNotNull('marketing_channel_id')
            ->selectRaw('
                marketing_channel_id,
                COUNT(*) as lost_count,
                SUM(estimated_value) as total_lost_value,
                SUM(acquisition_cost) as total_wasted_spend,
                AVG(estimated_value) as avg_deal_size
            ')
            ->groupBy('marketing_channel_id')
            ->with('marketingChannel:id,name,type')
            ->orderByDesc('lost_count')
            ->get();
    }

    /**
     * Kampaniya bo'yicha yo'qotish statistikasi.
     */
    public function getStatsByCampaign(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->whereNotNull('campaign_id')
            ->selectRaw('
                campaign_id,
                COUNT(*) as lost_count,
                SUM(estimated_value) as total_lost_value,
                SUM(acquisition_cost) as total_wasted_spend
            ')
            ->groupBy('campaign_id')
            ->with('campaign:id,name')
            ->orderByDesc('lost_count')
            ->get();
    }

    /**
     * Raqobatchi bo'yicha yo'qotish statistikasi.
     */
    public function getStatsByCompetitor(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->whereNotNull('lost_to_competitor')
            ->selectRaw('
                lost_to_competitor,
                COUNT(*) as lost_count,
                SUM(estimated_value) as total_lost_value
            ')
            ->groupBy('lost_to_competitor')
            ->orderByDesc('lost_count')
            ->get();
    }

    /**
     * Kunlik yo'qotish trendi.
     */
    public function getDailyTrend(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->selectRaw('
                DATE(lost_at) as date,
                COUNT(*) as count,
                SUM(estimated_value) as total_value,
                SUM(acquisition_cost) as wasted_spend
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Recovery imkoniyatlari ro'yxati.
     */
    public function getRecoverableOpportunities(
        string $businessId,
        int $limit = 20
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->recoverable()
            ->with(['lead', 'assignedToUser', 'marketingChannel'])
            ->orderByDesc('estimated_value')
            ->limit($limit)
            ->get();
    }

    /**
     * User bo'yicha yo'qotish statistikasi.
     */
    public function getStatsByUser(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return LostOpportunity::where('business_id', $businessId)
            ->lostBetween($from, $to)
            ->whereNotNull('assigned_to')
            ->selectRaw('
                assigned_to,
                COUNT(*) as lost_count,
                SUM(estimated_value) as total_lost_value
            ')
            ->groupBy('assigned_to')
            ->with('assignedToUser:id,name')
            ->orderByDesc('lost_count')
            ->get();
    }

    // ==========================================
    // RECOVERY METHODS
    // ==========================================

    /**
     * Recovery urinish logi.
     */
    public function logRecoveryAttempt(LostOpportunity $opportunity): void
    {
        $opportunity->logRecoveryAttempt();

        Log::info('LostOpportunityService: Recovery attempt logged', [
            'lost_opportunity_id' => $opportunity->id,
            'attempts' => $opportunity->recovery_attempts,
        ]);
    }

    /**
     * Recovered deb belgilash.
     */
    public function markAsRecovered(
        LostOpportunity $opportunity,
        ?Lead $newLead = null
    ): void {
        $opportunity->markAsRecovered($newLead?->id);

        Log::info('LostOpportunityService: Opportunity marked as recovered', [
            'lost_opportunity_id' => $opportunity->id,
            'new_lead_id' => $newLead?->id,
        ]);
    }

    // ==========================================
    // REPORTING METHODS
    // ==========================================

    /**
     * Executive summary uchun yo'qotish hisoboti.
     */
    public function generateLostOpportunityReport(
        string $businessId,
        Carbon $from,
        Carbon $to
    ): array {
        $stats = $this->getLostStats($businessId, $from, $to);
        $byReason = $this->getStatsByReason($businessId, $from, $to);
        $byChannel = $this->getStatsByChannel($businessId, $from, $to);
        $topCompetitors = $this->getStatsByCompetitor($businessId, $from, $to)->take(5);

        // Top loss reasons
        $topReasons = $byReason->take(3)->pluck('reason_label')->toArray();

        // Top losing channel
        $topLosingChannel = $byChannel->first();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => $stats,
            'insights' => [
                'main_loss_reasons' => $topReasons,
                'worst_performing_channel' => $topLosingChannel?->marketingChannel?->name,
                'top_competitor' => $topCompetitors->first()?->lost_to_competitor,
                'recovery_rate' => $stats['total_count'] > 0
                    ? round(($stats['recovered_count'] / $stats['total_count']) * 100, 2)
                    : 0,
            ],
            'by_reason' => $byReason,
            'by_channel' => $byChannel,
            'by_competitor' => $topCompetitors,
            'actionable_message' => $this->generateActionableMessage($stats, $byReason, $topCompetitors),
        ];
    }

    /**
     * Telegram uchun actionable xabar yaratish.
     */
    private function generateActionableMessage(array $stats, Collection $byReason, Collection $competitors): string
    {
        $message = "📉 *Yo'qotilgan imkoniyatlar*\n\n";
        $message .= "Jami: {$stats['total_count']} ta lid\n";
        $message .= "Yo'qotilgan daromad: " . number_format($stats['total_estimated_value'], 0, '.', ' ') . " so'm\n";
        $message .= "Sarflangan xarajat: " . number_format($stats['total_acquisition_cost'], 0, '.', ' ') . " so'm\n\n";

        if ($byReason->isNotEmpty()) {
            $topReason = $byReason->first();
            $message .= "⚠️ Asosiy sabab: {$topReason->reason_label} ({$topReason->count} ta)\n";
        }

        if ($competitors->isNotEmpty()) {
            $topCompetitor = $competitors->first();
            $message .= "🏢 Ko'p yo'qotilgan raqobatchi: {$topCompetitor->lost_to_competitor}\n";
        }

        if ($stats['recoverable_count'] > 0) {
            $message .= "\n✅ Qayta urinish mumkin: {$stats['recoverable_count']} ta\n";
        }

        return $message;
    }
}
