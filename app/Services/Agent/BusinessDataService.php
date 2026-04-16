<?php

namespace App\Services\Agent;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\Lead;
use App\Services\Agent\Context\AgentContextEnricher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessDataService
{
    public function getContextForAI(string $businessId, string $agentType = 'general'): string
    {
        return Cache::remember("agent_context:{$businessId}:{$agentType}", 300, function () use ($businessId) {
            try {
                $base = $this->buildContext($businessId);
                $health = $this->buildHealthContext($businessId);
                return $health ? ($base . "\n\n" . $health) : $base;
            } catch (\Exception $e) {
                Log::warning('BusinessDataService xato', ['error' => $e->getMessage()]);
                return 'Biznes ma\'lumotlarini olishda xatolik.';
            }
        });
    }

    /**
     * Biznes sog'ligi kontekstini olish (BusinessHealthService calculator'lari orqali)
     */
    private function buildHealthContext(string $businessId): string
    {
        $parts = [];
        try {
            $enricher = app(AgentContextEnricher::class);
            $parts[] = $enricher->buildFullContext($businessId);
        } catch (\Exception $e) {
            Log::warning('Enrich context xato', ['error' => $e->getMessage()]);
        }

        // CRM kontekstini ham qo'shish
        try {
            $crm = app(\App\Services\CRM\CRMContextProvider::class);
            $crmContext = $crm->buildBusinessCRMContext($businessId);
            if ($crmContext) $parts[] = $crmContext;
        } catch (\Exception $e) {
            Log::warning('CRM context xato', ['error' => $e->getMessage()]);
        }

        return implode("\n\n", array_filter($parts));
    }

    private function buildContext(string $businessId): string
    {
        $business = Business::find($businessId);
        if (! $business) return 'Biznes topilmadi.';

        $parts = [];
        $parts[] = "Biznes: {$business->name}";
        if ($business->category) {
            $parts[] = "Soha: {$business->category}";
        } else {
            $parts[] = "Soha: ANIQLANMAGAN (Sozlamalar bo'limida biznes profilini to'ldirish kerak)";
        }
        if ($business->description) $parts[] = "Tavsif: " . mb_substr($business->description, 0, 200);

        // Xodimlar
        $this->safe($parts, fn() => "Jamoa: " . ($business->users()->count() + 1) . " kishi");

        // Dream Buyer
        $dreamBuyer = DreamBuyer::where('business_id', $businessId)->first();
        $parts[] = $dreamBuyer
            ? "Ideal mijoz: {$dreamBuyer->name}" . ($dreamBuyer->description ? " — " . mb_substr($dreamBuyer->description, 0, 150) : '')
            : "Ideal mijoz: KIRITILMAGAN";

        // Marketing kanallari
        $this->safe($parts, function () use ($business) {
            $channels = $business->marketingChannels()->where('is_active', true)->pluck('type')->toArray();
            return $channels ? "Marketing kanallari: " . implode(', ', $channels) : "Marketing kanallari: ULANMAGAN";
        });

        // Raqobatchilar
        $this->safe($parts, function () use ($business) {
            $count = $business->competitors()->count();
            if ($count > 0) {
                $names = $business->competitors()->pluck('name')->take(5)->implode(', ');
                return "Raqobatchilar ({$count}): {$names}";
            }
            return "Raqobatchilar: KIRITILMAGAN";
        });

        // Takliflar
        $this->safe($parts, function () use ($business) {
            $count = $business->offers()->count();
            return $count > 0 ? "Takliflar: {$count} ta" : "Takliflar: KIRITILMAGAN";
        });

        // Lidlar
        $leadsTotal = 0;
        $this->safe($parts, function () use ($businessId, &$leadsTotal) {
            $leadsTotal = Lead::where('business_id', $businessId)->count();
            $leadsThisMonth = Lead::where('business_id', $businessId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            return "Lidlar: jami {$leadsTotal}, shu oy {$leadsThisMonth}";
        });

        // Lead statuslari
        if ($leadsTotal > 0) {
            $this->safe($parts, function () use ($businessId) {
                $statuses = Lead::where('business_id', $businessId)
                    ->select('status', DB::raw('count(*) as cnt'))
                    ->groupBy('status')
                    ->pluck('cnt', 'status')
                    ->toArray();
                $statusText = collect($statuses)->map(fn($cnt, $s) => "{$s}: {$cnt}")->implode(', ');
                return "Lead holatlari: {$statusText}";
            });
        }

        // Bugungi holat
        $this->safe($parts, function () use ($businessId) {
            $todayLeads = Lead::where('business_id', $businessId)->whereDate('created_at', now())->count();
            return "Bugun: {$todayLeads} yangi lid";
        });

        // To'liqlik
        $missing = [];
        if (!$dreamBuyer) $missing[] = 'Ideal mijoz portreti (Bosh sahifa > Ideal Mijoz)';
        if ($leadsTotal === 0) $missing[] = 'Lidlar (Lidlar bo\'limi)';

        $completeness = max(10, 100 - count($missing) * 15);
        $parts[] = "Ma'lumotlar to'liqligi: {$completeness}%";

        if (!empty($missing)) {
            $parts[] = "ETISHMAYOTGAN: " . implode(', ', $missing);
        }

        return implode("\n", $parts);
    }

    private function safe(array &$parts, callable $fn): void
    {
        try {
            $result = $fn();
            if ($result) $parts[] = $result;
        } catch (\Exception $e) {
            // Skip — xato query tizimni buzmasin
        }
    }
}
