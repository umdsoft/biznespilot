<?php

namespace App\Services\Agent\Analytics\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KPI hisoblash vositasi — to'g'ridan-to'g'ri bazadan hisoblaydi.
 * Bazadan ma'lumot olib, tayyor KPI raqamlarini qaytaradi (AI chaqirilmaydi, bepul).
 */
class KPICalculatorTool
{
    /**
     * Asosiy KPI ko'rsatkichlarini hisoblash
     *
     * @return array KPI raqamlari yoki xato
     */
    public function calculate(string $businessId, ?string $period = null, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            // Davr aniqlash
            if ($startDate && $endDate) {
                $start = $startDate;
                $end = $endDate;
            } else {
                $dates = $this->resolvePeriodDates($period);
                $start = $dates['start'];
                $end = $dates['end'];
            }

            // To'g'ridan-to'g'ri bazadan KPI hisoblash
            $data = $this->getKPIFromDB($businessId, $start, $end);

            // Oldingi davr bilan solishtirish
            $periodLength = max(1, (strtotime($end) - strtotime($start)) / 86400);
            $prevStart = date('Y-m-d', strtotime($start . " -{$periodLength} days"));
            $prevEnd = date('Y-m-d', strtotime($start . ' -1 day'));
            $prevData = $this->getKPIFromDB($businessId, $prevStart, $prevEnd);

            return [
                'success' => true,
                'period' => ['start' => $start, 'end' => $end],
                'current' => $data,
                'previous' => $prevData,
                'changes' => $this->calculateChanges($data, $prevData),
            ];

        } catch (\Exception $e) {
            Log::warning('KPICalculatorTool: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bugungi qisqa ma'lumot
     */
    public function getTodaySummary(string $businessId): array
    {
        try {
            $today = now()->toDateString();

            // Bugungi sotuvlar
            $sales = DB::table('sales')
                ->where('business_id', $businessId)
                ->whereDate('created_at', $today)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
                ->first();

            // Bugungi leadlar
            $leads = DB::table('leads')
                ->where('business_id', $businessId)
                ->whereDate('created_at', $today)
                ->count();

            // Kechagi solishtirish
            $yesterday = now()->subDay()->toDateString();
            $yesterdaySales = DB::table('sales')
                ->where('business_id', $businessId)
                ->whereDate('created_at', $yesterday)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
                ->first();

            return [
                'success' => true,
                'today' => [
                    'sales_count' => (int) $sales->count,
                    'sales_total' => (float) $sales->total,
                    'leads_count' => $leads,
                ],
                'yesterday' => [
                    'sales_count' => (int) $yesterdaySales->count,
                    'sales_total' => (float) $yesterdaySales->total,
                ],
            ];

        } catch (\Exception $e) {
            Log::warning('KPICalculatorTool: bugungi ma\'lumot xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bazadan KPI hisoblash (KPICalculator protected metodini ishlatmasdan)
     */
    private function getKPIFromDB(string $businessId, string $start, string $end): array
    {
        $sales = DB::table('sales')
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->selectRaw('COUNT(*) as sales_total, COALESCE(SUM(amount), 0) as revenue_total')
            ->first();

        $leads = DB::table('leads')
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->count();

        $salesTotal = (int) $sales->sales_total;
        $revenueTotal = (float) $sales->revenue_total;
        $avgCheck = $salesTotal > 0 ? round($revenueTotal / $salesTotal, 2) : 0;
        $conversionRate = $leads > 0 ? round(($salesTotal / $leads) * 100, 1) : 0;

        $spend = (float) DB::table('marketing_spends')
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->sum('amount');

        $cac = $salesTotal > 0 ? round($spend / $salesTotal, 2) : 0;
        $roi = $spend > 0 ? round((($revenueTotal - $spend) / $spend) * 100, 1) : 0;

        return [
            'revenue_total' => $revenueTotal,
            'sales_total' => $salesTotal,
            'leads_total' => $leads,
            'avg_check' => $avgCheck,
            'conversion_rate' => $conversionRate,
            'cac' => $cac,
            'roi' => $roi,
        ];
    }

    /**
     * Davr sanalarini aniqlash
     */
    private function resolvePeriodDates(?string $period): array
    {
        return match ($period) {
            'today' => ['start' => now()->toDateString(), 'end' => now()->toDateString()],
            'yesterday' => ['start' => now()->subDay()->toDateString(), 'end' => now()->subDay()->toDateString()],
            'week', 'this_week' => ['start' => now()->startOfWeek()->toDateString(), 'end' => now()->toDateString()],
            'last_week' => ['start' => now()->subWeek()->startOfWeek()->toDateString(), 'end' => now()->subWeek()->endOfWeek()->toDateString()],
            'month', 'this_month' => ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->toDateString()],
            'last_month' => ['start' => now()->subMonth()->startOfMonth()->toDateString(), 'end' => now()->subMonth()->endOfMonth()->toDateString()],
            default => ['start' => now()->subDays(30)->toDateString(), 'end' => now()->toDateString()], // oxirgi 30 kun
        };
    }

    /**
     * O'zgarishlarni hisoblash (foiz)
     */
    private function calculateChanges(array $current, array $previous): array
    {
        $changes = [];
        $keys = ['revenue_total', 'sales_total', 'leads_total', 'conversion_rate', 'avg_check', 'cac', 'roi'];

        foreach ($keys as $key) {
            $curr = $current[$key] ?? 0;
            $prev = $previous[$key] ?? 0;

            if ($prev > 0) {
                $changes[$key] = round((($curr - $prev) / $prev) * 100, 1);
            } else {
                $changes[$key] = $curr > 0 ? 100 : 0;
            }
        }

        return $changes;
    }
}
