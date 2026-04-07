<?php

namespace App\Services\Agent\Knowledge;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BusinessDataInspector
{
    public function inspect(string $businessId): array
    {
        return Cache::remember("biz_inspect:{$businessId}", 300, function () use ($businessId) {
            $business = Business::find($businessId);
            if (!$business) return ['error' => 'Biznes topilmadi'];

            return [
                'marketing' => $this->inspectMarketing($businessId, $business),
                'sales' => $this->inspectSales($businessId),
                'analytics' => $this->inspectAnalytics($businessId),
                'completeness' => $this->calculateCompleteness($businessId, $business),
            ];
        });
    }

    public function inspectMarketing(string $businessId, $business = null): array
    {
        $business = $business ?: Business::find($businessId);
        $dreamBuyer = DreamBuyer::where('business_id', $businessId)->first();

        $channels = [];
        try { $channels = $business->marketingChannels()->where('is_active', true)->pluck('type')->toArray(); } catch (\Exception $e) {}

        $competitors = 0;
        try { $competitors = $business->competitors()->count(); } catch (\Exception $e) {}

        return [
            'dream_buyer_exists' => (bool) $dreamBuyer,
            'dream_buyer_name' => $dreamBuyer?->name,
            'channels_connected' => $channels,
            'channels_count' => count($channels),
            'competitors_count' => $competitors,
            'missing' => array_filter([
                !$dreamBuyer ? 'Ideal mijoz portreti (Bosh sahifa > Ideal Mijoz)' : null,
                empty($channels) ? 'Marketing kanallari (Bosh sahifa > Integratsiyalar)' : null,
                $competitors === 0 ? 'Raqobatchilar (Marketing > Raqobatchilar)' : null,
            ]),
        ];
    }

    public function inspectSales(string $businessId): array
    {
        $leadsTotal = Lead::where('business_id', $businessId)->count();
        $leadsNew = Lead::where('business_id', $businessId)->where('status', 'new')->count();
        $leadsWon = Lead::where('business_id', $businessId)->where('status', 'won')->count();
        $leadsThisWeek = Lead::where('business_id', $businessId)->where('created_at', '>=', now()->subDays(7))->count();

        $telephonyConnected = false;
        try {
            $telephonyConnected = DB::table('integrations')
                ->where('business_id', $businessId)
                ->whereIn('type', ['sipuni', 'utel'])
                ->where('is_active', true)
                ->exists();
        } catch (\Exception $e) {}

        return [
            'leads_total' => $leadsTotal,
            'leads_new' => $leadsNew,
            'leads_won' => $leadsWon,
            'leads_this_week' => $leadsThisWeek,
            'telephony_connected' => $telephonyConnected,
            'missing' => array_filter([
                $leadsTotal === 0 ? 'Lidlar kiritilmagan (Bosh sahifa > Lidlar)' : null,
                !$telephonyConnected ? 'IP telefoniya ulanmagan (Integratsiyalar > Sipuni/Utel)' : null,
            ]),
        ];
    }

    public function inspectAnalytics(string $businessId): array
    {
        $hasLeads = Lead::where('business_id', $businessId)->exists();
        $hasSales = Lead::where('business_id', $businessId)->where('status', 'won')->exists();

        return [
            'has_data' => $hasLeads,
            'has_sales' => $hasSales,
            'can_calculate_kpi' => $hasSales,
            'missing' => array_filter([
                !$hasLeads ? 'Sotuv ma\'lumotlari (Lidlar bo\'limi)' : null,
                !$hasSales ? 'Tugallangan bitimlar (KPI hisoblash uchun)' : null,
            ]),
        ];
    }

    private function calculateCompleteness(string $businessId, $business): int
    {
        $score = 0;
        $total = 6;

        if ($business->name && $business->category) $score++;
        if (DreamBuyer::where('business_id', $businessId)->exists()) $score++;
        try { if ($business->marketingChannels()->where('is_active', true)->exists()) $score++; } catch (\Exception $e) {}
        if (Lead::where('business_id', $businessId)->exists()) $score++;
        try { if ($business->competitors()->exists()) $score++; } catch (\Exception $e) {}
        try { if ($business->offers()->exists()) $score++; } catch (\Exception $e) {}

        return (int) round($score / $total * 100);
    }

    public function getTextSummary(string $businessId): string
    {
        $data = $this->inspect($businessId);
        $parts = [];

        $parts[] = "Ma'lumotlar to'liqligi: {$data['completeness']}%";

        // Marketing
        $m = $data['marketing'];
        $parts[] = "Ideal mijoz portreti: " . ($m['dream_buyer_exists'] ? $m['dream_buyer_name'] : 'KIRITILMAGAN');
        $parts[] = "Marketing kanallari: " . ($m['channels_count'] > 0 ? implode(', ', $m['channels_connected']) : 'ULANMAGAN');
        $parts[] = "Raqobatchilar: " . ($m['competitors_count'] > 0 ? "{$m['competitors_count']} ta" : 'KIRITILMAGAN');

        // Sales
        $s = $data['sales'];
        $parts[] = "Lidlar: jami {$s['leads_total']}, yangi {$s['leads_new']}, yutilgan {$s['leads_won']}";
        $parts[] = "Shu hafta: {$s['leads_this_week']} ta yangi lid";
        $parts[] = "IP telefoniya: " . ($s['telephony_connected'] ? 'ulangan' : 'ULANMAGAN');

        // Missing
        $allMissing = array_merge($m['missing'], $s['missing'], $data['analytics']['missing']);
        if (!empty($allMissing)) {
            $parts[] = "ETISHMAYOTGAN: " . implode('; ', $allMissing);
        }

        return implode("\n", $parts);
    }
}
