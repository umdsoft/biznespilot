<?php

namespace App\Services\Team;

use App\Models\TeamAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Favqulodda hodisalarni bazadan aniqlash (AI chaqirilMAYDI — 100% bepul).
 * Har 2 soatda tekshiriladi.
 */
class EmergencyDetector
{
    /**
     * Biznes uchun favqulodda hodisalarni tekshirish
     * @return array Aniqlangan ogohlantirishlar
     */
    public function check(string $businessId): array
    {
        $alerts = [];

        // 1. Issiq lead javobsiz (30+ daqiqa)
        $this->checkUnansweredHotLeads($businessId, $alerts);

        // 2. Daromad keskin tushishi
        $this->checkRevenueDrop($businessId, $alerts);

        // 3. Salbiy izoh (oxirgi 2 soat)
        $this->checkNegativeReviews($businessId, $alerts);

        // 4. Obuna muddati tugayapti
        $this->checkExpiringSubscription($businessId, $alerts);

        // Aniqlangan ogohlantirishlarni saqlash
        foreach ($alerts as $alert) {
            TeamAlert::create(array_merge($alert, [
                'business_id' => $businessId,
            ]));
        }

        return $alerts;
    }

    /**
     * Issiq leadlar javobsiz qoldimi
     */
    private function checkUnansweredHotLeads(string $businessId, array &$alerts): void
    {
        try {
            $unanswered = DB::table('leads')
                ->where('business_id', $businessId)
                ->where('score', '>=', 76)
                ->where('status', 'new')
                ->where('created_at', '<', now()->subMinutes(30))
                ->where('created_at', '>', now()->subHours(24))
                ->count();

            if ($unanswered > 0) {
                $alerts[] = [
                    'alert_type' => 'hot_lead_unanswered',
                    'severity' => 'urgent',
                    'detecting_agent' => 'salomatxon',
                    'message' => "{$unanswered} ta issiq lead 30+ daqiqa javobsiz! Darhol javob bering.",
                ];
            }
        } catch (\Exception $e) {
            Log::warning('EmergencyDetector: hot lead tekshiruv xatosi', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Daromad keskin tushganmi
     */
    private function checkRevenueDrop(string $businessId, array &$alerts): void
    {
        try {
            $todayRevenue = (float) DB::table('sales')
                ->where('business_id', $businessId)
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');

            // Oxirgi 7 kunlik o'rtacha
            $avgRevenue = (float) DB::table('sales')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(7))
                ->where('created_at', '<', now()->startOfDay())
                ->selectRaw('COALESCE(SUM(amount) / NULLIF(COUNT(DISTINCT DATE(created_at)), 0), 0) as avg_daily')
                ->value('avg_daily');

            // Bugun saat 14:00 dan keyin va daromad 50% dan past
            if (now()->hour >= 14 && $avgRevenue > 0 && $todayRevenue < $avgRevenue * 0.5) {
                $drop = round((1 - $todayRevenue / $avgRevenue) * 100);
                $alerts[] = [
                    'alert_type' => 'revenue_drop',
                    'severity' => 'warning',
                    'detecting_agent' => 'jasurbek',
                    'message' => "Bugungi daromad o'rtachadan {$drop}% past: " . number_format($todayRevenue) . " so'm",
                ];
            }
        } catch (\Exception $e) {
            Log::warning('EmergencyDetector: revenue tekshiruv xatosi', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Salbiy izoh kelganmi
     */
    private function checkNegativeReviews(string $businessId, array &$alerts): void
    {
        try {
            $negatives = DB::table('customer_reviews')
                ->where('business_id', $businessId)
                ->where('sentiment', 'negative')
                ->where('response_status', 'pending')
                ->where('created_at', '>=', now()->subHours(2))
                ->count();

            if ($negatives > 0) {
                $alerts[] = [
                    'alert_type' => 'negative_review',
                    'severity' => 'urgent',
                    'detecting_agent' => 'imronbek',
                    'message' => "{$negatives} ta yangi salbiy izoh! Javob berish kerak.",
                ];
            }
        } catch (\Exception $e) {
            // customer_reviews jadvali bo'lmasligi mumkin
        }
    }

    /**
     * Obuna muddati tugayaptimi
     */
    private function checkExpiringSubscription(string $businessId, array &$alerts): void
    {
        try {
            $sub = DB::table('subscriptions')
                ->where('business_id', $businessId)
                ->where('status', 'active')
                ->first(['ends_at']);

            if ($sub && $sub->ends_at) {
                $daysLeft = now()->diffInDays($sub->ends_at, false);
                if ($daysLeft >= 0 && $daysLeft <= 3) {
                    $alerts[] = [
                        'alert_type' => 'subscription_expiring',
                        'severity' => 'warning',
                        'detecting_agent' => 'umidbek',
                        'message' => "Obuna muddati {$daysLeft} kundan keyin tugaydi! Yangilash kerak.",
                    ];
                }
            }
        } catch (\Exception $e) {
            // silently continue
        }
    }
}
