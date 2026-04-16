<?php

namespace App\Services\Marketing\Orchestrator;

use App\Events\Marketing\CampaignStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Intelligent Campaign Creator
 *
 * Dream Buyer + Offer + Channel + KPI history asosida
 * avtomatik kampaniya taklifi tayyorlaydi.
 *
 * Sinxron ishlash:
 *   - Dream Buyer → mijoz profili
 *   - Offer → taklif tafsilotlari
 *   - KPI → budjet asoslash (oldingi ROAS)
 *   - Channel → eng yaxshi kanal tanlash
 */
class CampaignCreator
{
    /**
     * Taklif asosida kampaniya loyihasi tayyorlash
     */
    public function proposeCampaign(string $businessId, string $offerId, array $options = []): array
    {
        try {
            $offer = DB::table('offers')->where('id', $offerId)->where('business_id', $businessId)->first();
            if (!$offer) {
                return ['success' => false, 'error' => 'Taklif topilmadi'];
            }

            $dreamBuyer = DB::table('dream_buyers')->where('business_id', $businessId)->first();

            // Kanallar va ularning samaradorligi
            $channels = $this->getChannelEffectiveness($businessId);
            if (empty($channels)) {
                return ['success' => false, 'error' => 'Faol kanal yo\'q'];
            }

            // Budjet taklif
            $budget = $this->suggestBudget($businessId, $options['budget'] ?? null);

            // Eng yaxshi kanal (oldingi ROAS bo'yicha)
            $bestChannel = $this->pickBestChannel($channels);

            // Kampaniya turi (offer asosida)
            $campaignType = $this->detectCampaignType($offer);

            // Kutilgan natijalar
            $expected = $this->calculateExpectedOutcome($budget, $bestChannel, $offer);

            $proposal = [
                'name' => "Kampaniya: " . $offer->name,
                'description' => "Taklif \"{$offer->name}\" uchun " . ($dreamBuyer->name ?? 'ideal mijoz') . " ga yo'naltirilgan kampaniya",
                'campaign_type' => $campaignType,
                'channel_id' => $bestChannel['id'],
                'channel_name' => $bestChannel['type'],
                'offer_id' => $offerId,
                'offer_name' => $offer->name,
                'target_audience' => $dreamBuyer->name ?? null,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(16)->toDateString(),
                'duration_days' => 14,
                'budget_planned' => $budget,
                'expected' => $expected,
            ];

            return ['success' => true, 'proposal' => $proposal];
        } catch (\Exception $e) {
            Log::error('CampaignCreator propose xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Taklifdan kampaniya yaratish (proposal tasdiqlanganidan keyin)
     */
    public function createFromProposal(string $businessId, array $proposal): array
    {
        try {
            $campaignId = Str::uuid()->toString();

            DB::table('marketing_campaigns')->insert([
                'id' => $campaignId,
                'business_id' => $businessId,
                'channel_id' => $proposal['channel_id'],
                'name' => $proposal['name'],
                'description' => $proposal['description'],
                'campaign_type' => $proposal['campaign_type'],
                'start_date' => $proposal['start_date'],
                'end_date' => $proposal['end_date'],
                'budget_planned' => $proposal['budget_planned'],
                'budget_spent' => 0,
                'impressions' => 0,
                'clicks' => 0,
                'leads_generated' => 0,
                'deals_closed' => 0,
                'revenue_generated' => 0,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Event fire
            event(new CampaignStarted(
                businessId: $businessId,
                campaignId: $campaignId,
                budget: $proposal['budget_planned'],
            ));

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'message' => "Kampaniya yaratildi ({$proposal['name']})",
            ];
        } catch (\Exception $e) {
            Log::error('CampaignCreator create xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kanallar samaradorligi
     */
    private function getChannelEffectiveness(string $businessId): array
    {
        $channels = DB::table('marketing_channels')
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        if ($channels->isEmpty()) return [];

        $result = [];
        foreach ($channels as $ch) {
            // Oldingi kampaniyalardan ROAS
            $avgRoas = DB::table('marketing_campaigns')
                ->where('channel_id', $ch->id)
                ->whereIn('status', ['completed', 'active'])
                ->where('budget_spent', '>', 0)
                ->avg(DB::raw('revenue_generated / NULLIF(budget_spent, 0)')) ?? 1.0;

            $result[] = [
                'id' => $ch->id,
                'type' => $ch->type,
                'name' => $ch->name ?? $ch->type,
                'avg_roas' => round($avgRoas, 2),
                'score' => $avgRoas >= 2 ? 100 : ($avgRoas >= 1 ? 60 : 30),
            ];
        }

        usort($result, fn($a, $b) => $b['score'] - $a['score']);
        return $result;
    }

    /**
     * Budjet taklifi
     */
    private function suggestBudget(string $businessId, ?float $userBudget): float
    {
        if ($userBudget) return $userBudget;

        // Oxirgi 30 kunlik o'rtacha kunlik sarflash
        $avgDailySpend = DB::table('kpi_daily_entries')
            ->where('business_id', $businessId)
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->avg('spend_total') ?? 0;

        // Agar 0 bo'lsa — default
        if ($avgDailySpend == 0) {
            return 1_000_000; // 1 mln so'm default
        }

        // 14 kunlik o'rtacha
        return round($avgDailySpend * 14);
    }

    /**
     * Eng yaxshi kanal tanlash
     */
    private function pickBestChannel(array $channels): array
    {
        return $channels[0]; // Allaqachon saralangan
    }

    /**
     * Kampaniya turi (offer asosida)
     */
    private function detectCampaignType($offer): string
    {
        // Agar offer'da narx bor — sales
        if (($offer->price ?? 0) > 0) return 'sales';
        return 'leads';
    }

    /**
     * Kutilgan natija
     */
    private function calculateExpectedOutcome(float $budget, array $channel, $offer): array
    {
        $expectedRoas = $channel['avg_roas'] ?? 1.5;
        $expectedRevenue = $budget * $expectedRoas;
        $offerPrice = $offer->price ?? 500_000;
        $expectedSales = $offerPrice > 0 ? (int) floor($expectedRevenue / $offerPrice) : 0;

        return [
            'expected_roas' => $expectedRoas,
            'expected_revenue' => $expectedRevenue,
            'expected_sales' => $expectedSales,
            'expected_cpa' => $expectedSales > 0 ? round($budget / $expectedSales) : 0,
        ];
    }
}
