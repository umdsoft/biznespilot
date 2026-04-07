<?php

namespace App\Services\Agent;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Biznes ma'lumotlari inventori — agentlar uchun kontekst.
 * Har bir agent o'z sohasidagi ma'lumotlarni oladi.
 */
class BusinessDataService
{
    /**
     * To'liq biznes konteksti — matn formatida (AI uchun)
     */
    public function getContextForAI(string $businessId, string $agentType = 'general'): string
    {
        return Cache::remember("agent_context:{$businessId}:{$agentType}", 300, function () use ($businessId, $agentType) {
            $business = Business::find($businessId);
            if (! $business) return 'Biznes topilmadi.';

            $parts = [];

            // Asosiy profil
            $parts[] = "Biznes: {$business->name}";
            if ($business->category) $parts[] = "Soha: {$business->category}";

            // Xodimlar
            $teamCount = $business->users()->count() + 1;
            $parts[] = "Jamoa: {$teamCount} kishi";

            // Dream Buyer
            $dreamBuyer = DreamBuyer::where('business_id', $businessId)->first();
            if ($dreamBuyer) {
                $parts[] = "Ideal mijoz: {$dreamBuyer->name}";
                if ($dreamBuyer->description) $parts[] = "Mijoz tavsifi: " . mb_substr($dreamBuyer->description, 0, 200);
            } else {
                $parts[] = "Ideal mijoz: KIRITILMAGAN";
            }

            // Marketing kanallari
            $channels = $business->marketingChannels()->where('is_active', true)->pluck('type')->toArray();
            $parts[] = $channels ? "Marketing kanallari: " . implode(', ', $channels) : "Marketing kanallari: ULANMAGAN";

            // Mahsulotlar
            $productCount = $business->products()->count();
            if ($productCount > 0) {
                $products = $business->products()->select('name', 'price')->limit(5)->get();
                $productList = $products->map(fn($p) => "{$p->name}" . ($p->price ? " ({$p->price} so'm)" : ''))->implode(', ');
                $parts[] = "Mahsulotlar ({$productCount} ta): {$productList}";
            } else {
                $parts[] = "Mahsulotlar: KIRITILMAGAN";
            }

            // Raqobatchilar
            $competitorCount = $business->competitors()->count();
            if ($competitorCount > 0) {
                $names = $business->competitors()->pluck('name')->take(5)->implode(', ');
                $parts[] = "Raqobatchilar ({$competitorCount}): {$names}";
            } else {
                $parts[] = "Raqobatchilar: KIRITILMAGAN";
            }

            // Takliflar/Offerlar
            $offerCount = $business->offers()->count();
            $parts[] = $offerCount > 0 ? "Takliflar: {$offerCount} ta" : "Takliflar: KIRITILMAGAN";

            // Sotuv ma'lumotlari
            $leadsTotal = Lead::where('business_id', $businessId)->count();
            $leadsThisMonth = Lead::where('business_id', $businessId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $parts[] = "Lidlar: jami {$leadsTotal}, shu oy {$leadsThisMonth}";

            // Lead statuslari
            if ($leadsTotal > 0) {
                $statuses = Lead::where('business_id', $businessId)
                    ->select('status', DB::raw('count(*) as cnt'))
                    ->groupBy('status')
                    ->pluck('cnt', 'status')
                    ->toArray();
                $statusText = collect($statuses)->map(fn($cnt, $s) => "{$s}: {$cnt}")->implode(', ');
                $parts[] = "Lead holatlari: {$statusText}";
            }

            // Bugungi holat
            $todaySales = DB::table('leads')
                ->where('business_id', $businessId)
                ->where('status', 'won')
                ->whereDate('created_at', now())
                ->count();
            $todayLeads = Lead::where('business_id', $businessId)
                ->whereDate('created_at', now())
                ->count();
            $parts[] = "Bugun: {$todayLeads} yangi lid, {$todaySales} sotuv";

            // To'liqlik baho
            $completeness = $this->calculateCompleteness($businessId, $business, $dreamBuyer, $channels, $productCount, $leadsTotal, $competitorCount, $offerCount);
            $parts[] = "Ma'lumotlar to'liqligi: {$completeness}%";

            // Etishmayotgan qismlar
            $missing = $this->getMissingParts($dreamBuyer, $channels, $productCount, $leadsTotal, $competitorCount, $offerCount);
            if (!empty($missing)) {
                $parts[] = "ETISHMAYOTGAN: " . implode(', ', $missing);
            }

            return implode("\n", $parts);
        });
    }

    private function calculateCompleteness(string $businessId, $business, $dreamBuyer, array $channels, int $products, int $leads, int $competitors, int $offers): int
    {
        $score = 0;
        $total = 8;

        if ($business->name && $business->category) $score++;
        if ($dreamBuyer) $score++;
        if (!empty($channels)) $score++;
        if ($products > 0) $score++;
        if ($leads > 0) $score++;
        if ($competitors > 0) $score++;
        if ($offers > 0) $score++;
        if ($business->users()->count() > 0) $score++;

        return (int) round($score / $total * 100);
    }

    private function getMissingParts($dreamBuyer, array $channels, int $products, int $leads, int $competitors, int $offers): array
    {
        $missing = [];
        if (!$dreamBuyer) $missing[] = 'Ideal mijoz portreti';
        if (empty($channels)) $missing[] = 'Marketing kanallari';
        if ($products === 0) $missing[] = 'Mahsulotlar';
        if ($leads === 0) $missing[] = 'Lidlar';
        if ($competitors === 0) $missing[] = 'Raqobatchilar';
        if ($offers === 0) $missing[] = 'Takliflar';
        return $missing;
    }
}
