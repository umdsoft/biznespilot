<?php

namespace App\Services\Agent\Context;

use App\Services\Agent\HealthMonitor\Calculators\CustomerHealthCalculator;
use App\Services\Agent\HealthMonitor\Calculators\FinanceHealthCalculator;
use App\Services\Agent\HealthMonitor\Calculators\MarketingHealthCalculator;
use App\Services\Agent\HealthMonitor\Calculators\SalesHealthCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agent javoblariga qo'shimcha biznes kontekst beradi.
 *
 * BusinessHealthService'dan farqli — DB ga yozmaydi, faqat o'qiydi.
 * Har agent chaqiriqida chuqur biznes tahlil qo'shish uchun.
 */
class AgentContextEnricher
{
    private const CACHE_TTL = 300; // 5 daqiqa — raqamlar tez o'zgarmaydi

    public function __construct(
        private MarketingHealthCalculator $marketingCalc,
        private SalesHealthCalculator $salesCalc,
        private FinanceHealthCalculator $financeCalc,
        private CustomerHealthCalculator $customerCalc,
    ) {}

    /**
     * Biznes sog'ligi snapshot — raqamlar bilan
     */
    public function getHealthSnapshot(string $businessId): array
    {
        $cacheKey = "agent_health_snapshot:{$businessId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($businessId) {
            try {
                $marketing = $this->marketingCalc->calculate($businessId);
                $sales = $this->salesCalc->calculate($businessId);
                $finance = $this->financeCalc->calculate($businessId);
                $customer = $this->customerCalc->calculate($businessId);

                $overall = (int) round(
                    $marketing['score'] * 0.25
                    + $sales['score'] * 0.30
                    + $finance['score'] * 0.25
                    + $customer['score'] * 0.20
                );

                return [
                    'overall' => $overall,
                    'grade' => $this->getGrade($overall),
                    'marketing' => $marketing,
                    'sales' => $sales,
                    'finance' => $finance,
                    'customer' => $customer,
                ];
            } catch (\Exception $e) {
                Log::warning('AgentContextEnricher: health xato', ['error' => $e->getMessage()]);
                return ['overall' => 0, 'grade' => 'N/A'];
            }
        });
    }

    /**
     * AI ga yuborish uchun matn formatida kontekst
     */
    public function buildHealthContext(string $businessId): string
    {
        $h = $this->getHealthSnapshot($businessId);

        if (!isset($h['overall']) || $h['overall'] === 0) {
            return '';
        }

        $parts = [];
        $parts[] = "BIZNES SOG'LIGI SKORI: {$h['overall']}/100 ({$h['grade']})";
        $parts[] = "- Marketing: {$h['marketing']['score']}/100";
        $parts[] = "- Sotuv: {$h['sales']['score']}/100";
        $parts[] = "- Moliya: {$h['finance']['score']}/100";
        $parts[] = "- Mijoz: {$h['customer']['score']}/100";

        // Past bo'lgan bo'limlarni qayd qilish
        $weak = [];
        foreach (['marketing' => 'Marketing', 'sales' => 'Sotuv', 'finance' => 'Moliya', 'customer' => 'Mijoz'] as $k => $label) {
            if (($h[$k]['score'] ?? 100) < 60) {
                $weak[] = "{$label} ({$h[$k]['score']})";
            }
        }
        if (!empty($weak)) {
            $parts[] = 'ZAIF BO\'LIMLAR: ' . implode(', ', $weak);
        }

        return implode("\n", $parts);
    }

    /**
     * Pul oqimi snapshot — oxirgi 30 kun
     */
    public function getCashFlowSnapshot(string $businessId): array
    {
        $cacheKey = "agent_cashflow_snapshot:{$businessId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($businessId) {
            try {
                // Oxirgi 30 kunlik KPI daromadlari
                $data = DB::table('kpi_daily_entries')
                    ->where('business_id', $businessId)
                    ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
                    ->selectRaw('
                        SUM(revenue_total) as total_revenue,
                        SUM(spend_total) as total_spend,
                        AVG(revenue_total) as avg_daily_revenue,
                        AVG(spend_total) as avg_daily_spend,
                        COUNT(*) as days
                    ')->first();

                if (!$data || $data->days == 0) {
                    // MUHIM: method imzosi `: array` deb e'lon qilingan, null TypeError chaqiradi.
                    // Bo'sh array qaytaramiz — caller `if (!$c)` bilan tekshiradi.
                    return [];
                }

                $netFlow = ($data->total_revenue ?? 0) - ($data->total_spend ?? 0);
                $roas = ($data->total_spend ?? 0) > 0
                    ? round(($data->total_revenue ?? 0) / $data->total_spend, 2)
                    : 0;

                return [
                    'period_days' => (int) $data->days,
                    'total_revenue' => (float) ($data->total_revenue ?? 0),
                    'total_spend' => (float) ($data->total_spend ?? 0),
                    'net_flow' => $netFlow,
                    'avg_daily_revenue' => round($data->avg_daily_revenue ?? 0),
                    'avg_daily_spend' => round($data->avg_daily_spend ?? 0),
                    'roas' => $roas,
                    'is_profitable' => $netFlow > 0,
                ];
            } catch (\Exception $e) {
                Log::warning('CashFlow snapshot xato', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Pul oqimi matn formatida
     */
    public function buildCashFlowContext(string $businessId): string
    {
        $c = $this->getCashFlowSnapshot($businessId);

        if (!$c) {
            return '';
        }

        $parts = [];
        $parts[] = 'MOLIYA (oxirgi ' . $c['period_days'] . ' kun):';
        $parts[] = '- Daromad: ' . number_format($c['total_revenue']) . " so'm";
        $parts[] = '- Xarajat: ' . number_format($c['total_spend']) . " so'm";
        $parts[] = '- Sof oqim: ' . number_format($c['net_flow']) . " so'm " . ($c['is_profitable'] ? '✅' : '❌ ZARAR');
        $parts[] = '- ROAS: ' . $c['roas'] . 'x';
        $parts[] = '- Kunlik o\'rtacha daromad: ' . number_format($c['avg_daily_revenue']) . " so'm";

        return implode("\n", $parts);
    }

    /**
     * Reputatsiya snapshot — oxirgi sharhlar
     */
    public function buildReputationContext(string $businessId): string
    {
        try {
            $hasTable = DB::getSchemaBuilder()->hasTable('customer_reviews');
            if (!$hasTable) return '';

            $stats = DB::table('customer_reviews')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN sentiment = "positive" THEN 1 ELSE 0 END) as positive,
                    SUM(CASE WHEN sentiment = "negative" THEN 1 ELSE 0 END) as negative,
                    AVG(rating) as avg_rating
                ')->first();

            if (!$stats || $stats->total == 0) return '';

            $parts = [];
            $parts[] = "REPUTATSIYA (oxirgi 30 kun):";
            $parts[] = "- Sharhlar: {$stats->total} ta";
            $parts[] = "- Ijobiy: {$stats->positive}, Salbiy: {$stats->negative}";
            if ($stats->avg_rating) {
                $parts[] = "- O'rtacha reyting: " . round($stats->avg_rating, 1) . '/5';
            }
            return implode("\n", $parts);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Mavsumiy hodisalar — keyingi 30 kun
     */
    public function buildSeasonalContext(string $businessId): string
    {
        try {
            // O'zbekiston bayramlari (hardcoded — DB ga bog'liq emas)
            $upcoming = $this->getUpcomingHolidays(30);
            if (empty($upcoming)) return '';

            $parts = ['MAVSUMIY HODISALAR (keyingi 30 kun):'];
            foreach ($upcoming as $event) {
                $parts[] = "- {$event['date']}: {$event['name']} ({$event['days_until']} kun qoldi)";
            }
            return implode("\n", $parts);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Mijoz lifecycle bosqichlari taqsimi
     */
    public function buildLifecycleContext(string $businessId): string
    {
        try {
            $hasTable = DB::getSchemaBuilder()->hasTable('customer_lifecycle_stages');
            if (!$hasTable) return '';

            $stages = DB::table('customer_lifecycle_stages')
                ->where('business_id', $businessId)
                ->select('current_stage', DB::raw('COUNT(*) as cnt'))
                ->groupBy('current_stage')
                ->get();

            if ($stages->isEmpty()) return '';

            $parts = ['MIJOZ LIFECYCLE:'];
            foreach ($stages as $s) {
                $parts[] = "- {$s->current_stage}: {$s->cnt} ta mijoz";
            }
            return implode("\n", $parts);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * To'liq enriched kontekst — barcha sourcelar birgalikda
     */
    public function buildFullContext(string $businessId): string
    {
        $parts = array_filter([
            $this->buildHealthContext($businessId),
            $this->buildCashFlowContext($businessId),
            $this->buildReputationContext($businessId),
            $this->buildSeasonalContext($businessId),
            $this->buildLifecycleContext($businessId),
        ]);
        return implode("\n\n", $parts);
    }

    /**
     * O'zbekiston bayramlari ro'yxati (hardcoded)
     */
    private function getUpcomingHolidays(int $daysAhead): array
    {
        $holidays = [
            ['month' => 1, 'day' => 1, 'name' => 'Yangi yil'],
            ['month' => 3, 'day' => 8, 'name' => 'Xalqaro xotin-qizlar kuni'],
            ['month' => 3, 'day' => 21, 'name' => 'Navro\'z bayrami'],
            ['month' => 5, 'day' => 9, 'name' => 'Xotira va qadrlash kuni'],
            ['month' => 9, 'day' => 1, 'name' => 'Mustaqillik kuni'],
            ['month' => 10, 'day' => 1, 'name' => 'O\'qituvchi va murabbiylar kuni'],
            ['month' => 12, 'day' => 8, 'name' => 'Konstitutsiya kuni'],
        ];

        $now = now();
        $upcoming = [];

        foreach ($holidays as $h) {
            // Joriy yil va keyingi yil uchun
            foreach ([$now->year, $now->year + 1] as $year) {
                $date = \Carbon\Carbon::create($year, $h['month'], $h['day']);
                $diff = $now->diffInDays($date, false);

                if ($diff > 0 && $diff <= $daysAhead) {
                    $upcoming[] = [
                        'date' => $date->format('Y-m-d'),
                        'name' => $h['name'],
                        'days_until' => (int) $diff,
                    ];
                }
            }
        }

        // Saralash sana bo'yicha
        usort($upcoming, fn($a, $b) => $a['days_until'] - $b['days_until']);
        return array_slice($upcoming, 0, 3); // Eng yaqin 3 ta
    }

    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => "A'lo",
            $score >= 70 => 'Yaxshi',
            $score >= 50 => "O'rtacha",
            $score >= 30 => 'Xavfli',
            default => 'Tanazzul',
        };
    }
}
